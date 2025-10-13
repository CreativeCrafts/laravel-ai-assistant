# Upgrade Guide

This document provides comprehensive upgrade instructions for major version changes of the Laravel AI Assistant package.

> **📖 Looking for detailed migration examples?** See [MIGRATION.md](MIGRATION.md) for comprehensive code examples showing how to migrate from legacy APIs to the SSOT API.

---

## v3.0 - Major API Cleanup (SSOT Architecture)

### Overview

Version 3.0 introduces a major cleanup to establish the **Single Source of Truth (SSOT)** architecture, where `Ai::responses()` becomes the unified entry point for all OpenAI operations. This release removes legacy components, deprecates old APIs, and marks internal implementation details appropriately.

**⚠️ Important**: This is a **backward-compatible release**. All deprecated APIs continue to work in v3.0 and will only be removed in v4.0.

### Changes Summary

All changes are categorized into three groups:

| Category | Status | Timeline | Action Required |
|----------|--------|----------|-----------------|
| **Deleted** | ❌ Removed in v3.0 | Now | Must migrate immediately |
| **Deprecated** | ⚠️ Still works, will be removed in v4.0 | Before v4.0 | Plan migration |
| **Internal** | 🔒 Marked as internal, still available | Ongoing | Avoid direct usage |

### Timeline

- **v3.0 (Current)**: Legacy code deleted, old APIs deprecated, internal classes marked
- **v3.x**: Deprecation warnings help guide migration
- **v4.0 (Future)**: All deprecated classes and methods will be removed

### Breaking Changes

**None**. Version 3.0 is backward compatible. The only breaking changes are for classes that were deleted (see "Deleted Classes" section below), which were legacy internal implementations not part of the public API.

---

## v3.0 - Deleted Classes and Components

The following components have been **completely removed** in v3.0. These were legacy internal implementations that have been replaced by the SSOT architecture.

### Compat Directory (Removed)

**Path**: `/src/Compat/` - **Entire directory deleted**

**Reason**: Legacy OpenAI client compatibility layer that predated the SSOT architecture.

**Deleted Files**:
- `OpenAI/Client.php` - Legacy OpenAI client wrapper
- `OpenAI/ChatResource.php` - Old chat resource
- `OpenAI/AudioResource.php` - Old audio resource  
- `OpenAI/CompletionsResource.php` - Old completions resource
- `OpenAI/Resources/Audio.php`
- `OpenAI/Resources/Chat.php`
- `OpenAI/Resources/Completions.php`
- `OpenAI/Responses/Chat/CreateResponse.php`
- `OpenAI/Responses/Completions/CreateResponse.php`
- `OpenAI/Responses/Completions/StreamedCompletionResponse.php`
- `OpenAI/Responses/Audio/TranscriptionResponse.php`
- `OpenAI/Responses/Audio/TranslationResponse.php`
- `OpenAI/Responses/StreamResponse.php`
- `OpenAI/Responses/Meta/MetaInformation.php`
- `OpenAI/aliases.php`

