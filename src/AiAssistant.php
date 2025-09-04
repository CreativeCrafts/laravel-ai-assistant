<?php

declare(strict_types=1);

namespace CreativeCrafts\LaravelAiAssistant;

use Closure;
use CreativeCrafts\LaravelAiAssistant\Contracts\AiAssistantContract;
use CreativeCrafts\LaravelAiAssistant\Contracts\FunctionCallParameterContract;
use CreativeCrafts\LaravelAiAssistant\DataTransferObjects\ChatResponseDto;
use CreativeCrafts\LaravelAiAssistant\DataTransferObjects\ResponseEnvelope;
use CreativeCrafts\LaravelAiAssistant\Exceptions\ApiResponseValidationException;
use CreativeCrafts\LaravelAiAssistant\Exceptions\FileOperationException;
use CreativeCrafts\LaravelAiAssistant\Services\AppConfig;
use CreativeCrafts\LaravelAiAssistant\Services\AssistantService;
use CreativeCrafts\LaravelAiAssistant\Services\StreamReader;
use CreativeCrafts\LaravelAiAssistant\Support\LegacyCompletionsShim;
use CreativeCrafts\LaravelAiAssistant\ValueObjects\TurnOptions;
use Generator;
use Illuminate\Contracts\Container\BindingResolutionException;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use InvalidArgumentException;
use ReflectionFunction;
use ReflectionMethod;
use ReflectionNamedType;
use stdClass;

/**
 * AiAssistant is the ephemeral, Responses API entry point. For persistent Assistants with threads and runs, use Assistant.
 * See README: "Which class should I use?" for guidance on choosing between AiAssistant and Assistant.
 * The AiAssistant class provides a comprehensive interface for interacting with various OpenAI API endpoints.
 * This class handles ephemeral, session-based interactions with OpenAI's APIs, including
 * - Chat Completions API (for conversational AI)
 * - Text Completions API (for text generation)
 * - Text Edits API (for text modification)
 * - Audio Transcription API (for speech-to-text)
 * Unlike the Assistant class which creates persistent OpenAI assistants, this class is designed
 * for immediate, stateless operations. Each configuration array serves a specific API endpoint:
 * - `chatTextGeneratorConfig`: For chat completion sessions and tool configurations
 * - `textGeneratorConfig`: For simple text generation
 * - `editTextGeneratorConfig`: For text editing operations
 * - `audioToTextGeneratorConfig`: For audio transcription settings
 * The different naming convention from Assistant's `modelConfig` is intentional to reflect
 * the ephemeral, API-specific nature of these configurations versus persistent assistant setup.
 */
class AiAssistant implements AiAssistantContract
{
    use LegacyCompletionsShim;

    protected AssistantService $client;
    /**
     * Configuration array for OpenAI Text Completions API.
     * Used for simple text generation and completion tasks.
     */
    protected array $textGeneratorConfig = [];
    /**
     * Configuration array for OpenAI Chat Completions API parameters.
     * This array stores session-specific configuration for chat completions including:
     * - model: The AI model to use for chat completions
     * - messages: Array of conversation messages
     * - tools: Array of tools available during the chat session (code_interpreter, file_search, function)
     * - tool_resources: Resources associated with tools (file_ids, vector_store_ids)
     * - instructions: System instructions for the chat session
     * - temperature: Response creativity/randomness
     * - response_format: Format for the response (text, json_schema)
     * - metadata: Session metadata
     * - attachments: File attachments for the session
     * - modalities: Supported input/output modalities
     * This is intentionally named differently from Assistant's `modelConfig` to reflect
     * its purpose for ephemeral chat sessions rather than persistent assistant configuration.
     */
    protected array $chatTextGeneratorConfig = [];
    /**
     * Internal normalized alias for per-turn options. Will mirror chatTextGeneratorConfig
     * for one release cycle to reduce a cognitive load. Prefer using turnOptions internally.
     */
    protected array $turnOptions = [];
    /** Control auto-reset semantics; default from config('ai-assistant.reset_after_turn', true) */
    protected ?bool $autoReset = null;
    /**
     * Configuration array for OpenAI Text Edits API.
     * Used for text modification and correction tasks.
     */
    protected array $editTextGeneratorConfig = [];
    /**
     * Configuration array for OpenAI Audio Transcription API.
     * Used for speech-to-text and audio processing tasks.
     */
    protected array $audioToTextGeneratorConfig = [];
    /** @var TurnOptions */
    private TurnOptions $options;

