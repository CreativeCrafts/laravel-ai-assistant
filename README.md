# Laravel AI Assistant

[![Latest Version on Packagist](https://img.shields.io/packagist/v/creativecrafts/laravel-ai-assistant.svg?style=flat-square)](https://packagist.org/packages/creativecrafts/laravel-ai-assistant)
[![GitHub Tests Action Status](https://img.shields.io/github/actions/workflow/status/creativecrafts/laravel-ai-assistant/run-tests.yml?branch=main&label=tests&style=flat-square)](https://github.com/creativecrafts/laravel-ai-assistant/actions?query=workflow%3Arun-tests+branch%3Amain)
[![GitHub Code Style Action Status](https://img.shields.io/github/actions/workflow/status/creativecrafts/laravel-ai-assistant/fix-php-code-style-issues.yml?branch=main&label=code%20style&style=flat-square)](https://github.com/creativecrafts/laravel-ai-assistant/actions?query=workflow%3A"Fix+PHP+code+style+issues"+branch%3Amain)
[![Total Downloads](https://img.shields.io/packagist/dt/creativecrafts/laravel-ai-assistant.svg?style=flat-square)](https://packagist.org/packages/creativecrafts/laravel-ai-assistant)

Laravel AI Assistant is a production-ready Laravel package for OpenAI APIs. It uses a Single Source of Truth (SSOT) architecture: **`Ai::responses()`** is the unified entry point for text, audio, image, streaming, and tool-calling workflows, with strong DX and predictable behavior.

---

## Highlights

- One primary API: `Ai::responses()`
- Voice analysis: identify who spoke when in a conversation (speaker diarization)
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

### Voice Analysis (Speaker Diarization)

Identify the different voices in a conversation and what each of them said, using OpenAI's
`gpt-4o-transcribe-diarize` model. Speakers are labelled `A`, `B`, ... automatically, or with your own
names when you provide a short (2–10 second) reference sample for up to four known speakers.

```php
$result = Ai::diarize(storage_path('calls/support-call.mp3'))
    ->knownSpeaker('agent', storage_path('voices/agent.wav'))       // optional, max 4
    ->knownSpeaker('customer', storage_path('voices/customer.wav'))
    ->language('en')
    ->send();

$result->speakers();              // ['agent', 'customer']
$result->segments;                // DiarizedSegment[]: speaker, start, end, text
$result->textFor('customer');     // everything the customer said
$result->speakingTime();          // ['agent' => 63.2, 'customer' => 41.0] (seconds)
$result->speakingShare();         // ['agent' => 60.65, 'customer' => 39.35] (%)
$result->dominantSpeaker();       // 'agent'
$result->turns();                 // consecutive segments merged per speaker
$result->toTranscript(true);      // "[00:00:01.200 - 00:00:04.500] agent: Thanks for calling..."
$result->toWebVtt();              // captions with <v speaker> voice tags

// No reference samples? Rename the automatic labels afterwards
$named = Ai::diarize($path)->send()->renameSpeakers(['A' => 'Agent', 'B' => 'Customer']);
```

Recordings can come from any filesystem disk (e.g. S3) or a stream:

```php
Ai::diarize()->fromDisk('s3', 'calls/2026/10/call-123.ogg')->send();
Ai::diarize($stream, 'call.webm')->send();
```

Stream speaker segments as soon as they are recognised (for example to broadcast them with Laravel Reverb):

```php
$stream = Ai::diarize($path)->stream(onDelta: fn (string $text, ?string $segmentId) => /* partial text */ null);

foreach ($stream as $segment) {
    broadcast(new SpeakerSegmentRecognised($segment->speaker, $segment->text));
}

$transcription = $stream->getReturn(); // the complete DiarizedTranscription
```

Diarization is also available through the unified builder:

```php
$response = Ai::responses()
    ->input()
    ->audio([
        'file' => storage_path('calls/support-call.mp3'),
        'action' => 'diarize',                               // or 'transcribe' + 'diarize' => true
        'known_speakers' => ['agent' => storage_path('voices/agent.wav')],
    ])
    ->send();

$response->metadata['speakers'];          // speaker labels, e.g. ['agent', ...]
$response->diarization()?->toTranscript();
```

Recordings longer than 30 seconds are chunked automatically (`chunking_strategy: auto`); use
`->serverVad(threshold: 0.6, silenceDurationMs: 500)` to tune voice activity detection. Supported
formats: flac, mp3, mp4, mpeg, mpga, m4a, ogg, wav and webm (25 MB max).

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
// Audio (speech, transcriptions incl. diarization, translations, custom voices)
$audio = Ai::audio()->createSpeech(['model' => 'gpt-4o-mini-tts', 'input' => 'Hello', 'voice' => 'marin']);
file_put_contents(storage_path('hello.mp3'), $audio['content']);

foreach (Ai::audio()->streamTranscription(['file' => $path, 'model' => 'gpt-4o-transcribe']) as $event) {
    // transcript.text.delta / transcript.text.segment / transcript.text.done
}

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
