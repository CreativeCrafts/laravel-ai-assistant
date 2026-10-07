# Audio

Speech-to-text, translation and text-to-speech, through the unified builder or the low-level Audio API.
For "who spoke when", see [Speaker diarization](speaker-diarization.md).

Supported input formats: `flac`, `mp3`, `mp4`, `mpeg`, `mpga`, `m4a`, `ogg`, `wav`, `webm` (25 MB max per
request; configurable with `OPENAI_AUDIO_FILE_SIZE_LIMIT_MB`).

## Transcription (speech → text)

```php
use CreativeCrafts\LaravelAiAssistant\Facades\Ai;

$response = Ai::responses()
    ->input()
    ->audio([
        'file' => storage_path('app/recordings/interview.mp3'),
        'action' => 'transcribe',
        'model' => 'gpt-4o-transcribe',   // default: gpt-4o-mini-transcribe
        'language' => 'en',               // ISO-639-1, improves accuracy and latency
        'prompt' => 'Laravel, Eloquent, Horizon, Reverb',  // spelling hints
    ])
    ->send();

echo $response->text;
```

| Option | Description |
|---|---|
| `file` | Path to the audio file (required) |
| `action` | `transcribe` |
| `model` | `whisper-1`, `gpt-4o-transcribe`, `gpt-4o-mini-transcribe`, `gpt-4o-transcribe-diarize` |
| `language` | Input language |
| `prompt` | Context or vocabulary hints (not supported when diarizing) |
| `response_format` | `json`, `text`, `srt`, `verbose_json`, `vtt`, `diarized_json` |
| `temperature` | `0`–`1` |

### Subtitles

```php
$srt = Ai::audio()->createTranscription([
    'file' => storage_path('app/videos/talk.mp4'),
    'model' => 'whisper-1',
    'response_format' => 'srt',
])['text'];   // plain-text formats (text, srt, vtt) come back under the `text` key

Storage::put('subtitles/talk.srt', $srt);
```

### Transcribing an upload in a queued job

```php
namespace App\Jobs;

use App\Models\Recording;
use CreativeCrafts\LaravelAiAssistant\Facades\Ai;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Storage;

class TranscribeRecording implements ShouldQueue
{
    use Queueable;

    public int $timeout = 300;
    public int $tries = 3;

    public function __construct(public Recording $recording) {}

    public function handle(): void
    {
        $text = Ai::audio()->createTranscription([
            'file' => Storage::disk('s3')->readStream($this->recording->path),
            'model' => 'gpt-4o-mini-transcribe',
        ])['text'] ?? '';

        $this->recording->update(['transcript' => $text]);
    }
}
```

When you pass a stream, give the API a filename with the right extension if the format can't be guessed:
`'file' => ['contents' => $stream, 'filename' => 'call.m4a']`.

### Streaming a transcription

```php
foreach (Ai::audio()->streamTranscription([
    'file' => storage_path('app/recordings/meeting.wav'),
    'model' => 'gpt-4o-mini-transcribe',
]) as $event) {
    if ($event['type'] === 'transcript.text.delta') {
        echo $event['delta'];
    }
}
```

## Translation (any language → English)

```php
$english = Ai::responses()
    ->input()
    ->audio(['file' => storage_path('app/recordings/yoruba.mp3'), 'action' => 'translate'])
    ->send()
    ->text;

// Low level
$result = Ai::audio()->createTranslation(['file' => $path, 'model' => 'whisper-1']);
```

## Text to speech

```php
$response = Ai::responses()
    ->input()
    ->audio([
        'text' => 'Your order has shipped and will arrive on Friday.',
        'action' => 'speech',
        'voice' => 'nova',      // alloy, echo, fable, onyx, nova, shimmer
        'speed' => 1.1,         // 0.25 – 4.0
        'format' => 'mp3',      // mp3, opus, aac, flac, wav, pcm
    ])
    ->send();

$response->saveAudio(storage_path('app/audio/order-shipped.mp3'));
```

### Return audio from a controller

```php
use CreativeCrafts\LaravelAiAssistant\Facades\Ai;

Route::get('/announcements/{announcement}/audio', function (Announcement $announcement) {
    $speech = Ai::audio()->createSpeech([
        'model' => 'gpt-4o-mini-tts',
        'voice' => 'coral',
        'input' => $announcement->body,
        'instructions' => 'Speak in a warm, upbeat tone.',
        'response_format' => 'mp3',
    ]);

    return response($speech['content'], 200, ['Content-Type' => $speech['content_type'] ?? 'audio/mpeg']);
});
```

The low-level `createSpeech()` passes the payload straight to the API, so you can use any model and voice
OpenAI supports (including `gpt-4o-mini-tts` with `instructions` and custom voices). The builder validates
`voice` against the six classic voices.

### Streaming speech

```php
foreach (Ai::audio()->streamSpeech([
    'model' => 'gpt-4o-mini-tts',
    'voice' => 'alloy',
    'input' => 'Streaming audio starts playing before the whole file is generated.',
]) as $event) {
    if ($event['type'] === 'speech.audio.delta') {
        $chunk = base64_decode($event['audio']);
        // write or forward $chunk
    }
}
```

## Custom voices

```php
$voice = Ai::audio()->createVoice([
    'name' => 'Brand narrator',
    'audio_sample' => storage_path('app/voices/narrator-sample.wav'),
]);

// $voice['id'] can then be used as the voice in createSpeech() requests
```

<!-- Voice consents (createVoiceConsent, listVoiceConsents, ...) are being added in a follow-up release; document them here once they ship. -->

## Audio inside a chat

To send audio as part of a conversation with a model (rather than transcribing it), use `audioInput()`; the
request is routed to Chat Completions:

```php
$response = Ai::responses()
    ->model('gpt-4o-audio-preview')
    ->input()
    ->audioInput(['file' => storage_path('app/question.wav'), 'format' => 'wav'])
    ->send();
```

## Low-level Audio API

| Method | Endpoint |
|---|---|
| `Ai::audio()->createSpeech(array $payload): array` | `POST /v1/audio/speech` (returns `content` + `content_type`) |
| `Ai::audio()->streamSpeech(array $payload): iterable` | `POST /v1/audio/speech` with `stream_format: sse` |
| `Ai::audio()->createTranscription(array $payload): array` | `POST /v1/audio/transcriptions` |
| `Ai::audio()->streamTranscription(array $payload): iterable` | `POST /v1/audio/transcriptions` with `stream: true` |
| `Ai::audio()->createTranslation(array $payload): array` | `POST /v1/audio/translations` |
| `Ai::audio()->createVoice(array $payload): array` | `POST /v1/audio/voices` |

## Errors

Through `Ai::responses()`, invalid input (missing `file`/`text`, unknown action or voice, speed out of range,
an unsupported or oversized file) throws `InvalidArgumentException`, and a failed API call throws
`RuntimeException` whose `getPrevious()` holds the original exception. The low-level `Ai::audio()` methods
throw `FileValidationException` for unreadable files and `ApiResponseValidationException` (HTTP status as the
code) for API errors. See [Error handling](error-handling.md).
