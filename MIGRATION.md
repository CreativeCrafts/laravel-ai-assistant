# Migration Guide to SSOT API

This comprehensive guide helps you migrate from legacy APIs to the new **Single Source of Truth (SSOT)** architecture, which establishes `Ai::responses()` as the unified entry point for all OpenAI operations.

## Table of Contents

- [Overview](#overview)
- [Why Migrate?](#why-migrate)
- [Migration Paths](#migration-paths)
  - [From OpenAiRepository](#from-openairepository)
  - [From AiAssistant Class](#from-aiassistant-class)
  - [From Compat Client](#from-compat-client)
- [Operation Examples](#operation-examples)
  - [Chat Completion](#1-chat-completion)
  - [Streaming Responses](#2-streaming-responses)
  - [Audio Transcription](#3-audio-transcription)
  - [Audio Translation](#4-audio-translation)
  - [Audio Speech Generation](#5-audio-speech-generation)
  - [Image Generation](#6-image-generation)
  - [Image Editing](#7-image-editing)
  - [Image Variation](#8-image-variation)
- [Benefits of SSOT API](#benefits-of-ssot-api)
- [Troubleshooting](#troubleshooting)

---

## Overview

The **SSOT (Single Source of Truth)** architecture unifies all OpenAI operations under a single, consistent API:

```php
use CreativeCrafts\LaravelAiAssistant\Facades\Ai;

// One entry point for everything
$response = Ai::responses()
    ->input()
    ->message('Hello')     // Text
    ->audio([...])          // Audio operations
    ->image([...])          // Image operations
    ->send();
```

The unified API automatically routes your request to the appropriate OpenAI endpoint based on the input type.

---

## Why Migrate?

### Legacy APIs Being Deprecated/Removed

1. **OpenAiRepository** - Deleted in v3.0 (direct OpenAI client wrapper)
2. **NullOpenAiRepository** - Deleted in v3.0 (null object pattern)
3. **AiAssistant Class** - Deprecated in v3.0, removed in v4.0 (old fluent API)
4. **Compat Client** - Deleted in v3.0 (legacy compatibility layer)

### Benefits of SSOT API

- ✅ **Unified Interface**: One API for text, audio, and images
- ✅ **Automatic Routing**: Request routing handled internally
- ✅ **Type Safety**: Full IDE autocompletion and type hints
- ✅ **Better DX**: Fluent, intuitive builder pattern
- ✅ **Future-Proof**: Aligns with OpenAI's evolution
- ✅ **Cleaner Code**: Less boilerplate, more clarity

---

## Migration Paths

### From OpenAiRepository

The `OpenAiRepository` directly called the legacy Compat Client. Here's how to migrate each method:

#### Chat Completion

**Before:**
```php
use CreativeCrafts\LaravelAiAssistant\Repositories\OpenAiRepository;

$repository = app(OpenAiRepository::class);

$response = $repository->createChatCompletion([
    'model' => 'gpt-4',
    'messages' => [
        ['role' => 'system', 'content' => 'You are a helpful assistant'],
        ['role' => 'user', 'content' => 'What is Laravel?'],
    ],
    'temperature' => 0.7,
]);

echo $response->choices[0]->message->content;
```

**After:**
```php
use CreativeCrafts\LaravelAiAssistant\Facades\Ai;

$response = Ai::responses()
    ->model('gpt-4')
    ->instructions('You are a helpful assistant')
    ->temperature(0.7)
    ->input()
    ->message('What is Laravel?')
    ->send();

echo $response->text;
```

#### Streaming Chat

**Before:**
```php
$stream = $repository->createStreamedChatCompletion([
    'model' => 'gpt-4',
    'messages' => [
        ['role' => 'user', 'content' => 'Tell me a story'],
    ],
]);

foreach ($stream as $chunk) {
    echo $chunk->choices[0]->delta->content ?? '';
}
```

**After:**
```php
$stream = Ai::responses()
    ->model('gpt-4')
    ->input()
    ->message('Tell me a story')
    ->stream();

foreach ($stream as $chunk) {
    echo $chunk;
}
```

#### Audio Transcription

**Before:**
```php
$response = $repository->transcribeAudio([
    'file' => storage_path('audio/recording.mp3'),
    'model' => 'whisper-1',
    'language' => 'en',
]);

echo $response->text;
```

**After:**
```php
$response = Ai::responses()
    ->input()
    ->audio([
        'file' => storage_path('audio/recording.mp3'),
        'action' => 'transcribe',
        'model' => 'whisper-1',
        'language' => 'en',
    ])
    ->send();

echo $response->text;
```

#### Audio Translation

**Before:**
```php
$response = $repository->translateAudio([
    'file' => storage_path('audio/french-audio.mp3'),
    'model' => 'whisper-1',
]);

echo $response->text;
```

**After:**
```php
$response = Ai::responses()
    ->input()
    ->audio([
        'file' => storage_path('audio/french-audio.mp3'),
        'action' => 'translate',
        'model' => 'whisper-1',
    ])
    ->send();

echo $response->text;
```

---

### From AiAssistant Class

The `AiAssistant` class provided a fluent API that's now superseded by `Ai::responses()`.

#### Basic Message

**Before:**
```php
use CreativeCrafts\LaravelAiAssistant\AiAssistant;

$assistant = AiAssistant::acceptPrompt('Explain Laravel queues')
    ->setModelName('gpt-4')
    ->setTemperature(0.8);

$response = $assistant->sendChatMessageDto();
echo $response->content;
```

**After:**
```php
use CreativeCrafts\LaravelAiAssistant\Facades\Ai;

$response = Ai::responses()
    ->model('gpt-4')
    ->temperature(0.8)
    ->input()
    ->message('Explain Laravel queues')
    ->send();

echo $response->text;
```

#### With System Instructions

**Before:**
```php
$assistant = AiAssistant::acceptPrompt('What is dependency injection?')
    ->setSystemMessage('You are a Laravel expert')
    ->setModelName('gpt-4');

$response = $assistant->sendChatMessageDto();
```

**After:**
```php
$response = Ai::responses()
    ->model('gpt-4')
    ->instructions('You are a Laravel expert')
    ->input()
    ->message('What is dependency injection?')
    ->send();
```

#### Streaming Response

**Before:**
```php
$assistant = AiAssistant::acceptPrompt('Write a poem about Laravel')
    ->setModelName('gpt-4');

foreach ($assistant->streamChatText() as $chunk) {
    echo $chunk;
    flush();
}
```

**After:**
```php
$stream = Ai::responses()
    ->model('gpt-4')
    ->input()
    ->message('Write a poem about Laravel')
    ->stream();

foreach ($stream as $chunk) {
    echo $chunk;
    flush();
}
```

#### Conversation Context

**Before:**
```php
$assistant = AiAssistant::acceptPrompt('Remember this: My name is John')
    ->startConversation();

$response1 = $assistant->sendChatMessageDto();

// Continue conversation
$assistant->acceptPrompt('What is my name?');
$response2 = $assistant->sendChatMessageDto();
```

**After:**
```php
// For stateful conversations, use Ai::chat()
$chat = Ai::chat()->start();

$response1 = $chat->send('Remember this: My name is John');
$response2 = $chat->send('What is my name?');

// Or use inConversation for one-off messages in a conversation
$response = Ai::responses()
    ->inConversation('conversation-id')
    ->input()
    ->message('What is my name?')
    ->send();
```

---

### From Compat Client

The Compat Client (`OpenAI\Client`) was a legacy compatibility layer that directly mimicked the OpenAI PHP SDK.

#### Chat Completion

**Before:**
```php
use CreativeCrafts\LaravelAiAssistant\Compat\OpenAI\Client;

$client = app(Client::class);

$response = $client->chat()->create([
    'model' => 'gpt-4',
    'messages' => [
        ['role' => 'system', 'content' => 'You are helpful'],
        ['role' => 'user', 'content' => 'Hello'],
    ],
]);

echo $response->choices[0]->message->content;
```

**After:**
```php
use CreativeCrafts\LaravelAiAssistant\Facades\Ai;

$response = Ai::responses()
    ->model('gpt-4')
    ->instructions('You are helpful')
    ->input()
    ->message('Hello')
    ->send();

echo $response->text;
```

#### Audio Operations

**Before:**
```php
$response = $client->audio()->transcribe([
    'file' => fopen(storage_path('audio/test.mp3'), 'r'),
    'model' => 'whisper-1',
]);

echo $response->text;
```

**After:**
```php
$response = Ai::responses()
    ->input()
    ->audio([
        'file' => storage_path('audio/test.mp3'),
        'action' => 'transcribe',
        'model' => 'whisper-1',
    ])
    ->send();

echo $response->text;
```

#### Image Generation

**Before:**
```php
$response = $client->images()->create([
    'prompt' => 'A beautiful sunset over mountains',
    'model' => 'dall-e-3',
    'size' => '1024x1024',
]);

$imageUrl = $response->data[0]->url;
```

**After:**
```php
$response = Ai::responses()
    ->input()
    ->image([
        'prompt' => 'A beautiful sunset over mountains',
        'model' => 'dall-e-3',
        'size' => '1024x1024',
    ])
    ->send();

$imageUrl = $response->imageUrls[0];
```

---

## Operation Examples

Detailed examples for all 8 major operations using the SSOT API.

### 1. Chat Completion

Simple text-based chat completion with system instructions.

```php
use CreativeCrafts\LaravelAiAssistant\Facades\Ai;

$response = Ai::responses()
    ->model('gpt-4')
    ->instructions('You are a helpful Laravel expert with 10 years of experience')
    ->temperature(0.7)
    ->maxTokens(500)
    ->input()
    ->message('What are the best practices for Laravel service providers?')
    ->send();

echo $response->text;

// Access additional metadata
echo "Model: {$response->model}\n";
echo "Tokens used: {$response->usage['total_tokens']}\n";
echo "Type: {$response->type}\n"; // 'chat'
```

**With conversation context:**
```php
$response = Ai::responses()
    ->inConversation('user-123-session-456')
    ->model('gpt-4')
    ->input()
    ->message('Continue our previous discussion about queues')
    ->send();

echo $response->text;
```

---

### 2. Streaming Responses

Real-time streaming for better user experience with long responses.

```php
use CreativeCrafts\LaravelAiAssistant\Facades\Ai;

$stream = Ai::responses()
    ->model('gpt-4')
    ->instructions('You are a creative storyteller')
    ->input()
    ->message('Write a short story about a Laravel developer discovering AI')
    ->stream();

foreach ($stream as $chunk) {
    echo $chunk;
    flush();
}
```

**With custom callbacks:**
```php
$stream = Ai::responses()
    ->model('gpt-4')
    ->input()
    ->message('Explain Laravel middleware in detail')
    ->stream(
        onEvent: function ($event) {
            // Process each event
            if ($event->type === 'content_delta') {
                echo $event->content;
            }
        },
        shouldStop: function () {
            // Early termination logic
            return false;
        }
    );

// Consume the generator
foreach ($stream as $chunk) {
    // Chunks are already processed by onEvent
}
```

---

### 3. Audio Transcription

Convert audio files to text using Whisper.

```php
use CreativeCrafts\LaravelAiAssistant\Facades\Ai;

$response = Ai::responses()
    ->input()
    ->audio([
        'file' => storage_path('audio/meeting-recording.mp3'),
        'action' => 'transcribe',
        'model' => 'whisper-1',
        'language' => 'en',
        'prompt' => 'This is a technical discussion about Laravel',
        'response_format' => 'verbose_json',
        'temperature' => 0.2,
    ])
    ->send();

echo "Transcription: {$response->text}\n";
echo "Language: {$response->language}\n";
echo "Duration: {$response->duration}s\n";

// Access segments if using verbose_json
if (!empty($response->segments)) {
    foreach ($response->segments as $segment) {
        echo "[{$segment['start']}s - {$segment['end']}s]: {$segment['text']}\n";
    }
}
```

**Simple transcription:**
```php
$response = Ai::responses()
    ->input()
    ->audio([
        'file' => storage_path('audio/voice-note.mp3'),
        'action' => 'transcribe',
    ])
    ->send();

echo $response->text;
```

---

### 4. Audio Translation

Translate audio from any language to English.

```php
use CreativeCrafts\LaravelAiAssistant\Facades\Ai;

$response = Ai::responses()
    ->input()
    ->audio([
        'file' => storage_path('audio/spanish-presentation.mp3'),
        'action' => 'translate',
        'model' => 'whisper-1',
        'prompt' => 'This is a business presentation about technology',
        'temperature' => 0.0,
    ])
    ->send();

echo "English Translation: {$response->text}\n";
echo "Type: {$response->type}\n"; // 'audio_translation'
```

**Multiple audio translations:**
```php
$audioFiles = [
    storage_path('audio/french-audio.mp3'),
    storage_path('audio/german-audio.mp3'),
    storage_path('audio/italian-audio.mp3'),
];

foreach ($audioFiles as $file) {
    $response = Ai::responses()
        ->input()
        ->audio([
            'file' => $file,
            'action' => 'translate',
        ])
        ->send();
    
    echo basename($file) . ": {$response->text}\n\n";
}
```

---

### 5. Audio Speech Generation

Generate natural-sounding speech from text (Text-to-Speech).

```php
use CreativeCrafts\LaravelAiAssistant\Facades\Ai;

$response = Ai::responses()
    ->input()
    ->audio([
        'text' => 'Welcome to Laravel AI Assistant. This package makes it easy to integrate OpenAI into your Laravel applications.',
        'action' => 'speech',
        'model' => 'tts-1-hd',
        'voice' => 'nova',
        'speed' => 1.0,
        'format' => 'mp3',
    ])
    ->send();

// Save the audio file
$outputPath = storage_path('audio/welcome-message.mp3');
file_put_contents($outputPath, $response->audioContent);

echo "Audio saved to: {$outputPath}\n";
echo "Type: {$response->type}\n"; // 'audio_speech'
```

**Different voices:**
```php
$voices = ['alloy', 'echo', 'fable', 'onyx', 'nova', 'shimmer'];
$text = 'Hello, this is a test of the text-to-speech functionality.';

foreach ($voices as $voice) {
    $response = Ai::responses()
        ->input()
        ->audio([
            'text' => $text,
            'action' => 'speech',
            'voice' => $voice,
        ])
        ->send();
    
    $path = storage_path("audio/voice-{$voice}.mp3");
    file_put_contents($path, $response->audioContent);
    echo "Created: {$path}\n";
}
```

**With speed variation:**
```php
$response = Ai::responses()
    ->input()
    ->audio([
        'text' => 'This is a fast-paced narration for an exciting demo.',
        'action' => 'speech',
        'voice' => 'onyx',
        'speed' => 1.5, // 50% faster
        'model' => 'tts-1',
    ])
    ->send();

file_put_contents(storage_path('audio/fast-speech.mp3'), $response->audioContent);
```

---

### 6. Image Generation

Generate images from text descriptions using DALL-E.

```php
use CreativeCrafts\LaravelAiAssistant\Facades\Ai;

$response = Ai::responses()
    ->input()
    ->image([
        'prompt' => 'A modern Laravel application dashboard with a clean, minimalist design, showing real-time analytics and charts',
        'model' => 'dall-e-3',
        'size' => '1024x1024',
        'quality' => 'hd',
        'style' => 'vivid',
        'n' => 1,
    ])
    ->send();

// Access generated image URLs
foreach ($response->imageUrls as $url) {
    echo "Image URL: {$url}\n";
}

// Save images locally
$savedPaths = $response->saveImages(storage_path('images'));
foreach ($savedPaths as $path) {
    echo "Saved: {$path}\n";
}

echo "Type: {$response->type}\n"; // 'image_generation'
```

**Multiple images with DALL-E 2:**
```php
$response = Ai::responses()
    ->input()
    ->image([
        'prompt' => 'A cute robot learning to code in Laravel',
        'model' => 'dall-e-2',
        'size' => '512x512',
        'n' => 4, // Generate 4 variations
    ])
    ->send();

echo "Generated {count($response->imageUrls)} images\n";
$response->saveImages(storage_path('images/robots'));
```

**Different sizes:**
```php
$sizes = ['1024x1024', '1792x1024', '1024x1792'];
$prompt = 'A futuristic cityscape with flying cars';

foreach ($sizes as $size) {
    $response = Ai::responses()
        ->input()
        ->image([
            'prompt' => $prompt,
            'size' => $size,
            'model' => 'dall-e-3',
        ])
        ->send();
    
    $filename = str_replace('x', '_', $size);
    file_put_contents(
        storage_path("images/city_{$filename}.png"),
        file_get_contents($response->imageUrls[0])
    );
}
```

---

### 7. Image Editing

Edit existing images using prompts and optional masks.

```php
use CreativeCrafts\LaravelAiAssistant\Facades\Ai;

$response = Ai::responses()
    ->input()
    ->image([
        'image' => storage_path('images/original-photo.png'),
        'prompt' => 'Add a beautiful sunset in the background',
        'mask' => storage_path('images/background-mask.png'), // Optional: transparent areas will be edited
        'model' => 'dall-e-2',
        'size' => '1024x1024',
        'n' => 2,
    ])
    ->send();

// Save edited images
$savedPaths = $response->saveImages(storage_path('images/edited'));
foreach ($savedPaths as $path) {
    echo "Edited image: {$path}\n";
}

echo "Type: {$response->type}\n"; // 'image_edit'
```

**Without mask (full image edit):**
```php
$response = Ai::responses()
    ->input()
    ->image([
        'image' => storage_path('images/product-photo.png'),
        'prompt' => 'Make the image look more professional with better lighting and a clean white background',
        'model' => 'dall-e-2',
        'n' => 1,
    ])
    ->send();

file_put_contents(
    storage_path('images/product-enhanced.png'),
    file_get_contents($response->imageUrls[0])
);
```

**Batch editing:**
```php
$imagesToEdit = [
    'photo1.png' => 'Add a vintage filter',
    'photo2.png' => 'Make it black and white with high contrast',
    'photo3.png' => 'Add a warm, golden hour lighting',
];

foreach ($imagesToEdit as $filename => $prompt) {
    $response = Ai::responses()
        ->input()
        ->image([
            'image' => storage_path("images/original/{$filename}"),
            'prompt' => $prompt,
        ])
        ->send();
    
    file_put_contents(
        storage_path("images/edited/{$filename}"),
        file_get_contents($response->imageUrls[0])
    );
    
    echo "Edited {$filename}\n";
}
```

---

### 8. Image Variation

Create variations of an existing image without a text prompt.

```php
use CreativeCrafts\LaravelAiAssistant\Facades\Ai;

$response = Ai::responses()
    ->input()
    ->image([
        'image' => storage_path('images/logo.png'),
        'model' => 'dall-e-2',
        'size' => '1024x1024',
        'n' => 5, // Create 5 variations
    ])
    ->send();

echo "Generated {count($response->imageUrls)} variations\n";

// Save all variations
$savedPaths = $response->saveImages(storage_path('images/logo-variations'));
foreach ($savedPaths as $index => $path) {
    echo "Variation " . ($index + 1) . ": {$path}\n";
}

echo "Type: {$response->type}\n"; // 'image_variation'
```

**Create variations for multiple images:**
```php
$sourceImages = [
    storage_path('images/design-v1.png'),
    storage_path('images/design-v2.png'),
];

foreach ($sourceImages as $index => $imagePath) {
    $response = Ai::responses()
        ->input()
        ->image([
            'image' => $imagePath,
            'n' => 3,
        ])
        ->send();
    
    $baseNumber = $index + 1;
    foreach ($response->imageUrls as $varIndex => $url) {
        $filename = "design-v{$baseNumber}-var" . ($varIndex + 1) . ".png";
        file_put_contents(
            storage_path("images/variations/{$filename}"),
            file_get_contents($url)
        );
        echo "Created: {$filename}\n";
    }
}
```

**Different sizes for variations:**
```php
$sizes = ['256x256', '512x512', '1024x1024'];
$sourceImage = storage_path('images/icon.png');

foreach ($sizes as $size) {
    $response = Ai::responses()
        ->input()
        ->image([
            'image' => $sourceImage,
            'size' => $size,
            'n' => 2,
        ])
        ->send();
    
    $sizeFolder = str_replace('x', '_', $size);
    $response->saveImages(storage_path("images/variations/{$sizeFolder}"));
    
    echo "Created {$size} variations\n";
}
```

---

### From OpenAIClientFacade

The `OpenAIClientFacade` is **deprecated as of v3.0** and will be removed in **v4.0**. It was an internal abstraction that exposed repository contracts directly. The SSOT API provides a cleaner, more unified approach.

#### Accessing Responses Repository

**Before:**
```php
use CreativeCrafts\LaravelAiAssistant\OpenAIClientFacade;

$facade = app(OpenAIClientFacade::class);

$response = $facade->responses()->createResponse([
    'conversation_id' => 'conv-123',
    'input' => [
        [
            'type' => 'message',
            'role' => 'user',
            'content' => 'Hello, how are you?',
        ],
    ],
]);

echo $response['output'][0]['content'];
```

**After:**
```php
use CreativeCrafts\LaravelAiAssistant\Facades\Ai;

$response = Ai::responses()
    ->inConversation('conv-123')
    ->input()
    ->message('Hello, how are you?')
    ->send();

echo $response->text;
```

#### Accessing Conversations Repository

**Before:**
```php
use CreativeCrafts\LaravelAiAssistant\OpenAIClientFacade;

$facade = app(OpenAIClientFacade::class);

$conversation = $facade->conversations()->createConversation([
    'model' => 'gpt-4',
    'instructions' => 'You are a helpful assistant',
]);

$conversationId = $conversation['id'];
```

**After:**
```php
use CreativeCrafts\LaravelAiAssistant\Facades\Ai;

// Conversations are created automatically when needed
$response = Ai::responses()
    ->model('gpt-4')
    ->instructions('You are a helpful assistant')
    ->input()
    ->message('Start a new conversation')
    ->send();

// Or use the chat API for multi-turn conversations
$chat = Ai::chat()
    ->model('gpt-4')
    ->instructions('You are a helpful assistant')
    ->start();

$conversationId = $chat->conversationId();
```

#### Accessing Files Repository

**Before:**
```php
use CreativeCrafts\LaravelAiAssistant\OpenAIClientFacade;

$facade = app(OpenAIClientFacade::class);

$fileResponse = $facade->files()->upload(
    storage_path('documents/report.pdf'),
    'assistants'
);

$fileId = $fileResponse['id'];
```

**After:**
```php
use CreativeCrafts\LaravelAiAssistant\Facades\Ai;

// Files are handled through the unified API
$response = Ai::responses()
    ->input()
    ->message('Analyze this document')
    ->attachFile(storage_path('documents/report.pdf'))
    ->send();

// Or use the internal FilesHelper for direct file operations
use CreativeCrafts\LaravelAiAssistant\Services\AssistantService;

$service = app(AssistantService::class);
$fileId = $service->uploadFile(
    storage_path('documents/report.pdf'),
    'assistants'
);
```

#### Direct Repository Injection (For Advanced Use Cases)

If you need direct access to repositories (not recommended for most use cases), inject the contracts instead of using the facade:

**Before:**
```php
use CreativeCrafts\LaravelAiAssistant\OpenAIClientFacade;

class MyService
{
    public function __construct()
    {
        $this->facade = app(OpenAIClientFacade::class);
    }

    public function process()
    {
        $response = $this->facade->responses()->createResponse([...]);
    }
}
```

**After:**
```php
use CreativeCrafts\LaravelAiAssistant\Contracts\ResponsesRepositoryContract;
use CreativeCrafts\LaravelAiAssistant\Contracts\ConversationsRepositoryContract;
use CreativeCrafts\LaravelAiAssistant\Contracts\FilesRepositoryContract;

class MyService
{
    public function __construct(
        private readonly ResponsesRepositoryContract $responsesRepository,
        private readonly ConversationsRepositoryContract $conversationsRepository,
        private readonly FilesRepositoryContract $filesRepository
    ) {}

    public function process()
    {
        $response = $this->responsesRepository->createResponse([...]);
    }
}
```

**Note:** Direct repository usage is marked as **@internal** and not recommended. Use `Ai::responses()` or `Ai::chat()` instead for better stability and DX.

---

### From AppConfig

The `AppConfig` class static methods are **deprecated as of v3.0** and will be removed in **v4.0**. Use `ModelConfigFactory` with `ModelOptions` for model configuration.

#### Text Generation Configuration

**Before:**
```php
use CreativeCrafts\LaravelAiAssistant\Services\AppConfig;

$config = AppConfig::textGeneratorConfig();
$model = $config['model'];
$temperature = $config['temperature'];
```

**After:**
```php
use CreativeCrafts\LaravelAiAssistant\Services\ModelConfigFactory;
use CreativeCrafts\LaravelAiAssistant\DataTransferObjects\ModelOptions;
use CreativeCrafts\LaravelAiAssistant\Enums\Modality;

$options = ModelOptions::fromConfig();
$config = ModelConfigFactory::for(Modality::Text, $options);

$model = $config->model;
$temperature = $config->temperature;
```

#### Chat Text Generation Configuration

**Before:**
```php
use CreativeCrafts\LaravelAiAssistant\Services\AppConfig;

$config = AppConfig::chatTextGeneratorConfig();
```

**After:**
```php
use CreativeCrafts\LaravelAiAssistant\Services\ModelConfigFactory;
use CreativeCrafts\LaravelAiAssistant\DataTransferObjects\ModelOptions;
use CreativeCrafts\LaravelAiAssistant\Enums\Modality;

$options = ModelOptions::fromConfig();
$config = ModelConfigFactory::for(Modality::Chat, $options);
```

#### Edit Text Generation Configuration

**Before:**
```php
use CreativeCrafts\LaravelAiAssistant\Services\AppConfig;

$config = AppConfig::editTextGeneratorConfig();
```

**After:**
```php
use CreativeCrafts\LaravelAiAssistant\Services\ModelConfigFactory;
use CreativeCrafts\LaravelAiAssistant\DataTransferObjects\ModelOptions;
use CreativeCrafts\LaravelAiAssistant\Enums\Modality;

$options = ModelOptions::fromConfig();
$config = ModelConfigFactory::for(Modality::Edit, $options);
```

#### Audio to Text Configuration

**Before:**
```php
use CreativeCrafts\LaravelAiAssistant\Services\AppConfig;

$config = AppConfig::audioToTextGeneratorConfig();
```

**After:**
```php
use CreativeCrafts\LaravelAiAssistant\Services\ModelConfigFactory;
use CreativeCrafts\LaravelAiAssistant\DataTransferObjects\ModelOptions;
use CreativeCrafts\LaravelAiAssistant\Enums\Modality;

$options = ModelOptions::fromConfig();
$config = ModelConfigFactory::for(Modality::AudioToText, $options);
```

#### Using Configuration Options

The `ModelConfigFactory` provides a type-safe, fluent way to create model configurations:

```php
use CreativeCrafts\LaravelAiAssistant\Services\ModelConfigFactory;
use CreativeCrafts\LaravelAiAssistant\DataTransferObjects\ModelOptions;
use CreativeCrafts\LaravelAiAssistant\Enums\Modality;

// Load from config
$options = ModelOptions::fromConfig();

// Or create custom options
$options = new ModelOptions(
    model: 'gpt-4',
    temperature: 0.7,
    maxTokens: 1000,
    topP: 1.0,
    frequencyPenalty: 0.0,
    presencePenalty: 0.0
);

// Create configuration for specific modality
$textConfig = ModelConfigFactory::for(Modality::Text, $options);
$chatConfig = ModelConfigFactory::for(Modality::Chat, $options);
$audioConfig = ModelConfigFactory::for(Modality::AudioToText, $options);
```

**Why This Change?**
- **Type Safety**: `ModelConfigFactory` uses typed DTOs and enums
- **Better DX**: IDE autocompletion and type hints
- **Testability**: Easier to mock and test
- **Flexibility**: Support for different modalities
- **Modern PHP**: Uses PHP 8.3 features (enums, readonly properties)

---

## Understanding OpenAI API Endpoints

### Response API vs Chat Completions API

The Laravel AI Assistant package uses **Response API** as the default endpoint for all text-based operations, following OpenAI's official recommendations for new projects.

#### When Response API is Used (Default)

The **Response API** (`/v1/responses`) is used for:

- ✅ **Text-based chat completions**
- ✅ **Multi-turn conversations**
- ✅ **Streaming responses**
- ✅ **System instructions**
- ✅ **Tool/function calling**

**Example:**
```php
use CreativeCrafts\LaravelAiAssistant\Facades\Ai;

// Automatically uses Response API
$response = Ai::responses()
    ->model('gpt-4')
    ->instructions('You are a helpful assistant')
    ->input()
    ->message('Hello, how are you?')
    ->send();
```

#### When Chat Completions API is Used (Exception)

The **Chat Completions API** (`/v1/chat/completions`) is used **only** for:

- 🎤 **Audio input in conversational context**

This is a temporary architectural decision because the Response API does not yet support audio input.

**Example:**
```php
use CreativeCrafts\LaravelAiAssistant\Facades\Ai;

// Uses Chat Completions API (audio input in chat)
$response = Ai::responses()
    ->inConversation('conv-123')
    ->input()
    ->message('Transcribe and respond:')
    ->audio([
        'file' => storage_path('audio/question.mp3'),
        'action' => 'transcribe',
    ])
    ->send();
```

#### When Dedicated Audio Endpoints are Used

For **standalone audio operations** (not in chat context), dedicated audio endpoints are used:

- 🎤 **Audio Transcription** → `/v1/audio/transcriptions`
- 🌐 **Audio Translation** → `/v1/audio/translations`
- 🔊 **Audio Speech (TTS)** → `/v1/audio/speech`

**Example:**
```php
use CreativeCrafts\LaravelAiAssistant\Facades\Ai;

// Uses dedicated audio transcription endpoint
$response = Ai::responses()
    ->input()
    ->audio([
        'file' => storage_path('audio/recording.mp3'),
        'action' => 'transcribe',
        'model' => 'whisper-1',
    ])
    ->send();
```

#### Complete Endpoint Routing Logic

The package's `RequestRouter` automatically determines the appropriate endpoint:

1. **Audio Transcription** (standalone) → `/v1/audio/transcriptions`
2. **Audio Translation** (standalone) → `/v1/audio/translations`
3. **Audio Speech** (TTS) → `/v1/audio/speech`
4. **Image Generation** → `/v1/images/generations`
5. **Image Edit** → `/v1/images/edits`
6. **Image Variation** → `/v1/images/variations`
7. **Audio Input in Chat Context** → `/v1/chat/completions` (temporary exception)
8. **Everything Else (Default)** → `/v1/responses` (Response API)

#### Benefits of Response API

OpenAI recommends the Response API for new projects because it provides:

- **Better conversation management** with native conversation IDs
- **Built-in multi-turn dialogue support** without manual state management
- **Improved streaming capabilities** with server-sent events
- **More consistent response format** across different operations
- **Future-proof architecture** as OpenAI evolves their APIs

#### Why You Don't Need to Worry

The Laravel AI Assistant package **handles all endpoint routing automatically**. You don't need to specify which endpoint to use—the package determines the optimal endpoint based on your input data.

**Just use the unified API:**
```php
use CreativeCrafts\LaravelAiAssistant\Facades\Ai;

// All of these automatically route to the correct endpoint
Ai::responses()->input()->message('...')->send();           // Response API
Ai::responses()->input()->audio([...])->send();              // Audio endpoint
Ai::responses()->input()->image([...])->send();              // Image endpoint
Ai::responses()->inConversation('id')->input()->message(...); // Response API
```

For more technical details, see [docs/ARCHITECTURE.md](docs/ARCHITECTURE.md).

---

## v4.0 Deprecation Timeline

### Overview

Version 4.0 will be a **major breaking release** that removes all deprecated classes and methods. This section provides a clear timeline and migration path.

### Deprecation Status (v3.0)

The following components are **deprecated in v3.0** and will be **removed in v4.0**:

| Component | Status | Removal Date | Migration Path |
|-----------|--------|--------------|----------------|
| `AiAssistant` class | ⚠️ Deprecated | v4.0 | Use `Ai::responses()` or `Ai::chat()` |
| `OpenAIClientFacade` class | ⚠️ Deprecated | v4.0 | Use `Ai::responses()` directly |
| `AppConfig` static methods | ⚠️ Deprecated | v4.0 | Use `ModelConfigFactory` |
| `AiAssistant::acceptPrompt()` | ⚠️ Deprecated | v4.0 | Use `Ai::responses()->input()->message()` |
| `AiAssistant::sendChatMessageDto()` | ⚠️ Deprecated | v4.0 | Use `Ai::responses()->send()` |
| `AiAssistant::streamChatText()` | ⚠️ Deprecated | v4.0 | Use `Ai::responses()->stream()` |
| `AiAssistant::reply()` | ⚠️ Deprecated | v4.0 | Use `Ai::responses()->send()` |

### Timeline

```
┌─────────────────────────────────────────────────────────────────────┐
│ v3.0 (Current) - October 2024                                       │
│ • Deprecated classes still work                                     │
│ • Deprecation warnings in logs                                      │
│ • Full backward compatibility                                       │
│ • SSOT API fully available                                          │
└─────────────────────────────────────────────────────────────────────┘
                              ↓
┌─────────────────────────────────────────────────────────────────────┐
│ v3.x - Migration Period (October 2024 - March 2025)                │
│ • Update your code to use SSOT API                                  │
│ • Test thoroughly with new API                                      │
│ • Fix all deprecation warnings                                      │
│ • Update dependencies                                               │
└─────────────────────────────────────────────────────────────────────┘
                              ↓
┌─────────────────────────────────────────────────────────────────────┐
│ v4.0 (Planned) - Q2 2025 (April-June 2025)                         │
│ • All deprecated classes REMOVED                                    │
│ • Breaking changes for unmigrated code                              │
│ • SSOT API only                                                     │
│ • Cleaner, more maintainable codebase                               │
└─────────────────────────────────────────────────────────────────────┘
```

### Migration Checklist

Use this checklist to prepare for v4.0:

- [ ] **Replace `AiAssistant` usage**
  - [ ] Search codebase for `AiAssistant::acceptPrompt()`
  - [ ] Replace with `Ai::responses()->input()->message()`
  - [ ] Update `sendChatMessageDto()` to `send()`
  - [ ] Update `streamChatText()` to `stream()`

- [ ] **Replace `OpenAIClientFacade` usage**
  - [ ] Search codebase for `OpenAIClientFacade`
  - [ ] Replace `$facade->responses()` with `Ai::responses()`
  - [ ] Replace `$facade->conversations()` with `Ai::chat()`
  - [ ] Replace `$facade->files()` with file helper methods

- [ ] **Replace `AppConfig` usage**
  - [ ] Search codebase for `AppConfig::`
  - [ ] Replace with `ModelConfigFactory::for(Modality::*, ModelOptions::fromConfig())`
  - [ ] Update configuration access patterns

- [ ] **Test thoroughly**
  - [ ] Run full test suite: `php artisan test`
  - [ ] Test in staging environment
  - [ ] Verify no deprecation warnings in logs
  - [ ] Check error monitoring for issues

- [ ] **Update documentation**
  - [ ] Update internal documentation
  - [ ] Update code comments
  - [ ] Update team knowledge base

### How to Find Deprecated Usage

Search your codebase for deprecated patterns:

```bash
# Find AiAssistant usage
grep -r "AiAssistant::" app/
grep -r "use.*AiAssistant" app/

# Find OpenAIClientFacade usage
grep -r "OpenAIClientFacade" app/
grep -r "facade()" app/

# Find AppConfig usage
grep -r "AppConfig::" app/
```

### v4.0 Breaking Changes (Planned)

When v4.0 is released, the following will cause errors:

```php
// ❌ Will throw "Class not found" error in v4.0
use CreativeCrafts\LaravelAiAssistant\AiAssistant;
$assistant = AiAssistant::acceptPrompt('Hello');

// ❌ Will throw "Class not found" error in v4.0
use CreativeCrafts\LaravelAiAssistant\OpenAIClientFacade;
$facade = app(OpenAIClientFacade::class);

// ❌ Will throw "Class not found" error in v4.0
use CreativeCrafts\LaravelAiAssistant\Services\AppConfig;
$config = AppConfig::textGeneratorConfig();

// ✅ Will work in v4.0 (migrate to this now)
use CreativeCrafts\LaravelAiAssistant\Facades\Ai;
$response = Ai::responses()->input()->message('Hello')->send();
```

### Getting Help with Migration

If you need assistance migrating to v4.0:

1. Review the complete migration examples in this document
2. Check [UPGRADE.md](UPGRADE.md) for version-specific changes
3. See working examples in [examples/](examples/) directory
4. Open an issue on [GitHub](https://github.com/creativecrafts/laravel-ai-assistant/issues) with the `migration` label
5. Join our Discord community for real-time help

**Start migrating today!** Don't wait until v4.0 is released. The SSOT API is available now in v3.0 and provides a better developer experience.

---

## Benefits of SSOT API

### 1. Unified Interface

One consistent API for all operations:

```php
// All operations use the same pattern
Ai::responses()->input()->message('...');  // Text
Ai::responses()->input()->audio([...]);     // Audio
Ai::responses()->input()->image([...]);     // Image
```

### 2. Automatic Endpoint Routing

The API intelligently routes to the correct OpenAI endpoint:

```php
// Automatically routed to chat completion endpoint
Ai::responses()->input()->message('Hello')->send();

// Automatically routed to audio transcription endpoint
Ai::responses()->input()->audio(['action' => 'transcribe', 'file' => '...'])->send();

// Automatically routed to image generation endpoint
Ai::responses()->input()->image(['prompt' => '...'])->send();
```

### 3. Better Developer Experience

- **Full IDE Support**: Complete autocompletion and type hints
- **Fluent API**: Method chaining feels natural
- **Clear Validation**: Helpful error messages during development
- **Consistent Responses**: All operations return structured DTOs

### 4. Future-Proof

The SSOT architecture:
- Aligns with OpenAI's API evolution
- Easier to add new features
- Backwards compatible approach
- Cleaner internal architecture

### 5. Simplified Maintenance

- Single entry point reduces cognitive load
- Less code duplication
- Easier testing and debugging
- Clear separation of concerns

---

## Troubleshooting

### Common Migration Issues

#### Issue: "Class not found" after migration

**Problem:**
```php
// Old code still references removed classes
use CreativeCrafts\LaravelAiAssistant\Repositories\OpenAiRepository;
```

**Solution:**
Remove old imports and use the Ai facade:
```php
use CreativeCrafts\LaravelAiAssistant\Facades\Ai;
```

---

#### Issue: "Method not found on ResponseDto"

**Problem:**
```php
// Old code expects different response structure
echo $response->choices[0]->message->content;
```

**Solution:**
Use the new ResponseDto properties:
```php
echo $response->text;  // For text responses
echo $response->audioContent;  // For audio responses
echo $response->imageUrls[0];  // For image responses
```

---

#### Issue: Audio file path not working

**Problem:**
```php
// Using file resource instead of path
'file' => fopen(storage_path('audio.mp3'), 'r')
```

**Solution:**
Pass the file path directly:
```php
'file' => storage_path('audio.mp3')
```

---

#### Issue: Missing 'action' parameter for audio

**Problem:**
```php
Ai::responses()->input()->audio(['file' => '...'])->send();
// Error: Audio action required
```

**Solution:**
Always specify the action:
```php
Ai::responses()->input()->audio([
    'file' => '...',
    'action' => 'transcribe', // or 'translate' or 'speech'
])->send();
```

---

#### Issue: Image generation expects 'prompt' parameter

**Problem:**
```php
// Trying to generate without a prompt
Ai::responses()->input()->image(['size' => '1024x1024'])->send();
```

**Solution:**
Image generation requires a prompt:
```php
Ai::responses()->input()->image([
    'prompt' => 'A beautiful landscape',
    'size' => '1024x1024',
])->send();
```

For variations (no prompt needed), include an image:
```php
Ai::responses()->input()->image([
    'image' => storage_path('images/source.png'),
])->send();
```

---

#### Issue: Conversation context not working

**Problem:**
```php
// Trying to continue conversation with plain responses
$response1 = Ai::responses()->input()->message('Hello')->send();
$response2 = Ai::responses()->input()->message('Remember what I said?')->send();
// Second request has no memory of the first
```

**Solution:**
Use conversation ID or the chat API:
```php
// Option 1: Use inConversation
$response1 = Ai::responses()
    ->inConversation('user-123')
    ->input()->message('Hello')->send();

$response2 = Ai::responses()
    ->inConversation('user-123')
    ->input()->message('Remember what I said?')->send();

// Option 2: Use the chat API (recommended for multi-turn conversations)
$chat = Ai::chat()->start();
$response1 = $chat->send('Hello');
$response2 = $chat->send('Remember what I said?');
```

---

#### Issue: Streaming not working as expected

**Problem:**
```php
// Trying to access ->text on a generator
$stream = Ai::responses()->input()->message('Hello')->stream();
echo $stream->text;  // Error: generator doesn't have text property
```

**Solution:**
Iterate through the stream:
```php
$stream = Ai::responses()->input()->message('Hello')->stream();

foreach ($stream as $chunk) {
    echo $chunk;
}
```

Or use `send()` for non-streaming:
```php
$response = Ai::responses()->input()->message('Hello')->send();
echo $response->text;
```

---

#### Issue: OpenAIClientFacade not found after upgrade

**Problem:**
```php
use CreativeCrafts\LaravelAiAssistant\OpenAIClientFacade;

$facade = app(OpenAIClientFacade::class);
// Error: Class not found (will happen in v4.0)
```

**Solution:**
The `OpenAIClientFacade` is deprecated. Use `Ai::responses()` directly:
```php
use CreativeCrafts\LaravelAiAssistant\Facades\Ai;

// Instead of $facade->responses()->createResponse()
$response = Ai::responses()->input()->message('Hello')->send();

// Instead of $facade->conversations()->createConversation()
$chat = Ai::chat()->start();

// Instead of $facade->files()->upload()
$service = app(AssistantService::class);
$fileId = $service->uploadFile($path, $purpose);
```

---

#### Issue: AiAssistant class deprecated warnings

**Problem:**
```php
use CreativeCrafts\LaravelAiAssistant\AiAssistant;

$assistant = AiAssistant::acceptPrompt('Hello');
// Deprecation warning: AiAssistant is deprecated
```

**Solution:**
Replace with the SSOT API:
```php
use CreativeCrafts\LaravelAiAssistant\Facades\Ai;

$response = Ai::responses()
    ->input()
    ->message('Hello')
    ->send();

// For conversations
$chat = Ai::chat()->start();
$response = $chat->send('Hello');
```

---

#### Issue: AppConfig static methods not working

**Problem:**
```php
use CreativeCrafts\LaravelAiAssistant\Services\AppConfig;

$config = AppConfig::textGeneratorConfig();
// Deprecation warning or error
```

**Solution:**
Use `ModelConfigFactory` with `ModelOptions`:
```php
use CreativeCrafts\LaravelAiAssistant\Services\ModelConfigFactory;
use CreativeCrafts\LaravelAiAssistant\DataTransferObjects\ModelOptions;
use CreativeCrafts\LaravelAiAssistant\Enums\Modality;

$options = ModelOptions::fromConfig();
$config = ModelConfigFactory::for(Modality::Text, $options);
```

---

#### Issue: Direct repository injection causing issues

**Problem:**
```php
use CreativeCrafts\LaravelAiAssistant\OpenAIClientFacade;

class MyService
{
    public function __construct()
    {
        $this->facade = app(OpenAIClientFacade::class);
    }
}
// Service resolution fails or deprecation warnings
```

**Solution:**
Inject repository contracts directly if needed (advanced use case):
```php
use CreativeCrafts\LaravelAiAssistant\Contracts\ResponsesRepositoryContract;

class MyService
{
    public function __construct(
        private readonly ResponsesRepositoryContract $responsesRepository
    ) {}
    
    public function process()
    {
        // Use repository directly (marked as @internal)
        $response = $this->responsesRepository->createResponse([...]);
    }
}
```

**Better solution:** Use the builder pattern instead:
```php
use CreativeCrafts\LaravelAiAssistant\Facades\Ai;

class MyService
{
    public function process()
    {
        $response = Ai::responses()->input()->message('...')->send();
    }
}
```

---

#### Issue: Migration from facade() method in custom services

**Problem:**
```php
class CustomService
{
    private function facade(): OpenAIClientFacade
    {
        return app(OpenAIClientFacade::class);
    }
    
    public function doWork()
    {
        $response = $this->facade()->responses()->createResponse([...]);
    }
}
```

**Solution:**
Remove the `facade()` method and use `Ai::responses()`:
```php
use CreativeCrafts\LaravelAiAssistant\Facades\Ai;

class CustomService
{
    public function doWork()
    {
        $response = Ai::responses()
            ->input()
            ->message('...')
            ->send();
    }
}
```

Or inject repositories directly if you need low-level access:
```php
use CreativeCrafts\LaravelAiAssistant\Contracts\ResponsesRepositoryContract;

class CustomService
{
    public function __construct(
        private readonly ResponsesRepositoryContract $responsesRepository
    ) {}
    
    public function doWork()
    {
        $response = $this->responsesRepository->createResponse([...]);
    }
}
```

---

#### Issue: Understanding when to use Response API vs Chat Completions

**Problem:**
Confusion about which API endpoint is being used and why.

**Solution:**
You don't need to choose! The package automatically routes to the correct endpoint:

- **Text chat** → Response API (default)
- **Audio in conversation** → Chat Completions API (temporary exception)
- **Standalone audio** → Dedicated audio endpoints
- **Images** → Dedicated image endpoints

Just use the unified API:
```php
use CreativeCrafts\LaravelAiAssistant\Facades\Ai;

// Automatically routed to the right endpoint
Ai::responses()->input()->message('...')->send();
Ai::responses()->input()->audio([...])->send();
Ai::responses()->input()->image([...])->send();
```

See the "[Understanding OpenAI API Endpoints](#understanding-openai-api-endpoints)" section for details.

---

#### Issue: FilesHelper or ToolsBuilder dependencies on AiAssistant

**Problem:**
Internal helpers depend on the deprecated `AiAssistant` class.

**Solution:**
These are **@internal** classes. Don't use them directly. Use the public API:

```php
use CreativeCrafts\LaravelAiAssistant\Facades\Ai;

// For file operations
$response = Ai::responses()
    ->input()
    ->message('Analyze this file')
    ->attachFile($path)
    ->send();

// For tool configuration
$response = Ai::responses()
    ->tools([
        [
            'type' => 'function',
            'function' => [
                'name' => 'get_weather',
                'description' => 'Get weather for a location',
                'parameters' => [...],
            ],
        ],
    ])
    ->input()
    ->message('What is the weather?')
    ->send();
```

---

#### Issue: Finding all deprecated usage in codebase

**Problem:**
Need to identify all places where deprecated classes are used.

**Solution:**
Use these bash commands to search your codebase:

```bash
# Find all AiAssistant usage
grep -r "AiAssistant::" app/ | grep -v "vendor"
grep -r "use.*AiAssistant" app/ | grep -v "vendor"

# Find all OpenAIClientFacade usage
grep -r "OpenAIClientFacade" app/ | grep -v "vendor"
grep -r "facade()" app/ | grep -v "vendor"

# Find all AppConfig usage
grep -r "AppConfig::" app/ | grep -v "vendor"
grep -r "use.*AppConfig" app/ | grep -v "vendor"

# Check for deprecated methods
grep -r "acceptPrompt\|sendChatMessageDto\|streamChatText" app/
```

Review the results and migrate each usage following the examples in this guide.

---

#### Issue: Tests failing after migration

**Problem:**
Tests are failing with "Class not found" or "Method not found" errors.

**Solution:**
1. Update test imports:
```php
// Before
use CreativeCrafts\LaravelAiAssistant\AiAssistant;

// After
use CreativeCrafts\LaravelAiAssistant\Facades\Ai;
```

2. Update test assertions:
```php
// Before
$response = AiAssistant::acceptPrompt('test')->sendChatMessageDto();
expect($response->content)->toBe('Hello');

// After
$response = Ai::responses()->input()->message('test')->send();
expect($response->text)->toBe('Hello');
```

3. Update mocks if using facades:
```php
use CreativeCrafts\LaravelAiAssistant\Facades\Ai;

// Mock the facade
Ai::fake([
    'responses' => ['text' => 'Mocked response'],
]);

$response = Ai::responses()->input()->message('test')->send();
expect($response->text)->toBe('Mocked response');
```

---

### Getting Help

If you encounter issues not covered here:

1. Check the [examples/](examples/) directory for working code samples
2. Review [UPGRADE.md](UPGRADE.md) for version-specific changes
3. See the [README.md](README.md) for API documentation
4. Review the "[v4.0 Deprecation Timeline](#v40-deprecation-timeline)" section for migration guidance
5. Open an issue on [GitHub](https://github.com/creativecrafts/laravel-ai-assistant/issues) with the `migration` label
6. Join our Discord community for real-time support

---

## Summary

The SSOT API provides a modern, unified interface for all OpenAI operations. Key takeaways:

- **One API**: `Ai::responses()` for everything
- **Clear Patterns**: Consistent builder pattern across all operations
- **Type Safety**: Full IDE support with autocompletion
- **8 Operations**: Chat, streaming, audio (3 types), images (3 types)
- **Easy Migration**: Direct mappings from legacy APIs

Start migrating today and enjoy a cleaner, more maintainable codebase!