    /**
     * Constructs a new AiAssistant instance.
     */
    public function __construct(
        protected string $prompt = ''
    ) {
        $this->textGeneratorConfig = AppConfig::textGeneratorConfig();
        $this->chatTextGeneratorConfig = AppConfig::chatTextGeneratorConfig();
        $this->turnOptions = $this->chatTextGeneratorConfig; // start in sync
        $this->options = TurnOptions::fromArray($this->chatTextGeneratorConfig);
        $this->editTextGeneratorConfig = AppConfig::editTextGeneratorConfig();
        $this->audioToTextGeneratorConfig = AppConfig::audioToTextGeneratorConfig();
        $this->autoReset = (bool)config('ai-assistant.reset_after_turn', true);
    }

    /**
     * Accepts a prompt and returns a new instance of the AiAssistant class.
     * This method is used to create a new AiAssistant instance with a given prompt.
     * It is a static method, allowing for a fluent interface when initializing the AiAssistant class.
     */
    public static function acceptPrompt(string $prompt): self
    {
        return new self($prompt);
    }

    /**
     * @throws BindingResolutionException
     */
    public static function init(?AssistantService $client = null): self
    {
        $resolved = $client ?? app()->make(AssistantService::class);
        $instance = new self();
        $instance->client($resolved);
        return $instance;
    }

    /**
     * Sets the AssistantService client for making API requests.
     */
    public function client(AssistantService $client): self
    {
        $this->client = $client;
        return $this;
    }

    /**
     * Control auto-reset behavior. When true, resetTurn() is called after each send/stream.
     * Documented default is config('ai-assistant.reset_after_turn', true).
     */
    public function withAutoReset(bool $enabled): self
    {
        $this->autoReset = $enabled;
        return $this;
    }

    /**
     * Use an existing conversation id.
     */
    public function useConversation(string $conversationId): self
    {
        $this->setTurn('conversation_id', $conversationId);
        return $this;
    }

    /**
     * Alias for instructions(): set a system message mapping to instructions.
     */
    public function setSystemMessage(string $message): self
    {
        return $this->instructions($message);
    }

    /**
     * Set developer/system instructions (assistant persona) to be sent per turn.
     */
    public function instructions(string $systemOrPersona): self
    {
        $this->setTurn('instructions', $systemOrPersona);
        return $this;
    }

    /**
     * Alias for instructions(): set a developer message mapping to instructions.
     */
    public function setDeveloperMessage(string $message): self
    {
        return $this->instructions($message);
    }

    /**
     * Set the model name for responses.create.
     */
    public function setModelName(string $model): self
    {
        $this->setTurn('model', $model);
        return $this;
    }

    /**
     * Include the file_search tool.
     *
     * @param array $vectorStoreIds Optional vector store IDs to associate with the file search tool
     */
    public function includeFileSearchTool(array $vectorStoreIds = []): self
    {
        // Deduplicate file_search tool
        $tools = (array)($this->chatTextGeneratorConfig['tools'] ?? []);
        $already = false;
        foreach ($tools as $t) {
            if (($t['type'] ?? null) === 'file_search') {
                $already = true;
                break;
            }
        }
        if ($already) {
            Log::warning('[AI Assistant] includeFileSearchTool: duplicate file_search tool skipped');
        } else {
            $tools[] = ['type' => 'file_search'];
        }
        $this->setTurn('tools', $tools);

        if ($vectorStoreIds !== []) {
            $this->chatTextGeneratorConfig['tool_resources'] = array_merge(
                $this->chatTextGeneratorConfig['tool_resources'] ?? [],
                [
                    'file_search' => [
                        'vector_store_ids' => $vectorStoreIds,
                    ]
                ]
            );
        }

        return $this;
    }

    /**
     * Toggle auto file_search behavior for this turn.
     */
    public function useFileSearch(bool $enabled = true): self
    {
        $this->setTurn('use_file_search', $enabled);
        return $this;
    }

    /**
     * Add the tool_choice option for this turn.
     * Accepts 'auto' | 'required' | 'none' | ['type' => 'function', 'name' => '...']
     */
    public function setToolChoice(string|array $choice): self
    {
        $this->setTurn('tool_choice', $choice);
        return $this;
    }

    /**
     * Set modalities for this turn.
     */
    public function setModalities(string|array $modalities): self
    {
        $this->setTurn('modalities', $modalities);
        return $this;
    }

    /**
     * Attach per-turn metadata.
     */
    public function withMetadata(array $metadata): self
    {
        $existing = (array)($this->chatTextGeneratorConfig['metadata'] ?? []);
        $this->setTurn('metadata', array_merge($existing, $metadata));
        return $this;
    }

