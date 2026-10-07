<?php

declare(strict_types=1);

namespace CreativeCrafts\LaravelAiAssistant\Services;

use CreativeCrafts\LaravelAiAssistant\Contracts\AudioProcessingContract;
use CreativeCrafts\LaravelAiAssistant\Contracts\ConversationsRepositoryContract;
use CreativeCrafts\LaravelAiAssistant\Contracts\FilesRepositoryContract;
use CreativeCrafts\LaravelAiAssistant\Contracts\ResponsesRepositoryContract;
use CreativeCrafts\LaravelAiAssistant\DataTransferObjects\CompletionRequest;
use CreativeCrafts\LaravelAiAssistant\DataTransferObjects\CompletionResult;
use CreativeCrafts\LaravelAiAssistant\Enums\Mode;
use CreativeCrafts\LaravelAiAssistant\Exceptions\ApiResponseValidationException;
use CreativeCrafts\LaravelAiAssistant\Exceptions\FileOperationException;
use Exception;
use Generator;
use InvalidArgumentException;
use JsonException;
use Throwable;
use SplFileInfo;
use stdClass;

/**
 * Internal AI operations service.
 *
 * @internal This class is an internal implementation detail. Use AiManager as the public API facade instead.
 *
 * DO NOT add new public methods to this class. All public-facing AI operations should be exposed
 * through AiManager (quick, chat, stream, complete). This ensures a consistent, discoverable API
 * for end users and prevents API surface area sprawl.
 *
 * Legacy methods (textCompletion, chatTextCompletion, streamedCompletion, streamedChat) are
 * deprecated and will be removed in a future version. New code should use AiManager::complete().
 *
 * @see AiManager
 */
class AssistantService implements AudioProcessingContract
{
    protected CacheService $cacheService;
    protected LoggingService $loggingService;

    public function __construct(
        CacheService $cacheService,
        private readonly ResponsesRepositoryContract $responsesRepository,
        private readonly ConversationsRepositoryContract $conversationsRepository,
        private readonly FilesRepositoryContract $filesRepository
    ) {
        $this->cacheService = $cacheService;
    }


    /**
     * Expose the current correlation id used in LoggingService for this request flow.
     */
    public function getCorrelationId(): ?string
    {
        return app(LoggingService::class)->getCorrelationId();
    }

    /**
     * Create a new conversation and return its id.
     */
    public function createConversation(array $metadata = []): string
    {
        $payload = [];
        if (!empty($metadata)) {
            $payload['metadata'] = $metadata;
        }
        $conv = $this->conversationsRepository->createConversation($payload);
        return (string)($conv['id'] ?? $conv['conversation']['id'] ?? '');
    }

    /**
     * Convenience wrapper: send a simple user text message in a conversation.
     */
    public function sendChatMessage(string $conversationId, string $message, array $options = []): array
    {
        $chatModel = config('ai-assistant.chat_model', config('ai-assistant.model'));
        $model = $options['model'] ?? (is_string($chatModel) ? $chatModel : '');
        $instructions = $options['instructions'] ?? null;
        $tools = $options['tools'] ?? [];
        $responseFormat = $options['response_format'] ?? null;
        $modalities = $options['modalities'] ?? null;
        $metadata = $options['metadata'] ?? [];
        $idempotencyKey = $options['idempotency_key'] ?? null;
        $temperature = isset($options['temperature']) && is_numeric($options['temperature']) ? (float)$options['temperature'] : null;

        $fileIds = array_values(array_unique(array_filter((array)($options['file_ids'] ?? []), 'is_string')));
        $imageInputs = array_values(array_filter((array)($options['input_images'] ?? []), static function ($v) {
            return is_string($v) || is_array($v);
        }));
        // Attachments are validated for backward compatibility; the Responses API reads attached files as input_file blocks
        foreach ($this->validateAttachments(array_values(array_filter((array)($options['attachments'] ?? []), 'is_array'))) as $attachment) {
            if (!in_array($attachment['file_id'], $fileIds, true)) {
                $fileIds[] = $attachment['file_id'];
            }
        }

        $contentBlocks = [['type' => 'input_text', 'text' => $message]];
        foreach ($imageInputs as $img) {
            if (is_string($img)) {
                $contentBlocks[] = [
                    'type' => 'input_image',
                    'file_id' => $img,
                ];
            } elseif (is_array($img)) {
                if (isset($img['file_id']) && is_string($img['file_id'])) {
                    $contentBlocks[] = [
                        'type' => 'input_image',
                        'file_id' => $img['file_id'],
                    ];
                } elseif (isset($img['url']) && is_string($img['url'])) {
                    $contentBlocks[] = [
                        'type' => 'input_image',
                        'image_url' => $img['url'],
                    ];
                }
            }
        }
        foreach ($fileIds as $fid) {
            $contentBlocks[] = [
                'type' => 'input_file',
                'file_id' => $fid,
            ];
        }

        $inputItems = [[
            'role' => 'user',
            'content' => $contentBlocks,
        ]];

        return $this->sendTurn(
            $conversationId,
            $instructions,
            $model,
            $tools,
            $inputItems,
            $responseFormat,
            $modalities,
            $metadata,
            $idempotencyKey,
            $options['tool_choice'] ?? null,
            $temperature
        );
    }

