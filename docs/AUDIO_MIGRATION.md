# Audio API Migration Guide

This guide helps you migrate from direct OpenAI audio API calls or separate audio methods to the unified Response API with Single Source of Truth (SSOT) architecture.

## Overview

The unified Response API provides a single interface for all audio operations:
- **Audio Transcription**: Convert speech to text
- **Audio Translation**: Translate speech to English text
- **Audio Speech**: Convert text to speech

### Benefits of the Unified API

- ✅ **Single Source of Truth**: One interface for all AI operations
- ✅ **Automatic Routing**: API automatically routes to the correct OpenAI endpoint
- ✅ **Consistent Interface**: Same builder pattern across all features
- ✅ **Type Safety**: Full IDE autocomplete and type hints
- ✅ **Better Error Handling**: Unified exception hierarchy
- ✅ **Laravel Conventions**: Follows Laravel best practices

---

## Audio Transcription Migration

### Before: Direct OpenAI API Calls

If you were making direct HTTP calls to OpenAI's audio transcription endpoint:

```php
use Illuminate\Support\Facades\Http;

$response = Http::withHeaders([
    'Authorization' => 'Bearer ' . config('services.openai.api_key'),
])
->attach('file', file_get_contents($audioFile), 'audio.mp3')
->post('https://api.openai.com/v1/audio/transcriptions', [
    'model' => 'whisper-1',
    'language' => 'en',
    'response_format' => 'json',
]);

$transcription = $response->json()['text'];
```

### After: Unified Response API

```php
use CreativeCrafts\LaravelAiAssistant\Facades\Ai;

$response = Ai::responses()
    ->input()
    ->audio([
        'file' => $audioFile,
        'action' => 'transcribe',
        'language' => 'en',
    ])
    ->send();

$transcription = $response->text;
```

### Migration Benefits

- ✅ No need to manage HTTP headers and authentication
- ✅ Automatic file handling and multipart requests
- ✅ Consistent error handling
- ✅ Type-safe response objects
- ✅ Built-in retry logic and rate limiting

### Advanced Transcription Features

**With Context Prompt:**

```php
// Before: Manual API construction
$response = Http::withHeaders([...])
    ->attach('file', file_get_contents($audioFile), 'audio.mp3')
    ->post('https://api.openai.com/v1/audio/transcriptions', [
        'model' => 'whisper-1',
        'prompt' => 'This is a technical discussion about Laravel.',
        'temperature' => 0.0,
    ]);

// After: Clean fluent interface
$response = Ai::responses()
    ->input()
    ->audio([
        'file' => $audioFile,
        'action' => 'transcribe',
        'prompt' => 'This is a technical discussion about Laravel.',
        'temperature' => 0.0,
    ])
    ->send();

echo $response->text;
```

**Different Response Formats:**

```php
// Before: Parse different formats manually
$response = Http::withHeaders([...])
    ->attach('file', file_get_contents($audioFile), 'audio.mp3')
    ->post('https://api.openai.com/v1/audio/transcriptions', [
        'model' => 'whisper-1',
        'response_format' => 'verbose_json',
    ]);

$data = $response->json();
$text = $data['text'];
$language = $data['language'] ?? null;
$duration = $data['duration'] ?? null;

// After: Structured response with metadata
$response = Ai::responses()
    ->input()
    ->audio([
        'file' => $audioFile,
        'action' => 'transcribe',
        'response_format' => 'verbose_json',
    ])
    ->send();

$text = $response->text;
$language = $response->metadata['language'] ?? null;
$duration = $response->metadata['duration'] ?? null;
```

---

## Audio Translation Migration

Audio translation converts speech in any language to English text.

### Before: Direct API Calls

```php
use Illuminate\Support\Facades\Http;

$response = Http::withHeaders([
    'Authorization' => 'Bearer ' . config('services.openai.api_key'),
])
->attach('file', file_get_contents($audioFile), 'audio.mp3')
->post('https://api.openai.com/v1/audio/translations', [
    'model' => 'whisper-1',
]);

$translation = $response->json()['text'];
```

### After: Unified Response API

```php
use CreativeCrafts\LaravelAiAssistant\Facades\Ai;

$response = Ai::responses()
    ->input()
    ->audio([
        'file' => $audioFile,
        'action' => 'translate',
    ])
    ->send();

$translation = $response->text;
```

### With Additional Options

```php
// After: Full configuration support
$response = Ai::responses()
    ->model('whisper-1')
    ->input()
    ->audio([
        'file' => $audioFile,
        'action' => 'translate',
        'prompt' => 'This audio discusses Laravel features.',
        'temperature' => 0.0,
        'response_format' => 'verbose_json',
    ])
    ->send();

echo $response->text;
```

---

## Audio Speech (Text-to-Speech) Migration

### Before: Direct API Calls

```php
use Illuminate\Support\Facades\Http;

$response = Http::withHeaders([
    'Authorization' => 'Bearer ' . config('services.openai.api_key'),
    'Content-Type' => 'application/json',
])
->post('https://api.openai.com/v1/audio/speech', [
    'model' => 'tts-1',
    'input' => 'Hello, welcome to Laravel AI Assistant!',
    'voice' => 'nova',
    'response_format' => 'mp3',
]);

$audioContent = $response->body();
file_put_contents('output.mp3', $audioContent);
```