    /**
     * Set an idempotency key for this turn.
     */
    public function setIdempotencyKey(string $key): self
    {
        $this->setTurn('idempotency_key', $key);
        return $this;
    }

    /**
     * Explicitly set attachments array for this turn.
     */
    public function setAttachments(array $attachments): self
    {
        // Early schema validation: each attachment must have file_id:string and optional tools: array
        foreach ($attachments as $idx => $att) {
            if (!is_array($att)) {
                throw new InvalidArgumentException('Attachment at index ' . $idx . ' must be an array.');
            }
            if (!isset($att['file_id']) || !is_string($att['file_id']) || trim($att['file_id']) === '') {
                throw new InvalidArgumentException('Attachment at index ' . $idx . ' must include a non-empty file_id string.');
            }
            if (isset($att['tools']) && !is_array($att['tools'])) {
                throw new InvalidArgumentException('Attachment tools for file_id ' . $att['file_id'] . ' must be an array.');
            }
        }
        // Validate tools inside attachments if provided
        foreach ($attachments as $att) {
            if (isset($att['tools'])) {
                foreach ((array)$att['tools'] as $tool) {
                    if (!is_array($tool) || !isset($tool['type'])) {
                        throw new InvalidArgumentException('Attachment tool entries must be arrays with a type. Allowed: file_search, code_interpreter.');
                    }
                    $type = $tool['type'];
                    if (!in_array($type, ['file_search', 'code_interpreter'], true)) {
                        throw new InvalidArgumentException("Invalid attachment tool type '{$type}'. Allowed: file_search, code_interpreter.");
                    }
                }
            }
        }
        $this->setTurn('attachments', array_values($attachments));
        return $this;
    }

    /**
     * Cancel an in-flight response.
     */
    public function cancelResponse(string $responseId): bool
    {
        $this->client = $this->client ?? resolve(AssistantService::class);
        return $this->client->cancel($responseId);
    }

    /**
     * Include the code_interpreter tool.
     * Deduplicates internally; merges provided file_ids into tool_resources uniquely.
     *
     * @param array $fileIds Optional file IDs to associate with the code interpreter tool
     */
    public function includeCodeInterpreterTool(array $fileIds = []): self
    {
        // Ensure tools array exists
        $tools = (array)($this->chatTextGeneratorConfig['tools'] ?? []);
        $has = false;
        foreach ($tools as $t) {
            if (($t['type'] ?? null) === 'code_interpreter') {
                $has = true;
                break;
            }
        }
        if ($has) {
            Log::warning('[AI Assistant] includeCodeInterpreterTool: duplicate code_interpreter tool skipped');
        } else {
            $tools[] = ['type' => 'code_interpreter'];
        }
        $this->setTurn('tools', $tools);

        if ($fileIds !== []) {
            // Merge file_ids under tool_resources.code_interpreter.file_ids uniquely
            $resources = (array)($this->chatTextGeneratorConfig['tool_resources'] ?? []);
            $ci = (array)($resources['code_interpreter'] ?? []);
            $existing = array_values(array_filter((array)($ci['file_ids'] ?? []), 'is_string'));
            $merged = array_values(array_unique(array_merge($existing, array_values(array_filter($fileIds, 'is_string')))));
            $resources['code_interpreter'] = ['file_ids' => $merged];
            $this->chatTextGeneratorConfig['tool_resources'] = $resources;
        }

        return $this;
    }

    /**
     * Set response_format to plain text.
     */
    public function setResponseFormatText(): self
    {
        $this->chatTextGeneratorConfig['response_format'] = 'text';
        return $this;
    }

    /**
     * Set response_format to a JSON schema structure.
     */
    public function setResponseFormatJsonSchema(array $jsonSchema, ?string $name = 'response'): self
    {
        if (empty($jsonSchema)) {
            throw new InvalidArgumentException('setResponseFormatJsonSchema() expects a non-empty JSON Schema array.');
        }
        if (!is_array($jsonSchema)) {
            throw new InvalidArgumentException('JSON Schema must be provided as an associative array.');
        }
        // Minimal validation: require a type or properties in schema
        $hasType = isset($jsonSchema['type']) && is_string($jsonSchema['type']) && $jsonSchema['type'] !== '';
        $hasProps = isset($jsonSchema['properties']) && is_array($jsonSchema['properties']);
        if (!$hasType && !$hasProps) {
            throw new InvalidArgumentException('JSON Schema must include at least a "type" or "properties" field.');
        }
        if ($name !== null) {
            $name = trim($name);
            if ($name === '') {
                throw new InvalidArgumentException('JSON schema name must be a non-empty string when provided.');
            }
        } else {
            $name = 'response';
        }
        $this->chatTextGeneratorConfig['response_format'] = [
            'type' => 'json_schema',
            'json_schema' => [
                'name' => $name,
                'schema' => $jsonSchema,
            ],
        ];
        return $this;
    }