    /**
     * Build and send a Responses.create turn.
     * Returns a normalized ResponseEnvelope array.
     */
    public function sendTurn(
        string $conversationId,
        ?string $instructions,
        ?string $model,
        array $tools,
        array $inputItems,
        array|string|null $responseFormat = null,
        array|string|null $modalities = null,
        array $metadata = [],
        ?string $idempotencyKey = null,
        array|string|null $toolChoice = null,
        ?float $temperature = null,
        ?int $maxCompletionTokens = null,
        ?array $presetInput = null,
    ): array {
        $payload = $this->buildResponsesCreatePayload(
            $conversationId,
            $instructions,
            $model,
            $tools,
            $inputItems,
            $responseFormat,
            $modalities,
            $metadata,
            $idempotencyKey,
            $toolChoice,
            $temperature,
            $maxCompletionTokens,
            $presetInput
        );
        $__start = microtime(true);
        $resp = $this->responsesRepository->createResponse($payload);
        $envelope = $this->normalizeResponseEnvelope($resp);
        // Emit metrics after normalisation
        try {
            $latency = max(0, microtime(true) - $__start);
            $modelName = (string)($payload['model'] ?? '');
            $usage = is_array($envelope['usage'] ?? null) ? $envelope['usage'] : [];
            $promptTokens = (int)($usage['input_tokens'] ?? ($usage['prompt_tokens'] ?? 0));
            $completionTokens = (int)($usage['output_tokens'] ?? ($usage['completion_tokens'] ?? 0));
            $toolCallsCount = is_array($envelope['toolCalls'] ?? null) ? count($envelope['toolCalls']) : 0;
            $this->metrics()->recordApiCall('responses.create', $latency, 200, [
                'response_id' => $envelope['responseId'] ?? null,
                'conversation_id' => $envelope['conversationId'] ?? null,
                'model' => $modelName,
                'tool_calls' => $toolCallsCount,
                'finish_reason' => $envelope['finishReason'] ?? null,
            ]);
            if ($promptTokens > 0 || $completionTokens > 0) {
                $this->metrics()->recordTokenUsage('responses.create', $promptTokens, $completionTokens, $modelName);
            }
        } catch (Throwable $e) {
            // Metrics should never break the main flow
        }

        // Auto tool/function calling loop
        $maxRoundsConfig = config('ai-assistant.tool_calling.max_rounds', 3);
        $maxRounds = is_numeric($maxRoundsConfig) ? (int)$maxRoundsConfig : 3;
        $round = 0;
        while (!empty($envelope['toolCalls']) && $round < max(1, $maxRounds)) {
            $round++;
            $calls = [];
            foreach ($envelope['toolCalls'] as $tc) {
                $name = (string)($tc['name'] ?? '');
                $argsRaw = $tc['arguments'] ?? [];
                $args = is_array($argsRaw) ? $argsRaw : (json_decode((string)$argsRaw, true) ?: []);
                $callId = (string)($tc['id'] ?? '');
                if ($name === '') {
                    continue;
                }
                try {
                    if (!$this->toolRegistry()->has($name)) {
                        // Provide graceful error output when the tool is missing
                        $calls[] = ['name' => $name, 'args' => $args, 'tool_call_id' => $callId, '__missing' => true];
                        continue;
                    }
                    $calls[] = ['name' => $name, 'args' => $args, 'tool_call_id' => $callId];
                } catch (Throwable $e) {
                    $calls[] = ['name' => $name, 'args' => $args, 'tool_call_id' => $callId, '__error' => $e->getMessage()];
                }
            }

            $results = [];
            /**
             * @var array<int, array{name:string,args:array,tool_call_id:string}> $execInputs
             */
            $execInputs = [];
            foreach ($calls as $c) {
                if (isset($c['__missing'])) {
                    $results[] = [
                        'tool_call_id' => (string)$c['tool_call_id'],
                        'output' => ['error' => 'Tool not registered', 'tool' => (string)$c['name']],
                    ];
                    continue;
                }
                if (isset($c['__error'])) {
                    $results[] = [
                        'tool_call_id' => (string)$c['tool_call_id'],
                        'output' => ['error' => (string)$c['__error']],
                    ];
                    continue;
                }
                // Ensure proper typing for ToolRegistry::executeAll()
                $execInputs[] = [
                    'name' => (string)$c['name'],
                    'args' => is_array($c['args']) ? $c['args'] : [],
                    'tool_call_id' => (string)$c['tool_call_id'],
                ];
            }
            if ($execInputs !== []) {
                $parallel = (bool)config('ai-assistant.tool_calling.parallel', false);
                $execResults = $this->toolRegistry()->executeAll($execInputs, $parallel);
                foreach ($execResults as $er) {
                    $results[] = [
                        'tool_call_id' => (string)($er['tool_call_id'] ?? ''),
                        'output' => $er['output'] ?? null,
                    ];
                }
            }

            $envelope = $this->continueWithToolResults(
                $conversationId,
                $results,
                $model,
                $instructions,
                $idempotencyKey,
                $tools,
                $responseFormat,
                $temperature,
                $maxCompletionTokens
            );
        }

        return $envelope;
    }

