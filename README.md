# Laravel AI Assistant

[![Latest Version on Packagist](https://img.shields.io/packagist/v/creativecrafts/laravel-ai-assistant.svg?style=flat-square)](https://packagist.org/packages/creativecrafts/laravel-ai-assistant)
[![GitHub Tests Action Status](https://img.shields.io/github/actions/workflow/status/creativecrafts/laravel-ai-assistant/run-tests.yml?branch=main&label=tests&style=flat-square)](https://github.com/creativecrafts/laravel-ai-assistant/actions?query=workflow%3Arun-tests+branch%3Amain)
[![GitHub Code Style Action Status](https://img.shields.io/github/actions/workflow/status/creativecrafts/laravel-ai-assistant/fix-php-code-style-issues.yml?branch=main&label=code%20style&style=flat-square)](https://github.com/creativecrafts/laravel-ai-assistant/actions?query=workflow%3A"Fix+PHP+code+style+issues"+branch%3Amain)
[![Total Downloads](https://img.shields.io/packagist/dt/creativecrafts/laravel-ai-assistant.svg?style=flat-square)](https://packagist.org/packages/creativecrafts/laravel-ai-assistant)

Laravel AI Assistant is a modern, production-ready package for integrating OpenAI's powerful language models into your Laravel applications. Built on OpenAI's **Responses API** and **Conversations API**, it provides a clean, fluent interface with native support for streaming, tool calls, multimodal inputs, and stateful conversations.

---

## 🚀 Quick Start

### Installation

```bash
composer require creativecrafts/laravel-ai-assistant
```

Publish the configuration:

```bash
php artisan vendor:publish --tag=laravel-ai-assistant-config
```

Run the installer (recommended):

```bash
php artisan ai:install
```

Set your OpenAI API key in `.env`:

```env
OPENAI_API_KEY=your-openai-api-key-here
```

### Your First AI Request

```php
use CreativeCrafts\LaravelAiAssistant\Facades\Ai;

// Quick one-off request
$response = Ai::quick('Explain Laravel queues in simple terms');
echo $response->text;
```

That's it! You're ready to build AI-powered features.

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

// One-off requests (stateless)
Ai::quick('Your prompt here');

// Stateful conversations
Ai::chat('System instructions here');

// Direct API access
Ai::responses();      // For single-turn responses
Ai::conversations();  // For multi-turn threads

// Streaming
Ai::stream('Your prompt here');
```

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

---

## 📊 Monitoring & Metrics

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

- [UPGRADE.md](UPGRADE.md) - Migration guide from Assistant API to Responses API
- [CHANGELOG.md](CHANGELOG.md) - Version history and changes
- [docs/ARCHITECTURE.md](docs/ARCHITECTURE.md) - Package architecture
- [docs/ENVIRONMENT_VARIABLES.md](docs/ENVIRONMENT_VARIABLES.md) - Complete environment variable reference
- [docs/PERFORMANCE_TUNING.md](docs/PERFORMANCE_TUNING.md) - Performance optimization guide
- [docs/PRODUCTION_CONFIGURATION.md](docs/PRODUCTION_CONFIGURATION.md) - Production deployment guide