    /**
     * Send the prepared user message and receive a typed envelope.
     */
    public function sendChatMessageEnvelope(): ResponseEnvelope
    {
        $arr = $this->sendChatMessage();
        return ResponseEnvelope::fromArray($arr);
    }

    /**
     * Send the prepared user message via Responses API.
     * Returns a normalized ResponseEnvelope array.
     *
     * @deprecated since 2.0.0 Use Ai::chat()->send() for a fluent, typed API, or sendChatMessageEnvelope()/sendChatMessageDto() for direct typed envelopes.
     */
    public function sendChatMessage(): array
    {
        $this->client = $this->client ?? resolve(AssistantService::class);
        $conversationId = $this->conversationId();
        if ($conversationId === null || $conversationId === '') {
            $this->startConversation();
            $conversationId = (string)$this->conversationId();
        }
        $message = (string)($this->chatTextGeneratorConfig['user_message'] ?? $this->prompt ?? '');
        if ($message === '') {
            throw new InvalidArgumentException('User message cannot be empty. Tip: call setUserMessage() or pass a prompt to AiAssistant::acceptPrompt().');
        }
        $options = ($this->options ?? TurnOptions::fromArray($this->chatTextGeneratorConfig))->toArray();
        $resp = $this->client->sendChatMessage($conversationId, $message, $options);
        $shouldReset = $this->autoReset ?? (bool)config('ai-assistant.reset_after_turn', true);
        if ($shouldReset) {
            $this->resetTurn();
        }
        return $resp;
    }

    /**
     * Get the current conversation id (if any).
     */
    public function conversationId(): ?string
    {
        $conversationId = $this->getTurn('conversation_id');
        return is_string($conversationId) ? $conversationId : null;
    }

    /**
     * Start a new conversation and keep its id internally.
     */
    public function startConversation(array $metadata = []): self
    {
        $this->client = $this->client ?? resolve(AssistantService::class);
        $conversationId = $this->client->createConversation($metadata);
        $this->setTurn('conversation_id', $conversationId);
        return $this;
    }

    /**
     * Reset selected per-turn state keys to avoid unintended reuse.
     * Note: auto-reset after each send/stream is controlled by config('ai-assistant.reset_after_turn', true).
     * You can override this at runtime via withAutoReset(bool $enabled).
     */
    public function resetTurn(array $keys = ['user_message', 'file_ids', 'input_images', 'attachments']): self
    {
        foreach ($keys as $key) {
            if (array_key_exists($key, $this->chatTextGeneratorConfig)) {
                unset($this->chatTextGeneratorConfig[$key]);
            }
        }
        return $this;
    }

    /**
     * Send the prepared user message and receive a simplified typed DTO.
     */
    public function sendChatMessageDto(): ChatResponseDto
    {
        $arr = $this->sendChatMessage();
        return ChatResponseDto::fromArray($arr);
    }

    /**
     * Minimal happy-path chain: optionally set the user message and send.
     * Example usages:
     * - AiAssistant::acceptPrompt('How?')->reply();
     * - AiAssistant::acceptPrompt('')->reply('How do I paginate?');
     *
     * @deprecated since 2.0.0 Use Ai::chat($message)->send() or Ai::chat()->setUserMessage($message)->send().
     */
    public function reply(?string $message = null): array
    {
        if ($message !== null) {
            $this->setUserMessage($message);
        }
        return $this->sendChatMessage();
    }

    /**
     * Set the user message (text) for the next turn.
     */
    public function setUserMessage(string $text): self
    {
        $this->setTurn('user_message', $text);
        return $this;
    }

    /**
     * Simple text streaming wrapper. Calls $onTextChunk for text deltas/completions and yields the same text pieces.
     *
     * @param callable(string $text):void $onTextChunk
     * @param callable():bool|null $shouldStop
     * @return Generator<string>
     */
    public function streamChatText(callable $onTextChunk, ?callable $shouldStop = null): Generator
    {
        $events = $this->streamChatMessage(null, $shouldStop);
        $reader = app(StreamReader::class);
        yield from $reader->onTextChunks($events, $onTextChunk);
    }