    /**
     * Send tool outputs back to the model as function_call_output items and let it continue the turn.
     *
     * @param array<int, array{tool_call_id?: string, output?: mixed}> $toolResults tool_call_id is the call_id of the function_call item
     * @param array $tools Tools to keep available, so the model can call another tool
     * @param array|string|null $responseFormat Output format of the turn, which applies to the answer that follows the tool calls
     * @param float|null $temperature Temperature of the turn, so the answer that follows the tool calls keeps it
     * @param int|null $maxCompletionTokens Output token limit of the turn (max_output_tokens)
     */
    public function continueWithToolResults(
        string $conversationId,
        array $toolResults,
        ?string $model = null,
        ?string $instructions = null,
        ?string $idempotencyKey = null,
        array $tools = [],
        array|string|null $responseFormat = null,
        ?float $temperature = null,
        ?int $maxCompletionTokens = null
    ): array {
        $outputs = [];
        foreach ($toolResults as $tr) {
            $toolCallId = (string)($tr['tool_call_id'] ?? '');
            $output = $tr['output'] ?? null;
            if ($toolCallId === '') {
                continue;
            }
            $outputs[] = [
                'type' => 'function_call_output',
                'call_id' => $toolCallId,
                'output' => is_string($output) ? $output : (string)json_encode($output),
            ];
        }

        // The function_call items are already stored in the conversation, so the outputs are the only new input
        $payload = $this->buildResponsesCreatePayload(
            $conversationId,
            $instructions,
            $model,
            tools: $tools,
            inputItems: [],
            responseFormat: $responseFormat,
            modalities: null,
            metadata: [],
            idempotencyKey: $idempotencyKey,
            toolChoice: null,
            temperature: $temperature,
            maxCompletionTokens: $maxCompletionTokens,
            presetInput: $outputs !== [] ? $outputs : null
        );
        $__start = microtime(true);
        $resp = $this->responsesRepository->createResponse($payload);
        $envelope = $this->normalizeResponseEnvelope($resp);
        // Emit metrics after normalization for continueWithToolResults
        try {
            $latency = max(0, microtime(true) - $__start);
            $modelName = (string)($payload['model'] ?? '');
            $usage = is_array($envelope['usage'] ?? null) ? $envelope['usage'] : [];
            $promptTokens = (int)($usage['input_tokens'] ?? ($usage['prompt_tokens'] ?? 0));
            $completionTokens = (int)($usage['output_tokens'] ?? ($usage['completion_tokens'] ?? 0));
            $toolCallsCount = is_array($envelope['toolCalls'] ?? null) ? count($envelope['toolCalls']) : 0;
            $this->metrics()->recordApiCall('responses.create', $latency, 200, [
                'response_id' => $envelope['responseId'] ?? null,
                'conversation_id' => $envelope['conversationId'] ?? null,
                'model' => $modelName,
                'tool_calls' => $toolCallsCount,
                'finish_reason' => $envelope['finishReason'] ?? null,
                'phase' => 'continueWithToolResults'
            ]);
            if ($promptTokens > 0 || $completionTokens > 0) {
                $this->metrics()->recordTokenUsage('responses.create', $promptTokens, $completionTokens, $modelName);
            }
        } catch (Throwable $e) {
            // ignore metrics errors
        }
        return $envelope;
    }

    // =========================
    // Responses + Conversations Orchestrator methods
    // =========================

    /**
     * List conversation items.
     */
    public function listConversationItems(string $conversationId, array $params = []): array
    {
        return $this->conversationsRepository->listItems($conversationId, $params);
    }

    /**
     * Transcribe audio to text using the repository abstraction.
     * This function sends an audio file to the OpenAI API for transcription
     * and returns the transcribed text.
     *
     * @param array $payload An array containing the necessary information for transcription.
     *                       This typically includes:
     *                       - 'file': The audio file to be transcribed (required)
     *                       - 'model': The model to use for transcription (optional)
     *                       - 'prompt': An optional text to guide the model's style or continue a previous audio segment
     *                       - 'response_format': The format of the transcript output (optional)
     *                       - 'temperature': The sampling temperature to use (optional)
     *                       - 'language': The language of the input audio (optional)
     * @return string The transcribed text from the audio file.
     * @throws InvalidArgumentException When required parameters are missing or invalid.
     * @throws FileOperationException When file is invalid or missing.
     */
    public function transcribeTo(array $payload): string
    {
        $this->validateAudioPayload($payload);

        // Use SSOT API via repository for audio transcription
        $payload['action'] = 'transcribe';
        $response = $this->responsesRepository->createResponse($payload);

        return isset($response['text']) ? (string)$response['text'] : '';
    }

