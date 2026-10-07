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
$conversationId = Ai::conversations()->start(['topic' => 'support']);

Ai::responses()
    ->inConversation($conversationId)
    ->input()
    ->message('Remember: I like short answers')
    ->send();

$conversation = Ai::conversations()->use($conversationId);
$conversation->items(['limit' => 20]);             // list items
$conversation->item('msg_123');                    // retrieve one item
$conversation->addItems([['type' => 'message', 'role' => 'user', 'content' => 'Hi']]);
$conversation->deleteItem('msg_123');
$conversation->update(['topic' => 'billing']);     // replace metadata
$conversation->delete();
```

### Stored Responses

```php
Ai::responses()->retrieve('resp_123', ['include' => ['message.output_text.logprobs']]);
Ai::responses()->listInputItems('resp_123');
Ai::responses()->countInputTokens(['model' => 'gpt-5', 'input' => 'How long is this prompt?']);
Ai::responses()->compact(['model' => 'gpt-5', 'input' => $longHistory]);
Ai::responses()->cancel('resp_123');               // background responses
Ai::responses()->delete('resp_123');

// Resume streaming a background response after a dropped connection
foreach (Ai::responses()->resume('resp_123', startingAfter: $lastSequenceNumber) as $event) {
    // response.output_text.delta, response.completed, ...
}
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

## OpenAI API Coverage

Every endpoint of the OpenAI REST API is available through a low-level repository on the `Ai` facade.
Methods take the API's JSON body (`$payload`) and query parameters (`$params`) as arrays and return the
decoded response, so new API parameters work without a package update.

| API | Accessor | Operations |
|---|---|---|
| Responses | `Ai::responses()` | create (builder), stream, retrieve, resume stream, cancel, delete, input items, input token counts, compact |
| Conversations | `Ai::conversations()` | start, retrieve, update, delete, list/add/retrieve/delete items |
| Chat Completions | `Ai::chatCompletions()` | create, stream, list/retrieve/update/delete stored completions, list messages |
| Completions (legacy) | `Ai::completions()` | create, stream |
| Embeddings | `Ai::embeddings()` | create |
| Audio | `Ai::audio()`, `Ai::diarize()` | speech (+ SSE), transcriptions (+ streaming, diarization), translations, custom voices |
| Images | `Ai::images()` | generate (+ stream), edit (+ stream), variations |
| Videos (Sora) | `Ai::videos()` | create, retrieve, list, delete, remix, edit, extend, download content, characters |
| Models | `Ai::models()` | list, retrieve, delete fine-tuned models |
| Moderations | `Ai::moderations()` | create |
| Files | `Ai::files()` | upload (with expiration), list, retrieve, delete, content |
| Uploads | `Ai::uploads()` | create, add part, complete, cancel, `uploadFile()` helper for large files |
| Batches | `Ai::batches()` | create, retrieve, list, cancel |
| Vector stores | `Ai::vectorStores()`, `Ai::vectorStoreFiles()`, `Ai::vectorStoreFileBatches()` | CRUD, search, files, file batches |
| Containers | `Ai::containers()`, `Ai::containerFiles()` | create, retrieve, list, delete, files and file content |
| Fine-tuning | `Ai::fineTuningJobs()`, `Ai::fineTuningCheckpointPermissions()`, `Ai::graders()` | jobs (create, list, retrieve, cancel, pause, resume, events, checkpoints), checkpoint permissions, run/validate graders |
| Evals | `Ai::evals()`, `Ai::evalRuns()` | evals CRUD, runs (create, list, retrieve, cancel, delete), output items |
| Realtime | `Ai::realtime()` | client secrets, WebRTC calls (`createCall()`), SIP accept/reject/refer/hangup, translation client secrets, transcription sessions |
| Live | `Ai::live()` | sessions, accept/reject/refer/hangup, fork, recordings |
| Webhook endpoints | `Ai::webhookEndpoints()` | CRUD, rotate secret, send test event, event types |
| Skills | `Ai::skills()` | CRUD, content, versions |
| Decisions | `Ai::decisions()` | create |
| Content provenance | `Ai::contentProvenanceChecks()` | create |
| Safety | `Ai::safety()` | alerts, cases |
| ChatKit (beta) | `Ai::chatKit()` | sessions, threads, thread items |
| Agents (beta) | `Ai::agents()`, `Ai::agentSessions()`, `Ai::agentEnvironments()`, `Ai::vaults()` | agents, sessions (+ streaming, events, turns, subagents, items, traces, artifacts), environments, templates, vaults and credentials |
| Administration | `Ai::admin()->…` | admin API keys, audit logs, certificates, data retention, external storage, groups, invites, projects (users, groups, service accounts, API keys, rate limits, permissions, roles, spend), roles, spend alerts/limits, usage and costs, users |
| Assistants (deprecated) | `Ai::assistants()` | CRUD — OpenAI deprecated the Assistants API (shutdown announced for August 26, 2026); use Responses + Conversations |

