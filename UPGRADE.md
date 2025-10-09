# Upgrade Guide

This document provides comprehensive upgrade instructions for major version changes of the Laravel AI Assistant package.

## Modern Approach: Unified Completion API (Recommended)

The **unified completion API** (`AiManager::complete()`) is the **modern, recommended approach** for all AI operations. It provides:

- **Explicit control**: Clear separation of mode (TEXT/CHAT) and transport (SYNC/STREAM)
- **Type safety**: Strongly-typed enums and DTOs
- **Consistency**: Single interface for all completion types
- **Future-proof**: Designed for extensibility and new features

### Quick Migration to Unified API

**Before (Legacy approaches):**

```php
// Old Assistant API (deprecated)
$assistant = app(Assistant::class)
    ->setModelName('gpt-4')
    ->createTask()
    ->askQuestion('Hello')
    ->process();
$text = $assistant->response();

// Or old AssistantService methods (deprecated)
$service->textCompletion('Hello', 'gpt-4');
```

**After (Modern unified API):**

```php
use CreativeCrafts\LaravelAiAssistant\Services\AiManager;
use CreativeCrafts\LaravelAiAssistant\Enums\{Mode, Transport};
use CreativeCrafts\LaravelAiAssistant\DataTransferObjects\CompletionRequest;

$ai = app(AiManager::class);

$result = $ai->complete(
    Mode::TEXT,
    Transport::SYNC,
    CompletionRequest::fromArray([
        'model' => 'gpt-4',
        'prompt' => 'Hello',
    ])
);

echo (string) $result;
```

### Unified API Examples

#### Text Completion

**Before:**
```php
$response = $assistantService->textCompletion(
    'Write a haiku about Laravel',
    'gpt-4o-mini'
);
```

**After:**
```php
$result = $ai->complete(
    Mode::TEXT,
    Transport::SYNC,
    CompletionRequest::fromArray([
        'model' => 'gpt-4o-mini',
        'prompt' => 'Write a haiku about Laravel',
        'temperature' => 0.7,
    ])
);
echo (string) $result;
```

#### Chat Completion

**Before:**
```php
$response = $assistantService->chatTextCompletion(
    [
        ['role' => 'user', 'content' => 'Explain DI']
    ],
    'gpt-4'
);
```

**After:**
```php
$result = $ai->complete(
    Mode::CHAT,
    Transport::SYNC,
    CompletionRequest::fromArray([
        'model' => 'gpt-4',
        'messages' => [
            ['role' => 'system', 'content' => 'You are a helpful assistant'],
            ['role' => 'user', 'content' => 'Explain DI'],
        ],
    ])
);
$data = $result->toArray();
```

#### Streaming

**Before:**
```php
$stream = $assistantService->streamedCompletion('Tell a story');
foreach ($stream as $chunk) {
    echo $chunk;
}
```

**After:**
```php
$result = $ai->complete(
    Mode::TEXT,
    Transport::STREAM,
    CompletionRequest::fromArray([
        'model' => 'gpt-4o-mini',
        'prompt' => 'Tell a story',
    ])
);
echo (string) $result; // Returns accumulated final result
```

> **Note**: For incremental streaming with callbacks, use `Ai::stream()` which provides `onEvent` and `shouldStop` callbacks.

### Benefits of Unified API

| Feature | Unified API | Legacy APIs |
|---------|-------------|-------------|
| **Type Safety** | ✅ Enums, DTOs | ❌ Strings, arrays |
| **IDE Support** | ✅ Full autocompletion | ⚠️ Limited |
| **Consistency** | ✅ Single interface | ❌ Multiple methods |
| **Deprecation Warnings** | ✅ None | ⚠️ Logged |
| **Future Support** | ✅ Active development | ❌ Maintenance only |

### Convenience Methods (Alternative)

For rapid development, you can still use convenience methods:

```php
use CreativeCrafts\LaravelAiAssistant\Facades\Ai;

// Quick one-off (good for simple cases)
$response = Ai::quick('Hello AI');

// Chat session (good for multi-turn)
$chat = Ai::chat('You are helpful');
$response = $chat->message('Hello')->send();

// Streaming (good for real-time UX)
foreach (Ai::stream('Tell a story') as $chunk) {
    echo $chunk;
}
```

These convenience methods are **not deprecated** and are perfect for simple use cases.

---

## Migrating from Assistant API to Responses/Conversations API

The Assistant API is **deprecated in 1.x** and will be removed in a future major version. We strongly recommend migrating to the modern **Responses API** and **Conversations API** (or the unified completion API above).

### Why Migrate?

The modern APIs align with OpenAI's direction — see: https://platform.openai.com/docs/guides/migrating-from-chat-completions-to-responses

Benefits:
- **Better structure**: Clean separation between responses (single turns) and conversations (multi-turn threads)
- **Enhanced features**: Native support for tool calls, streaming, and multimodal inputs
- **Improved ergonomics**: Fluent builder pattern with IDE autocompletion
- **Future-proof**: Follows OpenAI's direction for chat completions

### Migration Mapping

#### Quick One-Off Requests

**Before (Assistant API - DEPRECATED):**

```php
use CreativeCrafts\LaravelAiAssistant\Assistant;

$assistant = app(Assistant::class)
    ->setModelName('gpt-4')
    ->createTask()
    ->askQuestion('Explain Laravel queues')
    ->process();
    
$text = $assistant->response();
```

**After (Responses API):**

```php
use CreativeCrafts\LaravelAiAssistant\Facades\Ai;

// Simple approach
$response = Ai::quick('Explain Laravel queues');
echo $response->text;

// Or with full control
$response = Ai::responses()
    ->model('gpt-4')
    ->input()->message('Explain Laravel queues')
    ->send();
echo $response->text;
```

#### Stateful Conversations

**Before (Assistant API - DEPRECATED):**

```php
$assistant = app(Assistant::class);
$thread = $assistant->createThread(['title' => 'My Conversation']);
$assistant->writeMessage($thread->id, ['content' => 'Hello']);
$response = $assistant->process();
```

**After (Conversations API):**

```php
use CreativeCrafts\LaravelAiAssistant\Facades\Ai;

// Create and use a conversation
$conversationId = Ai::conversations()->start();

$response = Ai::conversations()
    ->use($conversationId)
    ->input()->message('Hello')
    ->send();
    
echo $response->text;

// Continue the conversation
$response = Ai::conversations()
    ->use($conversationId)
    ->input()->message('Tell me more')
    ->send();
```

#### Streaming Responses

**Before (Assistant API - DEPRECATED):**

```php
$assistant = Assistant::new()
    ->setModelName('gpt-4')
    ->enableStreaming();
    
foreach ($assistant->stream() as $chunk) {
    echo $chunk;
}
```

**After (Responses API):**

```php
use CreativeCrafts\LaravelAiAssistant\Facades\Ai;

// Quick streaming
foreach (Ai::stream('Tell me a story') as $chunk) {
    echo $chunk;
}

// Or with full control
$stream = Ai::responses()
    ->model('gpt-4')
    ->input()->message('Tell me a story')
    ->stream();
    
foreach ($stream as $chunk) {
    echo $chunk;
}
```

#### Tool/Function Calls

**Before (Assistant API - DEPRECATED):**

```php
$assistant = Assistant::new()
    ->includeFunctionCallTool([
        'type' => 'function',
        'function' => [
            'name' => 'get_weather',
            'description' => 'Get weather information',
            'parameters' => [
                'type' => 'object',
                'properties' => [
                    'location' => ['type' => 'string']
                ]
            ]
        ]
    ]);
```

**After (Responses API):**

```php
use CreativeCrafts\LaravelAiAssistant\Facades\Ai;

$response = Ai::responses()
    ->model('gpt-4')
    ->tools([
        [
            'type' => 'function',
            'function' => [
                'name' => 'get_weather',
                'description' => 'Get weather information',
                'parameters' => [
                    'type' => 'object',
                    'properties' => [
                        'location' => ['type' => 'string']
                    ]
                ]
            ]
        ]
    ])
    ->input()->message('What is the weather in London?')
    ->send();

if ($response->requiresAction) {
    // Handle tool calls
    foreach ($response->toolCalls as $toolCall) {
        // Execute tool and submit results
    }
}
```