    /**
     * Translate audio to text using the repository abstraction.
     * This function sends an audio file to the OpenAI API for translation
     * and returns the translated text.
     *
     * @param array $payload An array containing the necessary information for translation.
     *                       This typically includes:
     *                       - 'file': The audio file to be translated (required)
     *                       - 'model': The model to use for translation (optional)
     *                       - 'prompt': An optional text to guide the model's style or continue a previous audio segment
     *                       - 'response_format': The format of the transcript output (optional)
     *                       - 'temperature': The sampling temperature to use (optional)
     * @return string The translated text from the audio file.
     * @throws InvalidArgumentException When required parameters are missing or invalid.
     * @throws FileOperationException When file is invalid or missing.
     */
    public function translateTo(array $payload): string
    {
        $this->validateAudioPayload($payload);

        // Use SSOT API via repository for audio translation
        $payload['action'] = 'translate';
        $response = $this->responsesRepository->createResponse($payload);

        return isset($response['text']) ? (string)$response['text'] : '';
    }

    /**
     * Cancel an in-flight response by id.
     */
    public function cancel(string $responseId): bool
    {
        return $this->responsesRepository->cancelResponse($responseId);
    }

    /**
     * Upload a file and return its id.
     */
    public function uploadFile(string $filePath, string $purpose = 'assistants'): string
    {
        if ($filePath === '' || !is_readable($filePath)) {
            throw new FileOperationException("File not readable: {$filePath}");
        }
        // Normalize and validate purpose per OpenAI Files API
        $purpose = trim((string)$purpose);
        if ($purpose === '' || $purpose === 'assistant') {
            $purpose = 'assistants';
        }
        $allowed = ['assistants', 'batch', 'fine-tune', 'vision', 'user_data', 'responses'];
        if (!in_array($purpose, $allowed, true)) {
            $purpose = 'assistants';
        }

        $res = $this->filesRepository->upload($filePath, $purpose);
        $id = (string)($res['id'] ?? ($res['data']['id'] ?? ''));
        if ($id === '') {
            throw new ApiResponseValidationException('Upload succeeded but no file id returned.');
        }
        return $id;
    }

    /**
     * Convenience wrapper: streaming response for a user text message.
     */

    /**
     * Stream a Responses.create turn and yield normalized events.
     * If a callback is provided, it will be called with each normalized event.
     */
    public function streamTurn(
        string $conversationId,
        ?string $instructions,
        ?string $model,
        array $tools,
        array $inputItems,
        array|string|null $responseFormat = null,
        array|string|null $modalities = null,
        array $metadata = [],
        ?callable $onEvent = null,
        ?callable $shouldStop = null,
        ?string $idempotencyKey = null,
        array|string|null $toolChoice = null,
        ?float $temperature = null,
        ?int $maxCompletionTokens = null,
        ?array $presetInput = null,
    ): Generator {
        $payload = $this->buildResponsesCreatePayload(
            $conversationId,
            $instructions,
            $model,
            $tools,
            $inputItems,
            $responseFormat,
            $modalities,
            $metadata,
            $idempotencyKey,
            $toolChoice,
            $temperature,
            $maxCompletionTokens,
            $presetInput
        );

        $request = CompletionRequest::fromArray($payload);
        $events = $this->streaming()->process(Mode::CHAT, $request, $onEvent, $shouldStop);
        foreach ($events as $evt) {
            yield $evt;
        }
    }

    /**
     * Streaming helper that also performs tool/function execution when tool_call events are seen.
     * Yields original events, and after completion with tool calls, yields a final continuation event.
     */
    public function streamTurnWithTools(
        string $conversationId,
        ?string $instructions,
        ?string $model,
        array $tools,
        array $inputItems,
        array|string|null $responseFormat = null,
        array|string|null $modalities = null,
        array $metadata = [],
        ?callable $onEvent = null,
        ?string $idempotencyKey = null,
        array|string|null $toolChoice = null
    ): Generator {
        $collected = [];
        $failed = false;
        $completed = false;

        $inner = $this->streamTurn(
            $conversationId,
            $instructions,
            $model,
            $tools,
            $inputItems,
            $responseFormat,
            $modalities,
            $metadata,
            function ($evt) use (&$collected, &$failed, &$completed, $onEvent) {
                // Intercept tool_call.created events
                $type = (string)($evt['type'] ?? '');
                if (str_contains($type, 'tool_call.created')) {
                    $tc = $this->extractToolCallFromEvent($evt);
                    if ($tc !== null) {
                        $collected[] = $tc;
                    }
                }
                if ($type === 'response.failed') {
                    $failed = true;
                }
                if ($type === 'response.completed') {
                    $completed = true;
                }
                if ($onEvent) {
                    $onEvent($evt);
                }
            },
            null, // $shouldStop
            $idempotencyKey,
            $toolChoice
        );

        foreach ($inner as $evt) {
            yield $evt;
        }

        if (!$failed && $collected !== []) {
            // Execute tools and continue the turn
            $results = [];
            /**
             * @var array<int, array{name:string,args:array,tool_call_id:string}> $execInputs
             */
            $execInputs = [];
            foreach ($collected as $tc) {
                $name = (string)($tc['name'] ?? '');
                $args = $tc['arguments'] ?? [];
                $callId = (string)($tc['id'] ?? '');
                if ($name === '') {
                    continue;
                }
                if (!$this->toolRegistry()->has($name)) {
                    $results[] = [
                        'tool_call_id' => $callId,
                        'output' => ['error' => 'Tool not registered', 'tool' => $name],
                    ];
                    continue;
                }
                // Ensure proper typing for ToolRegistry::executeAll()
                /** @var array $normalizedArgs */
                $normalizedArgs = is_array($args)
                    ? $args
                    : (is_string($args) ? (json_decode($args, true) ?: []) : []);
                $execInputs[] = [
                    'name' => (string)$name,
                    'args' => $normalizedArgs,
                    'tool_call_id' => (string)$callId,
                ];
            }
            if ($execInputs !== []) {
                $parallel = (bool)config('ai-assistant.tool_calling.parallel', false);
                $execResults = $this->toolRegistry()->executeAll($execInputs, $parallel);
                foreach ($execResults as $er) {
                    $results[] = [
                        'tool_call_id' => (string)($er['tool_call_id'] ?? ''),
                        'output' => $er['output'] ?? null,
                    ];
                }
            }

            $envelope = $this->continueWithToolResults(
                $conversationId,
                $results,
                $model,
                $instructions,
                $idempotencyKey
            );

            yield [
                'type' => 'response.continuation.completed',
                'data' => $envelope,
                'isFinal' => true,
            ];
        }
    }

