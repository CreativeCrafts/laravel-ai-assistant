# Webhooks

OpenAI can notify your app when background work finishes (responses, batches, fine-tuning jobs, evals,
videos, incoming realtime calls, …). The package ships a route that verifies the signature and turns every
delivery into a Laravel event.

## 1. Create the endpoint in OpenAI

Either in the OpenAI dashboard (Settings → Webhooks) or from code:

```php
use CreativeCrafts\LaravelAiAssistant\Facades\Ai;

$endpoint = Ai::webhookEndpoints()->create([
    'url' => route('ai-assistant.webhook'),
    'events' => ['response.completed', 'response.failed', 'batch.completed', 'fine_tuning.job.succeeded'],
]);

$endpoint['secret']; // whsec_... (shown once, store it in .env)
```

## 2. Enable the route

```env
AI_WEBHOOKS_ENABLED=true
AI_WEBHOOKS_SIGNING_SECRET=whsec_...
# optional
AI_WEBHOOKS_PATH=/ai-assistant/webhook
AI_WEBHOOKS_MAX_SKEW_SECONDS=300
```

This registers `POST /ai-assistant/webhook` (named `ai-assistant.webhook`). It is outside the `web` group, so
no CSRF exemption is needed. Deliveries are verified with the Standard Webhooks scheme (`webhook-id`,
`webhook-timestamp`, `webhook-signature` headers); invalid signatures get `401`, stale timestamps are rejected.

## 3. Listen for events

Every verified delivery dispatches `OpenAiWebhookReceived`:

```php
use CreativeCrafts\LaravelAiAssistant\Events\OpenAiWebhookReceived;
use Illuminate\Support\Facades\Event;

// AppServiceProvider::boot()
Event::listen(function (OpenAiWebhookReceived $event) {
    match ($event->type) {
        'batch.completed' => ImportBatchResults::dispatch($event->data['id']),
        'fine_tuning.job.succeeded' => NotifyModelReady::dispatch($event->data['id']),
        'eval.run.succeeded' => ReportEvalResults::dispatch($event->data['id']),
        'video.completed' => CollectVideo::dispatch($event->data['id']),
        'realtime.call.incoming' => AnswerCall::dispatch($event->data['call_id'] ?? null),
        default => null,
    };
});
```

| Property | Description |
|---|---|
| `$event->type` | Event type, e.g. `batch.completed` |
| `$event->data` | The event's `data` object (usually contains the resource `id`) |
| `$event->payload` | The full delivery |
| `$event->eventId` | The delivery's event id (`evt_...`), useful for de-duplication |

Webhooks can be delivered more than once. Make handlers idempotent, for example:

```php
if (! Cache::add("openai-webhook:{$event->eventId}", true, now()->addDay())) {
    return; // already handled
}
```

### Response events

`response.*` events additionally update the response status store and dispatch typed events:

| Webhook | Laravel event |
|---|---|
| `response.completed` | `ResponseCompleted($responseId, $payload)` |
| `response.failed` | `ResponseFailed($responseId, $error, $payload)` |
| `response.cancelled`, `response.incomplete` | status store only |

```php
use CreativeCrafts\LaravelAiAssistant\Events\ResponseCompleted;
use CreativeCrafts\LaravelAiAssistant\Facades\Ai;

Event::listen(function (ResponseCompleted $event) {
    $response = Ai::responses()->retrieve($event->responseId);
    $report = Report::where('openai_response_id', $event->responseId)->firstOrFail();

    $report->update([
        'status' => 'ready',
        'body' => collect($response['output'])->flatMap(fn ($i) => $i['content'] ?? [])->pluck('text')->implode("\n"),
    ]);
});
```

Check the last known status of a response without calling the API:

```php
use CreativeCrafts\LaravelAiAssistant\Services\ResponseStatusStore;

app(ResponseStatusStore::class)->getLastStatus('resp_123');   // 'completed', 'failed', ...
```

## Testing your webhook locally

Expose your app with a tunnel (e.g. `ngrok http 8000` or Laravel Herd's sharing), point the endpoint at the
public URL, then send a test event:

```php
Ai::webhookEndpoints()->test($endpoint['id']);
```

## Managing webhook endpoints

```php
Ai::webhookEndpoints()->list();
Ai::webhookEndpoints()->retrieve('we_123');
Ai::webhookEndpoints()->update('we_123', ['events' => ['batch.completed']]);
Ai::webhookEndpoints()->rotateSecret('we_123');   // returns the new secret
Ai::webhookEndpoints()->listEventTypes();         // every event type you can subscribe to
Ai::webhookEndpoints()->delete('we_123');
```

## Your own senders (legacy HMAC)

The route also accepts the package's original HMAC scheme (`X-OpenAI-Signature` and `X-OpenAI-Timestamp`
headers) for internal services. Set `AI_WEBHOOKS_REQUIRE_TIMESTAMP=true` to enforce replay protection there.
Header names are configurable with `AI_WEBHOOKS_SIGNATURE_HEADER` and `AI_WEBHOOKS_TIMESTAMP_HEADER`.

## The `Route::aiAssistant()` macro

To mount the webhook and health endpoints under your own prefix and middleware:

```php
// routes/web.php or routes/api.php
Route::aiAssistant(['prefix' => 'ai', 'middleware' => ['api'], 'name' => 'ai.']);
// POST /ai/webhook (verified by the `verify.ai.webhook` middleware)
// GET  /ai/health/ready, /ai/health/live, /ai/health/detailed
```
