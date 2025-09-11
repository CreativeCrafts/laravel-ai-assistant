# Contracts

Interfaces that define public contracts you can type‑hint against.

## Classes in this directory
- **AiAssistantContract** (interface) — *Package component.*  
  FQCN: `CreativeCrafts\LaravelAiAssistant\Contracts\AiAssistantContract`
  - **Key methods:**
    - `public static acceptPrompt(string $prompt): self`
- **AppConfigContract** (interface) — *Package component.*  
  FQCN: `CreativeCrafts\LaravelAiAssistant\Contracts\AppConfigContract`
  - **Key methods:**
    - `public static openAiClient(Client $client = null): Client`
- **AssistantContract** (interface) — *Package component.*  
  FQCN: `CreativeCrafts\LaravelAiAssistant\Contracts\AssistantContract`
  - **Key methods:**
    - `public static new(): Assistant`
    - `public client(AssistantService $client): Assistant`
    - `public setModelName(string $modelName): Assistant`
    - `public adjustTemperature(int|float $temperature): Assistant`
    - `public setAssistantName(string $assistantName = ''): Assistant`
    - `public setAssistantDescription(string $assistantDescription = ''): Assistant`
    - `public setInstructions(string $instructions = ''): Assistant`
    - `public includeCodeInterpreterTool(array $fileIds = []): Assistant`
    - `public includeFileSearchTool(array $vectorStoreIds = []): Assistant`
    - `public includeFunctionCallTool(string $functionName, string $functionDescription = '', FunctionCallParameterContract|array $functionParameters = [],...): Assistant`
    - `public create(): NewAssistantResponseDataContract`
    - `public assignAssistant(string $assistantId): Assistant`
- **AssistantManagementContract** (interface) — *Package component.*  
  FQCN: `CreativeCrafts\LaravelAiAssistant\Contracts\AssistantManagementContract`
  - **Key methods:**
    - `public createAssistant(array $parameters): \CreativeCrafts\LaravelAiAssistant\Compat\OpenAI\Responses\Assistants\AssistantResponse`
    - `public getAssistantViaId(string $assistantId): \CreativeCrafts\LaravelAiAssistant\Compat\OpenAI\Responses\Assistants\AssistantResponse`
- **AssistantResourceContract** (interface) — *Package component.*  
  FQCN: `CreativeCrafts\LaravelAiAssistant\Contracts\AssistantResourceContract`
  - **Key methods:**
    - `public __construct(?Client $client = null)`
    - `public createAssistant(array $parameters): AssistantResponse`
    - `public getAssistantViaId(string $assistantId): AssistantResponse`
    - `public createThread(array $parameters): ThreadResponse`
    - `public writeMessage(string $threadId, array $messageData): ThreadMessageResponse`
    - `public runMessageThread(string $threadId, array $runThreadParameter): bool`
    - `public listMessages(string $threadId): string`
    - `public transcribeTo(array $payload): string`
    - `public translateTo(array $payload): string`
    - `public textCompletion(array $payload): string`
    - `public streamedCompletion(array $payload): string`
    - `public chatTextCompletion(array $payload): array`
- **AudioProcessingContract** (interface) — *Package component.*  
  FQCN: `CreativeCrafts\LaravelAiAssistant\Contracts\AudioProcessingContract`
  - **Key methods:**
    - `public transcribeTo(array $payload): string`
    - `public translateTo(array $payload): string`
- **ChatAssistantMessageDataContract** (interface) — *Package component.*  
  FQCN: `CreativeCrafts\LaravelAiAssistant\Contracts\ChatAssistantMessageDataContract`
  - **Key methods:**
    - `public __construct(string $role, string|array|null $content = null, ?string $refusal = null, ?string $name = null, ?array $audio = null,...)`
    - `public toArray(): array`
- **ChatAssistantMessageDataFactoryContract** (interface) — *Package component.*  
  FQCN: `CreativeCrafts\LaravelAiAssistant\Contracts\ChatAssistantMessageDataFactoryContract`
  - **Key methods:**
    - `public static buildChatAssistantMessageData(string|array|null $content, ?string $refusal, ?string $name, ?array $audio, ?array $toolCalls): ChatAssistantMessageDataContract`
