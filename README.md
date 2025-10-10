# Laravel AI Assistant

[![Latest Version on Packagist](https://img.shields.io/packagist/v/creativecrafts/laravel-ai-assistant.svg?style=flat-square)](https://packagist.org/packages/creativecrafts/laravel-ai-assistant)
[![GitHub Tests Action Status](https://img.shields.io/github/actions/workflow/status/creativecrafts/laravel-ai-assistant/run-tests.yml?branch=main&label=tests&style=flat-square)](https://github.com/creativecrafts/laravel-ai-assistant/actions?query=workflow%3Arun-tests+branch%3Amain)
[![GitHub Code Style Action Status](https://img.shields.io/github/actions/workflow/status/creativecrafts/laravel-ai-assistant/fix-php-code-style-issues.yml?branch=main&label=code%20style&style=flat-square)](https://github.com/creativecrafts/laravel-ai-assistant/actions?query=workflow%3A"Fix+PHP+code+style+issues"+branch%3Amain)
[![Total Downloads](https://img.shields.io/packagist/dt/creativecrafts/laravel-ai-assistant.svg?style=flat-square)](https://packagist.org/packages/creativecrafts/laravel-ai-assistant)

Laravel AI Assistant is a modern, production-ready package for integrating OpenAI's powerful language models into your Laravel applications. Built on OpenAI's **Responses API** and **Conversations API**, it provides a clean, fluent interface with native support for streaming, tool calls, multimodal inputs, and stateful conversations.

---

## 🚀 Quick Start (≤5 Minutes)

### Step 1: Install (1 min)

```bash
composer require creativecrafts/laravel-ai-assistant
php artisan ai:install
```

### Step 2: Configure (1 min)

Set your OpenAI API key in `.env`:

```env
OPENAI_API_KEY=your-openai-api-key-here
```

### Step 3: Hello World Chat (1 min)

```php
use CreativeCrafts\LaravelAiAssistant\Facades\Ai;

// Simple one-off request
$response = Ai::quick('Explain Laravel queues in simple terms');
echo $response->text;
```

### Step 4: Streaming Response (2 min)

```php
// Real-time streaming for better UX
foreach (Ai::stream('Tell me about Laravel') as $chunk) {
    echo $chunk;
    flush();
}
```

### Step 5: Audio Transcription (1 min)

```php
// Transcribe audio files to text
$response = Ai::responses()
    ->input()
    ->audio([
        'file' => storage_path('audio/recording.mp3'),
        'action' => 'transcribe',
    ])
    ->send();

echo $response->text; // "Hello, this is a test recording..."
```

### Step 6: Image Generation (1 min)

```php
// Generate images from text descriptions
$response = Ai::responses()
    ->input()
    ->image([
        'prompt' => 'A futuristic Laravel logo with neon lights',
    ])
    ->send();

// Save generated images
$response->saveImages(storage_path('images'));
```

**That's it!** You now have AI-powered chat, streaming, audio, and image capabilities. See [examples/](examples/) for more patterns.

---

## 📖 Core Concepts

### Why Responses API?

This package uses OpenAI's modern **Responses API** and **Conversations API**, which provide:

- **Better structure**: Clean separation between single-turn responses and multi-turn conversations
- **Enhanced features**: Native support for tool calls, streaming, and multimodal inputs
- **Improved ergonomics**: Fluent builder pattern with excellent IDE autocompletion
- **Future-proof**: Aligns with OpenAI's direction — see the official migration guide: https://platform.openai.com/docs/guides/migrating-from-chat-completions-to-responses

> **Note**: The legacy Assistant API (`Ai::assistant()`) is **deprecated in 1.x** and will be removed in a future version. See [UPGRADE.md](UPGRADE.md) for migration instructions.

### Available Methods

```php
use CreativeCrafts\LaravelAiAssistant\Facades\Ai;

// Unified completion API (recommended)
$ai->complete(Mode::TEXT, Transport::SYNC, $request);

// Convenience methods
Ai::quick('Your prompt here');           // One-off requests
Ai::chat('System instructions');         // Stateful conversations
Ai::stream('Your prompt here');          // Streaming responses

// Direct API access
Ai::responses();                         // Single-turn responses
Ai::conversations();                     // Multi-turn threads
```

### Comparison: Sync vs Stream

| Feature | SYNC | STREAM |
|---------|------|--------|
| **Response Time** | Wait for complete response | First token arrives quickly |
| **Memory Usage** | Lower | Higher (buffering) |
| **User Experience** | Blocking | Progressive, real-time |
| **Best For** | Batch processing, APIs | Interactive UIs, long responses |

### Feature Capabilities

| Capability | Description | Supported |
|------------|-------------|-----------|
| **Text Chat** | Single-turn and multi-turn conversations | ✅ |
| **Streaming** | Real-time token-by-token responses | ✅ |
| **Tool Calls** | Function calling and external integrations | ✅ |
| **Audio Transcription** | Convert audio to text (speech-to-text) | ✅ |
| **Audio Translation** | Translate audio to English | ✅ |
| **Text-to-Speech** | Generate natural speech from text | ✅ |
| **Image Generation** | Create images from text prompts (DALL-E) | ✅ |
| **Image Editing** | Modify existing images with prompts | ✅ |
| **Image Variations** | Generate variations of existing images | ✅ |
| **Webhooks** | Asynchronous background processing | ✅ |
| **Observability** | Built-in logging, metrics, and monitoring | ✅ |

---

## 🎯 Unified Completion API (Recommended)

The **unified completion API** provides a consistent interface for all AI operations with explicit control over mode and transport.

### Quick Example

```php
use CreativeCrafts\LaravelAiAssistant\Services\AiManager;
use CreativeCrafts\LaravelAiAssistant\Enums\{Mode, Transport};
use CreativeCrafts\LaravelAiAssistant\DataTransferObjects\CompletionRequest;

$ai = app(AiManager::class);

// Text completion (sync)
$result = $ai->complete(
    Mode::TEXT,
    Transport::SYNC,
    CompletionRequest::fromArray([
        'model' => 'gpt-4o-mini',
        'prompt' => 'Write a haiku about Laravel',
        'temperature' => 0.2,
    ])
);

echo (string) $result;
```

### Mode Options

- **`Mode::TEXT`** - Simple text completion (single prompt string)
- **`Mode::CHAT`** - Chat with message history (conversation context)

### Transport Options

- **`Transport::SYNC`** - Wait for complete response (blocking)
- **`Transport::STREAM`** - Stream tokens as generated (accumulated into final result)

### Chat Example

```php
$result = $ai->complete(
    Mode::CHAT,
    Transport::SYNC,
    CompletionRequest::fromArray([
        'model' => 'gpt-4o-mini',
        'messages' => [
            ['role' => 'system', 'content' => 'You are a helpful assistant'],
            ['role' => 'user', 'content' => 'Explain dependency injection'],
        ],
    ])
);

$data = $result->toArray();
```

### Streaming Example

```php
// Stream accumulates to final result
$finalResult = $ai->complete(
    Mode::TEXT,
    Transport::STREAM,
    CompletionRequest::fromArray([
        'model' => 'gpt-4o-mini',
        'prompt' => 'Tell me a story',
    ])
);

echo (string) $finalResult;
```

> **Note**: For incremental streaming events (processing chunks as they arrive), use `Ai::stream()` instead.

See [docs/API.md](docs/API.md) for complete API reference.

---

## 📚 Cookbook

### Quick Calls (One-Off Requests)

Use `Ai::quick()` for stateless, single-turn requests.

```php
use CreativeCrafts\LaravelAiAssistant\Facades\Ai;

// Simple text response
$response = Ai::quick('What is dependency injection?');
echo $response->text;

// Force JSON output
$response = Ai::quick([
    'message' => 'Give me 3 fun facts about Laravel',
    'response_format' => 'json',
]);
$facts = json_decode($response->text, true);

// Custom model and temperature
$response = Ai::quick([
    'message' => 'Write a haiku about PHP',
    'model' => 'gpt-4o-mini',
    'temperature' => 0.9,
]);
echo $response->text;
```

### Stateful Chat (Multi-Turn Conversations)

Use `Ai::chat()` for conversations that maintain context across multiple turns.

```php
use CreativeCrafts\LaravelAiAssistant\Facades\Ai;

// Create a chat session
$chat = Ai::chat('You are a helpful Laravel expert');

// First turn
$chat->message('What are service providers?');
$response = $chat->send();
echo $response->text;

// Continue the conversation (maintains context)
$chat->message('Can you show me an example?');
$response = $chat->send();
echo $response->text;

// Access conversation history
$messages = $chat->getMessages();
```

#### Customizing Chat Sessions

```php
$chat = Ai::chat('You are a coding assistant')
    ->setModelName('gpt-4')
    ->setTemperature(0.2)
    ->setMaxTokens(500);

$response = $chat
    ->message('Explain middleware in Laravel')
    ->send();
```

### Streaming Responses

Stream responses token-by-token for real-time UX (perfect for SSE, WebSockets, or Livewire).

```php
use CreativeCrafts\LaravelAiAssistant\Facades\Ai;

// Simple streaming
foreach (Ai::stream('Tell me a story about Laravel') as $chunk) {
    echo $chunk; // Outputs each token as it arrives
}

// Streaming with chat sessions
$chat = Ai::chat('You are a storyteller');
$chat->message('Tell me a story about a developer');

foreach ($chat->stream() as $chunk) {
    echo $chunk;
}

// Streaming with callbacks
$fullText = '';
$stream = Ai::stream(
    'Explain design patterns',
    onEvent: function ($event) use (&$fullText) {
        $fullText .= $event;
        // Broadcast to WebSocket, emit to frontend, etc.
    }
);

foreach ($stream as $chunk) {
    // Process each chunk
}
```

#### HTTP Streaming Response

```php
// routes/web.php
use CreativeCrafts\LaravelAiAssistant\Facades\Ai;
use CreativeCrafts\LaravelAiAssistant\Http\Responses\StreamedAiResponse;
use Illuminate\Http\Request;

Route::get('/ai/stream', function (Request $request) {
    $prompt = $request->input('q', 'Explain Laravel Eloquent');
    $generator = Ai::stream($prompt);
    
    return StreamedAiResponse::fromGenerator($generator);
});
```

#### Cancellation (Stopping Streams)

Stop streaming operations mid-flight using the `shouldStop` callback:

```php
use CreativeCrafts\LaravelAiAssistant\Facades\Ai;

// Limit by chunk count
$maxChunks = 10;
$chunkCount = 0;

$stream = Ai::stream(
    'Write a very long essay',
    onEvent: null,
    shouldStop: function () use (&$chunkCount, $maxChunks): bool {
        $chunkCount++;
        return $chunkCount >= $maxChunks; // Stop after 10 chunks
    }
);

foreach ($stream as $chunk) {
    echo $chunk;
    if ($chunkCount >= $maxChunks) {
        break;
    }
}

// Limit by time
$startTime = microtime(true);
$maxDuration = 5.0; // 5 seconds

$stream = Ai::stream(
    'Generate content',
    shouldStop: function () use ($startTime, $maxDuration): bool {
        return (microtime(true) - $startTime) >= $maxDuration;
    }
);

foreach ($stream as $chunk) {
    echo $chunk;
}

// User-initiated cancellation
$cancelled = false; // Set to true from external signal

$stream = Ai::stream(
    'Long operation',
    shouldStop: fn() => $cancelled
);

foreach ($stream as $chunk) {
    echo $chunk;
    if ($cancelled) {
        break;
    }
}
```

### Tool Calls (Function Calling)

Let the AI call functions/tools to perform actions or retrieve data.

```php
use CreativeCrafts\LaravelAiAssistant\Facades\Ai;

// Define tools
$tools = [
    [
        'type' => 'function',
        'function' => [
            'name' => 'get_weather',
            'description' => 'Get the current weather for a location',
            'parameters' => [
                'type' => 'object',
                'properties' => [
                    'location' => [
                        'type' => 'string',
                        'description' => 'City name, e.g. London, Paris',
                    ],
                    'unit' => [
                        'type' => 'string',
                        'enum' => ['celsius', 'fahrenheit'],
                    ],
                ],
                'required' => ['location'],
            ],
        ],
    ],
];

// Send request with tools
$response = Ai::responses()
    ->model('gpt-4')
    ->tools($tools)
    ->input()->message('What is the weather in London?')
    ->send();

// Check if AI wants to call a tool
if ($response->requiresAction) {
    foreach ($response->toolCalls as $toolCall) {
        if ($toolCall['function']['name'] === 'get_weather') {
            $args = json_decode($toolCall['function']['arguments'], true);
            $location = $args['location'];
            
            // Call your actual weather service
            $weatherData = getWeather($location);
            
            // Submit tool result back to AI
            // (Implementation depends on your flow)
        }
    }
}
```

#### Complete Tool Call Flow

```php
use CreativeCrafts\LaravelAiAssistant\Facades\Ai;

// 1. Initial request with tools
$conversation = Ai::conversations()->start();
$response = Ai::conversations()
    ->use($conversation)
    ->input()->message('What is the weather in Paris?')
    ->responses()
    ->tools($tools)
    ->send();

// 2. Execute tool if requested
if ($response->requiresAction) {
    foreach ($response->toolCalls as $toolCall) {
        $result = executeFunction($toolCall);
        
        // 3. Submit tool results
        $finalResponse = Ai::conversations()
            ->use($conversation)
            ->input()->toolResult($toolCall['id'], $result)
            ->send();
            
        echo $finalResponse->text;
    }
}
```

### Conversations API (Advanced)

For explicit control over multi-turn conversations.

```php
use CreativeCrafts\LaravelAiAssistant\Facades\Ai;

// Create a new conversation
$conversationId = Ai::conversations()->start([
    'metadata' => ['user_id' => auth()->id()],
]);

// Send first turn
$response = Ai::conversations()
    ->use($conversationId)
    ->input()->message('Hello! What can you help me with?')
    ->responses()
    ->instructions('You are a helpful assistant')
    ->send();

echo $response->text;

// Continue conversation
$response = Ai::conversations()
    ->use($conversationId)
    ->input()->message('Tell me about Laravel')
    ->send();

// List conversation history
$items = Ai::conversations()
    ->use($conversationId)
    ->items();

foreach ($items as $item) {
    echo $item['role'] . ': ' . $item['content'] . "\n";
}
```

#### Advanced Conversation Management

```php
// Create without auto-activation
$conversationId = Ai::conversations()->start(
    metadata: ['topic' => 'Laravel Help'],
    setActive: false
);

// Use existing conversation
$response = Ai::conversations()
    ->use($conversationId)
    ->input()->message('My question here')
    ->responses()
    ->model('gpt-4')
    ->instructions('You are an expert')
    ->send();

// Get a ResponsesBuilder bound to conversation
$responsesBuilder = Ai::conversations()
    ->use($conversationId)
    ->responses();

// Now customize and send
$response = $responsesBuilder
    ->model('gpt-4o-mini')
    ->input()->message('Another question')
    ->send();
```

### Responses API (Low-Level)

For complete control over single-turn responses.

```php
use CreativeCrafts\LaravelAiAssistant\Facades\Ai;

$response = Ai::responses()
    ->model('gpt-4')
    ->instructions('You are a helpful coding assistant')
    ->input()->message('Explain SOLID principles')
    ->send();

echo $response->text;

// With conversation binding
$response = Ai::responses()
    ->inConversation($conversationId)
    ->model('gpt-4o-mini')
    ->input()->message('Continue')
    ->send();
```

#### Complex Input Items

```php
$response = Ai::responses()
    ->model('gpt-4')
    ->withInput([
        ['role' => 'system', 'content' => 'You are a helpful assistant'],
        ['role' => 'user', 'content' => 'Hello'],
        ['role' => 'assistant', 'content' => 'Hi! How can I help?'],
        ['role' => 'user', 'content' => 'Explain Laravel routes'],
    ])
    ->send();
```

### Webhooks (Background Processing)

Process AI requests asynchronously via webhooks.

#### 1. Configure Environment

```env
AI_ASSISTANT_WEBHOOK_SIGNING_SECRET=base64:YOUR_SECURE_SECRET_HERE
AI_BACKGROUND_JOBS_ENABLED=true
AI_QUEUE_NAME=ai-assistant
```

#### 2. Register Webhook Route

```php
// routes/web.php
use App\Http\Controllers\AiWebhookController;

Route::post('/ai/webhook', [AiWebhookController::class, 'handle'])
    ->middleware('verify.ai.webhook'); // Verifies webhook signature
```

#### 3. Create Webhook Controller

```php
namespace App\Http\Controllers;

use Illuminate\Http\Request;

class AiWebhookController extends Controller
{
    public function handle(Request $request)
    {
        $event = $request->input('event');
        $data = $request->input('data');
        
        match ($event) {
            'response.completed' => $this->handleResponseCompleted($data),
            'response.failed' => $this->handleResponseFailed($data),
            'tool.called' => $this->handleToolCall($data),
            default => null,
        };
        
        return response()->json(['status' => 'received']);
    }
    
    private function handleResponseCompleted(array $data): void
    {
        // Process completed AI response
        $text = $data['text'];
        $conversationId = $data['conversation_id'];
        
        // Store, broadcast, notify user, etc.
    }
    
    private function handleResponseFailed(array $data): void
    {
        // Handle failed request
        logger()->error('AI request failed', $data);
    }
    
    private function handleToolCall(array $data): void
    {
        // Execute tool and return result
    }
}
```

#### 4. Trigger Background Request

```php
use CreativeCrafts\LaravelAiAssistant\Facades\Ai;

// Dispatch to queue (webhook will be called when complete)
Ai::responses()
    ->model('gpt-4')
    ->input()->message('Generate a long report')
    ->sendAsync(webhookUrl: route('ai.webhook'));
```

### Audio Processing

Process audio files with transcription, translation, and text-to-speech capabilities.

#### Audio Transcription

```php
use CreativeCrafts\LaravelAiAssistant\Facades\Ai;

// Basic transcription
$response = Ai::responses()
    ->input()
    ->audio([
        'file' => storage_path('audio/meeting.mp3'),
        'action' => 'transcribe',
    ])
    ->send();

echo $response->text; // Transcribed text

// Advanced transcription with language and context
$response = Ai::responses()
    ->input()
    ->audio([
        'file' => storage_path('audio/interview.mp3'),
        'action' => 'transcribe',
        'language' => 'en',
        'prompt' => 'This is a technical interview about Laravel.',
        'temperature' => 0,
    ])
    ->send();

echo $response->text;
```

#### Audio Translation

```php
// Translate audio to English
$response = Ai::responses()
    ->input()
    ->audio([
        'file' => storage_path('audio/spanish_speech.mp3'),
        'action' => 'translate',
    ])
    ->send();

echo $response->text; // English translation
```

#### Text-to-Speech

```php
// Generate speech from text
$response = Ai::responses()
    ->input()
    ->audio([
        'text' => 'Welcome to Laravel AI Assistant!',
        'action' => 'speech',
        'voice' => 'nova',
    ])
    ->send();

// Save audio file
$response->saveAudio(storage_path('audio/welcome.mp3'));

// Advanced speech with custom settings
$response = Ai::responses()
    ->input()
    ->audio([
        'text' => 'This is a professional announcement.',
        'action' => 'speech',
        'model' => 'tts-1-hd',
        'voice' => 'onyx',
        'format' => 'mp3',
        'speed' => 1.0,
    ])
    ->send();

$response->saveAudio(storage_path('audio/announcement.mp3'));
```

### Image Generation and Editing

Create and manipulate images using DALL-E models.

#### Image Generation

```php
use CreativeCrafts\LaravelAiAssistant\Facades\Ai;

// Basic image generation
$response = Ai::responses()
    ->input()
    ->image([
        'prompt' => 'A futuristic Laravel logo with neon lights',
    ])
    ->send();

// Save generated images
$paths = $response->saveImages(storage_path('images'));

// Advanced generation with DALL-E 3
$response = Ai::responses()
    ->input()
    ->image([
        'prompt' => 'A professional developer workspace with multiple monitors',
        'model' => 'dall-e-3',
        'size' => '1024x1024',
        'quality' => 'hd',
        'style' => 'vivid',
    ])
    ->send();

foreach ($response->images as $image) {
    echo $image['url'] . "\n";
    echo "Revised prompt: " . $image['revised_prompt'] . "\n";
}
```

#### Image Editing

```php
// Edit existing image
$response = Ai::responses()
    ->input()
    ->image([
        'image' => storage_path('images/photo.png'),
        'prompt' => 'Add a sunset background',
    ])
    ->send();

$response->saveImages(storage_path('images/edited'));

// Edit with mask
$response = Ai::responses()
    ->input()
    ->image([
        'image' => storage_path('images/product.png'),
        'mask' => storage_path('images/mask.png'),
        'prompt' => 'Change the product color to blue',
        'n' => 3,
    ])
    ->send();
```

#### Image Variations

```php
// Create variations of an existing image
$response = Ai::responses()
    ->input()
    ->image([
        'image' => storage_path('images/logo.png'),
        'n' => 5,
    ])
    ->send();

$paths = $response->saveImages(storage_path('images/variations'));
```

---

## 🎵 Audio Features

Laravel AI Assistant provides comprehensive audio processing capabilities through OpenAI's Whisper and TTS models, all accessible via the unified Response API.

### Audio Transcription

Convert audio files to text in their original language.

```php
use CreativeCrafts\LaravelAiAssistant\Facades\Ai;

// Basic transcription
$response = Ai::responses()
    ->input()
    ->audio([
        'file' => storage_path('audio/recording.mp3'),
        'action' => 'transcribe',
    ])
    ->send();

echo $response->text;

// Advanced transcription with language specification and context
$response = Ai::responses()
    ->input()
    ->audio([
        'file' => storage_path('audio/meeting.mp3'),
        'action' => 'transcribe',
        'model' => 'whisper-1',
        'language' => 'en',
        'prompt' => 'This is a business meeting discussing Q4 results.',
        'response_format' => 'json',
        'temperature' => 0,
    ])
    ->send();

echo $response->text;

// Access metadata
$duration = $response->metadata['duration'] ?? null;
$language = $response->metadata['language'] ?? null;
```

**Supported audio formats**: mp3, mp4, mpeg, mpga, m4a, wav, webm

### Audio Translation

Translate audio from any supported language to English.

```php
// Translate foreign language audio to English
$response = Ai::responses()
    ->input()
    ->audio([
        'file' => storage_path('audio/spanish_presentation.mp3'),
        'action' => 'translate',
    ])
    ->send();

echo $response->text; // English translation

// With context for better translation
$response = Ai::responses()
    ->input()
    ->audio([
        'file' => storage_path('audio/french_interview.mp3'),
        'action' => 'translate',
        'prompt' => 'This is a formal technical presentation about software development.',
        'temperature' => 0.2,
    ])
    ->send();

echo $response->text;
```

### Text-to-Speech

Generate natural-sounding speech from text using multiple voice options.

```php
// Basic text-to-speech
$response = Ai::responses()
    ->input()
    ->audio([
        'text' => 'Hello! Welcome to Laravel AI Assistant.',
        'action' => 'speech',
    ])
    ->send();

// Save audio to file
$response->saveAudio(storage_path('audio/welcome.mp3'));

// Advanced speech generation
$response = Ai::responses()
    ->input()
    ->audio([
        'text' => 'This is a professional announcement with high-quality audio.',
        'action' => 'speech',
        'model' => 'tts-1-hd', // High-quality model
        'voice' => 'nova',      // Female, friendly voice
        'format' => 'mp3',
        'speed' => 1.0,
    ])
    ->send();

if ($response->isAudio()) {
    $path = storage_path('audio/announcement.mp3');
    $response->saveAudio($path);
}
```

**Available voices**:
- `alloy` - Neutral, balanced (general purpose)
- `echo` - Male, clear (announcements, narration)
- `fable` - Warm, expressive (storytelling)
- `onyx` - Deep, authoritative (professional content)
- `nova` - Female, friendly (conversational)
- `shimmer` - Soft, calming (meditation, relaxation)

**Models**:
- `tts-1` - Standard quality (faster, lower cost)
- `tts-1-hd` - High quality (slower, higher quality)

See [docs/API.md#audio-apis](docs/API.md#audio-apis) for complete audio API documentation.

---

## 🖼️ Image Features

Create, edit, and manipulate images using OpenAI's DALL-E models through the unified Response API.

### Image Generation

Generate images from text descriptions.

```php
use CreativeCrafts\LaravelAiAssistant\Facades\Ai;

// Basic image generation
$response = Ai::responses()
    ->input()
    ->image([
        'prompt' => 'A futuristic Laravel logo with neon lights',
    ])
    ->send();

// Access generated images
foreach ($response->images as $image) {
    $url = $image['url'];
    echo "Image URL: {$url}\n";
}

// Save all generated images
$paths = $response->saveImages(storage_path('images'));

// Advanced generation with DALL-E 3
$response = Ai::responses()
    ->input()
    ->image([
        'prompt' => 'A professional developer workspace with dual monitors showing Laravel code',
        'model' => 'dall-e-3',
        'size' => '1024x1024',
        'quality' => 'hd',
        'style' => 'vivid',
        'n' => 1,
    ])
    ->send();

if ($response->isImage()) {
    foreach ($response->images as $image) {
        echo "URL: " . $image['url'] . "\n";
        echo "Revised prompt: " . $image['revised_prompt'] . "\n";
    }
}
```

**DALL-E Models**:
- `dall-e-2` - Lower cost, supports multiple images (1-10), good quality
- `dall-e-3` - Higher cost, single image only, excellent quality and prompt following

**Sizes**:
- DALL-E 2: `256x256`, `512x512`, `1024x1024`
- DALL-E 3: `1024x1024`, `1792x1024`, `1024x1792`

### Image Editing

Edit existing images using text prompts.

```php
// Basic image editing
$response = Ai::responses()
    ->input()
    ->image([
        'image' => storage_path('images/photo.png'),
        'prompt' => 'Add a sunset background',
    ])
    ->send();

$editedPaths = $response->saveImages(storage_path('images/edited'));

// Edit with transparency mask
$response = Ai::responses()
    ->input()
    ->image([
        'image' => storage_path('images/product.png'),
        'mask' => storage_path('images/mask.png'),
        'prompt' => 'Change the product color to blue',
        'n' => 3,
        'size' => '1024x1024',
    ])
    ->send();

// Process multiple variations
foreach ($response->images as $index => $image) {
    $path = storage_path("images/variation_{$index}.png");
    file_put_contents($path, file_get_contents($image['url']));
}
```

**Requirements**:
- Format: PNG only
- Must be square (same width and height)
- Max file size: 4MB
- Only DALL-E 2 supports editing

### Image Variations

Create variations of existing images without text prompts.

```php
// Generate variations
$response = Ai::responses()
    ->input()
    ->image([
        'image' => storage_path('images/logo.png'),
        'n' => 5,
        'size' => '1024x1024',
    ])
    ->send();

// Save all variations
$paths = $response->saveImages(storage_path('images/variations'));

// Or download manually
foreach ($response->images as $index => $image) {
    $imageData = file_get_contents($image['url']);
    file_put_contents(
        storage_path("images/variation_{$index}.png"),
        $imageData
    );
}
```

**Note**: Image variations do not use prompts. If you need to modify images based on text descriptions, use Image Editing instead.

See [docs/API.md#image-apis](docs/API.md#image-apis) for complete image API documentation.

---

## 🔐 Security

### Webhook Signature Verification

The `verify.ai.webhook` middleware automatically validates webhook signatures.

```php
// Middleware is registered automatically
// Just add it to your webhook route:
Route::post('/ai/webhook', [AiWebhookController::class, 'handle'])
    ->middleware('verify.ai.webhook');
```

### Rate Limiting

```php
// config/ai-assistant.php
'rate_limiting' => [
    'enabled' => true,
    'max_requests_per_minute' => 60,
],
```

### Content Filtering

```php
$response = Ai::quick('Your prompt')
    ->withContentFilter([
        'hate' => 'medium',
        'violence' => 'medium',
        'sexual' => 'medium',
    ]);
```

---

## ⚙️ Configuration

### Environment Variables

```env
# Required
OPENAI_API_KEY=sk-...

# Optional
AI_ASSISTANT_MODEL=gpt-4o-mini
AI_ASSISTANT_TEMPERATURE=0.7
AI_ASSISTANT_MAX_TOKENS=1000
AI_ASSISTANT_TIMEOUT=30

# Streaming
AI_ASSISTANT_STREAMING_ENABLED=true
AI_ASSISTANT_STREAMING_BUFFER_SIZE=8192
AI_ASSISTANT_STREAMING_CHUNK_SIZE=1024

# Background Jobs
AI_BACKGROUND_JOBS_ENABLED=false
AI_QUEUE_NAME=ai-assistant

# Webhooks
AI_ASSISTANT_WEBHOOK_SIGNING_SECRET=base64:...

# Caching
AI_ASSISTANT_CACHE_ENABLED=true
AI_ASSISTANT_CACHE_TTL=3600
```

### Presets

Choose a configuration preset for common use cases:

```env
AI_ASSISTANT_PRESET=simple  # Options: simple, balanced, creative, precise, efficient
```

**Presets:**

- `simple`: Fast responses with `gpt-4o-mini`, higher temperature
- `balanced`: Standard `gpt-4` with moderate settings (default)
- `creative`: Higher temperature, more creative outputs
- `precise`: Lower temperature, more deterministic
- `efficient`: Optimized for speed and cost

---

## 🎯 Advanced Features

### Caching Responses

```php
use CreativeCrafts\LaravelAiAssistant\Facades\Ai;

$response = Ai::quick('Explain Laravel')
    ->cache(ttl: 3600); // Cache for 1 hour

// Cached responses return instantly
$cachedResponse = Ai::quick('Explain Laravel'); // Hits cache
```

### JSON Mode

```php
$response = Ai::quick([
    'message' => 'List 5 PHP frameworks with descriptions',
    'response_format' => 'json',
]);

$frameworks = json_decode($response->text, true);
```

### Temperature Control

```php
// Creative writing (high temperature)
$creative = Ai::chat('You are a creative writer')
    ->setTemperature(0.9)
    ->message('Write a poem about Laravel')
    ->send();

// Factual answers (low temperature)
$factual = Ai::chat('You are a technical expert')
    ->setTemperature(0.1)
    ->message('What is Laravel?')
    ->send();
```

### Token Limits

```php
$response = Ai::chat('Be concise')
    ->setMaxTokens(100)
    ->message('Explain Laravel in one sentence')
    ->send();
```

---

## 🧪 Testing

### Mocking AI Responses

```php
use CreativeCrafts\LaravelAiAssistant\Facades\Ai;
use CreativeCrafts\LaravelAiAssistant\DataTransferObjects\ChatResponseDto;

// In your tests
Ai::fake();

Ai::shouldReceive('quick')
    ->once()
    ->with('Test prompt')
    ->andReturn(new ChatResponseDto([
        'text' => 'Mocked response',
        'model' => 'gpt-4',
        'usage' => ['total_tokens' => 10],
    ]));

$response = Ai::quick('Test prompt');
$this->assertEquals('Mocked response', $response->text);
```

### Testing Streaming

```php
Ai::fake();

Ai::shouldReceive('stream')
    ->andReturn(new \ArrayIterator(['chunk1', 'chunk2', 'chunk3']));

$chunks = [];
foreach (Ai::stream('Test') as $chunk) {
    $chunks[] = $chunk;
}

$this->assertCount(3, $chunks);
```

### Integration Tests

This package includes comprehensive integration tests that verify the unified Response API with real OpenAI endpoints (audio, image, chat completion).

#### Running All Tests

```bash
# Run all tests (excludes integration tests by default)
composer test

# Run only unit tests
./vendor/bin/pest tests/Unit

# Run only feature tests
./vendor/bin/pest tests/Feature
```

#### Running Integration Tests

Integration tests are marked with `@group integration` and are automatically skipped unless you have a valid OpenAI API key configured.

```bash
# Run integration tests (simulated adapter tests - no API key required)
./vendor/bin/pest tests/Integration/AudioAdaptersIntegrationTest.php
./vendor/bin/pest tests/Integration/ImageAdaptersIntegrationTest.php

# Run real API integration tests (requires OPENAI_API_KEY)
./vendor/bin/pest tests/Integration/RealApiIntegrationTest.php

# Run all integration tests
./vendor/bin/pest --group=integration

# Exclude integration tests from CI
./vendor/bin/pest --exclude-group=integration
```

#### Real API Integration Tests

Real API tests make actual calls to OpenAI endpoints and require:

1. **API Key**: Set `OPENAI_API_KEY` environment variable
2. **Cost Awareness**: Full suite costs < $1 (using minimal test data)
3. **Optional Execution**: Tests are automatically skipped without API key

**Cost Optimization:**
- Audio tests use minimal 483-byte MP3 file
- Image tests use 256x256 size with DALL-E 2 (cheapest)
- Text-to-speech uses minimal text (2 words)
- Total cost: ~$0.075 for complete suite

**Enable real API tests:**

```bash
# Option 1: Set environment variable
export OPENAI_API_KEY="sk-..."
./vendor/bin/pest tests/Integration/RealApiIntegrationTest.php

# Option 2: Configure in your .env
OPENAI_API_KEY=sk-...
```

**Test Coverage:**
- ✅ Audio transcription (Whisper API)
- ✅ Audio translation (Whisper API)
- ✅ Audio speech generation (TTS API)
- ✅ Image generation (DALL-E API)
- ✅ Image editing (DALL-E API)
- ✅ Image variations (DALL-E API)
- ✅ Unified API end-to-end flow
- ✅ Error handling across services

---

## 📊 Monitoring & Observability

### Observability Integration

Track all AI operations with correlation IDs, metrics, and structured logging:

```php
use CreativeCrafts\LaravelAiAssistant\Facades\{Ai, Observability};
use Illuminate\Support\Str;

// Set correlation ID for request tracing
Observability::setCorrelationId(Str::uuid()->toString());

// Track operation with memory monitoring
$startTime = microtime(true);
$checkpoint = Observability::trackMemory('ai-chat');

try {
    // Log request
    Observability::log('ai-request', 'info', 'Processing AI request');
    
    // Make AI request
    $response = Ai::quick('Hello AI');
    
    $duration = microtime(true) - $startTime;
    
    // Log success metrics
    Observability::logApiResponse(
        'ai-completion',
        true,
        ['text_length' => strlen($response->text)],
        $duration
    );
    
    // Record performance metrics
    Observability::recordApiCall(
        '/ai/complete',
        $duration * 1000,
        200
    );
    
    // End memory tracking
    $metrics = Observability::endMemoryTracking($checkpoint);
    
    Observability::logPerformanceMetrics(
        'ai-chat',
        $duration * 1000,
        $metrics
    );
    
} catch (\Exception $e) {
    Observability::endMemoryTracking($checkpoint);
    Observability::report($e, ['operation' => 'ai-chat']);
    throw $e;
}
```

### Streaming with Observability

```php
Observability::setCorrelationId(request()->header('X-Request-ID'));

$checkpoint = Observability::trackMemory('ai-stream');
$chunkCount = 0;

try {
    Observability::log('ai-stream', 'info', 'Starting stream');
    
    foreach (Ai::stream('Tell a story') as $chunk) {
        $chunkCount++;
        echo $chunk;
    }
    
    $metrics = Observability::endMemoryTracking($checkpoint);
    Observability::logPerformanceMetrics(
        'ai-stream',
        $duration * 1000,
        array_merge($metrics, ['chunks' => $chunkCount])
    );
} catch (\Exception $e) {
    Observability::endMemoryTracking($checkpoint);
    Observability::reportApiError(
        'ai-stream',
        '/stream',
        500,
        $e->getMessage(),
        [],
        ['error' => $e->getMessage()]
    );
    throw $e;
}
```

See [docs/OBSERVABILITY.md](docs/OBSERVABILITY.md) for complete documentation on correlation IDs, logging, metrics, error reporting, and memory tracking.

### Built-in Metrics

```php
use CreativeCrafts\LaravelAiAssistant\Services\MetricsService;

$metrics = app(MetricsService::class);

// Get request metrics
$requestCount = $metrics->getRequestCount();
$avgResponseTime = $metrics->getAverageResponseTime();
$tokenUsage = $metrics->getTotalTokenUsage();

// Get error rates
$errorRate = $metrics->getErrorRate();
```

### Logging

```php
// Automatic logging of all requests (when enabled)
// config/ai-assistant.php
'logging' => [
    'enabled' => true,
    'channel' => 'stack',
    'level' => 'info',
],
```

---

## 🚀 Performance Optimization

### Connection Pooling

```php
// config/ai-assistant.php
'connection_pool' => [
    'enabled' => true,
    'max_connections' => 10,
    'timeout' => 30,
],
```

### Request Batching

```php
use CreativeCrafts\LaravelAiAssistant\Facades\Ai;

// Process multiple requests concurrently
$prompts = [
    'Explain Laravel',
    'Explain Symfony',
    'Explain CodeIgniter',
];

$promises = [];
foreach ($prompts as $prompt) {
    $promises[] = Ai::quick($prompt)->async();
}

$responses = Promise\Utils::unwrap($promises);
```

---

## 🔄 Migration Guide

### Upgrading from Assistant API (Deprecated)

If you're using the legacy `Ai::assistant()` or `Assistant` class, please see [UPGRADE.md](UPGRADE.md) for comprehensive migration instructions.


---

## 📝 Requirements

- PHP 8.2+
- Laravel 10.x, 11.x, or 12.x
- OpenAI API key

---

## 🤝 Contributing

Contributions are welcome! Please see [CONTRIBUTING.md](CONTRIBUTING.md) for details.

---

## 🔒 Security

If you discover any security-related issues, please email security@creativecrafts.com instead of using the issue tracker.

---

## 📄 License

The MIT License (MIT). Please see [LICENSE](LICENSE.md) for more information.

---

## 🙏 Credits

- [Creative Crafts](https://github.com/creativecrafts)
- [All Contributors](../../contributors)

---

## 📚 Additional Documentation

### Core Documentation

- **[docs/API.md](docs/API.md)** - Complete API reference with all methods, modes, and examples
  - **[Audio APIs](docs/API.md#audio-apis)** - Audio transcription, translation, and text-to-speech documentation
  - **[Image APIs](docs/API.md#image-apis)** - Image generation, editing, and variation documentation
  - **[SSOT Architecture](docs/API.md#ssot-architecture)** - Understanding the unified API design
- **[UPGRADE.md](UPGRADE.md)** - Migration guide from legacy APIs to modern interfaces
- **[docs/OBSERVABILITY.md](docs/OBSERVABILITY.md)** - Comprehensive observability guide with correlation IDs, metrics, and logging
- **[CHANGELOG.md](CHANGELOG.md)** - Version history and changes

### Advanced Guides

- [docs/ARCHITECTURE.md](docs/ARCHITECTURE.md) - Package architecture and design patterns
- [docs/ENVIRONMENT_VARIABLES.md](docs/ENVIRONMENT_VARIABLES.md) - Complete environment variable reference
- [docs/PERFORMANCE_TUNING.md](docs/PERFORMANCE_TUNING.md) - Performance optimization guide
- [docs/PRODUCTION_CONFIGURATION.md](docs/PRODUCTION_CONFIGURATION.md) - Production deployment guide
- [docs/SCALING.md](docs/SCALING.md) - Scaling strategies and best practices

### Examples

See [examples/](examples/) for runnable code samples demonstrating:
- Hello world chat completion
- Streaming responses with cancellation
- Unified completion API usage
- Observability integration
- Advanced patterns


---

## Unified Completion Entrypoint (New)

Use a single public entrypoint to cover text/chat × sync/stream via AiManager::complete.

Text (sync):

```php
use CreativeCrafts\LaravelAiAssistant\Enums\{Mode, Transport};
use CreativeCrafts\LaravelAiAssistant\DataTransferObjects\{CompletionRequest};
use CreativeCrafts\LaravelAiAssistant\Services\AiManager;

$ai = app(AiManager::class);

$result = $ai->complete(
    Mode::TEXT,
    Transport::SYNC,
    CompletionRequest::fromArray([
        'model' => 'gpt-4o-mini',
        'prompt' => 'Write a haiku about Laravel',
        'temperature' => 0.2,
    ])
);

echo (string) $result; // final text
```

Chat (sync):

```php
$result = $ai->complete(
    Mode::CHAT,
    Transport::SYNC,
    CompletionRequest::fromArray([
        'model' => 'gpt-4o-mini',
        'messages' => [
            ['role' => 'user', 'content' => 'Summarize this: ...'],
        ],
    ])
);

$data = $result->toArray(); // normalized message array
```

Stream (accumulate to final result):

```php
// Text stream → accumulated final string
$final = (string) $ai->complete(
    Mode::TEXT,
    Transport::STREAM,
    CompletionRequest::fromArray([
        'model' => 'gpt-4o-mini',
        'prompt' => 'Stream a short message',
    ])
);

// Chat stream → accumulated final array
$finalArray = $ai->complete(
    Mode::CHAT,
    Transport::STREAM,
    CompletionRequest::fromArray([
        'model' => 'gpt-4o-mini',
        'messages' => [ ['role' => 'user', 'content' => 'Hello'] ],
    ])
)->toArray();

// If you need incremental events, prefer ChatSession::stream(...)
```

Deprecation notice:

Legacy methods on AssistantService (textCompletion, chatTextCompletion, streamedCompletion, streamedChat) are deprecated and will be removed in a future release. They now log a deprecation warning once per process. Prefer AiManager::complete.
