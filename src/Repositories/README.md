# Repositories

High‑level API wrappers coordinating SDK calls with retries.

## Classes in this directory
- **NullOpenAiRepository** (class) — *Package component.*  
  FQCN: `CreativeCrafts\LaravelAiAssistant\Repositories\NullOpenAiRepository`
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
- **OpenAiRepository** (class) — *Package component.*  
  FQCN: `CreativeCrafts\LaravelAiAssistant\Repositories\OpenAiRepository`
  - **Key methods:**
    - `public __construct(protected Client $client)`
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

## When to Use & Examples
### NullOpenAiRepository
**Use it when:**
- You need direct access to upstream Assistant/Thread/Run endpoints, with retries and idempotency.

**Example:**
```php
use CreativeCrafts\LaravelAiAssistant\Repositories\NullOpenAiRepository;

$repo = app(NullOpenAiRepository::class);
$assistant = $repo->createAssistant(['name' => 'Docs', 'model' => 'gpt-4o']);
```

### OpenAiRepository
**Use it when:**
- You need direct access to upstream Assistant/Thread/Run endpoints, with retries and idempotency.

**Example:**
```php
use CreativeCrafts\LaravelAiAssistant\Repositories\OpenAiRepository;

$repo = app(OpenAiRepository::class);
$assistant = $repo->createAssistant(['name' => 'Docs', 'model' => 'gpt-4o']);
```