    /**
     * NEW: Unified streaming completion (accumulates to a final result).
     *
     * @internal Use AiManager::complete() instead. This method is called internally by AiManager.
     * @throws Exception
     */
    public function completeStream(Mode $mode, CompletionRequest $request): CompletionResult
    {
        if ($mode === Mode::TEXT) {
            $text = $this->streaming()->accumulateText($request);
            return CompletionResult::fromText($text);
        }

        $data = $this->streaming()->accumulateChat($request);
        return CompletionResult::fromArray($data);
    }

    /**
     * NEW: Unified sync completion for Text or Chat.
     *
     * @internal Use AiManager::complete() instead. This method is called internally by AiManager.
     * @throws JsonException
     * @throws \Psr\SimpleCache\InvalidArgumentException
     */
    public function completeSync(Mode $mode, CompletionRequest $request): CompletionResult
    {
        $payload = $request->toArray();

        // Use the SSOT API via repository for synchronous completions
        $response = $this->responsesRepository->createResponse($payload);

        if ($mode === Mode::TEXT) {
            // Extract text from response
            $text = $response['choices'][0]['text'] ?? $response['choices'][0]['message']['content'] ?? '';
            return CompletionResult::fromText($text);
        }

        return CompletionResult::fromArray($response);
    }

    /**
     * Backward-compat: legacy streaming entry used by AiAssistant::streamChatText tests.
     *
     * @param string $conversationId
     * @param string $message
     * @param array $options
     * @param callable(array):void|null $onEvent
     * @param callable():bool|null $shouldStop
     * @return Generator
     */
    public function getStreamingResponse(string $conversationId, string $message, array $options = [], ?callable $onEvent = null, ?callable $shouldStop = null): Generator
    {
        $instructions = isset($options['instructions']) && is_string($options['instructions']) && $options['instructions'] !== '' ? $options['instructions'] : null;
        $model = isset($options['model']) && is_string($options['model']) && $options['model'] !== '' ? $options['model'] : null;
        $tools = isset($options['tools']) && is_array($options['tools']) ? $options['tools'] : [];
        $responseFormat = $options['response_format'] ?? null;
        $responseFormat = is_array($responseFormat) || is_string($responseFormat) ? $responseFormat : null;
        $modalities = $options['modalities'] ?? null;
        $modalities = is_array($modalities) || is_string($modalities) ? $modalities : null;
        $metadata = isset($options['metadata']) && is_array($options['metadata']) ? $options['metadata'] : [];
        $idempotencyKey = isset($options['idempotency_key']) && is_string($options['idempotency_key']) && $options['idempotency_key'] !== '' ? $options['idempotency_key'] : null;
        $toolChoice = $options['tool_choice'] ?? null;
        $toolChoice = is_array($toolChoice) || is_string($toolChoice) ? $toolChoice : null;

        $inputItems = [
            [
                'role' => 'user',
                'content' => [['type' => 'input_text', 'text' => $message]],
            ]
        ];

        $events = $this->streamTurn(
            $conversationId,
            $instructions,
            $model,
            $tools,
            $inputItems,
            $responseFormat,
            $modalities,
            $metadata,
            $onEvent,
            $shouldStop,
            $idempotencyKey,
            $toolChoice
        );
        foreach ($events as $evt) {
            yield $evt;
        }
    }