    /**
     * Stream the response for the prepared user message via Responses API.
     */
    public function streamChatMessage(?callable $onEvent = null, ?callable $shouldStop = null): Generator
    {
        $this->client = $this->client ?? resolve(AssistantService::class);
        $conversationId = $this->conversationId();
        if ($conversationId === null || $conversationId === '') {
            $this->startConversation();
            $conversationId = (string)$this->conversationId();
        }
        $message = (string)($this->chatTextGeneratorConfig['user_message'] ?? $this->prompt ?? '');
        if ($message === '') {
            throw new InvalidArgumentException('User message cannot be empty. Tip: call setUserMessage() or pass a prompt to AiAssistant::acceptPrompt().');
        }
        $options = ($this->options ?? TurnOptions::fromArray($this->chatTextGeneratorConfig))->toArray();
        $gen = $this->client->getStreamingResponse($conversationId, $message, $options, $onEvent, $shouldStop);
        $shouldReset = $this->autoReset ?? (bool)config('ai-assistant.reset_after_turn', true);
        if ($shouldReset) {
            $this->resetTurn();
        }
        return $gen;
    }

    /**
     * Continue a turn returning a typed ResponseEnvelope.
     */
    public function continueWithToolResultsEnvelope(array $toolResults): ResponseEnvelope
    {
        $arr = $this->continueWithToolResults($toolResults);
        return ResponseEnvelope::fromArray($arr);
    }

    /**
     * Continue a turn with tool_result items.
     *
     * @deprecated since 2.0.0 Use continueWithToolResultsEnvelope()/continueWithToolResultsDto() or Ai::chat()->continueWithToolResults().
     */
    public function continueWithToolResults(array $toolResults): array
    {
        $this->client = $this->client ?? resolve(AssistantService::class);
        $conversationId = $this->conversationId();
        if ($conversationId === null || $conversationId === '') {
            throw new InvalidArgumentException('No conversation in progress. Call startConversation() or useConversation().');
        }
        $model = $this->chatTextGeneratorConfig['model'] ?? null;
        $instructions = $this->chatTextGeneratorConfig['instructions'] ?? null;
        return $this->client->continueWithToolResults($conversationId, $toolResults, $model, $instructions);
    }

    /**
     * Continue a turn returning a simplified ChatResponseDto.
     */
    public function continueWithToolResultsDto(array $toolResults): ChatResponseDto
    {
        $arr = $this->continueWithToolResults($toolResults);
        return ChatResponseDto::fromArray($arr);
    }

    /**
     * Attach a Laravel UploadedFile to this turn (as file_reference / file_search attachment).
     */
    public function attachUploadedFile(UploadedFile $file): self
    {
        $path = $file->getRealPath();
        if (!is_string($path) || $path === '') {
            throw new InvalidArgumentException('UploadedFile does not have a valid real path.');
        }
        $fileId = $this->uploadFile($path, 'assistants/answers');
        return $this->attachFilesToTurn([$fileId]);
    }

    /**
     * Upload a file to OpenAI and return the file_id.
     */
    public function uploadFile(string $path, string $purpose = 'assistants/answers'): string
    {
        $this->client = $this->client ?? resolve(AssistantService::class);
        return $this->client->uploadFile($path, $purpose);
    }

    /**
     * Attach file ids to the next turn (as file_reference blocks / file_search attachments).
     */
    public function attachFilesToTurn(array $fileIds, ?bool $useFileSearch = null): self
    {
        $current = (array)($this->chatTextGeneratorConfig['file_ids'] ?? []);
        foreach ($fileIds as $fid) {
            if (is_string($fid) && $fid !== '' && !in_array($fid, $current, true)) {
                $current[] = $fid;
            }
        }
        $this->setTurn('file_ids', $current);

        if ($useFileSearch !== null) {
            $this->setTurn('use_file_search', (bool)$useFileSearch);
        }
        // Optionally include file_search tool; respect use_file_search flag (default true)
        $use = $this->chatTextGeneratorConfig['use_file_search'] ?? true;
        if ($use) {
            if (empty($this->chatTextGeneratorConfig['tools'])) {
                $this->chatTextGeneratorConfig['tools'] = [];
            }
            $hasFileSearch = false;
            foreach ($this->chatTextGeneratorConfig['tools'] as $t) {
                if (is_array($t) && ($t['type'] ?? null) === 'file_search') {
                    $hasFileSearch = true;
                    break;
                }
            }
            if (!$hasFileSearch) {
                $this->chatTextGeneratorConfig['tools'][] = ['type' => 'file_search'];
            }
        }
        return $this;
    }