#### Setting Instructions (System Prompts)

**Before (Assistant API - DEPRECATED):**

```php
$assistant = Assistant::new()
    ->setInstructions('You are a helpful coding assistant');
```

**After (Responses API):**

```php
use CreativeCrafts\LaravelAiAssistant\Facades\Ai;

$response = Ai::responses()
    ->instructions('You are a helpful coding assistant')
    ->input()->message('Help me with Laravel')
    ->send();
```

### Quick Migration Checklist

- [ ] Replace `app(Assistant::class)` with `Ai::responses()` or `Ai::conversations()`
- [ ] Replace `Assistant::new()` with `Ai::responses()` or `Ai::conversations()`
- [ ] Replace `->createTask()->askQuestion()` with `->input()->message()`
- [ ] Replace `->process()` with `->send()`
- [ ] Replace `->createThread()` with `Ai::conversations()->start()`
- [ ] Replace `->writeMessage()` with `->input()->message()`
- [ ] Update streaming from `$assistant->stream()` to `Ai::stream()` or `->stream()`
- [ ] Update tool definitions to use `->tools()` method
- [ ] Test all AI interactions thoroughly

## Upgrading from 1.x to 2.0

Version 2.0 represents a significant architectural overhaul of the Laravel AI Assistant package. This upgrade introduces breaking changes that require code modifications.

### Breaking Changes Overview

#### 1. Service Architecture Refactor

- **Old**: Direct service method calls on legacy classes
- **New**: Unified AssistantService with improved method signatures
- **Impact**: All service interactions require updating

#### 2. Data Transfer Objects (DTOs)

- **Old**: Basic arrays and legacy data structures
- **New**: Strongly typed DTOs (AssistantMessageData, NewAssistantResponseData, FunctionCalData)
- **Impact**: Data handling code needs restructuring

#### 3. Method Signatures

- **Old**: Inconsistent method signatures across different services
- **New**: Standardized, fluent API with method chaining
- **Impact**: Method calls require syntax updates

### Step-by-Step Migration

#### Step 1: Update Dependencies

```bash
composer update creativecrafts/laravel-ai-assistant
```

#### Step 2: Update Service Instantiation

**Before (1.x):**

```php
use CreativeCrafts\LaravelAiAssistant\AiAssistant;

$assistant = app(AiAssistant::class);
```

**After (2.0+):**

```php
use CreativeCrafts\LaravelAiAssistant\Assistant;

$assistant = Assistant::new();
```

#### Step 3: Update Assistant Creation

**Before (1.x):**

```php
$assistant = new AiAssistant();
$response = $assistant->createAssistant([
    'name' => 'My Assistant',
    'model' => 'gpt-4',
    'instructions' => 'You are a helpful assistant'
]);
```

**After (2.0+):**

```php
$assistant = Assistant::new()
    ->setAssistantName('My Assistant')
    ->setModelName('gpt-4')
    ->setInstructions('You are a helpful assistant')
    ->create();
```

#### Step 4: Update Chat Interactions

**Before (1.x):**

```php
$response = $assistant->chat([
    'model' => 'gpt-4',
    'messages' => [
        ['role' => 'user', 'content' => 'Hello']
    ]
]);
```

**After (2.0+):**

```php
$assistant = Assistant::new()
    ->setModelName('gpt-4')
    ->createTask()
    ->askQuestion('Hello')
    ->process();
    
$response = $assistant->response();
```

#### Step 5: Update Thread Management

**Before (1.x):**

```php
$thread = $assistant->createThread(['title' => 'My Thread']);
$message = $assistant->addMessage($thread['id'], ['content' => 'Hello']);
```

**After (2.0+):**

```php
$assistantService = app(AssistantService::class);
$thread = $assistantService->createThread(['title' => 'My Thread']);
$message = $assistantService->writeMessage($thread->id, ['content' => 'Hello']);
```