- **ChatCompletionDataContract** (interface) — *Package component.*  
  FQCN: `CreativeCrafts\LaravelAiAssistant\Contracts\ChatCompletionDataContract`
  - **Key methods:**
    - `public __construct(string $model, array $message, ?float $temperature, ?bool $store, ?string $reasoningEffort, ?array $metadata, ?int $m...)`
    - `public toArray(): array`
    - `public shouldStream(): bool`
- **ChatMessageDataContract** (interface) — *Package component.*  
  FQCN: `CreativeCrafts\LaravelAiAssistant\Contracts\ChatMessageDataContract`
  - **Key methods:**
    - `public __construct(string $prompt,)`
    - `public messages(): array`
    - `public setAssistantInstructions(string $instructions): array`
    - `public cacheConversation(array $conversation): void`
- **ChatResponseDtoContract** (interface) — *Package component.*  
  FQCN: `CreativeCrafts\LaravelAiAssistant\Contracts\ChatResponseDtoContract`
  - **Key methods:**
    - `public static fromArray(array $data): self`
    - `public toArray(): array`
- **ChatTextCompletionContract** (interface) — *Package component.*  
  FQCN: `CreativeCrafts\LaravelAiAssistant\Contracts\ChatTextCompletionContract`
  - **Key methods:**
    - `public __invoke(array $payload): array`
    - `public static messages(string $prompt): array`
    - `public static cacheChatConversation(array $conversation): void`
    - `public chatTextCompletion(array $payload): array`
    - `public streamedChat(array $payload): array`
- **ConversationStore** (interface) — *Package component.*  
  FQCN: `CreativeCrafts\LaravelAiAssistant\Contracts\ConversationStore`
- **ConversationsRepositoryContract** (interface) — *Package component.*  
  FQCN: `CreativeCrafts\LaravelAiAssistant\Contracts\ConversationsRepositoryContract`
  - **Key methods:**
    - `public createConversation(array $payload = []): array`
    - `public getConversation(string $conversationId): array`
    - `public listItems(string $conversationId, array $params = []): array`
    - `public createItems(string $conversationId, array $items): array`
    - `public deleteItem(string $conversationId, string $itemId): bool`
- **CreateAssistantDataContract** (interface) — *Package component.*  
  FQCN: `CreativeCrafts\LaravelAiAssistant\Contracts\CreateAssistantDataContract`
  - **Key methods:**
    - `public __construct(string $model, float|int|null $topP = null, ?float $temperature = null, ?string $assistantDescription = null, ?string...)`
    - `public toArray(): array`
- **CustomFunctionDataContract** (interface) — *Package component.*  
  FQCN: `CreativeCrafts\LaravelAiAssistant\Contracts\CustomFunctionDataContract`
  - **Key methods:**
    - `public toArray(): array`
- **FilesRepositoryContract** (interface) — *Package component.*  
  FQCN: `CreativeCrafts\LaravelAiAssistant\Contracts\FilesRepositoryContract`
  - **Key methods:**
    - `public upload(string $filePath, string $purpose = 'assistants'): array`
    - `public retrieve(string $fileId): array`
    - `public delete(string $fileId): bool`
- **FunctionCallDataContract** (interface) — *Package component.*  
  FQCN: `CreativeCrafts\LaravelAiAssistant\Contracts\FunctionCallDataContract`
  - **Key methods:**
    - `public __construct(string $functionName, string $functionDescription = '', FunctionCallParameterContract|array $parameters = [], bool $i...)`
    - `public toArray(): array`
- **FunctionCallParameterContract** (interface) — *Package component.*  
  FQCN: `CreativeCrafts\LaravelAiAssistant\Contracts\FunctionCallParameterContract`
  - **Key methods:**
    - `public __construct(string $type, array $properties = [],)`
    - `public toArray(): array`
