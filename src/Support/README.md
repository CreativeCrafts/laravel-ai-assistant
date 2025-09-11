# Support

Small utilities used across the package (retry, idempotency, helpers).

## Classes in this directory
- **LegacyCompletionsShim** (trait) — *Package component.*  
  FQCN: `CreativeCrafts\LaravelAiAssistant\Support\LegacyCompletionsShim`
  - **Key methods:**
    - `public draft(): string`
    - `public translateTo(string $language): string`
    - `public andRespond(): array`
    - `public withCustomFunction(CustomFunctionData $customFunctionData): array`
    - `public spellingAndGrammarCorrection(): string`
    - `public improveWriting(): string`
    - `public transcribeTo(string $language, ?string $optionalText = ''): string`
    - `public translateAudioTo(): string`
    - `protected processTextCompletion(): string`
    - `protected processChatTextCompletion(): array`
    - `protected processTextEditCompletion(string $instructions): string`
    - `private validateAudioFilePath(string $filePath): void`
- **Modality** (class) — *Package component.*  
  FQCN: `CreativeCrafts\LaravelAiAssistant\Support\Modality`
  - **Key methods:**
    - `public static text(): string`
    - `public static audio(): string`
- **Retry** (class) — *Package component.*  
  FQCN: `CreativeCrafts\LaravelAiAssistant\Support\Retry`
  - **Key methods:**
    - `public static backoffDelays(int $maxRetries, int $initialMs, int $maxMs): array`
    - `public static shouldRetry(Throwable $e): bool`
    - `public static usleepMs(int $ms): void`
- **ToolChoice** (class) — *Package component.*  
  FQCN: `CreativeCrafts\LaravelAiAssistant\Support\ToolChoice`
  - **Key methods:**
    - `public static auto(): string`
    - `public static required(): string`
    - `public static none(): string`
    - `public static forFunction(string $name): array`

## When to Use & Examples
### LegacyCompletionsShim
**Use it when:**
- You need helper utilities to extend functionality.

**Example:**
```php
use CreativeCrafts\LaravelAiAssistant\Support\LegacyCompletionsShim;
```

### Modality
**Use it when:**
- You need helper utilities to extend functionality.

**Example:**
```php
use CreativeCrafts\LaravelAiAssistant\Support\Modality;
```

### Retry
**Use it when:**
- You need helper utilities to extend functionality.

**Example:**
```php
use CreativeCrafts\LaravelAiAssistant\Support\Retry;
```

### ToolChoice
**Use it when:**
- You need helper utilities to extend functionality.

**Example:**
```php
use CreativeCrafts\LaravelAiAssistant\Support\ToolChoice;
```