    private function validateAttachments(array $attachments): array
    {
        $validated = [];
        foreach ($attachments as $idx => $att) {
            if (!is_array($att)) {
                throw new InvalidArgumentException('Attachment at index ' . $idx . ' must be an array.');
            }
            $fileId = $att['file_id'] ?? null;
            if (!is_string($fileId) || trim($fileId) === '') {
                throw new InvalidArgumentException('Attachment at index ' . $idx . ' must include a non-empty file_id string.');
            }
            if (isset($att['tools'])) {
                if (!is_array($att['tools'])) {
                    throw new InvalidArgumentException('Attachment tools for file_id ' . $fileId . ' must be an array.');
                }
                foreach ($att['tools'] as $tIdx => $tool) {
                    if (!is_array($tool) || !isset($tool['type']) || !is_string($tool['type']) || trim($tool['type']) === '') {
                        throw new InvalidArgumentException('Attachment tool at index ' . $tIdx . ' for file_id ' . $fileId . ' must include a non-empty type.');
                    }
                }
            }
            $validated[] = [
                'file_id' => $fileId,
                'tools' => array_values(array_map(static function ($tool) {
                    return is_array($tool) ? $tool : [];
                }, $att['tools'] ?? [])),
            ];
        }
        return $validated;
    }

    private function buildResponsesCreatePayload(
        string $conversationId,
        ?string $instructions,
        ?string $model,
        array $tools,
        array $inputItems,
        array|string|null $responseFormat,
        array|string|null $modalities,
        array $metadata,
        ?string $idempotencyKey,
        array|string|null $toolChoice = null,
        ?float $temperature = null,
        ?int $maxCompletionTokens = null,
        ?array $presetInput = null,
    ): array {
        $payload = [];
        $defaultModel = config('ai-assistant.default_model', config('ai-assistant.chat_model', config('ai-assistant.model')));
        $payload['model'] = $model ?: (is_string($defaultModel) ? $defaultModel : '');
        $payload['conversation'] = $conversationId;
        $instr = $instructions;
        $defaultInstrConfig = config('ai-assistant.default_instructions', '');
        $defaultInstr = is_string($defaultInstrConfig) ? $defaultInstrConfig : '';
        if (($instr === null || $instr === '') && $defaultInstr !== '') {
            $instr = $defaultInstr;
        }
        if ($instr !== null && $instr !== '') {
            $payload['instructions'] = $instr;
        }
        if (!empty($tools)) {
            $payload['tools'] = $this->normalizeTools($tools);
        }
        // If input is already set via InputBuilder (SSOT approach), use it directly
        if (!empty($presetInput)) {
            $payload['input'] = $presetInput;
        } elseif (!empty($inputItems)) {
            // Ensure shape matches Responses API: input: [ { role, content: [ blocks... ] } ]
            // Backward-compat: convert legacy 'text' blocks to 'input_text' to satisfy Responses API schema
            $normalizedInput = [];
            foreach ($inputItems as $itm) {
                if (!is_array($itm)) {
                    continue;
                }
                $role = $itm['role'] ?? null;
                $content = $itm['content'] ?? [];
                if (!is_array($content)) {
                    $content = [];
                }
                $normBlocks = [];
                foreach ($content as $blk) {
                    if (is_array($blk)) {
                        if (($blk['type'] ?? null) === 'text') {
                            $blk['type'] = 'input_text';
                        }
                        // Legacy file references become Responses API file inputs
                        if (($blk['type'] ?? null) === 'file_reference') {
                            $blk['type'] = 'input_file';
                        }
                        // Normalize input_image legacy nested shape: image: { file_id | url }
                        if (($blk['type'] ?? null) === 'input_image' && isset($blk['image']) && is_array($blk['image'])) {
                            if (isset($blk['image']['file_id']) && is_string($blk['image']['file_id'])) {
                                $blk['file_id'] = $blk['image']['file_id'];
                            } elseif (isset($blk['image']['url']) && is_string($blk['image']['url'])) {
                                $blk['image_url'] = $blk['image']['url'];
                            }
                            unset($blk['image']);
                        }
                        $normBlocks[] = $blk;
                    }
                }
                // Responses input messages have no "attachments" field: send attached files as input_file blocks
                if (isset($itm['attachments']) && is_array($itm['attachments'])) {
                    $attachedIds = array_column(array_filter($normBlocks, static fn (array $b): bool => ($b['type'] ?? null) === 'input_file'), 'file_id');
                    foreach ($itm['attachments'] as $att) {
                        $fileId = is_array($att) ? ($att['file_id'] ?? null) : null;
                        if (is_string($fileId) && $fileId !== '' && !in_array($fileId, $attachedIds, true)) {
                            $normBlocks[] = ['type' => 'input_file', 'file_id' => $fileId];
                            $attachedIds[] = $fileId;
                        }
                    }
                }
                $normalizedInput[] = ['role' => $role, 'content' => $normBlocks];
            }
            $payload['input'] = $normalizedInput;
        }
        if (!empty($metadata)) {
            $payload['metadata'] = $metadata;
        }
        if ($responseFormat !== null) {
            $payload['text'] = [
                'format' => $this->normalizeResponseFormat($responseFormat),
            ];
        }
        if ($modalities !== null) {
            $payload['modalities'] = $modalities;
        }
        if ($toolChoice !== null) {
            // {type: function, function: {name}} -> {type: function, name}
            if (is_array($toolChoice) && ($toolChoice['type'] ?? null) === 'function' && isset($toolChoice['function']['name'])) {
                $toolChoice = ['type' => 'function', 'name' => $toolChoice['function']['name']];
            }
            $payload['tool_choice'] = $toolChoice;
        }
        if ($idempotencyKey !== null) {
            $payload['_idempotency_key'] = $idempotencyKey;
        } else {
            $idemEnabled = (bool)(config('ai-assistant.responses.idempotency_enabled', true));
            if ($idemEnabled) {
                try {
                    /** @var IdempotencyService $idem */
                    $idem = app(IdempotencyService::class);
                    $payload['_idempotency_key'] = $idem->buildKey($payload);
                } catch (Throwable $e) {
                    // Do not fail request if idempotency generation fails; proceed without it.
                }
            }
        }
        // Handle temperature parameter
        if ($temperature !== null) {
            $payload['temperature'] = $temperature;
        }
        // Map legacy max_completion_tokens to Responses max_output_tokens
        // Prioritize parameter over config
        $maxOut = $maxCompletionTokens;
        if ($maxOut === null) {
            $maxOut = config('ai-assistant.responses.max_output_tokens');
        }
        if ($maxOut === null) {
            $maxOut = config('ai-assistant.max_completion_tokens');
        }
        if (is_numeric($maxOut)) {
            $intMax = (int)$maxOut;
            if ($intMax < 1) {
                throw new InvalidArgumentException('responses.max_output_tokens must be >= 1.');
            }
            $payload['max_output_tokens'] = $intMax;
        }
        return $payload;
    }

