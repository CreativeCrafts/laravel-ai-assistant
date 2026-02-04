# Laravel AI Assistant

[![Latest Version on Packagist](https://img.shields.io/packagist/v/creativecrafts/laravel-ai-assistant.svg?style=flat-square)](https://packagist.org/packages/creativecrafts/laravel-ai-assistant)
[![GitHub Tests Action Status](https://img.shields.io/github/actions/workflow/status/creativecrafts/laravel-ai-assistant/run-tests.yml?branch=main&label=tests&style=flat-square)](https://github.com/creativecrafts/laravel-ai-assistant/actions?query=workflow%3Arun-tests+branch%3Amain)
[![GitHub Code Style Action Status](https://img.shields.io/github/actions/workflow/status/creativecrafts/laravel-ai-assistant/fix-php-code-style-issues.yml?branch=main&label=code%20style&style=flat-square)](https://github.com/creativecrafts/laravel-ai-assistant/actions?query=workflow%3A"Fix+PHP+code+style+issues"+branch%3Amain)
[![Total Downloads](https://img.shields.io/packagist/dt/creativecrafts/laravel-ai-assistant.svg?style=flat-square)](https://packagist.org/packages/creativecrafts/laravel-ai-assistant)

Laravel AI Assistant is a production-ready Laravel package for OpenAI APIs. It uses a Single Source of Truth (SSOT) architecture: **`Ai::responses()`** is the unified entry point for text, audio, image, streaming, and tool-calling workflows, with strong DX and predictable behavior.

---

## Highlights

- One primary API: `Ai::responses()`
- Automatic routing for audio and image operations
- Streaming, tool calls, and structured output
- Files, conversations, webhooks, and observability
- Advanced endpoints: Moderations, Batches, Realtime Sessions, Assistants, Vector Stores

---

## Quick Start

### 1) Install

```bash
composer require creativecrafts/laravel-ai-assistant
php artisan ai:install
```

### 2) Configure

```env
OPENAI_API_KEY=your-openai-api-key-here
```

### 3) Chat (SSOT)

```php
use CreativeCrafts\LaravelAiAssistant\Facades\Ai;

$response = Ai::responses()
    ->input()
    ->message('Explain Laravel queues in simple terms')
    ->send();

echo $response->text;
```

### 4) Streaming

```php
foreach (Ai::responses()->input()->message('Tell me about Laravel')->stream() as $event) {
    // $event is a normalized SSE event
    // You can also use Ai::stream(...) for a simpler chat stream
}
```

### 5) Audio Transcription

```php
$response = Ai::responses()
    ->input()
    ->audio([
        'file' => storage_path('audio/recording.mp3'),
        'action' => 'transcribe',
    ])
    ->send();

echo $response->text;
```

### 6) Image Generation

```php
$response = Ai::responses()
    ->input()
    ->image([
        'prompt' => 'A futuristic Laravel logo with neon lights',
    ])
    ->send();

$response->saveImages(storage_path('images'));
```

---

## Core Usage

### The SSOT Builder (`Ai::responses()`)

Use the unified builder for text, audio, and image operations:

```php
$response = Ai::responses()
    ->model('gpt-4o-mini')
    ->temperature(0.3)
    ->input()
    ->message('Write a haiku about Laravel')
    ->send();
```

### Conversations

```php
$conversation = Ai::conversations()->create();

Ai::responses()
    ->inConversation($conversation['id'])
    ->input()
    ->message('Remember: I like short answers')
    ->send();
```

### Tool Calling (Chat Sessions)

```php
use CreativeCrafts\LaravelAiAssistant\Support\ToolsBuilder;

$session = Ai::chat('You are a helpful assistant');
$session->tools()
    ->includeFunctionCallTool('getWeather', 'Fetch weather', [
        'properties' => ['city' => ['type' => 'string']],
        'required' => ['city'],
    ]);

$response = $session->send('What is the weather in Paris?');
```

### Audio

```php
// Speech synthesis
$response = Ai::responses()
    ->input()
    ->audio([
        'text' => 'Welcome to Laravel AI Assistant',
        'action' => 'speech',
        'voice' => 'alloy',
    ])
    ->send();

$response->saveAudio(storage_path('audio/welcome.mp3'));
```

### Images

```php
// Image editing
$response = Ai::responses()
    ->input()
    ->image([
        'image' => storage_path('images/input.png'),
        'mask' => storage_path('images/mask.png'),
        'prompt' => 'Add a neon glow',
    ])
    ->send();

$response->saveImages(storage_path('images/edited'));
```

---

## Files

### Upload

```php
$fileId = Ai::files()->upload(storage_path('docs/guide.pdf'))['id'] ?? null;
```

### Download Content

```php
$content = Ai::files()->content('file_123');
file_put_contents(storage_path('downloads/file.jsonl'), $content['content']);
```

---

## Advanced Endpoints (Low-Level Repositories)

These are thin wrappers around OpenAI endpoints for advanced use cases.

```php
// Moderations
$result = Ai::moderations()->create([
    'input' => 'Check this content',
]);

// Batches
$batch = Ai::batches()->create([
    'input_file_id' => 'file_123',
    'endpoint' => '/v1/responses',
    'completion_window' => '24h',
]);

// Realtime Sessions
$session = Ai::realtimeSessions()->create([
    'model' => 'gpt-4o-realtime-preview',
]);

// Assistants (v2 beta)
$assistant = Ai::assistants()->create([
    'model' => 'gpt-4o-mini',
    'name' => 'Support Assistant',
]);

// Vector Stores (v2 beta)
$store = Ai::vectorStores()->create([
    'name' => 'Support Docs',
]);
```

---

## Webhooks

Enable in config and set a signing secret. Optional timestamp enforcement is supported.

```env
AI_WEBHOOKS_ENABLED=true
AI_WEBHOOKS_SIGNING_SECRET=your-strong-secret
AI_WEBHOOKS_REQUIRE_TIMESTAMP=true
```

---

## Testing

Integration tests are available under `tests/Integration`. They are skipped unless a valid API key is configured.

---

## Migration & Upgrade

- Migration guide: `MIGRATION.md`
- Upgrade guide: `UPGRADE.md`

---

## Support

See `CHANGELOG.md` for recent changes and `examples/` for additional usage patterns.
