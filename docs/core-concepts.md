# Core concepts

The package gives you two layers. Knowing which one you are using makes everything else predictable.

## Layer 1: high-level builders

Fluent, Laravel-flavoured APIs that return typed DTOs.

| Entry point | Returns | Use it for |
|---|---|---|
| `Ai::responses()` | `ResponsesBuilder` → `ResponseDto` / `ChatResponseDto` | Text, audio and image generation through one builder ([guide](responses.md)) |
| `Ai::chat($message)` | `ChatSession` → `ChatResponseDto` | Tool calling, file search, structured JSON ([guide](chat-sessions-and-tools.md)) |
| `Ai::quick($message)` | `ChatResponseDto` | One-off questions |
| `Ai::stream($message)` | `Generator<StreamingEventDto>` | Streaming text ([guide](streaming.md)) |
| `Ai::conversations()` | `ConversationsBuilder` | Multi-turn state stored by OpenAI ([guide](conversations.md)) |
| `Ai::diarize($file)` | `DiarizationBuilder` → `DiarizedTranscription` | Speaker diarization ([guide](speaker-diarization.md)) |

## Layer 2: low-level API repositories

One repository per OpenAI API, mirroring the REST reference one-to-one. Every method takes the API's JSON
body as `$payload` and its query string as `$params`, and returns the decoded JSON as an array. Because the
payload is passed through untouched, **new OpenAI parameters work without a package update**.

```php
use CreativeCrafts\LaravelAiAssistant\Facades\Ai;

$result = Ai::embeddings()->create([
    'model' => 'text-embedding-3-small',
    'input' => 'The quick brown fox',
]);

$vector = $result['data'][0]['embedding'];
```

| Area | Accessors |
|---|---|
| Text | `responses()`, `conversations()`, `chatCompletions()`, `completions()` |
| Audio & vision | `audio()`, `diarize()`, `images()`, `videos()` |
| Knowledge | `embeddings()`, `files()`, `uploads()`, `vectorStores()`, `vectorStoreFiles()`, `vectorStoreFileBatches()` |
| Jobs & training | `batches()`, `fineTuningJobs()`, `fineTuningCheckpointPermissions()`, `graders()`, `evals()`, `evalRuns()` |
| Models & safety | `models()`, `moderations()`, `safety()`, `decisions()`, `contentProvenanceChecks()` |
| Realtime | `realtime()`, `realtimeSessions()`, `live()` |
| Agents & tools | `agents()`, `agentSessions()`, `agentEnvironments()`, `vaults()`, `skills()`, `containers()`, `containerFiles()`, `chatKit()` |
| Platform | `webhookEndpoints()`, `admin()` |
| Deprecated | `assistants()` (OpenAI is shutting the Assistants API down; use Responses + Conversations) |

The full list of methods is in the [API reference](api-reference.md).

### Dependency injection

Each accessor resolves a contract from the container, so you can type-hint it instead of using the facade
(and swap it for a fake in tests, see [Testing](testing.md)):

```php
use CreativeCrafts\LaravelAiAssistant\Contracts\ModerationsRepositoryContract;

class CommentModerator
{
    public function __construct(private ModerationsRepositoryContract $moderations) {}

    public function isFlagged(string $text): bool
    {
        $result = $this->moderations->create(['model' => 'omni-moderation-latest', 'input' => $text]);

        return (bool) ($result['results'][0]['flagged'] ?? false);
    }
}
```

## Return values

| Endpoint kind | What you get back |
|---|---|
| JSON | The decoded body as an `array` |
| List | The page: `['object' => 'list', 'data' => [...], 'has_more' => bool, 'first_id' => ..., 'last_id' => ...]` |
| Delete | Usually the API's deletion object (`['id' => ..., 'deleted' => true]`); a few older endpoints (files, vector stores, responses, conversations) return `bool` |
| Binary download | `['content' => <bytes>, 'content_type' => 'video/mp4']` |
| Streaming (`stream*`) | An `iterable` of decoded Server-Sent Events (arrays) |

### Paginating list endpoints

```php
$after = null;

do {
    $page = Ai::files()->list(array_filter(['limit' => 100, 'after' => $after]));

    foreach ($page['data'] as $file) {
        // ...
    }

    $after = $page['last_id'] ?? null;
} while ($page['has_more'] ?? false);
```

A reusable lazy collection works nicely for large accounts:

```php
use Illuminate\Support\LazyCollection;

function allBatches(): LazyCollection
{
    return LazyCollection::make(function () {
        $after = null;
        do {
            $page = Ai::batches()->list(array_filter(['limit' => 100, 'after' => $after]));
            yield from $page['data'];
            $after = $page['last_id'] ?? null;
        } while ($page['has_more'] ?? false);
    });
}

allBatches()->where('status', 'completed')->each(fn (array $batch) => /* ... */ null);
```

### Saving binary content

```php
$file = Ai::files()->content('file_abc123');
Storage::put('exports/results.jsonl', $file['content']);

$video = Ai::videos()->downloadContent('video_abc123');
Storage::disk('s3')->put('videos/clip.mp4', $video['content']);
```

## File parameters

Wherever an endpoint accepts a file (`file`, `image`, `mask`, `video`, `input_reference`, `audio_sample`,
`files`, …) you can pass:

- a local path: `storage_path('app/photo.png')`
- an `SplFileInfo`, including Laravel's `UploadedFile` (`$request->file('photo')`)
- an open stream resource: `fopen($path, 'r')` or `Storage::readStream($path)`
- a PSR-7 stream
- an explicit part: `['contents' => $bytes, 'filename' => 'photo.png', 'content_type' => 'image/png']`
- a list of any of the above when the endpoint accepts several files

```php
Ai::images()->edit([
    'model' => 'gpt-image-1',
    'image' => [$request->file('product'), storage_path('app/logo.png')],
    'prompt' => 'Place the logo on the product packaging',
]);
```

## Escape hatch: call any endpoint directly

If OpenAI ships an endpoint before the package has a repository for it, use the transport directly. You still
get authentication, retries and error handling:

```php
use CreativeCrafts\LaravelAiAssistant\Transport\OpenAITransport;

$transport = app(OpenAITransport::class);

$result = $transport->request('GET', '/v1/some/new/endpoint', ['query' => ['limit' => 10]]);
$result = $transport->request('POST', '/v1/some/new/endpoint', ['json' => ['foo' => 'bar']]);

foreach ($transport->streamRequest('POST', '/v1/some/stream', ['json' => ['stream' => true]]) as $line) {
    // raw SSE lines
}
```

`$options` accepts `query`, `json`, `multipart`, `body`, `headers`, `timeout` and `idempotent`.
