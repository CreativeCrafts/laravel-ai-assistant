# Streaming

Streaming shows the answer while it is being generated instead of after the whole response is ready.
This guide covers streaming in PHP, to the browser (Inertia + React), and over websockets with Laravel Reverb.

## The event format

Streams yield normalised Responses API events. The ones you will use most:

| `type` | `data` | Meaning |
|---|---|---|
| `response.created` | `['response' => [...]]` | Generation started |
| `response.output_text.delta` | `['delta' => 'Hel', 'accumulated' => 'Hel', 'typing' => true, ...]` | New text |
| `response.output_text.done` | `['text' => 'Hello!', ...]` (the API's own payload) | Text finished |
| `response.completed` | `['response' => [...]]`, including `usage` | Done (`isFinal` is `true`) |
| `response.failed` | `['response' => [...]]` with the error | Failed (`isFinal` is `true`) |

A `response.canceled` event throws `ResponseCanceledException`.

## Streaming in PHP

### `Ai::stream()`

Yields `StreamingEventDto` objects (`$event->type`, `$event->data`, `$event->isFinal`):

```php
use CreativeCrafts\LaravelAiAssistant\Facades\Ai;

foreach (Ai::stream('Tell me a short story about Laravel') as $event) {
    if ($event->type === 'response.output_text.delta') {
        echo $event->data['delta'];
    }
}
```

### Unified builder

`ResponsesBuilder::stream()` yields the same events as plain arrays and sends the same input and settings as
`send()` would. `input()->message()` returns the input builder, so keep the builder in a variable and call
`stream()` on it:

```php
$builder = Ai::responses()
    ->model('gpt-5-mini')
    ->instructions('Answer in markdown.');

$builder->input()->message('Explain Laravel service providers');

foreach ($builder->stream() as $event) {
    if ($event['type'] === 'response.output_text.delta') {
        echo $event['data']['delta'];
    }
}
```

`withMessages()`, `inputItems()` and `input()->imageInput()` work the same way. `inputItems()` also offers
`appendUserImageUrl($url)`, `appendUserImageId($fileId)` and `appendRaw($item)`.

### Chat sessions

```php
$session = Ai::chat('Write a limerick about queues')->instructions('Be funny.');

foreach ($session->stream() as $event) {               // StreamingEventDto
    if ($event->type === 'response.output_text.delta') {
        echo $event->data['delta'];
    }
}
```

`streamText(callable $onTextChunk)` yields only the text chunks, but it currently reads the whole stream
before yielding, so use `stream()` when you need tokens as they arrive.

### Callbacks and stopping early

Every stream method accepts two optional callables:

```php
$stream = Ai::stream(
    'Write a long essay about PHP',
    onEvent: function ($event) {
        // called for every event: log, broadcast, update progress...
    },
    shouldStop: fn () => connection_aborted() === 1 || Cache::get("stop:{$id}"),
);
```

`shouldStop` is checked after each event; returning `true` stops reading the stream.

### Resume a dropped stream

For background responses, reconnect from the last event you processed:

```php
foreach (Ai::responses()->resume('resp_123', startingAfter: 42) as $event) {
    // raw Responses API events, including sequence_number
}
```

## Streaming to the browser with Inertia + React

### Option A: streamed text with `useStream` (simplest)

Controller (POST route, so the prompt can be large and CSRF-protected):

```php
// routes/web.php
Route::post('/chat/stream', ChatStreamController::class)->middleware('auth')->name('chat.stream');
```

```php
namespace App\Http\Controllers;

use CreativeCrafts\LaravelAiAssistant\Facades\Ai;
use Illuminate\Http\Request;

class ChatStreamController extends Controller
{
    public function __invoke(Request $request)
    {
        $validated = $request->validate(['message' => ['required', 'string', 'max:4000']]);

        return response()->stream(function () use ($validated) {
            $events = Ai::stream(
                $validated['message'],
                shouldStop: fn () => connection_aborted() === 1,
            );

            foreach ($events as $event) {
                if ($event->type === 'response.output_text.delta') {
                    echo $event->data['delta'];
                    if (ob_get_level() > 0) {
                        ob_flush();
                    }
                    flush();
                }
            }
        }, 200, [
            'Content-Type' => 'text/plain; charset=utf-8',
            'Cache-Control' => 'no-cache',
            'X-Accel-Buffering' => 'no',
        ]);
    }
}
```

React component using Laravel's [`@laravel/stream-react`](https://www.npmjs.com/package/@laravel/stream-react)
hook (`npm install @laravel/stream-react`):

```tsx
import { useState, type FormEvent } from 'react';
import { useStream } from '@laravel/stream-react';
import { Button } from '@/components/ui/button';
import { Textarea } from '@/components/ui/textarea';

export default function Chat() {
    const [message, setMessage] = useState('');
    const { data, isFetching, isStreaming, send, cancel } = useStream('/chat/stream');

    const submit = (e: FormEvent) => {
        e.preventDefault();
        send({ message });
        setMessage('');
    };

    return (
        <div className="mx-auto max-w-2xl space-y-4 p-6">
            <div className="min-h-32 whitespace-pre-wrap rounded-lg border p-4 text-sm">
                {data || (isFetching ? 'Thinking…' : 'Ask me anything about Laravel.')}
            </div>
            <form onSubmit={submit} className="flex gap-2">
                <Textarea value={message} onChange={(e) => setMessage(e.target.value)} />
                {isStreaming ? (
                    <Button type="button" variant="outline" onClick={cancel}>Stop</Button>
                ) : (
                    <Button type="submit" disabled={!message.trim()}>Send</Button>
                )}
            </form>
        </div>
    );
}
```

### Option B: Server-Sent Events with `StreamedAiResponse`

`StreamedAiResponse::fromGenerator()` turns a generator of `['type' => ..., 'data' => string]` items into a
`text/event-stream` response, sends keep-alive comments and finishes with `event: done`:

```php
use CreativeCrafts\LaravelAiAssistant\Facades\Ai;
use CreativeCrafts\LaravelAiAssistant\Http\Responses\StreamedAiResponse;

Route::get('/chat/sse', function (Request $request) {
    $events = (function () use ($request) {
        foreach (Ai::stream((string) $request->query('q')) as $event) {
            if ($event->type === 'response.output_text.delta') {
                yield ['type' => 'delta', 'data' => $event->data['delta']];
            } elseif ($event->type === 'response.completed') {
                yield ['type' => 'completed', 'data' => json_encode($event->data['response']['usage'] ?? [])];
            }
        }
    })();

    return StreamedAiResponse::fromGenerator($events, heartbeatSeconds: 15);
})->middleware('auth');
```

Consume it with `useEventStream` from `@laravel/stream-react`, or a plain `EventSource`:

```ts
const source = new EventSource(`/chat/sse?q=${encodeURIComponent(question)}`);
source.addEventListener('delta', (e) => setAnswer((a) => a + (e as MessageEvent).data));
source.addEventListener('done', () => source.close());
```

> Behind Nginx, keep `X-Accel-Buffering: no` (set automatically by `StreamedAiResponse`). With PHP-FPM make
> sure output buffering and compression don't hold the response back.

## Streaming over websockets with Laravel Reverb

Websockets are a good fit when generation runs in a queue (so web workers stay free) or several users watch
the same answer. Stream in a job and broadcast each chunk:

```php
namespace App\Events;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;

class AiTokenStreamed implements ShouldBroadcastNow
{
    use InteractsWithSockets;

    public function __construct(
        public string $chatId,
        public string $delta,
        public bool $done = false,
    ) {}

    public function broadcastOn(): PrivateChannel
    {
        return new PrivateChannel("chats.{$this->chatId}");
    }
}
```

```php
namespace App\Jobs;

use App\Events\AiTokenStreamed;
use CreativeCrafts\LaravelAiAssistant\Facades\Ai;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class StreamAiAnswer implements ShouldQueue
{
    use Queueable;

    public int $timeout = 300;

    public function __construct(public string $chatId, public string $prompt) {}

    public function handle(): void
    {
        $buffer = '';

        foreach (Ai::stream($this->prompt) as $event) {
            if ($event->type !== 'response.output_text.delta') {
                continue;
            }

            // Batch small deltas to keep the number of websocket messages reasonable
            $buffer .= $event->data['delta'];
            if (mb_strlen($buffer) >= 40) {
                broadcast(new AiTokenStreamed($this->chatId, $buffer));
                $buffer = '';
            }
        }

        broadcast(new AiTokenStreamed($this->chatId, $buffer, done: true));
    }
}
```

```php
// routes/channels.php
Broadcast::channel('chats.{chatId}', fn ($user, string $chatId) => $user->chats()->whereKey($chatId)->exists());
```

React (with [`@laravel/echo-react`](https://www.npmjs.com/package/@laravel/echo-react)):

```tsx
import { useState } from 'react';
import { useEcho } from '@laravel/echo-react';

type AiTokenStreamed = { delta: string; done: boolean };

export function LiveAnswer({ chatId }: { chatId: string }) {
    const [answer, setAnswer] = useState('');
    const [done, setDone] = useState(false);

    useEcho<AiTokenStreamed>(`chats.${chatId}`, 'AiTokenStreamed', (e) => {
        setAnswer((current) => current + e.delta);
        if (e.done) setDone(true);
    });

    return <p className="whitespace-pre-wrap">{answer}{!done && <span className="animate-pulse">▍</span>}</p>;
}
```

The same pattern works for live [speaker diarization](speaker-diarization.md#streaming-segments) segments.

## Streaming other endpoints

Low-level repositories expose streaming methods. All of them yield decoded event arrays except
`streamResponse()`, which yields raw SSE lines: wrap it with
`CreativeCrafts\LaravelAiAssistant\Support\ServerSentEvents::decode()` to get arrays.

| Method | Events |
|---|---|
| `app(ResponsesRepositoryContract::class)->streamResponse($payload)` | Raw SSE lines of Responses API events |
| `Ai::chatCompletions()->stream($payload)` | Chat completion chunks |
| `Ai::completions()->stream($payload)` | Legacy completion chunks |
| `Ai::audio()->streamSpeech($payload)` | `speech.audio.delta` / `speech.audio.done` |
| `Ai::audio()->streamTranscription($payload)` | `transcript.text.delta` / `transcript.text.done` |
| `Ai::images()->streamGeneration($payload)` / `streamEdit($payload)` | `image_generation.partial_image`, … |
| `Ai::agentSessions()->stream($payload)` / `streamEvents($id)` | Agent session events |

```php
foreach (Ai::chatCompletions()->stream([
    'model' => 'gpt-5-mini',
    'messages' => [['role' => 'user', 'content' => 'Count to five']],
]) as $chunk) {
    echo $chunk['choices'][0]['delta']['content'] ?? '';
}
```