    /**
     * Convert tool definitions from the Chat Completions/Assistants shapes the chat helpers build into the
     * Responses API shapes. Tools that are already in the Responses shape are returned unchanged.
     *
     * @param array<int|string, mixed> $tools
     * @return array<int, mixed>
     */
    private function normalizeTools(array $tools): array
    {
        $normalized = [];
        foreach ($tools as $tool) {
            if (!is_array($tool)) {
                $normalized[] = $tool;
                continue;
            }
            $type = $tool['type'] ?? null;
            if ($type === 'function' && isset($tool['function']) && is_array($tool['function'])) {
                // {type: function, function: {name, description, parameters, strict}} -> {type: function, name, ...}
                $function = $tool['function'];
                unset($tool['function']);
                $tool = array_merge($tool, $function);
            }
            if ($type === 'function') {
                $tool['parameters'] = $this->normalizeFunctionParameters($tool['parameters'] ?? null);
            } elseif ($type === 'file_search') {
                $ids = $tool['vector_store_ids'] ?? [];
                if (!is_array($ids) || $ids === []) {
                    throw new InvalidArgumentException('The file_search tool needs at least one vector store id, e.g. includeFileSearchTool([\'vs_123\']).');
                }
            } elseif ($type === 'code_interpreter' && !isset($tool['container'])) {
                $tool['container'] = ['type' => 'auto'];
            }
            $normalized[] = $tool;
        }
        return $normalized;
    }

    /**
     * JSON Schema objects must encode as JSON objects: an empty PHP array would be sent as [].
     */
    private function normalizeFunctionParameters(mixed $parameters): mixed
    {
        if ($parameters === null || $parameters === [] || ($parameters instanceof stdClass && get_object_vars($parameters) === [])) {
            return ['type' => 'object', 'properties' => new stdClass(), 'required' => [], 'additionalProperties' => false];
        }
        if (is_array($parameters) && array_key_exists('properties', $parameters) && $parameters['properties'] === []) {
            $parameters['properties'] = new stdClass();
        }
        return $parameters;
    }

    /**
     * Convert response formats to the Responses API text.format shape:
     * 'text' -> {type: text}; {type: json_schema, json_schema: {name, schema, strict}} -> {type: json_schema, name, schema, strict}.
     *
     * @return array<string, mixed>
     */
    private function normalizeResponseFormat(array|string $format): array
    {
        if (is_string($format)) {
            return ['type' => $format];
        }
        if (($format['type'] ?? null) === 'json_schema' && isset($format['json_schema']) && is_array($format['json_schema'])) {
            $schema = $format['json_schema'];
            unset($format['json_schema']);
            $format = array_merge($format, $schema);
        }
        return $format;
    }