### After: Unified Response API

```php
use CreativeCrafts\LaravelAiAssistant\Facades\Ai;

$response = Ai::responses()
    ->input()
    ->audio([
        'text' => 'Hello, welcome to Laravel AI Assistant!',
        'action' => 'speech',
        'voice' => 'nova',
    ])
    ->send();

file_put_contents('output.mp3', $response->audioContent);
```

### Advanced Speech Generation

**Multiple Voices and Speeds:**

```php
// Before: Multiple manual API calls
foreach (['alloy', 'echo', 'nova'] as $voice) {
    $response = Http::withHeaders([...])
        ->post('https://api.openai.com/v1/audio/speech', [
            'model' => 'tts-1',
            'input' => $text,
            'voice' => $voice,
            'speed' => 1.25,
        ]);
    file_put_contents("speech-{$voice}.mp3", $response->body());
}

// After: Clean loop with unified API
foreach (['alloy', 'echo', 'nova'] as $voice) {
    $response = Ai::responses()
        ->input()
        ->audio([
            'text' => $text,
            'action' => 'speech',
            'voice' => $voice,
            'speed' => 1.25,
        ])
        ->send();
    
    file_put_contents("speech-{$voice}.mp3", $response->audioContent);
}
```

**High-Quality Speech with HD Model:**

```php
// Before: Manual model specification
$response = Http::withHeaders([...])
    ->post('https://api.openai.com/v1/audio/speech', [
        'model' => 'tts-1-hd',
        'input' => $text,
        'voice' => 'onyx',
    ]);

// After: Fluent model configuration
$response = Ai::responses()
    ->model('tts-1-hd')
    ->input()
    ->audio([
        'text' => $text,
        'action' => 'speech',
        'voice' => 'onyx',
    ])
    ->send();

file_put_contents('hd-speech.mp3', $response->audioContent);
```

**Different Audio Formats:**

```php
// Before: Handle different formats manually
$formats = ['mp3', 'opus', 'aac', 'flac'];
foreach ($formats as $format) {
    $response = Http::withHeaders([...])
        ->post('https://api.openai.com/v1/audio/speech', [
            'model' => 'tts-1',
            'input' => $text,
            'voice' => 'alloy',
            'response_format' => $format,
        ]);
    file_put_contents("output.{$format}", $response->body());
}

// After: Consistent format handling
$formats = ['mp3', 'opus', 'aac', 'flac'];
foreach ($formats as $format) {
    $response = Ai::responses()
        ->input()
        ->audio([
            'text' => $text,
            'action' => 'speech',
            'voice' => 'alloy',
            'format' => $format,
        ])
        ->send();
    
    file_put_contents("output.{$format}", $response->audioContent);
}
```

---

## Search and Replace Patterns

Use these patterns to quickly migrate your code:

### Transcription

```php
// Find:
Http::withHeaders([
    'Authorization' => 'Bearer ' . config('services.openai.api_key'),
])
->attach('file', file_get_contents($file), basename($file))
->post('https://api.openai.com/v1/audio/transcriptions', [
    'model' => 'whisper-1',
    // ...options
]);

// Replace with:
Ai::responses()
    ->input()
    ->audio([
        'file' => $file,
        'action' => 'transcribe',
        // ...options
    ])
    ->send();
```

### Translation

```php
// Find:
Http::withHeaders([...])
->attach('file', file_get_contents($file), basename($file))
->post('https://api.openai.com/v1/audio/translations', [...]);

// Replace with:
Ai::responses()
    ->input()
    ->audio([
        'file' => $file,
        'action' => 'translate',
        // ...options
    ])
    ->send();
```

### Speech Generation

```php
// Find:
Http::withHeaders([...])
->post('https://api.openai.com/v1/audio/speech', [
    'model' => 'tts-1',
    'input' => $text,
    // ...options
]);

// Replace with:
Ai::responses()
    ->input()
    ->audio([
        'text' => $text,
        'action' => 'speech',
        // ...options
    ])
    ->send();
```

### Response Handling

```php
// Find:
$transcription = $response->json()['text'];

// Replace with:
$transcription = $response->text;

// Find:
$audioContent = $response->body();

// Replace with:
$audioContent = $response->audioContent;
```

---

## Parameter Mapping

### Transcription Parameters

| Direct API | Unified API | Notes |
|------------|-------------|-------|
| `model` | `model` (method) or `'model'` in array | Can be set at builder level |
| `file` | `'file'` | Required |
| N/A | `'action' => 'transcribe'` | Required to specify transcription |
| `language` | `'language'` | Optional |
| `prompt` | `'prompt'` | Optional context |
| `response_format` | `'response_format'` | json, text, srt, verbose_json, vtt |
| `temperature` | `'temperature'` | 0.0 to 1.0 |

### Translation Parameters

