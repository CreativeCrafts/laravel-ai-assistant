# DataFactories

Components grouped by responsibility.

## Classes in this directory
- **ChatAssistantMessageDataFactory** (class) — *Package component.*  
  FQCN: `CreativeCrafts\LaravelAiAssistant\DataFactories\ChatAssistantMessageDataFactory`
  - **Key methods:**
    - `public static buildChatAssistantMessageData(string|array|null $content, ?string $refusal, ?string $name, ?array $audio, ?array $toolCalls): ChatAssistantMessageDataContract`
- **ModelConfigDataFactory** (class) — *Package component.*  
  FQCN: `CreativeCrafts\LaravelAiAssistant\DataFactories\ModelConfigDataFactory`
  - **Key methods:**
    - `public static buildTranscribeData(array $config): TranscribeToDataContract`
    - `public static buildCreateAssistantData(array $config): CreateAssistantDataContract`
    - `public static buildChatCompletionData(array $config): ChatCompletionDataContract`
    - `private buildResponseFormat(array $config): array`

## When to Use & Examples
### ChatAssistantMessageDataFactory
**Use it when:**
- General use for this area.

**Example:**
```php
use CreativeCrafts\LaravelAiAssistant\DataFactories\ChatAssistantMessageDataFactory;
```

### ModelConfigDataFactory
**Use it when:**
- General use for this area.

**Example:**
```php
use CreativeCrafts\LaravelAiAssistant\DataFactories\ModelConfigDataFactory;
```