    private function normalizeResponseEnvelope(array $resp): array
    {
        $id = (string)($resp['id'] ?? '');
        $convId = (string)($resp['conversation_id'] ?? ($resp['conversation']['id'] ?? ''));
        $finish = $resp['finish_reason'] ?? ($resp['status'] ?? null);
        $usage = $resp['usage'] ?? $resp['token_usage'] ?? null;

        $messagesText = '';
        $toolCalls = [];
        $messageBlocks = [];
        $output = $resp['output'] ?? $resp['outputs'] ?? [];
        if (is_array($output)) {
            foreach ($output as $item) {
                if (!is_array($item)) {
                    continue;
                }
                $type = $item['type'] ?? null;
                if ($type === 'output_text' && isset($item['content'][0]['text'])) {
                    $text = (string)$item['content'][0]['text'];
                    if ($text !== '') {
                        if ($messagesText !== '') {
                            $messagesText .= "\n"; // separate different output items with newline
                        }
                        $messagesText .= $text;
                        $messageBlocks[] = ['type' => 'text', 'text' => $text];
                    }
                } elseif ($type === 'message' && isset($item['content']) && is_array($item['content'])) {
                    // message with blocks
                    $blockTexts = [];
                    foreach ($item['content'] as $block) {
                        if (!is_array($block)) {
                            continue;
                        }
                        $messageBlocks[] = $block;
                        if (isset($block['text']) && in_array(($block['type'] ?? null), ['text', 'output_text'], true)) {
                            $blockTexts[] = (string)$block['text'];
                        }
                    }
                    $blockText = trim(implode("\n", array_filter($blockTexts, static fn ($t) => $t !== '')));
                    if ($blockText !== '') {
                        if ($messagesText !== '') {
                            $messagesText .= "\n"; // separate message items
                        }
                        $messagesText .= $blockText;
                    }
                } elseif ($type === 'function_call') {
                    // Responses API function calls: results are matched by call_id, which is what "id" carries here
                    $toolCalls[] = [
                        'id' => $item['call_id'] ?? null,
                        'name' => $item['name'] ?? null,
                        'arguments' => $item['arguments'] ?? null,
                        'item_id' => $item['id'] ?? null,
                    ];
                } elseif ($type === 'tool_call') {
                    $toolCalls[] = [
                        'id' => $item['id'] ?? null,
                        'name' => $item['name'] ?? null,
                        'arguments' => $item['arguments'] ?? null,
                    ];
                }
            }
        }

        $raw = $resp;
        // Include normalized message blocks in raw for advanced consumers
        $raw['_normalized_message_blocks'] = $messageBlocks;

        return [
            'responseId' => $id,
            'conversationId' => $convId,
            'messages' => $messagesText,
            'messageBlocks' => $messageBlocks,
            'toolCalls' => $toolCalls,
            'usage' => $usage,
            'finishReason' => $finish,
            'raw' => $raw,
        ];
    }

    private function metrics(): MetricsCollectionService
    {
        return app(MetricsCollectionService::class);
    }

    private function toolRegistry(): ToolRegistry
    {
        return app(ToolRegistry::class);
    }

    /**
     * Validate audio processing payload.
     *
     * @param array $payload Audio payload to validate
     * @throws InvalidArgumentException When required parameters are missing or invalid
     * @throws FileOperationException When file is invalid or missing
     */
    private function validateAudioPayload(array $payload): void
    {
        if (empty($payload)) {
            throw new InvalidArgumentException('Audio payload cannot be empty.');
        }

        // Validate file parameter
        if (!isset($payload['file'])) {
            throw new InvalidArgumentException('File parameter is required for audio processing.');
        }

        $file = $payload['file'];
        if (is_string($file)) {
            if (!is_readable($file)) {
                throw new FileOperationException('File parameter must be a readable file path.');
            }
        } elseif ($file instanceof SplFileInfo) {
            $path = $file->getRealPath() ?: $file->getPathname();
            if (!is_string($path) || $path === '' || !is_readable($path)) {
                throw new FileOperationException('File parameter must be a readable file.');
            }
        } elseif (!is_resource($file)) {
            throw new FileOperationException('File parameter must be a valid file resource or path.');
        }

        // Validate model if provided
        if (isset($payload['model'])) {
            if (!is_string($payload['model']) || trim($payload['model']) === '') {
                throw new InvalidArgumentException('Model must be a non-empty string.');
            }
        }

        // Validate temperature if provided
        if (isset($payload['temperature'])) {
            if (!is_numeric($payload['temperature']) || $payload['temperature'] < 0 || $payload['temperature'] > 1) {
                throw new InvalidArgumentException('Temperature must be a number between 0 and 1 for audio processing.');
            }
        }

        // Validate language if provided
        if (isset($payload['language'])) {
            if (!is_string($payload['language']) || strlen($payload['language']) !== 2) {
                throw new InvalidArgumentException('Language must be a 2-character ISO 639-1 language code.');
            }
        }
    }

    private function streaming(): StreamingService
    {
        return app(StreamingService::class);
    }

    private function extractToolCallFromEvent(array $evt): ?array
    {
        $d = $evt['data'] ?? null;
        if (!is_array($d)) {
            return null;
        }
        // Try common shapes
        $id = $d['id'] ?? ($d['tool_call']['id'] ?? null);
        $name = $d['name'] ?? ($d['tool_call']['name'] ?? null);
        $args = $d['arguments'] ?? ($d['tool_call']['arguments'] ?? null);
        if ($id && $name) {
            // decode args if string
            if (!is_array($args)) {
                $decoded = json_decode((string)$args, true);
                $args = is_array($decoded) ? $decoded : [];
            }
            return ['id' => (string)$id, 'name' => (string)$name, 'arguments' => $args];
        }
        // Some events nest under item or step
        if (isset($d['item']) && is_array($d['item'])) {
            $item = $d['item'];
            $id = $item['id'] ?? null;
            $name = $item['name'] ?? null;
            $args = $item['arguments'] ?? null;
            if ($id && $name) {
                if (!is_array($args)) {
                    $decoded = json_decode((string)$args, true);
                    $args = is_array($decoded) ? $decoded : [];
                }
                return ['id' => (string)$id, 'name' => (string)$name, 'arguments' => $args];
            }
        }
        return null;
    }
}
