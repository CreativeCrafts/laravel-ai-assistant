# Jobs

Components grouped by responsibility.

## Classes in this directory
- **ExecuteToolCallJob** (class) — *Package component.*  
  FQCN: `CreativeCrafts\LaravelAiAssistant\Jobs\ExecuteToolCallJob`
  - **Key methods:**
    - `public __construct(public string $toolName, public array $arguments = [])`
    - `public handle(): mixed`
- **ProcessLongRunningAiOperation** (class) — *Package component.*  
  FQCN: `CreativeCrafts\LaravelAiAssistant\Jobs\ProcessLongRunningAiOperation`
  - **Key methods:**
    - `public __construct(array $jobData)`
    - `public handle(): void`
    - `public failed(Throwable $exception): void`
    - `private processOperation(string $operation, array $parameters): mixed`
    - `private processTextCompletion(array $parameters): array`
    - `private processChatCompletion(array $parameters): array`
    - `private processAudioTranscription(array $parameters): array`
    - `private updateJobStatus(string $jobId, string $status, mixed $result = null, ?string $error = null): void`

## When to Use & Examples
### ExecuteToolCallJob
**Use it when:**
- General use for this area.

**Example:**
```php
use CreativeCrafts\LaravelAiAssistant\Jobs\ExecuteToolCallJob;
```

### ProcessLongRunningAiOperation
**Use it when:**
- General use for this area.

**Example:**
```php
use CreativeCrafts\LaravelAiAssistant\Jobs\ProcessLongRunningAiOperation;
```