#### Step 6: Update Function Calls

**Before (1.x):**

```php
$assistant->addFunction([
    'name' => 'get_weather',
    'description' => 'Get weather information',
    'parameters' => [...]
]);
```

**After (2.0+):**

```php
$assistant = Assistant::new()
    ->includeFunctionCallTool([
        'type' => 'function',
        'function' => [
            'name' => 'get_weather',
            'description' => 'Get weather information',
            'parameters' => [...]
        ]
    ]);
```

### Configuration Changes

#### Environment Variables

No changes to environment variables are required. All existing configuration remains compatible.

#### Published Assets

If you have published the package assets, you may want to republish them:

```bash
# Republish configuration
php artisan vendor:publish --tag="ai-assistant-config" --force

# Republish migrations
php artisan vendor:publish --tag="ai-assistant-migrations" --force

# Republish model stubs (new in 2.0)
php artisan vendor:publish --tag="ai-assistant-models" --force
```

### Data Structure Changes

#### Response Formats

Responses now use standardized DTOs instead of raw arrays:

**Before (1.x):**

```php
$response = $assistant->createAssistant([...]);
// $response is a raw array
$assistantId = $response['id'];
```

**After (2.0+):**

```php
$response = $assistant->create();
// $response is a NewAssistantResponseData DTO
$assistantId = $response->id;
```

### Testing Updates

#### Mock Updates

Update your test mocks to use the new service architecture:

**Before (1.x):**

```php
$mock = Mockery::mock(AiAssistant::class);
$mock->shouldReceive('createAssistant')->andReturn([...]);
```

**After (2.0+):**

```php
$mock = Mockery::mock(AssistantService::class);
$mock->shouldReceive('createAssistant')->andReturn(new AssistantResponse(...));
```

### Common Migration Issues

#### Issue 1: Method Not Found

**Error**: `Call to undefined method`
**Solution**: Check the new method names in the AssistantService class

#### Issue 2: Array Access on Object

**Error**: `Cannot access offset on object`
**Solution**: Update array access to use DTO properties

#### Issue 3: Type Errors

**Error**: Type mismatch in method parameters
**Solution**: Use the new DTO classes instead of raw arrays

### Rollback Strategy

If you need to rollback to 1.x:

1. **Revert composer.json**:
   ```bash
   composer require creativecrafts/laravel-ai-assistant:^1.3
   ```

2. **Restore old code**: Revert your application code changes

3. **Clear caches**:
   ```bash
   php artisan config:clear
   php artisan cache:clear
   ```

### Getting Help

If you encounter issues during migration:

1. Check the [updated documentation](README.md)
2. Review the [test files](tests/) for usage examples
3. Open an issue on [GitHub](https://github.com/creativecrafts/laravel-ai-assistant/issues)

## Future Upgrades

### Best Practices for Smooth Upgrades

1. **Keep dependencies updated**: Regularly update to minor versions
2. **Read changelogs**: Review CHANGELOG.md before upgrading
3. **Test thoroughly**: Use comprehensive tests to catch breaking changes
4. **Use semantic versioning**: Pin major versions in production

### Deprecation Policy

- Deprecated methods include clear deprecation notices
- Deprecated features are supported for at least one major version
- Migration paths are provided for all deprecated features

## Version Support Matrix

| Package Version | Laravel Version  | PHP Version | Support Status |
|-----------------|------------------|-------------|----------------|
| 2.1.x           | 10.x, 11.x, 12.x | 8.2, 8.3    | ✅ Active       |
| 2.0.x           | 10.x, 11.x, 12.x | 8.2, 8.3    | ✅ Security     |
| 1.3.x           | 10.x, 11.x       | 8.2         | 🔶 EOL         |
| 1.2.x           | 10.x, 11.x       | 8.2         | ❌ EOL          |

**Legend:**

- ✅ Active: Regular updates and new features
- ✅ Security: Security fixes only
- 🔶 EOL: End of life, critical fixes only
- ❌ EOL: No longer supported