| Direct API | Unified API | Notes |
|------------|-------------|-------|
| `model` | `model` (method) or `'model'` in array | Can be set at builder level |
| `file` | `'file'` | Required |
| N/A | `'action' => 'translate'` | Required to specify translation |
| `prompt` | `'prompt'` | Optional context |
| `response_format` | `'response_format'` | json, text, srt, verbose_json, vtt |
| `temperature` | `'temperature'` | 0.0 to 1.0 |

### Speech Parameters

| Direct API | Unified API | Notes |
|------------|-------------|-------|
| `model` | `model` (method) or `'model'` in array | tts-1 or tts-1-hd |
| `input` | `'text'` | Required |
| N/A | `'action' => 'speech'` | Required to specify speech generation |
| `voice` | `'voice'` | alloy, echo, fable, onyx, nova, shimmer |
| `response_format` | `'format'` | mp3, opus, aac, flac, wav, pcm |
| `speed` | `'speed'` | 0.25 to 4.0 |

---

## Common Migration Issues

### Issue 1: Response Format Differences

**Problem:**
```php
// Old code expecting raw array
$text = $response->json()['text'];
$language = $response->json()['language'];
```

**Solution:**
```php
// Use response properties and metadata
$text = $response->text;
$language = $response->metadata['language'] ?? null;
```

### Issue 2: Binary Audio Content

**Problem:**
```php
// Old code using body()
$audioContent = $response->body();
```

**Solution:**
```php
// Use audioContent property
$audioContent = $response->audioContent;
```

### Issue 3: File Attachment

**Problem:**
```php
// Old code manually handling file upload
->attach('file', file_get_contents($audioFile), basename($audioFile))
```

**Solution:**
```php
// Just pass the file path
->audio([
    'file' => $audioFile,
    // ...
])
```

The unified API handles file reading and multipart requests automatically.

### Issue 4: Error Handling

**Problem:**
```php
// Old code checking HTTP status codes
if ($response->failed()) {
    $error = $response->json()['error']['message'];
}
```

**Solution:**
```php
// Use try-catch with specific exceptions
use CreativeCrafts\LaravelAiAssistant\Exceptions\AudioTranscriptionException;
use CreativeCrafts\LaravelAiAssistant\Exceptions\AudioSpeechException;

try {
    $response = Ai::responses()
        ->input()
        ->audio([...])
        ->send();
} catch (AudioTranscriptionException $e) {
    // Handle transcription errors
    logger()->error('Transcription failed', ['error' => $e->getMessage()]);
} catch (AudioSpeechException $e) {
    // Handle speech generation errors
    logger()->error('Speech generation failed', ['error' => $e->getMessage()]);
}
```

---

## Testing Your Migration

After migrating, verify your audio operations:

### Test Transcription

```php
use CreativeCrafts\LaravelAiAssistant\Facades\Ai;

$response = Ai::responses()
    ->input()
    ->audio([
        'file' => storage_path('test-audio.mp3'),
        'action' => 'transcribe',
    ])
    ->send();

dump([
    'type' => $response->type, // Should be 'audio_transcription'
    'text' => $response->text,
    'metadata' => $response->metadata,
]);
```

### Test Translation

```php
$response = Ai::responses()
    ->input()
    ->audio([
        'file' => storage_path('spanish-audio.mp3'),
        'action' => 'translate',
    ])
    ->send();

dump([
    'type' => $response->type, // Should be 'audio_translation'
    'text' => $response->text,
]);
```

### Test Speech Generation

```php
$response = Ai::responses()
    ->input()
    ->audio([
        'text' => 'Testing text-to-speech migration',
        'action' => 'speech',
        'voice' => 'nova',
    ])
    ->send();

dump([
    'type' => $response->type, // Should be 'audio_speech'
    'has_content' => !empty($response->audioContent),
    'size' => strlen($response->audioContent),
]);
```

---

## Migration Checklist

- [ ] Replace direct HTTP calls with `Ai::responses()`
- [ ] Add `'action'` parameter to audio arrays ('transcribe', 'translate', or 'speech')
- [ ] Change `'input'` parameter to `'text'` for speech generation
- [ ] Update response handling to use `$response->text` and `$response->audioContent`
- [ ] Replace `$response->json()` with response properties
- [ ] Update error handling to use try-catch with specific exceptions
- [ ] Remove manual HTTP header management
- [ ] Remove manual file attachment code
- [ ] Test all audio operations with real files
- [ ] Update tests to use the unified API
- [ ] Review and update documentation

---

## Additional Resources

- [API Documentation](./API.md)
- [Audio Transcription Example](../examples/05-audio-transcription.php)
- [Audio Speech Example](../examples/06-audio-speech.php)
- [Unified API Example](../examples/08-unified-api.php)
- [Architecture Overview](./ARCHITECTURE.md)

---

## Getting Help

If you encounter issues during migration:

1. Check the [examples directory](../examples/) for working code
2. Review the [API documentation](./API.md)
3. Search for similar issues on [GitHub](https://github.com/creativecrafts/laravel-ai-assistant/issues)
4. Open a new issue with your specific migration problem

---

**Migration completed?** Great! The unified API will make your audio operations more reliable, consistent, and easier to maintain.