    /**
     * Add an input_image from a Laravel UploadedFile for this turn.
     */
    public function addImageFromUploadedFile(UploadedFile $file): self
    {
        $path = $file->getRealPath();
        if (!is_string($path) || $path === '') {
            throw new InvalidArgumentException('UploadedFile does not have a valid real path.');
        }
        $this->client = $this->client ?? resolve(AssistantService::class);
        $fileId = $this->client->uploadFile($path, 'assistants/answers');
        $imgs = (array)($this->chatTextGeneratorConfig['input_images'] ?? []);
        if (!in_array($fileId, $imgs, true)) {
            $imgs[] = $fileId;
        }
        $this->setTurn('input_images', $imgs);
        return $this;
    }

    /**
     * Attach files from Laravel Storage disk paths to this turn.
     * Example: ['documents/report.pdf', 'invoices/2024-01.pdf']
     */
    public function attachFilesFromStorage(array $paths): self
    {
        $ids = [];
        foreach ($paths as $p) {
            if (!is_string($p) || trim($p) === '') {
                continue;
            }
            $abs = Storage::path($p);
            $id = $this->uploadFile($abs, 'assistants/answers');
            if (is_string($id) && $id !== '') {
                $ids[] = $id;
            }
        }
        if ($ids !== []) {
            $this->attachFilesToTurn($ids);
        }
        return $this;
    }

    /**
     * Overload: include a function-calling tool using a typed parameters contract.
     */
    public function includeFunctionCallToolFromContract(
        string $functionName,
        string $functionDescription,
        FunctionCallParameterContract $parameters,
        bool $isStrict = false
    ): self {
        return $this->includeFunctionCallTool(
            $functionName,
            $functionDescription,
            $parameters->toArray(),
            $isStrict
        );
    }

    /**
     * Include a function-calling tool definition.
     */
    public function includeFunctionCallTool(string $functionName, string $functionDescription = '', array $functionParameters = [], bool $isStrict = false): self
    {
        $allow = config('ai-assistant.tools.allowlist');
        if (is_array($allow) && $allow !== [] && !in_array($functionName, $allow, true)) {
            throw new InvalidArgumentException("Disallowed tool. Configure ai-assistant.tools.allowlist to include '{$functionName}'.");
        }
        $tool = [
            'type' => 'function',
            'function' => [
                'name' => $functionName,
                'description' => $functionDescription,
                'parameters' => $functionParameters !== [] ? [
                    'type' => 'object',
                    'properties' => $functionParameters['properties'] ?? [],
                    'required' => $functionParameters['required'] ?? [],
                    'additionalProperties' => $functionParameters['additionalProperties'] ?? false,
                ] : new stdClass(),
                'strict' => $isStrict,
            ],
        ];
        // Deduplicate function tools by name
        $tools = (array)($this->chatTextGeneratorConfig['tools'] ?? []);
        foreach ($tools as $t) {
            if (($t['type'] ?? null) === 'function' && ($t['function']['name'] ?? null) === $functionName) {
                Log::warning('[AI Assistant] includeFunctionCallTool: duplicate function tool skipped', ['function' => $functionName]);
                return $this;
            }
        }
        $tools[] = $tool;
        $this->setTurn('tools', $tools);
        return $this;
    }

