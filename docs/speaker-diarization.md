# Speaker diarization (who spoke when)

`Ai::diarize()` transcribes a conversation and labels every segment with its speaker, using OpenAI's
`gpt-4o-transcribe-diarize` model. Typical uses: call-centre analytics, meeting minutes, interview
transcripts, podcast captions.

## Quick start

```php
use CreativeCrafts\LaravelAiAssistant\Facades\Ai;

$result = Ai::diarize(storage_path('app/calls/support-call.mp3'))->send();

$result->speakers();        // ['A', 'B']
echo $result->toTranscript();
// A: Thanks for calling Acme, how can I help?
// B: Hi, I was charged twice this month.
```

Speakers are labelled `A`, `B`, `C`, … automatically.

## Naming speakers

### With reference samples (known speakers)

Give a short (2–10 second) sample of up to **four** known voices and segments come back with those names:

```php
$result = Ai::diarize(storage_path('app/calls/support-call.mp3'))
    ->knownSpeaker('agent', storage_path('app/voices/agent.wav'))
    ->knownSpeaker('customer', storage_path('app/voices/customer.wav'))
    ->language('en')
    ->send();

// Or all at once
Ai::diarize($path)->knownSpeakers([
    'agent' => storage_path('app/voices/agent.wav'),
    'customer' => storage_path('app/voices/customer.wav'),
]);
```

### Renaming afterwards

```php
$named = Ai::diarize($path)->send()->renameSpeakers(['A' => 'Agent', 'B' => 'Customer']);
```

## Input sources

```php
Ai::diarize(storage_path('app/call.mp3'))->send();             // local path
Ai::diarize($request->file('recording'))->send();               // UploadedFile (original filename is kept)
Ai::diarize()->fromDisk('s3', 'calls/2026/10/call-123.ogg')->send();  // any filesystem disk
Ai::diarize($stream, 'call.webm')->send();                      // stream resource + filename
```

Supported formats: `flac`, `mp3`, `mp4`, `mpeg`, `mpga`, `m4a`, `ogg`, `wav`, `webm` (25 MB max).

## Builder options

| Method | Description |
|---|---|
| `file(mixed $file, ?string $filename = null)` | Path, `SplFileInfo`/`UploadedFile` or stream |
| `fromDisk(string $disk, string $path)` | Read from a Laravel filesystem disk |
| `model(string $model)` | Defaults to `gpt-4o-transcribe-diarize` |
| `language(string $language)` | ISO-639-1 code |
| `temperature(float $temperature)` | `0`–`1` |
| `knownSpeaker(string $name, string\|SplFileInfo $sample)` / `knownSpeakers(array $speakers)` | Up to 4 reference samples |
| `autoChunking()` | Server-side chunking (default; recordings over 30 s are chunked automatically) |
| `serverVad(?float $threshold, ?int $prefixPaddingMs, ?int $silenceDurationMs)` | Tune voice activity detection |
| `withOptions(array $options)` | Any extra API parameters |
| `toPayload()` | Inspect the request that would be sent |
| `send(): DiarizedTranscription` | Run it |
| `stream(?callable $onDelta = null): Generator` | Yield segments as they are recognised |

## Working with the result: `DiarizedTranscription`

```php
$result->text;                    // full transcript text
$result->segments;                // list of DiarizedSegment (id, speaker, start, end, text)
$result->duration;                // seconds, when reported
$result->usage;                   // token/seconds usage

$result->speakers();              // ['agent', 'customer']
$result->speakerCount();          // 2
$result->hasSpeaker('agent');     // true
$result->segmentsFor('customer'); // DiarizedSegment[]
$result->textFor('customer');     // everything the customer said
$result->speakingTime();          // ['agent' => 63.2, 'customer' => 41.0] (seconds)
$result->speakingTimeFor('agent');// 63.2
$result->speakingShare();         // ['agent' => 60.65, 'customer' => 39.35] (%)
$result->dominantSpeaker();       // 'agent'
$result->turns();                 // consecutive segments merged per speaker
$result->toTranscript();          // "agent: Thanks for calling..."
$result->toTranscript(true);      // "[00:00:01.200 - 00:00:04.500] agent: Thanks for calling..."
$result->toWebVtt();              // WebVTT captions with <v speaker> voice tags
$result->toArray();               // serialisable summary
```

Each `DiarizedSegment` has `id`, `speaker`, `start`, `end`, `text` and a `duration()` helper.

## Recipes

### Call-centre analytics in a queued job

```php
namespace App\Jobs;

use App\Models\Call;
use CreativeCrafts\LaravelAiAssistant\Facades\Ai;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class AnalyseCall implements ShouldQueue
{
    use Queueable;

    public int $timeout = 600;

    public function __construct(public Call $call) {}

    public function handle(): void
    {
        $result = Ai::diarize()
            ->fromDisk('s3', $this->call->recording_path)
            ->knownSpeaker('agent', storage_path("app/voices/{$this->call->agent_id}.wav"))
            ->language('en')
            ->send();

        $this->call->update([
            'transcript' => $result->toTranscript(true),
            'agent_talk_share' => $result->speakingShare()['agent'] ?? 0,
            'customer_said' => $result->textFor('A'),   // unknown voices keep automatic labels
            'segments' => $result->toArray()['segments'],
        ]);
    }
}
```

### Captions for a video player

```php
Storage::put("captions/{$video->id}.vtt", Ai::diarize($video->audioPath())->send()->toWebVtt());
```

```html
<video controls src="/videos/1.mp4">
    <track kind="captions" src="/captions/1.vtt" srclang="en" label="English" default>
</video>
```

### Summarise who said what

```php
$result = Ai::diarize($path)->send()->renameSpeakers(['A' => 'Ada', 'B' => 'Tunde']);

$summary = Ai::responses()
    ->model('gpt-5-mini')
    ->instructions('Summarise the meeting. List decisions and action items with the owner\'s name.')
    ->input()
    ->message($result->toTranscript())
    ->send()
    ->text;
```

## Streaming segments

Segments are yielded as soon as they are final; the generator's return value is the complete transcription:

```php
$stream = Ai::diarize($path)->stream(
    onDelta: fn (string $text, ?string $segmentId) => /* partial text while a segment is spoken */ null,
);

foreach ($stream as $segment) {
    broadcast(new SpeakerSegmentRecognised($meeting->id, $segment->speaker, $segment->text));
}

$transcription = $stream->getReturn(); // DiarizedTranscription
```

See [Streaming](streaming.md#streaming-over-websockets-with-laravel-reverb) for the Reverb broadcasting setup.

## Through the unified builder

```php
$response = Ai::responses()
    ->input()
    ->audio([
        'file' => storage_path('app/calls/support-call.mp3'),
        'action' => 'diarize',                       // or 'transcribe' + 'diarize' => true
        'known_speakers' => ['agent' => storage_path('app/voices/agent.wav')],
    ])
    ->send();

$response->metadata['speakers'];            // ['agent', 'A', ...]
$response->diarization()?->toTranscript();  // DiarizedTranscription helpers
```

## Errors

- `InvalidArgumentException`: invalid option (temperature or VAD threshold out of range, empty speaker
  name, more than four known speakers)
- `FileValidationException`: file, disk path or reference sample missing, unreadable or of an unsupported type
- `AudioTranscriptionException`: no file was given, or the API/stream reported an error