**Migration**: Use `Ai::responses()` instead. See [MIGRATION.md](MIGRATION.md#from-compat-client) for examples.

### Legacy Repository Classes (Removed)

**Files Deleted**:
- `/src/Repositories/OpenAiRepository.php`
- `/src/Repositories/NullOpenAiRepository.php`

**Reason**: These repositories directly called the legacy Compat Client. With SSOT, all operations go through `ResponsesBuilder` → `RequestRouter` → `AdapterFactory` → `OpenAiClient`.

**Migration**: Use `Ai::responses()` instead. See [MIGRATION.md](MIGRATION.md#from-openairepository) for examples.

### Legacy Contracts (Removed)

**File Deleted**:
- `/src/Contracts/OpenAiRepositoryContract.php`

**Reason**: Contract for the removed OpenAiRepository.

**Migration**: Use `Ai::responses()` or `Ai::conversations()` facades directly.

---

## v3.0 - Internal Classes (Not for Direct Use)

The following classes are marked as **@internal** in v3.0. They are implementation details of the SSOT architecture and should not be used directly in your application code.

### Adapter Classes

**Path**: `/src/Adapters/`

**Marked as Internal**:
- `AdapterFactory.php` - Creates appropriate adapters based on request type
- `AudioSpeechAdapter.php` - Handles text-to-speech operations
- `AudioTranscriptionAdapter.php` - Handles audio transcription
- `AudioTranslationAdapter.php` - Handles audio translation
- `ChatCompletionAdapter.php` - Handles chat completions
- `EndpointAdapter.php` - Adapter interface
- `ImageEditAdapter.php` - Handles image editing
- `ImageGenerationAdapter.php` - Handles image generation
- `ImageVariationAdapter.php` - Handles image variations
- `ResponseApiAdapter.php` - API response transformation
- `ArrayConfigAdapter.php` - Configuration transformation

**Reason**: Internal implementation details of request transformation. Users should interact through `ResponsesBuilder` only.

**Usage**: Do not instantiate or call adapters directly. Use `Ai::responses()` instead.

### Routing and HTTP Classes

**Marked as Internal**:
- `/src/Services/RequestRouter.php` - Routes requests to appropriate endpoints
- `/src/Services/OpenAiClient.php` - Low-level HTTP client for OpenAI API
- `/src/Http/MultipartRequestBuilder.php` - Builds multipart HTTP requests

**Reason**: Internal routing logic and HTTP utilities.

**Usage**: Do not use directly. Use `Ai::responses()` which handles routing automatically.

### Repository Implementations

**Marked as Internal**:
- `/src/Repositories/Http/ResponsesHttpRepository.php`
- `/src/Repositories/Http/ConversationsHttpRepository.php`
- `/src/Repositories/Http/FilesHttpRepository.php`
- `/src/Repositories/Http/ResponsesInputItemsHttpRepository.php`

**Reason**: Low-level HTTP repositories used internally by `AssistantService`.

**Usage**: Do not use directly. Use builders: `Ai::responses()` or `Ai::conversations()`.

### Repository Contracts

**Marked as Internal**:
- `/src/Contracts/ResponsesRepositoryContract.php`
- `/src/Contracts/ConversationsRepositoryContract.php`
- `/src/Contracts/ResponsesInputItemsRepositoryContract.php`
- `/src/Contracts/FilesRepositoryContract.php`

**Reason**: Low-level abstractions. Users should use builders, not repositories.

**Usage**: Do not implement or type-hint these contracts. Use `Ai::responses()` or `Ai::conversations()` instead.

### Internal Facades

**Marked as Internal**:
- `/src/Facades/AiAssistantCache.php` - Internal caching facade
- `/src/Facades/Observability.php` - Internal observability facade

**Reason**: These are internal utilities for package functionality.

**Usage**: Do not use in application code. These are for internal package operations only.

---

## v3.0 - AiAssistant Class Deprecation

### Overview

The `AiAssistant` class is **deprecated as of v3.0** and will be removed in **v4.0**. Users should migrate to the unified `Ai::responses()` API or `Ai::chat()` for chat sessions.

### Why This Change?

The SSOT (Single Source of Truth) architecture establishes `Ai::responses()` as the single public entry point for all OpenAI operations, providing a cleaner, more maintainable API surface.

### Migration Path

#### Basic Chat Completion

**Before (Deprecated):**
```php
use CreativeCrafts\LaravelAiAssistant\AiAssistant;

$assistant = AiAssistant::acceptPrompt('Hello, how are you?')
    ->setModelName('gpt-4')
    ->setTemperature(0.7);

$response = $assistant->sendChatMessageDto();
echo $response->content;
```

**After (Recommended):**
```php
use CreativeCrafts\LaravelAiAssistant\Facades\Ai;

$response = Ai::responses()
    ->model('gpt-4')
    ->temperature(0.7)
    ->input('Hello, how are you?')
    ->send();

echo $response->text();
```

#### Using Chat Sessions

**Before (Deprecated):**
```php
$assistant = AiAssistant::acceptPrompt('What is Laravel?')
    ->setModelName('gpt-4')
    ->startConversation();

$response = $assistant->sendChatMessageDto();
```

**After (Recommended):**
```php
use CreativeCrafts\LaravelAiAssistant\Facades\Ai;

$chat = Ai::chat()
    ->model('gpt-4')
    ->start();

$response = $chat->send('What is Laravel?');
```

#### With System Instructions

**Before (Deprecated):**
```php
$assistant = AiAssistant::acceptPrompt('Explain dependency injection')
    ->setSystemMessage('You are a helpful coding tutor')
    ->setModelName('gpt-4');

$response = $assistant->sendChatMessageDto();
```

**After (Recommended):**
```php
$response = Ai::responses()
    ->model('gpt-4')
    ->instructions('You are a helpful coding tutor')
    ->input('Explain dependency injection')
    ->send();
```

#### Streaming Responses

**Before (Deprecated):**
```php
$assistant = AiAssistant::acceptPrompt('Tell me a story')
    ->setModelName('gpt-4');

foreach ($assistant->streamChatText() as $chunk) {
    echo $chunk;
}
```

**After (Recommended):**
```php
$stream = Ai::responses()
    ->model('gpt-4')
    ->input('Tell me a story')
    ->stream();

foreach ($stream as $chunk) {
    echo $chunk;
}
```

#### With Tools/Functions

**Before (Deprecated):**
```php
$assistant = AiAssistant::acceptPrompt('What is the weather?')
    ->includeFunctionCallTool(
        'get_weather',
        'Get current weather',
        ['location' => ['type' => 'string']],
        true
    );

$response = $assistant->sendChatMessageEnvelope();
```

**After (Recommended):**
```php
$response = Ai::responses()
    ->input('What is the weather?')
    ->tool([
        'type' => 'function',
        'function' => [
            'name' => 'get_weather',
            'description' => 'Get current weather',
            'parameters' => [
                'type' => 'object',
                'properties' => [
                    'location' => ['type' => 'string'],
                ],
            ],
            'strict' => true,
        ],
    ])
    ->send();
```

### Timeline

- **v3.0**: AiAssistant deprecated (still functional)
- **v4.0**: AiAssistant will be removed

### Benefits of Migration

- ✅ Cleaner, more intuitive API
- ✅ Better IDE support and autocomplete
- ✅ Unified approach for all OpenAI operations
- ✅ Improved type safety
- ✅ Future-proof architecture

---

## v3.0 - AiAssistant Facade Deprecation

### Overview

The `AiAssistant` facade is **deprecated as of v3.0** and will be removed in **v4.0**. Users should migrate to the `Ai` facade instead.

### Why This Change?

The `AiAssistant` facade provides access to the deprecated `AiAssistant` class. With the new SSOT architecture, the `Ai` facade is the recommended entry point for all AI operations.

### Migration Path

#### Basic Usage

**Before (Deprecated):**
```php
use CreativeCrafts\LaravelAiAssistant\Facades\AiAssistant;

$response = AiAssistant::acceptPrompt('Hello, how are you?')
    ->sendChatMessageDto();
```

**After (Recommended):**
```php
use CreativeCrafts\LaravelAiAssistant\Facades\Ai;

$response = Ai::chat('Hello, how are you?')->send();
// Or using responses builder
$response = Ai::responses()
    ->input('Hello, how are you?')
    ->send();
```

#### Chat Operations

**Before (Deprecated):**
```php
use CreativeCrafts\LaravelAiAssistant\Facades\AiAssistant;

$response = AiAssistant::reply('What is Laravel?');
```

**After (Recommended):**
```php
use CreativeCrafts\LaravelAiAssistant\Facades\Ai;

$response = Ai::chat('What is Laravel?')->send();
// Or use quick method for one-shot
$response = Ai::quick('What is Laravel?');
```

#### Streaming

**Before (Deprecated):**
```php
use CreativeCrafts\LaravelAiAssistant\Facades\AiAssistant;

$stream = AiAssistant::acceptPrompt('Tell me a story')
    ->streamChatText(function($chunk) {
        echo $chunk;
    });
```

**After (Recommended):**
```php
use CreativeCrafts\LaravelAiAssistant\Facades\Ai;

$stream = Ai::stream('Tell me a story', function($chunk) {
    echo $chunk;
});
// Or using responses builder
foreach (Ai::responses()->input('Tell me a story')->stream() as $chunk) {
    echo $chunk;
}
```

#### Audio Operations

**Before (Deprecated):**
```php
use CreativeCrafts\LaravelAiAssistant\Facades\AiAssistant;

$text = AiAssistant::transcribeTo('english', $audioFile);
```

**After (Recommended):**
```php
use CreativeCrafts\LaravelAiAssistant\Facades\Ai;

$response = Ai::responses()
    ->input()
    ->audio([
        'file' => $audioFile,
        'action' => 'transcribe',
        'language' => 'english',
    ])
    ->send();
```

#### Image Operations

**Before (Deprecated):**
```php
use CreativeCrafts\LaravelAiAssistant\Facades\AiAssistant;

// Image generation through old methods
$assistant = AiAssistant::init()
    ->createImageGeneration($prompt);
```

**After (Recommended):**
```php
use CreativeCrafts\LaravelAiAssistant\Facades\Ai;

$response = Ai::responses()
    ->input()
    ->image([
        'prompt' => $prompt,
        'action' => 'generate',
    ])
    ->send();
```

### Timeline

- **v3.0**: AiAssistant facade deprecated (still functional, triggers deprecation warning)
- **v4.0**: AiAssistant facade will be removed

### Key Differences

| AiAssistant Facade | Ai Facade |
|-------------------|-----------|
| `AiAssistant::acceptPrompt()` | `Ai::chat()` or `Ai::responses()->input()` |
| `AiAssistant::reply()` | `Ai::chat()->send()` |
| `AiAssistant::streamChatText()` | `Ai::stream()` |
| `AiAssistant::transcribeTo()` | `Ai::responses()->input()->audio()` |
| `AiAssistant::init()` | `Ai::responses()` |

---

## v3.0 - OpenAIClientFacade Deprecation

### Overview

The `OpenAIClientFacade` class is **deprecated as of v3.0** and will be removed in **v4.0**. Users should migrate to the `Ai` facade methods instead.

### Why This Change?

The `OpenAIClientFacade` was an internal abstraction that exposed repository contracts directly. With the SSOT (Single Source of Truth) architecture, the `Ai` facade provides a cleaner, more unified API through builders rather than repositories.

### Migration Path

#### Using Responses

**Before (Deprecated):**
```php
use CreativeCrafts\LaravelAiAssistant\OpenAIClientFacade;

$facade = app(OpenAIClientFacade::class);
$repository = $facade->responses();
// Then use repository methods...
```

**After (Recommended):**
```php
use CreativeCrafts\LaravelAiAssistant\Facades\Ai;

// Direct access to ResponsesBuilder
$response = Ai::responses()
    ->model('gpt-4')
    ->input('Your prompt here')
    ->send();
```

#### Using Conversations

**Before (Deprecated):**
```php
use CreativeCrafts\LaravelAiAssistant\OpenAIClientFacade;

$facade = app(OpenAIClientFacade::class);
$repository = $facade->conversations();
// Then use repository methods...
```

**After (Recommended):**
```php
use CreativeCrafts\LaravelAiAssistant\Facades\Ai;

// Direct access to ConversationsBuilder
$chat = Ai::conversations()
    ->create('My Conversation')
    ->send('Hello!');
```

#### Using Files

**Before (Deprecated):**
```php
use CreativeCrafts\LaravelAiAssistant\OpenAIClientFacade;

$facade = app(OpenAIClientFacade::class);
$filesRepository = $facade->files();
// Then use file repository methods...
```

**After (Recommended):**
```php
use CreativeCrafts\LaravelAiAssistant\Facades\Ai;

// Use Ai facade methods for file operations
// (Refer to documentation for specific file operation methods)
```

### Timeline

- **v3.0**: OpenAIClientFacade deprecated (still functional, triggers deprecation warning)
- **v4.0**: OpenAIClientFacade will be removed

### Benefits of Migration

- ✅ Cleaner API through the unified `Ai` facade
- ✅ Builder pattern instead of direct repository access
- ✅ Better type safety and IDE support
- ✅ Consistent with SSOT architecture
- ✅ Reduced cognitive load with single entry point

---

## v3.0 - AppConfig Deprecation

### Overview

The `AppConfig` class is **deprecated as of v3.0** and will be removed in **v4.0**. Users should migrate to the `ModelConfigFactory` with `ModelOptions` instead.

### Why This Change?

The `AppConfig` class used static methods to retrieve configuration arrays for different modalities. The new `ModelConfigFactory` with `ModelOptions` provides a more flexible, type-safe, and testable approach to configuration management using the factory pattern and DTOs.

### Migration Path

#### Text Generator Configuration

**Before (Deprecated):**
```php
use CreativeCrafts\LaravelAiAssistant\Services\AppConfig;

$config = AppConfig::textGeneratorConfig();
```

**After (Recommended):**
```php
use CreativeCrafts\LaravelAiAssistant\Factories\ModelConfigFactory;
use CreativeCrafts\LaravelAiAssistant\Enums\Modality;
use CreativeCrafts\LaravelAiAssistant\DataTransferObjects\ModelOptions;

$config = ModelConfigFactory::for(
    Modality::Text,
    ModelOptions::fromConfig()
);
```

#### Chat Text Generator Configuration

**Before (Deprecated):**
```php
use CreativeCrafts\LaravelAiAssistant\Services\AppConfig;

$config = AppConfig::chatTextGeneratorConfig();
```

**After (Recommended):**
```php
use CreativeCrafts\LaravelAiAssistant\Factories\ModelConfigFactory;
use CreativeCrafts\LaravelAiAssistant\Enums\Modality;
use CreativeCrafts\LaravelAiAssistant\DataTransferObjects\ModelOptions;

$config = ModelConfigFactory::for(
    Modality::Chat,
    ModelOptions::fromConfig()
);
```

#### Edit Text Generator Configuration

**Before (Deprecated):**
```php
use CreativeCrafts\LaravelAiAssistant\Services\AppConfig;

$config = AppConfig::editTextGeneratorConfig();
```

**After (Recommended):**
```php
use CreativeCrafts\LaravelAiAssistant\Factories\ModelConfigFactory;
use CreativeCrafts\LaravelAiAssistant\Enums\Modality;
use CreativeCrafts\LaravelAiAssistant\DataTransferObjects\ModelOptions;

$config = ModelConfigFactory::for(
    Modality::Edit,
    ModelOptions::fromConfig()
);
```

#### Audio to Text Generator Configuration

**Before (Deprecated):**
```php
use CreativeCrafts\LaravelAiAssistant\Services\AppConfig;

$config = AppConfig::audioToTextGeneratorConfig();
```

**After (Recommended):**
```php
use CreativeCrafts\LaravelAiAssistant\Factories\ModelConfigFactory;
use CreativeCrafts\LaravelAiAssistant\Enums\Modality;
use CreativeCrafts\LaravelAiAssistant\DataTransferObjects\ModelOptions;

$config = ModelConfigFactory::for(
    Modality::AudioToText,
    ModelOptions::fromConfig()
);
```

### Timeline

- **v3.0**: AppConfig deprecated (still functional, triggers deprecation warning)
- **v4.0**: AppConfig will be removed

### Benefits of Migration

- ✅ Type-safe configuration with DTOs
- ✅ Factory pattern for better testability
- ✅ Flexible configuration options
- ✅ Better IDE support and autocompletion
- ✅ Consistent with modern architecture patterns
- ✅ Easier to mock and test

---

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