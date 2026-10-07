# Videos (Sora)

`Ai::videos()` wraps OpenAI's Videos API. Video generation is asynchronous: you create a job, wait for it to
finish (poll or use a [webhook](webhooks.md)), then download the content.

## Create and download a video

```php
use CreativeCrafts\LaravelAiAssistant\Facades\Ai;

$video = Ai::videos()->create([
    'model' => 'sora-2',
    'prompt' => 'A slow drone shot over Lagos at sunset, cinematic',
    'seconds' => '8',
    'size' => '1280x720',
]);

// $video['status'] is 'queued' or 'in_progress'
```

### Poll from a queued job

```php
namespace App\Jobs;

use App\Models\VideoRequest;
use CreativeCrafts\LaravelAiAssistant\Facades\Ai;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Storage;

class CollectVideo implements ShouldQueue
{
    use Queueable;

    public int $tries = 60;

    public function __construct(public VideoRequest $request) {}

    public function handle(): void
    {
        $video = Ai::videos()->retrieve($this->request->openai_video_id);

        match ($video['status']) {
            'completed' => $this->store(),
            'failed' => $this->request->update(['status' => 'failed', 'error' => $video['error']['message'] ?? null]),
            default => $this->release(15),   // check again in 15 seconds
        };
    }

    private function store(): void
    {
        $mp4 = Ai::videos()->downloadContent($this->request->openai_video_id);
        Storage::disk('s3')->put("videos/{$this->request->id}.mp4", $mp4['content']);

        // Thumbnails and sprites are available as variants
        $thumb = Ai::videos()->downloadContent($this->request->openai_video_id, ['variant' => 'thumbnail']);
        Storage::disk('s3')->put("videos/{$this->request->id}.webp", $thumb['content']);

        $this->request->update(['status' => 'ready']);
    }
}
```

Prefer webhooks? Listen for `video.completed` with the `OpenAiWebhookReceived` event (see [Webhooks](webhooks.md)).

## Start from a reference image

```php
$video = Ai::videos()->create([
    'model' => 'sora-2',
    'prompt' => 'The character waves and walks out of frame',
    'input_reference' => $request->file('reference'),   // path, UploadedFile or stream
]);
```

## Remix, edit and extend

```php
// Remix a finished video with a new prompt
Ai::videos()->remix('video_123', ['prompt' => 'Same scene, but at night with neon lights']);

// Edit or extend (multipart, `video` accepts a file)
Ai::videos()->edit(['model' => 'sora-2', 'video' => storage_path('app/clip.mp4'), 'prompt' => 'Make it snow']);
Ai::videos()->extend(['model' => 'sora-2', 'video' => storage_path('app/clip.mp4'), 'prompt' => 'Continue the scene']);
```

## Characters

```php
$character = Ai::videos()->createCharacter([
    'name' => 'Mascot',
    'video' => storage_path('app/mascot-turnaround.mp4'),
]);

Ai::videos()->retrieveCharacter($character['id']);
```

## Manage videos

```php
Ai::videos()->list(['limit' => 20, 'order' => 'desc']);
Ai::videos()->retrieve('video_123');
Ai::videos()->delete('video_123');
```

## Methods

| Method | Endpoint |
|---|---|
| `create(array $payload)` | `POST /v1/videos` (multipart, `input_reference`) |
| `retrieve(string $videoId)` | `GET /v1/videos/{id}` |
| `list(array $params = [])` | `GET /v1/videos` |
| `delete(string $videoId)` | `DELETE /v1/videos/{id}` |
| `remix(string $videoId, array $payload)` | `POST /v1/videos/{id}/remix` |
| `downloadContent(string $videoId, array $params = [])` | `GET /v1/videos/{id}/content` → `['content', 'content_type']` |
| `edit(array $payload)` | `POST /v1/videos/edits` |
| `extend(array $payload)` | `POST /v1/videos/extensions` |
| `createCharacter(array $payload)` | `POST /v1/videos/characters` |
| `retrieveCharacter(string $characterId)` | `GET /v1/videos/characters/{id}` |