- **MessageDataContract** (interface) — *Package component.*  
  FQCN: `CreativeCrafts\LaravelAiAssistant\Contracts\MessageDataContract`
  - **Key methods:**
    - `public __construct(string|array $message, string $role = 'user', string $toolCallId = '',)`
    - `public toArray(): array`
- **ModelConfigDataFactoryContract** (interface) — *Package component.*  
  FQCN: `CreativeCrafts\LaravelAiAssistant\Contracts\ModelConfigDataFactoryContract`
  - **Key methods:**
    - `public static buildTranscribeData(array $config): TranscribeToDataContract`
- **NewAssistantResponseDataContract** (interface) — *Package component.*  
  FQCN: `CreativeCrafts\LaravelAiAssistant\Contracts\NewAssistantResponseDataContract`
  - **Key methods:**
    - `public __construct(AssistantResponse $assistantResponse)`
    - `public assistantId(): string`
    - `public assistant(): AssistantResponse`
- **OpenAiRepositoryContract** (interface) — *Package component.*  
  FQCN: `CreativeCrafts\LaravelAiAssistant\Contracts\OpenAiRepositoryContract`
  - **Key methods:**
    - `public createAssistant(array $parameters): AssistantResponse`
    - `public retrieveAssistant(string $assistantId): AssistantResponse`
    - `public createThread(array $parameters): ThreadResponse`
    - `public createThreadMessage(string $threadId, array $messageData): ThreadMessageResponse`
    - `public createThreadRun(string $threadId, array $parameters): ThreadRunResponse`
    - `public retrieveThreadRun(string $threadId, string $runId): ThreadRunResponse`
    - `public listThreadMessages(string $threadId): array`
    - `public createCompletion(array $parameters): CompletionResponse`
    - `public createStreamedCompletion(array $parameters): iterable`
    - `public createChatCompletion(array $parameters): ChatResponse`
    - `public createStreamedChatCompletion(array $parameters): iterable`
    - `public transcribeAudio(array $parameters): TranscriptionResponse`
- **ResponsesRepositoryContract** (interface) — *Package component.*  
  FQCN: `CreativeCrafts\LaravelAiAssistant\Contracts\ResponsesRepositoryContract`
  - **Key methods:**
    - `public createResponse(array $payload): array`
    - `public streamResponse(array $payload): iterable`
    - `public getResponse(string $responseId): array`
    - `public cancelResponse(string $responseId): bool`
    - `public deleteResponse(string $responseId): bool`
- **TextCompletionContract** (interface) — *Package component.*  
  FQCN: `CreativeCrafts\LaravelAiAssistant\Contracts\TextCompletionContract`
  - **Key methods:**
    - `public textCompletion(array $payload): string`
    - `public streamedCompletion(array $payload): string`
    - `public chatTextCompletion(array $payload): array`
    - `public streamedChat(array $payload): array`
- **ThreadOperationContract** (interface) — *Package component.*  
  FQCN: `CreativeCrafts\LaravelAiAssistant\Contracts\ThreadOperationContract`
  - **Key methods:**
    - `public createThread(array $parameters): \CreativeCrafts\LaravelAiAssistant\Compat\OpenAI\Responses\Threads\ThreadResponse`
    - `public writeMessage(string $threadId, array $messageData): \CreativeCrafts\LaravelAiAssistant\Compat\OpenAI\Responses\Threads\Messages\ThreadMessageResponse`
    - `public runMessageThread(string $threadId, array $runThreadParameter, int $timeoutSeconds = 300, int $maxRetryAttempts = 60, float $initialDel...): bool`
    - `public listMessages(string $threadId): string`
- **TranscribeToDataContract** (interface) — *Package component.*  
  FQCN: `CreativeCrafts\LaravelAiAssistant\Contracts\TranscribeToDataContract`
  - **Key methods:**
    - `public __construct(string $model, float $temperature, string $responseFormat, mixed $filePath, string $language, ?string $prompt = null)`
    - `public toArray(): array`

## When to Use & Examples
### AiAssistantContract
**Use it when:**
- You want to type-hint interfaces for swapping implementations and testing.