    /**
     * Define a function tool from a callable by inferring a simple JSON Schema via Reflection.
     * Supports scalar types (string,int,float,bool) and array (as array of strings).
     */
    public function includeFunctionFromCallable(
        callable $fn,
        ?string $exportedName = null,
        string $description = '',
        bool $isStrict = false
    ): self {
        // Determine name
        $name = $exportedName;
        $ref = null;
        if (is_array($fn) && count($fn) === 2) {
            [$objOrClass, $method] = $fn;
            $ref = new ReflectionMethod($objOrClass, $method);
            $name = $name ?? (is_string($objOrClass) ? $objOrClass . '::' . $method : get_class($objOrClass) . '::' . $method);
        } elseif ($fn instanceof Closure) {
            $ref = new ReflectionFunction($fn);
            $name = $name ?? ($ref->getName() ?: 'closure');
        } elseif (is_string($fn)) {
            $ref = new ReflectionFunction($fn);
            $name = $name ?? $fn;
        } elseif (is_object($fn) && method_exists($fn, '__invoke')) {
            $ref = new ReflectionMethod($fn, '__invoke');
            $name = $name ?? (get_class($fn) . '::__invoke');
        } else {
            throw new InvalidArgumentException('Unsupported callable type for includeFunctionFromCallable');
        }
        // Sanitize tool name to allowed pattern ^[a-zA-Z0-9_-]{1,64}$
        $safeName = preg_replace('/[^a-zA-Z0-9_-]/', '_', (string)$name);
        $safeName = substr($safeName ?? '', 0, 64);

        // Build schema
        $properties = [];
        $required = [];
        foreach ($ref->getParameters() as $p) {
            $propType = 'string';
            $t = $p->getType();
            if ($t instanceof ReflectionNamedType) {
                $tn = $t->getName();
                $propType = match ($tn) {
                    'string' => 'string',
                    'int' => 'integer',
                    'float', 'double' => 'number',
                    'bool' => 'boolean',
                    'array' => 'array',
                    default => 'string',
                };
            }
            $schema = ['type' => $propType];
            if ($propType === 'array') {
                $schema['items'] = ['type' => 'string'];
            }
            $properties[$p->getName()] = $schema;
            if (!$p->isOptional()) {
                $required[] = $p->getName();
            }
        }
        $params = [
            'properties' => $properties,
            'required' => $required,
            'additionalProperties' => false,
        ];

        return $this->includeFunctionCallTool($safeName ?: 'function', $description, $params, $isStrict);
    }

    /**
     * Clarified helper name: add an input_image from a local file by uploading it.
     */
    public function addInputImageFromFile(string $path): self
    {
        return $this->addImageFromFile($path);
    }

    /**
     * Upload an image file and attach it to the next turn as an input_image block.
     */
    public function addImageFromFile(string $path): self
    {
        $this->client = $this->client ?? resolve(AssistantService::class);
        $fileId = $this->client->uploadFile($path, 'assistants/answers');
        $imgs = (array)($this->chatTextGeneratorConfig['input_images'] ?? []);
        if (!in_array($fileId, $imgs, true)) {
            $imgs[] = $fileId;
        }
        $this->setTurn('input_images', $imgs);
        return $this;
    }

    /**
     * Clarified helper name: add an input_image referencing a public URL.
     */
    public function addInputImageFromUrl(string $url): self
    {
        return $this->addImageFromUrl($url);
    }

    /**
     * Add an input_image block referencing a public URL for this turn.
     */
    public function addImageFromUrl(string $url): self
    {
        $url = trim($url);
        if ($url === '' || !preg_match('/^https?:\/\//i', $url)) {
            throw new InvalidArgumentException("Invalid URL provided to addImageFromUrl(). Example: https://example.com/image.jpg or http://cdn.example.com/a.png");
        }
        $imgs = (array)($this->chatTextGeneratorConfig['input_images'] ?? []);
        $imgs[] = ['url' => $url];
        $this->setTurn('input_images', $imgs);
        return $this;
    }

    /**
     * Attach a file_id as a file_reference block for this turn.
     */
    public function attachFileReference(string $fileId, ?bool $useFileSearch = null): self
    {
        return $this->attachFilesToTurn([$fileId], $useFileSearch);
    }

    /**
     * Attach files specifically for file_search via attachments.
     * Accepts a single file_id or an array of file_ids.
     */
    public function attachForFileSearch(string|array $fileIds, ?bool $useFileSearch = null): self
    {
        $ids = is_array($fileIds) ? $fileIds : [$fileIds];
        $ids = array_values(array_filter($ids, 'is_string'));
        $attachments = (array)($this->chatTextGeneratorConfig['attachments'] ?? []);
        $existingIds = [];
        foreach ($attachments as $att) {
            if (is_array($att) && isset($att['file_id'])) {
                $existingIds[] = $att['file_id'];
            }
        }
        foreach ($ids as $fid) {
            if (!in_array($fid, $existingIds, true)) {
                $attachments[] = [
                    'file_id' => $fid,
                    'tools' => [['type' => 'file_search']],
                ];
            }
        }
        $this->setTurn('attachments', $attachments);
        if ($useFileSearch !== null) {
            $this->setTurn('use_file_search', (bool)$useFileSearch);
        }
        return $this;
    }

    /**
     * Generates a draft based on the provided prompt.
     * This method sets the prompt in the text generator configuration and calls the processTextCompletion method to generate the draft.
     */

