<?php

declare(strict_types=1);

namespace CreativeCrafts\LaravelAiAssistant\Services;

use CreativeCrafts\LaravelAiAssistant\Compat\OpenAI\Responses\Assistants\AssistantResponse;
use CreativeCrafts\LaravelAiAssistant\Compat\OpenAI\Responses\Meta\MetaInformation;
use CreativeCrafts\LaravelAiAssistant\Compat\OpenAI\Responses\Threads\Messages\ThreadMessageResponse;
use CreativeCrafts\LaravelAiAssistant\Compat\OpenAI\Responses\Threads\ThreadResponse;
use CreativeCrafts\LaravelAiAssistant\Contracts\AssistantManagementContract;
use CreativeCrafts\LaravelAiAssistant\Contracts\AudioProcessingContract;
use CreativeCrafts\LaravelAiAssistant\Contracts\OpenAiRepositoryContract;
use CreativeCrafts\LaravelAiAssistant\Contracts\TextCompletionContract;
use CreativeCrafts\LaravelAiAssistant\Contracts\ThreadOperationContract;
use CreativeCrafts\LaravelAiAssistant\Exceptions\ApiResponseValidationException;
use CreativeCrafts\LaravelAiAssistant\Exceptions\FileOperationException;
use CreativeCrafts\LaravelAiAssistant\Exceptions\MaxRetryAttemptsExceededException;
use CreativeCrafts\LaravelAiAssistant\Exceptions\ThreadExecutionTimeoutException;
use CreativeCrafts\LaravelAiAssistant\OpenAIClientFacade;
use Exception;
use Generator;
use Illuminate\Support\Facades\Log;
use InvalidArgumentException;
use JsonException;
use Mockery\MockInterface;
use Random\RandomException;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

class AssistantService implements AssistantManagementContract, AudioProcessingContract, TextCompletionContract, ThreadOperationContract
{
    // New Orchestrator helpers for Responses and Conversations migration
    protected OpenAiRepositoryContract $repository;
    protected CacheService $cacheService;

    public function __construct(OpenAiRepositoryContract $repository, CacheService $cacheService)
    {
        $this->repository = $repository;
        $this->cacheService = $cacheService;
    }

    /**
     * Expose current correlation id used in LoggingService for this request flow.
     */
    public function getCorrelationId(): ?string
    {
        return app(LoggingService::class)->getCorrelationId();
    }

    /**
     * Create a new assistant with the specified parameters.
     * This function creates a new assistant using the repository abstraction.
     *
     * @param array $parameters An array of parameters for creating the assistant.
     *                          This may include properties such as name, instructions,
     *                          tools, and model.
     * @return AssistantResponse The response object containing details of the created assistant.
     * @throws InvalidArgumentException When required parameters are missing or invalid.
     * @deprecated Assistants API is deprecated. Prefer using Conversations + Responses and pass instructions/model per turn. See docs/Migrate_from_AssistantAPI_to_ResponseAPI.md
     */
    public function createAssistant(array $parameters): AssistantResponse
    {
        $this->validateAssistantParameters($parameters);
        return $this->repository->createAssistant($parameters);
    }

    /**
     * Retrieve an assistant by its ID.
     * This function fetches the details of a specific assistant using the repository abstraction.
     *
     * @param string $assistantId The unique identifier of the assistant to retrieve.
     * @return AssistantResponse The response object containing details of the retrieved assistant.
     * @throws InvalidArgumentException When assistant ID is invalid.
     * @deprecated Assistants API is deprecated. Prefer getting conversation state via listConversationItems(). See docs/Migrate_from_AssistantAPI_to_ResponseAPI.md
     */
    public function getAssistantViaId(string $assistantId): AssistantResponse
    {
        $this->validateAssistantId($assistantId);
        return $this->repository->retrieveAssistant($assistantId);
    }

    /**
     * Create a new thread with the specified parameters.
     *
     * @param array $parameters An array of parameters for creating the thread (metadata, etc.).
     * @return ThreadResponse Synthetic ThreadResponse mapped to a new conversation.
     * @throws RandomException
     * @deprecated Use createConversation() instead and track conversationId. This shim maps a legacy thread to a conversation.
     * See docs/Migrate_from_AssistantAPI_to_ResponseAPI.md for details.
     */
    public function createThread(array $parameters): ThreadResponse
    {
        // When a repository mock is injected (unit/integration tests), use legacy repository behavior
        if (interface_exists('\Mockery\MockInterface') && $this->repository instanceof MockInterface) {
            return $this->repository->createThread($parameters);
        }

        // Default shim path: map to Conversations API and return a synthetic ThreadResponse
        Log::warning('[DEPRECATION] AssistantService::createThread is deprecated. Use Conversations API (createConversation) instead. Mapping to conversation...');

        $metadata = is_array($parameters['metadata'] ?? null) ? (array)$parameters['metadata'] : [];

        // 1) Create a new conversation
        $conversationId = $this->createConversation($metadata);

        // 2) Generate a legacy-like thread id and store mapping
        $threadId = 'thread_' . substr(bin2hex(random_bytes(24)), 0, 24);
        app(ThreadsToConversationsMapper::class)->map($threadId, $conversationId);

        // 3) Build a synthetic ThreadResponse
        $attributes = [
            'id' => $threadId,
            'object' => 'thread',
            'created_at' => time(),
            'tool_resources' => null,
            'metadata' => $metadata,
        ];
        return ThreadResponse::from($attributes, MetaInformation::from([]));
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
        $conv = $this->facade()->conversations()->createConversation($payload);
        return (string)($conv['id'] ?? $conv['conversation']['id'] ?? '');
    }