**Example:**
```php
use CreativeCrafts\LaravelAiAssistant\Contracts\AiAssistantContract;

function handle(AiAssistantContract $dep) { /* ... */ }
```

### AppConfigContract
**Use it when:**
- You want to type-hint interfaces for swapping implementations and testing.

**Example:**
```php
use CreativeCrafts\LaravelAiAssistant\Contracts\AppConfigContract;

function handle(AppConfigContract $dep) { /* ... */ }
```

### AssistantContract
**Use it when:**
- You want to type-hint interfaces for swapping implementations and testing.

**Example:**
```php
use CreativeCrafts\LaravelAiAssistant\Contracts\AssistantContract;

function handle(AssistantContract $dep) { /* ... */ }
```

### AssistantManagementContract
**Use it when:**
- You want to type-hint interfaces for swapping implementations and testing.

**Example:**
```php
use CreativeCrafts\LaravelAiAssistant\Contracts\AssistantManagementContract;

function handle(AssistantManagementContract $dep) { /* ... */ }
```

### AssistantResourceContract
**Use it when:**
- You want to type-hint interfaces for swapping implementations and testing.

**Example:**
```php
use CreativeCrafts\LaravelAiAssistant\Contracts\AssistantResourceContract;

function handle(AssistantResourceContract $dep) { /* ... */ }
```

### AudioProcessingContract
**Use it when:**
- You want to type-hint interfaces for swapping implementations and testing.

**Example:**
```php
use CreativeCrafts\LaravelAiAssistant\Contracts\AudioProcessingContract;

function handle(AudioProcessingContract $dep) { /* ... */ }
```

### ChatAssistantMessageDataContract
**Use it when:**
- You want to type-hint interfaces for swapping implementations and testing.

**Example:**
```php
use CreativeCrafts\LaravelAiAssistant\Contracts\ChatAssistantMessageDataContract;

function handle(ChatAssistantMessageDataContract $dep) { /* ... */ }
```

### ChatAssistantMessageDataFactoryContract
**Use it when:**
- You want to type-hint interfaces for swapping implementations and testing.

**Example:**
```php
use CreativeCrafts\LaravelAiAssistant\Contracts\ChatAssistantMessageDataFactoryContract;

function handle(ChatAssistantMessageDataFactoryContract $dep) { /* ... */ }
```

### ChatCompletionDataContract
**Use it when:**
- You want to type-hint interfaces for swapping implementations and testing.

**Example:**
```php
use CreativeCrafts\LaravelAiAssistant\Contracts\ChatCompletionDataContract;

function handle(ChatCompletionDataContract $dep) { /* ... */ }
```

### ChatMessageDataContract
**Use it when:**
- You want to type-hint interfaces for swapping implementations and testing.

**Example:**
```php
use CreativeCrafts\LaravelAiAssistant\Contracts\ChatMessageDataContract;

function handle(ChatMessageDataContract $dep) { /* ... */ }
```

### ChatResponseDtoContract
**Use it when:**
- You want to type-hint interfaces for swapping implementations and testing.

**Example:**
```php
use CreativeCrafts\LaravelAiAssistant\Contracts\ChatResponseDtoContract;

function handle(ChatResponseDtoContract $dep) { /* ... */ }
```

### ChatTextCompletionContract
**Use it when:**
- You want to type-hint interfaces for swapping implementations and testing.

**Example:**
```php
use CreativeCrafts\LaravelAiAssistant\Contracts\ChatTextCompletionContract;

function handle(ChatTextCompletionContract $dep) { /* ... */ }
```

### ConversationStore
**Use it when:**
- You want to type-hint interfaces for swapping implementations and testing.

**Example:**
```php
use CreativeCrafts\LaravelAiAssistant\Contracts\ConversationStore;

function handle(ConversationStore $dep) { /* ... */ }
```

### ConversationsRepositoryContract
**Use it when:**
- You want to type-hint interfaces for swapping implementations and testing.

**Example:**
```php
use CreativeCrafts\LaravelAiAssistant\Contracts\ConversationsRepositoryContract;

function handle(ConversationsRepositoryContract $dep) { /* ... */ }
```