    /**
     * Translates the current prompt to the specified language.
     * This method creates a proper translation instruction with the current prompt and calls the processTextCompletion method to generate the translated text.
     */

    /**
     * Initiates a chat response based on the current prompt.
     * This method prepares the chat text generator configuration with the current prompt,
     * then calls the processChatTextCompletion method to generate the chat response.
     */

    /**
     * Adds a custom function to the chat text generator configuration and processes the chat completion.
     * This method takes a CustomFunctionData object as a parameter, extracts the messages from the ChatMessageData instance,
     * and appends the custom function data to the chat text generator configuration. It then calls the processChatTextCompletion method
     * to generate the chat response with the custom function.
     */

    /**
     * Performs spelling and grammar correction on the current prompt.
     * This method appends a specific instruction to the prompt, indicating that the AI assistant should fix the spelling and grammar errors.
     * It then calls the processTextEditCompletion method to generate the corrected text.
     */

    /**
     * Improves the readability of the current prompt by generating an edited version.
     * This method appends a specific instruction to the prompt, indicating that the AI assistant should edit the text to make it more readable.
     * It then calls the processTextEditCompletion method to generate the improved text.
     */

    /**
     * Transcribes an audio file to text using the specified language.
     * This method opens the audio file specified by the prompt, sets the language for transcription,
     * and optionally provides an optional text prompt for the transcription process.
     * It then calls the transcribeTo method of the AssistantService client to perform the transcription.
     *
     * @throws InvalidArgumentException When language parameter is invalid.
     * @throws FileOperationException When file cannot be opened or is invalid.
     */
    /**
     * @deprecated since 1.8.0 Use AssistantService::transcribeTo() instead.
     */

    /**
     * Translates an audio file to text using the specified language.
     * This function opens the audio file specified by the prompt, sets the language for transcription,
     * and then calls the translateTo method of the AssistantService client to perform the translation.
     *
     * @throws FileOperationException When file cannot be opened or is invalid.
     */

    /**
     * Processes the text completion request using the provided configuration.
     * This method checks if the 'stream' option is set in the text generator configuration.
     * If it is, it calls the 'streamedCompletion' method of the AssistantService client.
     * Otherwise, it calls the 'textCompletion' method of the AssistantService client.
     */
    /**
     * @deprecated since 1.8.0 Use AssistantService::textCompletion() instead.
     */

    /**
     * Processes the chat text completion request using the provided configuration.
     * This method checks if the 'stream' option is set in the chat text generator configuration.
     * If it is, it calls the 'streamedChat' method of the AssistantService client.
     * Otherwise, it calls the 'chatTextCompletion' method of the AssistantService client.
     * After processing the request, it caches the conversation using the ChatMessageData instance.
     */
    /**
     * @deprecated since 1.8.0 Use AssistantService::chatTextCompletion() or sendChatMessage() instead.
     */

    /**
     * Processes the text edit completion request using the provided instructions.
     * This function checks the model specified in the edit text generator configuration.
     * If the model is 'gpt-3.5-turbo' or starts with 'gpt-4', it prepares a chat text completion request
     * by setting assistant instructions and calling the processChatTextCompletion method.
     * The function then returns the content of the first message in the response.
     * If the model is not 'gpt-3.5-turbo' or does not start with 'gpt-4', it prepares a text completion request
     * by appending the instructions and the prompt to the text generator configuration,
     * and then calls the processTextCompletion method.
     *
     * @throws ApiResponseValidationException When API response structure is invalid.
     */


    /**
     * Safely open audio file for reading.
     * This method opens the audio file with proper error handling and
     * returns a file resource for use in API calls.
     *
     * @param string $filePath Path to the audio file
     * @return resource File resource for the opened file
     * @throws FileOperationException When file cannot be opened
     */

    // =========================
    // Responses/Conversations Fluent API (Migration: Fluent API updates)
    // =========================

    /**
     * Prefer this helper to write per-turn options. Mirrors to both turnOptions and chatTextGeneratorConfig for BC.
     */
    private function setTurn(string $key, mixed $value): void
    {
        $this->turnOptions[$key] = $value;
        $this->chatTextGeneratorConfig[$key] = $value;
        $this->options = ($this->options ?? TurnOptions::fromArray($this->chatTextGeneratorConfig))->withRaw($key, $value);
    }

    /**
     * Read a per-turn option, preferring the normalized turnOptions.
     */
    private function getTurn(string $key, mixed $default = null): mixed
    {
        return $this->turnOptions[$key] ?? $this->chatTextGeneratorConfig[$key] ?? $default;
    }
}