Return values:
- JSON endpoints return the decoded body as an array (list endpoints return the page: `data`, `has_more`, `last_id`; pass `after` to paginate).
- Binary downloads (file, video, artifact and recording content) return `['content' => ..., 'content_type' => ...]`.
- Streaming methods (`stream*`) return iterables of decoded Server-Sent Events.

```php
// Embeddings
$vectors = Ai::embeddings()->create(['model' => 'text-embedding-3-small', 'input' => ['Laravel', 'Symfony']]);

// Chat Completions streaming
foreach (Ai::chatCompletions()->stream(['model' => 'gpt-5', 'messages' => [['role' => 'user', 'content' => 'Hi']]]) as $chunk) {
    echo $chunk['choices'][0]['delta']['content'] ?? '';
}

// Images (gpt-image) with partial image streaming
$image = Ai::images()->generate(['model' => 'gpt-image-1', 'prompt' => 'A lighthouse at dawn', 'size' => '1024x1024']);
$edit = Ai::images()->edit(['model' => 'gpt-image-1', 'image' => storage_path('in.png'), 'prompt' => 'Add snow']);

// Videos
$video = Ai::videos()->create(['model' => 'sora-2', 'prompt' => 'A drone shot over Lagos at sunset', 'seconds' => '8']);
$mp4 = Ai::videos()->downloadContent($video['id']);
file_put_contents(storage_path('video.mp4'), $mp4['content']);

// Large files (up to 8 GB) via the Uploads API
$upload = Ai::uploads()->uploadFile(storage_path('training.jsonl'), 'fine-tune');
$job = Ai::fineTuningJobs()->create(['model' => 'gpt-4.1-mini', 'training_file' => $upload['file']['id']]);

// Evals
$eval = Ai::evals()->create(['name' => 'Support answers', 'data_source_config' => [...], 'testing_criteria' => [...]]);
$run = Ai::evalRuns()->create($eval['id'], ['data_source' => [...]]);

// Realtime: mint an ephemeral key for a browser client, or create a WebRTC call server-side
$secret = Ai::realtime()->createClientSecret(['session' => ['type' => 'realtime', 'model' => 'gpt-realtime']]);
$call = Ai::realtime()->createCall($sdpOffer, ['type' => 'realtime', 'model' => 'gpt-realtime']); // ['sdp' => ..., 'call_id' => ...]

// Vector store search
$hits = Ai::vectorStores()->search('vs_123', ['query' => 'refund policy', 'max_num_results' => 5]);

// Agents (beta)
$session = Ai::agentSessions()->create([
    'agent_id' => 'agent_123',
    'environment' => ['type' => 'openai_hosted'],
    'input' => 'Summarise the attached report',
]);
foreach (Ai::agentSessions()->streamEvents($session['id']) as $event) {
    // session events
}

// Administration API (requires an Admin API key)
$projects = Ai::admin()->projects()->list(['limit' => 20]);
$costs = Ai::admin()->usage()->costs(['start_time' => now()->subDays(7)->timestamp]);

// Anything else: call any endpoint directly through the transport
$result = app(\CreativeCrafts\LaravelAiAssistant\Transport\OpenAITransport::class)
    ->request('GET', '/v1/some/new/endpoint', ['query' => ['limit' => 10]]);
```

Optional settings:

```env
OPENAI_ADMIN_KEY=sk-admin-...   # Administration API and fine-tuning checkpoint permissions
OPENAI_PROJECT=proj_...         # sent as the OpenAI-Project header
```

---

## Webhooks

The webhook route verifies OpenAI's signed deliveries (Standard Webhooks: `webhook-id`, `webhook-timestamp`,
`webhook-signature`). Use the `whsec_...` signing secret shown when you create the endpoint in the OpenAI
dashboard or with `Ai::webhookEndpoints()->create(...)`:

```env
AI_WEBHOOKS_ENABLED=true
AI_WEBHOOKS_SIGNING_SECRET=whsec_...
```

Every verified event dispatches `OpenAiWebhookReceived`; response events also update the response status
store and dispatch `ResponseCompleted` / `ResponseFailed`:

```php
use CreativeCrafts\LaravelAiAssistant\Events\OpenAiWebhookReceived;

Event::listen(function (OpenAiWebhookReceived $event) {
    match ($event->type) {
        'batch.completed' => ProcessBatchResults::dispatch($event->data['id']),
        'fine_tuning.job.succeeded' => NotifyModelReady::dispatch($event->data['id']),
        'realtime.call.incoming' => AnswerCall::dispatch($event->data['call_id'] ?? null),
        default => null,
    };
});
```

The package's original HMAC scheme (`X-OpenAI-Signature` / `X-OpenAI-Timestamp` headers) is still accepted for
your own senders; set `AI_WEBHOOKS_REQUIRE_TIMESTAMP=true` to enforce replay protection there.

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
