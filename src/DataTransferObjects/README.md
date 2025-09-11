# DataTransferObjects

Lightweight value objects (DTOs) returned from services and APIs.

## Classes in this directory
- **ChatMessageData** (class) — *Package component.*  
  FQCN: `CreativeCrafts\LaravelAiAssistant\DataTransferObjects\ChatMessageData`
  - **Key methods:**
    - `public __construct(protected readonly string $prompt)`
    - `public messages(): array`
    - `public setAssistantInstructions(string $instructions): array`
    - `public cacheConversation(array $conversation): void`
- **CustomFunctionData** (class) — *Package component.*  
  FQCN: `CreativeCrafts\LaravelAiAssistant\DataTransferObjects\CustomFunctionData`
  - **Key methods:**
    - `public __construct(protected string $name, protected string $description, protected array $parameters = [ 'type' => 'object', 'propertie...)`
    - `public toArray(): array`
- **ResponseEnvelope** (class) — *Package component.*  
  FQCN: `CreativeCrafts\LaravelAiAssistant\DataTransferObjects\ResponseEnvelope`
  - **Key methods:**
    - `private __construct(private readonly array $normalized)`
    - `public static fromArray(array $normalized): self`
    - `public id(): string`
    - `public conversationId(): string`
    - `public contentText(): string`
    - `public blocks(): array`
    - `public toolCalls(): array`
    - `public usage(): array`
    - `public status(): mixed`
    - `public raw(): array`
    - `public toArray(): array`
    - `public jsonSerialize(): array`

## When to Use & Examples
### ChatMessageData
**Use it when:**
- You need to pass structured data between layers, avoiding raw arrays.

**Example:**
```php
use CreativeCrafts\LaravelAiAssistant\DataTransferObjects\ChatMessageData;
```

### CustomFunctionData
**Use it when:**
- You need to pass structured data between layers, avoiding raw arrays.

**Example:**
```php
use CreativeCrafts\LaravelAiAssistant\DataTransferObjects\CustomFunctionData;
```

### ResponseEnvelope
**Use it when:**
- You need to pass structured data between layers, avoiding raw arrays.

**Example:**
```php
use CreativeCrafts\LaravelAiAssistant\DataTransferObjects\ResponseEnvelope;
```
