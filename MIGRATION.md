# Migration Guide (SSOT API)

This guide shows how to move from legacy APIs to the SSOT (Single Source of Truth) API centered on `Ai::responses()`.

---

## Quick Mapping

| Legacy API | SSOT Replacement |
|------------|------------------|
| `AiAssistant` / `AiAssistant` facade | `Ai::responses()` or `Ai::chat()` |
| `OpenAiRepository` | `Ai::responses()` |
| Compat client (`/src/Compat`) | `Ai::responses()` |

---

## SSOT Basics

```php
use CreativeCrafts\LaravelAiAssistant\Facades\Ai;

$response = Ai::responses()
    ->input()
    ->message('Hello world')
    ->send();

echo $response->text;
```

The builder automatically routes the request to the correct OpenAI endpoint.

---

## Common Migrations

### 1) Chat (legacy `AiAssistant`)

**Before**
```php
use CreativeCrafts\LaravelAiAssistant\AiAssistant;

$assistant = AiAssistant::acceptPrompt('Hello')
    ->setModelName('gpt-4')
    ->setTemperature(0.7);

$response = $assistant->sendChatMessageDto();
```

**After**
```php
use CreativeCrafts\LaravelAiAssistant\Facades\Ai;

$response = Ai::responses()
    ->model('gpt-4')
    ->temperature(0.7)
    ->input()
    ->message('Hello')
    ->send();
```

### 2) Streaming

**Before**
```php
$stream = $assistant->streamChatText('Tell me a story');
foreach ($stream as $chunk) {
    echo $chunk;
}
```

**After**
```php
foreach (Ai::responses()->input()->message('Tell me a story')->stream() as $event) {
    // handle SSE events
}
```

### 3) Audio Transcription

**Before**
```php
$repo->transcribeAudio(['file' => 'audio.mp3']);
```

**After**
```php
$response = Ai::responses()
    ->input()
    ->audio([
        'file' => storage_path('audio/recording.mp3'),
        'action' => 'transcribe',
    ])
    ->send();
```

### 4) Audio Translation

```php
$response = Ai::responses()
    ->input()
    ->audio([
        'file' => storage_path('audio/french.mp3'),
        'action' => 'translate',
    ])
    ->send();
```

### 5) Text-to-Speech

```php
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

### 6) Image Generation

```php
$response = Ai::responses()
    ->input()
    ->image([
        'prompt' => 'A futuristic Laravel logo',
    ])
    ->send();

$response->saveImages(storage_path('images'));
```

### 7) Image Edit

```php
$response = Ai::responses()
    ->input()
    ->image([
        'image' => storage_path('images/input.png'),
        'mask' => storage_path('images/mask.png'),
        'prompt' => 'Add neon glow',
    ])
    ->send();
```

### 8) Tools

```php
use CreativeCrafts\LaravelAiAssistant\Support\ToolsBuilder;

$tools = (new ToolsBuilder())
    ->includeFunctionCallTool('getWeather', 'Fetch weather', [
        'properties' => ['city' => ['type' => 'string']],
        'required' => ['city'],
    ])
    ->toArray();

$response = Ai::responses()
    ->tools($tools)
    ->input()
    ->message('Weather in Paris?')
    ->send();
```

---

## Files and Content Downloads (3.1+)

```php
$file = Ai::files()->upload(storage_path('docs/guide.pdf'));
$content = Ai::files()->content($file['id']);
```

---

## Advanced Endpoints (Low-Level Repositories)

```php
Ai::moderations()->create(['input' => 'Check this']);
Ai::batches()->create([...]);
Ai::realtimeSessions()->create(['model' => 'gpt-4o-realtime-preview']);
Ai::assistants()->create(['model' => 'gpt-4o-mini']);
Ai::vectorStores()->create(['name' => 'Docs']);
```

---

## Troubleshooting

- Ensure `OPENAI_API_KEY` is set.
- Prefer `Ai::responses()` for most operations.
- If you built custom transports or repositories, update for 3.1 contract changes.