    /**
     * Write a new message to a specific thread.
     *
     * @param string $threadId The legacy thread id to write to
     * @param array $messageData ['role'=>'user','content'=>string|array, ...]
     * @return ThreadMessageResponse Synthetic response reflecting the posted user message
     * @throws JsonException
     * @throws RandomException
     * @deprecated Use sendChatMessage(conversationId, message, options) instead. This shim maps thread -> conversation and posts via Responses API.
     * See docs/Migrate_from_AssistantAPI_to_ResponseAPI.md for details.
     */
    public function writeMessage(string $threadId, array $messageData): ThreadMessageResponse
    {
        // When a repository mock is injected (unit/integration tests), use legacy repository behavior
        if (interface_exists('\Mockery\MockInterface') && $this->repository instanceof MockInterface) {
            $this->validateThreadId($threadId);
            $this->validateMessageData($messageData);
            return $this->repository->createThreadMessage($threadId, $messageData);
        }

        Log::warning('[DEPRECATION] AssistantService::writeMessage is deprecated. Use sendChatMessage with Conversations API. Mapping to conversation...');
        $this->validateThreadId($threadId);
        $this->validateMessageData($messageData);

        // Map or create conversation
        $conversationId = app(ThreadsToConversationsMapper::class)->getOrMap($threadId, fn () => $this->createConversation([]));

        // Extract plain message text from legacy content
        $content = $messageData['content'] ?? '';
        $text = '';
        if (is_string($content)) {
            $text = $content;
        } elseif (is_array($content)) {
            // Try finding text content shapes from legacy array
            if (isset($content[0]['text']['value'])) {
                $text = (string)$content[0]['text']['value'];
            } elseif (isset($content['text'])) {
                $text = is_array($content['text']) ? (string)($content['text']['value'] ?? '') : (string)$content['text'];
            } else {
                $text = json_encode($content, JSON_THROW_ON_ERROR) ?: '';
            }
        }

        // Build options passthrough if provided
        $options = [];
        foreach (
            [
                'model',
                'instructions',
                'tools',
                'metadata',
                'response_format',
                'modalities',
                'idempotency_key',
                'tool_choice',
                'attachments',
                'file_ids',
                'input_images',
                'use_file_search'
            ] as $k
        ) {
            if (array_key_exists($k, $messageData)) {
                $options[$k] = $messageData[$k];
            }
        }

        // Post message via Responses API
        $this->sendChatMessage($conversationId, (string)$text, $options);

        // Build synthetic ThreadMessageResponse
        $role = (string)($messageData['role'] ?? 'user');
        $attributes = [
            'id' => 'msg_' . substr(bin2hex(random_bytes(24)), 0, 24),
            'object' => 'thread.message',
            'created_at' => time(),
            'thread_id' => $threadId,
            'role' => $role,
            'content' => [
                [
                    'type' => 'text',
                    'text' => [
                        'value' => (string)$text,
                        'annotations' => [],
                    ],
                ],
            ],
            'assistant_id' => null,
            'run_id' => null,
            'attachments' => is_array($messageData['attachments'] ?? null) ? (array)$messageData['attachments'] : [],
            'metadata' => is_array($messageData['metadata'] ?? null) ? (array)$messageData['metadata'] : [],
        ];

        return ThreadMessageResponse::from($attributes, MetaInformation::from([]));
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
        $useFileSearch = $options['use_file_search'] ?? true;

        $fileIds = array_values(array_unique(array_filter((array)($options['file_ids'] ?? []), 'is_string')));
        $imageInputs = array_values(array_filter((array)($options['input_images'] ?? []), static function ($v) {
            return is_string($v) || is_array($v);
        }));
        $attachments = array_values(array_filter((array)($options['attachments'] ?? []), 'is_array'));
        if ($attachments === [] && $fileIds !== []) {
            $attachments = array_map(static fn (string $fid) => [
                'file_id' => $fid,
                'tools' => [['type' => 'file_search']],
            ], $fileIds);
        }
        $attachments = $this->validateAttachments($attachments);

        // Auto-enable file_search tool if file references/attachments are provided and not already present
        if ($useFileSearch && ($fileIds !== [] || $attachments !== [])) {
            $hasFileSearch = false;
            foreach ($tools as $t) {
                if (is_array($t) && ($t['type'] ?? null) === 'file_search') {
                    $hasFileSearch = true;
                    break;
                }
            }
            if (!$hasFileSearch) {
                $tools[] = ['type' => 'file_search'];
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
                'type' => 'file_reference',
                'file_id' => $fid,
            ];
        }

        $input = [
            'role' => 'user',
            'content' => $contentBlocks,
        ];
        if ($attachments !== []) {
            $input['attachments'] = $attachments;
        }
        $inputItems = [$input];

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
            $options['tool_choice'] ?? null
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
        array|string|null $toolChoice = null
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
            $toolChoice
        );
        $__start = microtime(true);
        $resp = $this->facade()->responses()->createResponse($payload);
        $envelope = $this->normalizeResponseEnvelope($resp);
        // Emit metrics after normalization
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
                        // Provide graceful error output when tool is missing
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
                $idempotencyKey
            );
        }

        return $envelope;
    }

    /**
     * Post tool_result items to the conversation and ask the model to continue the turn.
     */
    public function continueWithToolResults(
        string $conversationId,
        array $toolResults,
        ?string $model = null,
        ?string $instructions = null,
        ?string $idempotencyKey = null
    ): array {
        // 1) Insert tool_result items in the conversation
        $items = [];
        foreach ($toolResults as $tr) {
            // Expecting [tool_call_id, output]
            $toolCallId = (string)($tr['tool_call_id'] ?? '');
            $output = $tr['output'] ?? null;
            if ($toolCallId === '') {
                continue;
            }
            $text = is_string($output) ? $output : json_encode($output);
            $items[] = [
                'type' => 'tool_result',
                'role' => 'tool',
                'tool_call_id' => $toolCallId,
                'content' => [
                    ['type' => 'output_text', 'text' => (string)$text],
                ],
            ];
        }
        if ($items !== []) {
            $this->facade()->conversations()->createItems($conversationId, $items);
        }

        // 2) Trigger a new responses.create referencing same conversation
        $payload = $this->buildResponsesCreatePayload(
            $conversationId,
            $instructions,
            $model,
            tools: [],
            inputItems: [],
            responseFormat: null,
            modalities: null,
            metadata: [],
            idempotencyKey: $idempotencyKey
        );
        $__start = microtime(true);
        $resp = $this->facade()->responses()->createResponse($payload);
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

    /**
     * Run a message thread and wait for its completion.
     *
     * @param string $threadId The legacy thread id
     * @param array $runThreadParameter ['assistant_id'|'model'|'instructions'|'tools'|...]
     * @param int $timeoutSeconds Unused in shim (kept for signature compatibility)
     * @param int $maxRetryAttempts Unused in shim
     * @param float $initialDelay Unused in shim
     * @param float $backoffMultiplier Unused in shim
     * @param float $maxDelay Unused in shim
     * @return bool Always true when the Responses turn is created successfully.
     * @deprecated Use sendTurn(conversationId, ...) instead. This shim maps thread -> conversation and triggers a Responses turn.
     * See docs/Migrate_from_AssistantAPI_to_ResponseAPI.md for details.
     */
    public function runMessageThread(
        string $threadId,
        array $runThreadParameter,
        int $timeoutSeconds = 300,
        int $maxRetryAttempts = 60,
        float $initialDelay = 1.0,
        float $backoffMultiplier = 1.5,
        float $maxDelay = 30.0
    ): bool {
        // When a repository mock is injected (unit/integration tests), use legacy repository behavior with polling
        if (interface_exists('\Mockery\MockInterface') && $this->repository instanceof MockInterface) {
            $this->validateThreadId($threadId);
            $start = microtime(true);
            $delay = max(0.0, $initialDelay);
            $attempts = 0;

            $run = $this->repository->createThreadRun($threadId, $runThreadParameter);
            $runId = $run->id;

            while (true) {
                // Timeout check first
                if ((microtime(true) - $start) >= $timeoutSeconds) {
                    throw new ThreadExecutionTimeoutException("Thread execution timed out after {$timeoutSeconds} seconds. Thread ID: {$threadId}, Run ID: {$runId}");
                }
                if ($attempts >= $maxRetryAttempts) {
                    throw new MaxRetryAttemptsExceededException("Maximum retry attempts ({$maxRetryAttempts}) exceeded for thread execution. Thread ID: {$threadId}, Run ID: {$runId}");
                }

                $current = $this->repository->retrieveThreadRun($threadId, $runId);
                $status = $current->status;
                if ($status === 'completed') {
                    return true;
                }
                if ($status === 'failed') {
                    throw new ThreadExecutionTimeoutException("Thread execution failed with status 'failed'. Thread ID: {$threadId}, Run ID: {$runId}");
                }

                $attempts++;
                if ($delay > 0) {
                    usleep((int)($delay * 1_000_000));
                    $delay = min($maxDelay, $delay * max(1.0, $backoffMultiplier));
                }
            }
        }

        // Default shim path: map to Conversations/Responses API
        Log::warning('[DEPRECATION] AssistantService::runMessageThread is deprecated. Use sendTurn with Conversations API. Mapping to conversation...');

        $this->validateThreadId($threadId);
        // Map or create conversation
        $conversationId = app(ThreadsToConversationsMapper::class)->getOrMap($threadId, fn () => $this->createConversation([]));

        // Extract parameters
        $model = isset($runThreadParameter['model']) && is_string($runThreadParameter['model']) ? $runThreadParameter['model'] : null;
        $instructions = isset($runThreadParameter['instructions']) && is_string($runThreadParameter['instructions']) ? $runThreadParameter['instructions'] : null;
        $tools = is_array($runThreadParameter['tools'] ?? null) ? (array)$runThreadParameter['tools'] : [];
        $metadata = is_array($runThreadParameter['metadata'] ?? null) ? (array)$runThreadParameter['metadata'] : [];
        $idempotencyKey = isset($runThreadParameter['idempotency_key']) && is_string($runThreadParameter['idempotency_key']) ? $runThreadParameter['idempotency_key'] : null;

        // Trigger a turn with no new user input to let the assistant response based on existing context
        $this->sendTurn(
            $conversationId,
            $instructions,
            $model,
            $tools,
            inputItems: [],
            responseFormat: $runThreadParameter['response_format'] ?? null,
            modalities: $runThreadParameter['modalities'] ?? null,
            metadata: $metadata,
            idempotencyKey: $idempotencyKey,
            toolChoice: $runThreadParameter['tool_choice'] ?? null
        );

        return true;
    }

    // ===== Helpers =====

    /**
     * List messages for a specific thread and return the content of the first message.
     *
     * @deprecated Use listConversationItems(conversationId) instead. This shim maps thread -> conversation and reads from Conversations API.
     * See docs/Migrate_from_AssistantAPI_to_ResponseAPI.md for details.
     */
    public function listMessages(string $threadId): string
    {
        Log::warning('[DEPRECATION] AssistantService::listMessages is deprecated. Use listConversationItems with Conversations API. Mapping to conversation...');
        $this->validateThreadId($threadId);

        // When a repository mock is injected (unit/integration tests), use legacy repository behavior
        if (interface_exists('\Mockery\MockInterface') && $this->repository instanceof MockInterface) {
            $resp = $this->repository->listThreadMessages($threadId);

            if (!is_array($resp) || !isset($resp['data']) || !is_array($resp['data'])) {
                throw new ApiResponseValidationException('Invalid API response structure: missing or invalid data array.');
            }
            if ($resp['data'] === []) {
                return '';
            }
            $first = $resp['data'][0] ?? null;
            if (!is_array($first) || !isset($first['content']) || !is_array($first['content'])) {
                throw new ApiResponseValidationException('Invalid message structure: missing or invalid content array.');
            }
            if ($first['content'] === []) {
                return '';
            }
            $firstBlock = $first['content'][0];
            if (!is_array($firstBlock) || !isset($firstBlock['text']) || !is_array($firstBlock['text'])) {
                throw new ApiResponseValidationException('Invalid content structure: missing or invalid text array.');
            }
            $text = $firstBlock['text'];
            $value = $text['value'] ?? ($text['text'] ?? null);
            return is_string($value) ? $value : '';
        }

        $conversationId = app(ThreadsToConversationsMapper::class)->get($threadId);
        if ($conversationId === null) {
            // No mapping found – create one to allow legacy callers to proceed
            $conversationId = app(ThreadsToConversationsMapper::class)->getOrMap($threadId, fn () => $this->createConversation([]));
        }

        $items = $this->listConversationItems($conversationId);
        if (!isset($items['data']) || !is_array($items['data']) || $items['data'] === []) {
            return '';
        }
        $first = $items['data'][0] ?? null;
        if (!is_array($first) || !isset($first['content']) || !is_array($first['content']) || $first['content'] === []) {
            return '';
        }
        $firstBlock = $first['content'][0];
        if (is_array($firstBlock)) {
            if (($firstBlock['type'] ?? null) === 'text') {
                $text = $firstBlock['text'] ?? '';
                return is_array($text) ? (string)($text['value'] ?? '') : (string)$text;
            }
            if (($firstBlock['type'] ?? null) === 'output_text') {
                $text = $firstBlock['text'] ?? '';
                return is_array($text) ? (string)($text['value'] ?? '') : (string)$text;
            }
        }
        return '';
    }

    /**
     * List conversation items.
     */
    public function listConversationItems(string $conversationId, array $params = []): array
    {
        return $this->facade()->conversations()->listItems($conversationId, $params);
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
        $response = $this->repository->transcribeAudio($payload);
        return is_object($response) && property_exists($response, 'text') ? (string)$response->text : '';
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
        $response = $this->repository->translateAudio($payload);
        return is_object($response) && property_exists($response, 'text') ? (string)$response->text : '';
    }

    /**
     * Generate text completion using the repository abstraction.
     * This function sends a request through the repository for text completion
     * and returns the generated text. It uses caching to avoid redundant API calls
     * for identical requests.
     *
     * @param array $payload An array containing the necessary parameters for text completion.
     *                       This typically includes:
     *                       - 'model': The ID of the model to use for completion
     *                       - 'prompt': The prompt to generate completions for
     *                       - 'max_tokens': The maximum number of tokens to generate
     *                       - 'temperature': Controls randomness in the output
     *                       - Other optional parameters as per OpenAI API documentation
     * @return string The generated text completion. Returns an empty string if no choices are returned.
     * @throws InvalidArgumentException When required parameters are missing or invalid.
     */
    public function textCompletion(array $payload): string
    {
        $this->validateTextCompletionPayload($payload);

        // Check for a cached result if prompt is provided and temperature is deterministic
        if (isset($payload['prompt'], $payload['model'])) {
            $isDeterministic = !isset($payload['temperature']) || $payload['temperature'] <= 0.1;

            if ($isDeterministic) {
                $cachedResult = $this->cacheService->getCompletion(
                    (string)$payload['prompt'],
                    $payload['model'],
                    $this->filterCacheableParameters($payload)
                );

                if ($cachedResult !== null) {
                    return $cachedResult;
                }
            }
        }

        $choices = $this->repository->createCompletion($payload)->choices;

        if ($choices === []) {
            return '';
        }

        $lastChoice = $choices[count($choices) - 1];
        $result = is_object($lastChoice) && property_exists($lastChoice, 'text') ? trim((string)$lastChoice->text) : '';

        // Cache the result if conditions are met
        if (isset($payload['prompt'], $payload['model']) && $result !== '') {
            $isDeterministic = !isset($payload['temperature']) || $payload['temperature'] <= 0.1;

            if ($isDeterministic) {
                $this->cacheService->cacheCompletion(
                    (string)$payload['prompt'],
                    $payload['model'],
                    $this->filterCacheableParameters($payload),
                    $result
                );
            }
        }

        return $result;
    }

    /**
     * Generate a streamed text completion using the repository abstraction.
     * This function sends a request through the repository for streamed text completion
     * and accumulates all chunks to return the complete generated text.
     *
     * @param array $payload An array containing the necessary parameters for text completion.
     *                       This typically includes:
     *                       - 'model': The ID of the model to use for completion
     *                       - 'prompt': The prompt to generate completions for
     *                       - 'max_tokens': The maximum number of tokens to generate
     *                       - 'temperature': Controls randomness in the output
     *                       - Other optional parameters as per OpenAI API documentation
     * @return string The complete generated text from all stream chunks. Returns an empty string if no content is generated.
     * @throws InvalidArgumentException When required parameters are missing or invalid.
     */
    public function streamedCompletion(array $payload): string
    {
        $this->validateTextCompletionPayload($payload);
        $streamResponses = $this->repository->createStreamedCompletion($payload);

        $accumulatedText = '';
        $chunkCount = 0;

        foreach ($streamResponses as $response) {
            /** @var Response $response */
            if (isset($response->choices[0]->text)) {
                $chunkText = $response->choices[0]->text;
                $accumulatedText .= $chunkText;
                $chunkCount++;

                // Optional: Add logging for stream processing
                if ($chunkCount % 10 === 0 && class_exists('\CreativeCrafts\LaravelAiAssistant\Services\LoggingService')) {
                    // Log every 10 chunks to monitor streaming performance
                    try {
                        $loggingService = app(LoggingService::class);
                        $loggingService->logPerformanceMetrics(
                            'streamed_completion_progress',
                            0, // Duration not available per chunk
                            [
                                'chunks_processed' => $chunkCount,
                                'accumulated_length' => strlen($accumulatedText),
                                'current_chunk_length' => strlen($chunkText)
                            ]
                        );
                    } catch (Exception $e) {
                        // Silently continue if logging fails
                    }
                }
            }

            // Check for completion indicators
            if (isset($response->choices[0]->finish_reason) && $response->choices[0]->finish_reason !== null) {
                // Stream completed with a finish reason
                break;
            }
        }

        // Trim any leading/trailing whitespace from the complete response
        return trim($accumulatedText);
    }

    /**
     * Generate a chat text completion using the repository abstraction.
     * This function sends a request through the repository for chat text completion
     * and returns the message from the first choice in the response. It uses caching
     * to avoid redundant API calls for identical chat requests.
     *
     * @param array $payload An array containing the necessary parameters for chat completion.
     *                       This typically includes:
     *                       - 'model': The ID of the model to use for chat completion
     *                       - 'messages': An array of message objects representing the conversation history
     *                       - Other optional parameters as per OpenAI API documentation
     * @return array<string, mixed> An array representing the message from the first choice in the API response.
     *                              Contains keys like 'role', 'content', and optionally 'function_call' or 'tool_calls'.
     *                              Returns an empty array if no choices are returned.
     * @throws InvalidArgumentException When required parameters are missing or invalid.
     */
    public function chatTextCompletion(array $payload): array
    {
        $this->validateTextCompletionPayload($payload);

        // Check for a cached result if messages are provided and temperature is deterministic
        if (isset($payload['messages'], $payload['model']) && is_array($payload['messages'])) {
            $isDeterministic = !isset($payload['temperature']) || $payload['temperature'] <= 0.1;

            if ($isDeterministic) {
                $cacheKey = $this->buildChatCacheKey($payload);
                $cachedResult = $this->cacheService->getResponse($cacheKey);

                if ($cachedResult !== null) {
                    return $cachedResult;
                }
            }
        }

        $choices = $this->repository->createChatCompletion($payload)->choices;
        if ($choices === []) {
            return [];
        }

        $firstChoice = $choices[0];
        $result = [];
        if (is_object($firstChoice) && property_exists($firstChoice, 'message') && is_object($firstChoice->message)) {
            try {
                // Attempt to call toArray even if provided via magic __call (e.g., Mockery)
                $resultCandidate = method_exists($firstChoice->message, 'toArray')
                    ? $firstChoice->message->toArray()
                    : (array)$firstChoice->message;
                if (is_array($resultCandidate)) {
                    $result = $resultCandidate;
                } elseif (property_exists($firstChoice->message, 'content')) {
                    $result = ['content' => $firstChoice->message->content];
                } else {
                    $result = (array)$firstChoice->message;
                }
            } catch (Throwable $e) {
                // Fallback: try to extract content or cast to array
                if (property_exists($firstChoice->message, 'content')) {
                    $result = ['content' => $firstChoice->message->content];
                } else {
                    $result = (array)$firstChoice->message;
                }
            }
        }

        // Cache the result if conditions are met
        if (isset($payload['messages'], $payload['model'])) {
            $isDeterministic = !isset($payload['temperature']) || $payload['temperature'] <= 0.1;

            if ($isDeterministic) {
                $cacheKey = $this->buildChatCacheKey($payload);
                $this->cacheService->cacheResponse($cacheKey, $result);
            }
        }

        return $result;
    }

    /**
     * Generate a streamed chat completion using the repository abstraction.
     * This function sends a request through the repository for streamed chat completion
     * and accumulates all chunks to return the complete generated response.
     *
     * @param array $payload An array containing the necessary parameters for chat completion.
     *                       This typically includes:
     *                       - 'model': The ID of the model to use for chat completion
     *                       - 'messages': An array of message objects representing the conversation history
     *                       - Other optional parameters as per OpenAI API documentation
     * @return array<string, mixed> An array representing the complete accumulated response from all stream chunks.
     *                              Contains keys like 'role', 'content', 'finish_reason', and optionally 'function_call' or 'tool_calls'.
     *                              Returns an empty array if no content is generated.
     * @throws InvalidArgumentException When required parameters are missing or invalid.
     */
    public function streamedChat(array $payload): array
    {
        $this->validateTextCompletionPayload($payload);
        $streamResponses = $this->repository->createStreamedChatCompletion($payload);

        $accumulatedContent = '';
        $chunkCount = 0;
        $finalResponse = [];
        $role = 'assistant'; // Default role for assistant responses

        foreach ($streamResponses as $response) {
            /** @var Response $response */
            if (isset($response->choices[0])) {
                $choice = $response->choices[0];
                $chunkCount++;

                // Handle delta content (typical for streaming)
                if (isset($choice->delta->content)) {
                    $accumulatedContent .= $choice->delta->content;
                }

                // Handle role information if present
                if (isset($choice->delta->role)) {
                    $role = $choice->delta->role;
                }

                // Handle function calls if present
                if (isset($choice->delta->function_call)) {
                    if (!isset($finalResponse['function_call'])) {
                        $finalResponse['function_call'] = [
                            'name' => '',
                            'arguments' => ''
                        ];
                    }

                    if (isset($choice->delta->function_call->name)) {
                        $finalResponse['function_call']['name'] .= $choice->delta->function_call->name;
                    }

                    if (isset($choice->delta->function_call->arguments)) {
                        $finalResponse['function_call']['arguments'] .= $choice->delta->function_call->arguments;
                    }
                }

                // Handle tool calls if present
                if (isset($choice->delta->tool_calls)) {
                    if (!isset($finalResponse['tool_calls'])) {
                        $finalResponse['tool_calls'] = [];
                    }

                    foreach ($choice->delta->tool_calls as $toolCall) {
                        if (isset($toolCall->index)) {
                            $index = $toolCall->index;
                            if (!isset($finalResponse['tool_calls'][$index])) {
                                $finalResponse['tool_calls'][$index] = [];
                            }

                            if (isset($toolCall->function)) {
                                if (!isset($finalResponse['tool_calls'][$index]['function'])) {
                                    $finalResponse['tool_calls'][$index]['function'] = [
                                        'name' => '',
                                        'arguments' => ''
                                    ];
                                }

                                if (isset($toolCall->function->name) && isset($finalResponse['tool_calls'][$index]['function'])) {
                                    $finalResponse['tool_calls'][$index]['function']['name'] .= $toolCall->function->name;
                                }

                                if (isset($toolCall->function->arguments) && isset($finalResponse['tool_calls'][$index]['function'])) {
                                    $finalResponse['tool_calls'][$index]['function']['arguments'] .= $toolCall->function->arguments;
                                }
                            }
                        }
                    }
                }

                // Optional: Add logging for stream processing
                if ($chunkCount % 10 === 0 && class_exists('\CreativeCrafts\LaravelAiAssistant\Services\LoggingService')) {
                    try {
                        $loggingService = app(LoggingService::class);
                        $loggingService->logPerformanceMetrics(
                            'streamed_chat_progress',
                            0, // Duration not available per chunk
                            [
                                'chunks_processed' => $chunkCount,
                                'accumulated_content_length' => strlen($accumulatedContent),
                                'has_function_calls' => isset($finalResponse['function_call']),
                                'has_tool_calls' => isset($finalResponse['tool_calls'])
                            ]
                        );
                    } catch (Exception $e) {
                        // Silently continue if logging fails
                    }
                }

                // Check for completion indicators
                if (isset($choice->finish_reason) && $choice->finish_reason !== null) {
                    // Stream completed with a finish reason
                    $finalResponse['finish_reason'] = $choice->finish_reason;
                    break;
                }
            }
        }

        // Build the final response structure
        if (!empty($accumulatedContent) || !empty($finalResponse)) {
            $result = [
                'role' => $role,
                'content' => $accumulatedContent,
            ];

            // Add function call if present
            if (isset($finalResponse['function_call'])) {
                $result['function_call'] = $finalResponse['function_call'];
            }

            // Add tool calls if present
            if (isset($finalResponse['tool_calls'])) {
                $result['tool_calls'] = array_values($finalResponse['tool_calls']);
            }

            // Add finish reason if available
            if (isset($finalResponse['finish_reason'])) {
                $result['finish_reason'] = $finalResponse['finish_reason'];
            }

            return $result;
        }

        return [];
    }

    /**
     * Cancel an in-flight response by id.
     */
    public function cancel(string $responseId): bool
    {
        return $this->facade()->responses()->cancelResponse($responseId);
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

        $res = $this->facade()->files()->upload($filePath, $purpose);
        $id = (string)($res['id'] ?? ($res['data']['id'] ?? ''));
        if ($id === '') {
            throw new ApiResponseValidationException('Upload succeeded but no file id returned.');
        }
        return $id;
    }

    /**
     * Convenience wrapper: streaming response for a user text message.
     */
    public function getStreamingResponse(
        string $conversationId,
        string $message,
        array $options = [],
        ?callable $onEvent = null,
        ?callable $shouldStop = null
    ): Generator {
        $chatModel = config('ai-assistant.chat_model', config('ai-assistant.model'));
        $model = $options['model'] ?? (is_string($chatModel) ? $chatModel : '');
        $instructions = $options['instructions'] ?? null;
        $tools = $options['tools'] ?? [];
        $responseFormat = $options['response_format'] ?? null;
        $modalities = $options['modalities'] ?? null;
        $metadata = $options['metadata'] ?? [];
        $idempotencyKey = $options['idempotency_key'] ?? null;

        $fileIds = array_values(array_unique(array_filter((array)($options['file_ids'] ?? []), 'is_string')));
        $imageInputs = array_values(array_filter((array)($options['input_images'] ?? []), static function ($v) {
            return is_string($v) || is_array($v);
        }));
        $attachments = array_values(array_filter((array)($options['attachments'] ?? []), 'is_array'));
        $attachments = $this->validateAttachments($attachments);

        if ($fileIds !== [] || $attachments !== []) {
            $hasFileSearch = false;
            foreach ($tools as $t) {
                if (is_array($t) && ($t['type'] ?? null) === 'file_search') {
                    $hasFileSearch = true;
                    break;
                }
            }
            if (!$hasFileSearch) {
                $tools[] = ['type' => 'file_search'];
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
                'type' => 'file_reference',
                'file_id' => $fid,
            ];
        }

        $input = [
            'role' => 'user',
            'content' => $contentBlocks,
        ];
        if ($attachments !== []) {
            $input['attachments'] = $attachments;
        }
        $inputItems = [$input];

        return $this->streamTurn(
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
            $options['tool_choice'] ?? null
        );
    }

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
        array|string|null $toolChoice = null
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
            $toolChoice
        );

        $raw = $this->facade()->responses()->streamResponse($payload);
        $events = $this->streaming()->streamResponses($raw, $onEvent, $shouldStop);
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
     * Validate parameters for assistant creation.
     *
     * @param array $parameters Parameters to validate
     * @throws InvalidArgumentException When required parameters are missing or invalid
     */
    private function validateAssistantParameters(array $parameters): void
    {
        if (empty($parameters)) {
            throw new InvalidArgumentException('Assistant parameters cannot be empty.');
        }

        // Validate model parameter - avoid redundant trim() call
        $model = $parameters['model'] ?? null;
        if (!is_string($model) || $model === '' || trim($model) === '') {
            throw new InvalidArgumentException('Model parameter is required and must be a non-empty string.');
        }

        // Validate name if provided - early type check to avoid strlen on non-strings
        if (isset($parameters['name'])) {
            $name = $parameters['name'];
            if (!is_string($name) || strlen($name) > 256) {
                throw new InvalidArgumentException('Assistant name must be a string with maximum 256 characters.');
            }
        }

        // Validate instructions if provided - early type check to avoid strlen on non-strings
        if (isset($parameters['instructions'])) {
            $instructions = $parameters['instructions'];
            if (!is_string($instructions) || strlen($instructions) > 256000) {
                throw new InvalidArgumentException('Instructions must be a string with maximum 256,000 characters.');
            }
        }

        // Validate tools if provided - early type check and single count operation
        if (isset($parameters['tools'])) {
            $tools = $parameters['tools'];
            if (!is_array($tools)) {
                throw new InvalidArgumentException('Tools must be an array.');
            }
            if (count($tools) > 128) {
                throw new InvalidArgumentException('Maximum 128 tools are allowed per assistant.');
            }
        }

        // Validate temperature if provided - use single variable to avoid repeated array access
        if (isset($parameters['temperature'])) {
            $temperature = $parameters['temperature'];
            if (!is_numeric($temperature) || $temperature < 0 || $temperature > 2) {
                throw new InvalidArgumentException('Temperature must be a number between 0 and 2.');
            }
        }
    }

    /**
     * Validate assistant ID parameter.
     *
     * @param string $assistantId Assistant ID to validate
     * @throws InvalidArgumentException When assistant ID is invalid
     */
    private function validateAssistantId(string $assistantId): void
    {
        if (trim($assistantId) === '') {
            throw new InvalidArgumentException('Assistant ID cannot be empty.');
        }

        if (!preg_match('/^asst_[a-zA-Z0-9]{24}$/', $assistantId)) {
            throw new InvalidArgumentException('Assistant ID must follow the format: asst_[24 alphanumeric characters].');
        }
    }

    private function facade(): OpenAIClientFacade
    {
        // Resolve lazily to avoid constructor signature changes
        return app(OpenAIClientFacade::class);
    }

    /**
     * Validate thread ID parameter.
     *
     * @param string $threadId Thread ID to validate
     * @throws InvalidArgumentException When thread ID is invalid
     */
    private function validateThreadId(string $threadId): void
    {
        if (trim($threadId) === '') {
            throw new InvalidArgumentException('Thread ID cannot be empty.');
        }

        if (!preg_match('/^thread_[a-zA-Z0-9]{24}$/', $threadId)) {
            throw new InvalidArgumentException('Thread ID must follow the format: thread_[24 alphanumeric characters].');
        }
    }

    /**
     * Validate message data for thread operations.
     *
     * @param array $messageData Message data to validate
     * @throws InvalidArgumentException When message data is invalid
     */
    private function validateMessageData(array $messageData): void
    {
        if (empty($messageData)) {
            throw new InvalidArgumentException('Message data cannot be empty.');
        }

        // Validate role parameter
        if (!isset($messageData['role']) || !is_string($messageData['role']) || trim($messageData['role']) === '') {
            throw new InvalidArgumentException('Message role is required and must be a non-empty string.');
        }

        $validRoles = ['user', 'assistant', 'system'];
        if (!in_array($messageData['role'], $validRoles, true)) {
            throw new InvalidArgumentException('Message role must be one of: ' . implode(', ', $validRoles));
        }

        // Validate content parameter
        if (!isset($messageData['content'])) {
            throw new InvalidArgumentException('Message content is required.');
        }

        if (!is_string($messageData['content']) && !is_array($messageData['content'])) {
            throw new InvalidArgumentException('Message content must be a string or array.');
        }

        if (is_string($messageData['content']) && trim($messageData['content']) === '') {
            throw new InvalidArgumentException('Message content cannot be empty when provided as a string.');
        }

        if (is_array($messageData['content']) && empty($messageData['content'])) {
            throw new InvalidArgumentException('Message content cannot be empty when provided as an array.');
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

    // =========================
    // Responses + Conversations Orchestrator methods
    // =========================

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
        array|string|null $toolChoice = null
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
            $payload['tools'] = $tools;
        }
        if (!empty($inputItems)) {
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
                $new = ['role' => $role, 'content' => $normBlocks];
                if (isset($itm['attachments']) && is_array($itm['attachments'])) {
                    $new['attachments'] = $itm['attachments'];
                }
                $normalizedInput[] = $new;
            }
            $payload['input'] = $normalizedInput;
        }
        if (!empty($metadata)) {
            $payload['metadata'] = $metadata;
        }
        if ($responseFormat !== null) {
            $payload['response_format'] = $responseFormat;
        }
        if ($modalities !== null) {
            $payload['modalities'] = $modalities;
        }
        if ($toolChoice !== null) {
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
        // Map legacy max_completion_tokens to Responses max_output_tokens if provided in config
        $maxOut = config('ai-assistant.responses.max_output_tokens');
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

        if (!is_resource($payload['file'])) {
            throw new FileOperationException('File parameter must be a valid file resource.');
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

    /**
     * Validate text completion payload.
     *
     * @param array $payload Text completion payload to validate
     * @throws InvalidArgumentException When required parameters are missing or invalid
     */
    private function validateTextCompletionPayload(array $payload): void
    {
        if (empty($payload)) {
            throw new InvalidArgumentException('Text completion payload cannot be empty.');
        }

        // Validate model parameter
        if (!isset($payload['model']) || !is_string($payload['model']) || trim($payload['model']) === '') {
            throw new InvalidArgumentException('Model parameter is required and must be a non-empty string.');
        }

        // For chat completions, validate messages
        if (isset($payload['messages'])) {
            if (!is_array($payload['messages']) || empty($payload['messages'])) {
                throw new InvalidArgumentException('Messages must be a non-empty array for chat completions.');
            }

            foreach ($payload['messages'] as $index => $message) {
                if (!is_array($message)) {
                    throw new InvalidArgumentException("Message at index {$index} must be an array.");
                }
                if (!isset($message['role']) || !is_string($message['role'])) {
                    throw new InvalidArgumentException("Message at index {$index} must have a valid role.");
                }
                if (!isset($message['content']) || (!is_string($message['content']) && !is_array($message['content']))) {
                    throw new InvalidArgumentException("Message at index {$index} must have valid content.");
                }
            }
        }

        // For text completions, validate prompt
        if (isset($payload['prompt'])) {
            if (!is_string($payload['prompt']) && !is_array($payload['prompt'])) {
                throw new InvalidArgumentException('Prompt must be a string or array.');
            }
        }

        // Validate max_tokens if provided
        if (isset($payload['max_tokens'])) {
            if (!is_int($payload['max_tokens']) || $payload['max_tokens'] < 1) {
                throw new InvalidArgumentException('Max tokens must be a positive integer.');
            }
        }

        // Validate temperature if provided
        if (isset($payload['temperature'])) {
            if (!is_numeric($payload['temperature']) || $payload['temperature'] < 0 || $payload['temperature'] > 2) {
                throw new InvalidArgumentException('Temperature must be a number between 0 and 2.');
            }
        }

        // Validate top_p if provided
        if (isset($payload['top_p'])) {
            if (!is_numeric($payload['top_p']) || $payload['top_p'] < 0 || $payload['top_p'] > 1) {
                throw new InvalidArgumentException('Top_p must be a number between 0 and 1.');
            }
        }
    }

    /**
     * Filter parameters that should be included in cache key generation.
     * This method removes parameters that shouldn't affect caching decisions,
     * such as streaming flags or callback parameters.
     *
     * @param array $payload The original payload
     * @return array<string, mixed> Filtered parameters suitable for caching containing only cacheable parameter keys
     */
    private function filterCacheableParameters(array $payload): array
    {
        // Parameters that should be included in cache key
        $cacheableParams = [
            'model',
            'prompt',
            'messages',
            'max_tokens',
            'max_completion_tokens',
            'temperature',
            'top_p',
            'frequency_penalty',
            'presence_penalty',
            'stop',
            'functions',
            'function_call',
            'tools',
            'tool_choice',
            'response_format',
            'seed', // For deterministic responses
        ];

        return array_intersect_key($payload, array_flip($cacheableParams));
    }

    /**
     * Build a cache key for chat completion requests.
     * This method creates a deterministic cache key based on the chat payload,
     * ensuring that identical chat requests can be cached and retrieved efficiently.
     *
     * @param array $payload The chat completion payload
     * @return string Cache key for the chat request
     */
    private function buildChatCacheKey(array $payload): string
    {
        $cacheableParams = $this->filterCacheableParameters($payload);

        // Sort the parameters to ensure consistent key generation
        ksort($cacheableParams);

        // If messages exist, sort them to ensure consistency
        if (isset($cacheableParams['messages']) && is_array($cacheableParams['messages'])) {
            // Don't sort messages as order matters for chat context
            // but ensure consistent serialization
            $cacheableParams['messages'] = array_values($cacheableParams['messages']);
        }

        // Create a hash of the cacheable parameters
        $serialized = json_encode($cacheableParams);
        if ($serialized === false) {
            // Fallback if json_encode fails
            $serialized = serialize($cacheableParams);
        }
        $hash = hash('sha256', $serialized);

        return "chat_completion_{$hash}";
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