### CreateAssistantDataContract
**Use it when:**
- You want to type-hint interfaces for swapping implementations and testing.

**Example:**
```php
use CreativeCrafts\LaravelAiAssistant\Contracts\CreateAssistantDataContract;

function handle(CreateAssistantDataContract $dep) { /* ... */ }
```

### CustomFunctionDataContract
**Use it when:**
- You want to type-hint interfaces for swapping implementations and testing.

**Example:**
```php
use CreativeCrafts\LaravelAiAssistant\Contracts\CustomFunctionDataContract;

function handle(CustomFunctionDataContract $dep) { /* ... */ }
```

### FilesRepositoryContract
**Use it when:**
- You want to type-hint interfaces for swapping implementations and testing.

**Example:**
```php
use CreativeCrafts\LaravelAiAssistant\Contracts\FilesRepositoryContract;

function handle(FilesRepositoryContract $dep) { /* ... */ }
```

### FunctionCallDataContract
**Use it when:**
- You want to type-hint interfaces for swapping implementations and testing.

**Example:**
```php
use CreativeCrafts\LaravelAiAssistant\Contracts\FunctionCallDataContract;

function handle(FunctionCallDataContract $dep) { /* ... */ }
```

### FunctionCallParameterContract
**Use it when:**
- You want to type-hint interfaces for swapping implementations and testing.

**Example:**
```php
use CreativeCrafts\LaravelAiAssistant\Contracts\FunctionCallParameterContract;

function handle(FunctionCallParameterContract $dep) { /* ... */ }
```

### MessageDataContract
**Use it when:**
- You want to type-hint interfaces for swapping implementations and testing.

**Example:**
```php
use CreativeCrafts\LaravelAiAssistant\Contracts\MessageDataContract;

function handle(MessageDataContract $dep) { /* ... */ }
```

### ModelConfigDataFactoryContract
**Use it when:**
- You want to type-hint interfaces for swapping implementations and testing.

**Example:**
```php
use CreativeCrafts\LaravelAiAssistant\Contracts\ModelConfigDataFactoryContract;

function handle(ModelConfigDataFactoryContract $dep) { /* ... */ }
```

### NewAssistantResponseDataContract
**Use it when:**
- You want to type-hint interfaces for swapping implementations and testing.

**Example:**
```php
use CreativeCrafts\LaravelAiAssistant\Contracts\NewAssistantResponseDataContract;

function handle(NewAssistantResponseDataContract $dep) { /* ... */ }
```

### OpenAiRepositoryContract
**Use it when:**
- You want to type-hint interfaces for swapping implementations and testing.

**Example:**
```php
use CreativeCrafts\LaravelAiAssistant\Contracts\OpenAiRepositoryContract;

function handle(OpenAiRepositoryContract $dep) { /* ... */ }
```

### ResponsesRepositoryContract
**Use it when:**
- You want to type-hint interfaces for swapping implementations and testing.

**Example:**
```php
use CreativeCrafts\LaravelAiAssistant\Contracts\ResponsesRepositoryContract;

function handle(ResponsesRepositoryContract $dep) { /* ... */ }
```

### TextCompletionContract
**Use it when:**
- You want to type-hint interfaces for swapping implementations and testing.

**Example:**
```php
use CreativeCrafts\LaravelAiAssistant\Contracts\TextCompletionContract;

function handle(TextCompletionContract $dep) { /* ... */ }
```

### ThreadOperationContract
**Use it when:**
- You want to type-hint interfaces for swapping implementations and testing.

**Example:**
```php
use CreativeCrafts\LaravelAiAssistant\Contracts\ThreadOperationContract;

function handle(ThreadOperationContract $dep) { /* ... */ }
```

### TranscribeToDataContract
**Use it when:**
- You want to type-hint interfaces for swapping implementations and testing.

**Example:**
```php
use CreativeCrafts\LaravelAiAssistant\Contracts\TranscribeToDataContract;

function handle(TranscribeToDataContract $dep) { /* ... */ }
```
