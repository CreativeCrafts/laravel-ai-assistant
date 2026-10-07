# Operations

Tools for running the package in production: Artisan commands, health checks, observability, caching,
persistence and queues.

## Artisan commands

| Command | What it does |
|---|---|
| `php artisan ai:install [--driver=memory\|eloquent]` | Publishes config and migrations, writes the persistence driver to `.env` |
| `php artisan ai:config-validate [--json] [--strict] [--ci]` | Validates configuration; `--ci` exits non-zero on problems (use it in your pipeline) |
| `php artisan ai:test-connection [--json] [--detailed] [--timeout=30]` | Makes a real request to verify the API key and connectivity |
| `php artisan ai:health-check [--detailed] [--json] [--fix]` | Full diagnostics: config, connectivity, cache, memory, disk |
| `php artisan ai-cache:stats` | Cache statistics |
| `php artisan ai-cache:clear [--area=config\|response\|completion] [--key=] [--prefix=config:\|response:\|completion:]` | Clear package cache safely |

Example CI step:

```yaml
- name: Validate AI configuration
  run: php artisan ai:config-validate --ci
  env:
    OPENAI_API_KEY: ${{ secrets.OPENAI_API_KEY }}
```

## Health checks

Enabled by default (`AI_HEALTH_CHECKS_ENABLED`). Routes under `AI_HEALTH_CHECK_ROUTE_PREFIX`
(default `/ai-assistant/health`):

| Route | Use |
|---|---|
| `GET /ai-assistant/health` | Minimal status for load balancers (200 or 503) |
| `GET /ai-assistant/health/detailed` | Full report: configuration, API connectivity, cache, memory, disk |
| `GET /ai-assistant/health/ready` | Readiness probe (configuration, API connectivity and cache must pass) |
| `GET /ai-assistant/health/live` | Liveness probe |

Protect the detailed endpoint:

```env
AI_HEALTH_CHECK_MIDDLEWARE=auth.basic
AI_HEALTH_CHECK_HIDE_PATHS=true
```

Kubernetes example:

```yaml
livenessProbe:
  httpGet: { path: /ai-assistant/health/live, port: 80 }
readinessProbe:
  httpGet: { path: /ai-assistant/health/ready, port: 80 }
```

The API connectivity check makes a tiny request (`AI_HEALTH_CHECK_API_MODEL`, 1 token). Disable it with
`AI_HEALTH_CHECK_API_ENABLED=false` if you don't want probes to spend tokens.

## Observability

The `Observability` facade gives you structured logs, metrics and error reports with a correlation id that
follows a request through every AI call:

```php
use CreativeCrafts\LaravelAiAssistant\Facades\Observability;

Observability::setCorrelationId($request->header('X-Request-Id') ?? (string) Str::uuid());

Observability::log('support.reply', 'info', 'Generating reply', ['ticket_id' => $ticket->id]);

$checkpoint = Observability::trackMemory('bulk-embeddings');
// ... heavy work ...
Observability::updateMemoryTracking($checkpoint, 'halfway');
$report = Observability::endMemoryTracking($checkpoint);

Observability::recordTokenUsage('support.reply', promptTokens: 812, completionTokens: 164, model: 'gpt-5-mini');
Observability::recordApiCall('/v1/responses', responseTime: 1.42, statusCode: 200);

try {
    // ...
} catch (Throwable $e) {
    Observability::report($e, ['ticket_id' => $ticket->id], ['feature' => 'support']);
    throw $e;
}
```

Response times, token usage and error rates for the package's own calls are recorded automatically when
`AI_METRICS_ENABLED=true` (driver: `AI_METRICS_DRIVER=log`).

### Tracking token usage per user

Text responses include token `usage`. Store it to bill customers or enforce quotas:

```php
$response = Ai::responses()->model('gpt-5-mini')->input()->message($prompt)->send();

$usage = $response->raw['usage'] ?? [];

$request->user()->aiUsage()->create([
    'model' => 'gpt-5-mini',
    'input_tokens' => $usage['input_tokens'] ?? 0,
    'output_tokens' => $usage['output_tokens'] ?? 0,
]);
```

## Caching

The package uses your cache store (`AI_ASSISTANT_CACHE_STORE`, defaults to the app's default store) with a
prefix, TTLs, optional compression/encryption and stampede protection. The `AiAssistantCache` facade exposes
it for your own AI results:

```php
use CreativeCrafts\LaravelAiAssistant\Facades\AiAssistantCache;

$summary = AiAssistantCache::rememberCompletion(
    prompt: $article->body,
    model: 'gpt-5-mini',
    parameters: ['task' => 'summary'],
    resolver: fn () => Ai::responses()->model('gpt-5-mini')
        ->instructions('Summarise in 2 sentences.')
        ->input()->message($article->body)
        ->send()->text,
    ttl: 86400,
);

AiAssistantCache::clearCompletions();
AiAssistantCache::getStats();
```

Caching identical prompts is one of the easiest ways to cut costs. For semantic duplicates, see
[embeddings](embeddings-and-vector-stores.md).

## Persistence

| Driver | Behaviour |
|---|---|
| `memory` (default) | No database tables; conversation bookkeeping lives for the request |
| `eloquent` | Stores assistant profiles, conversations, conversation items, response records and tool invocations |

```bash
php artisan ai:install --driver=eloquent
php artisan migrate

# Optional: publish the Eloquent models to customise them
php artisan vendor:publish --tag=ai-assistant-models
```

## Queues and Horizon

AI calls are slow compared to normal HTTP work. Keep web requests fast by moving them to queued jobs (or
stream them, see [Streaming](streaming.md)):

```php
GenerateProductDescription::dispatch($product)->onQueue('ai');
```

A dedicated Horizon supervisor keeps AI work from starving your other queues:

```php
// config/horizon.php
'environments' => [
    'production' => [
        'ai-supervisor' => [
            'connection' => 'redis',
            'queue' => ['ai'],
            'balance' => 'auto',
            'maxProcesses' => 10,
            'timeout' => 300,   // longer than your AI timeouts
            'tries' => 3,
        ],
    ],
],
```

Remember to set the queue connection's `retry_after` (in `config/queue.php`) higher than the job timeout.

Tool calls can also run on the queue: `AI_TOOL_CALLING_EXECUTOR=queue` (see
[Chat sessions & tools](chat-sessions-and-tools.md#run-tools-on-a-queue)).

## Production checklist

- [ ] `OPENAI_API_KEY` (and `OPENAI_PROJECT`) set from your secret manager, never committed
- [ ] `php artisan ai:config-validate --ci` passes in CI
- [ ] `AI_ASSISTANT_MOCK` is not enabled
- [ ] Timeouts: PHP, web server, queue `timeout`/`retry_after` longer than AI request timeouts
- [ ] Long work runs on a dedicated queue; streaming endpoints have `X-Accel-Buffering: no`
- [ ] Webhooks enabled with a signing secret for background responses, batches and fine-tuning
- [ ] Health check detailed route protected with middleware
- [ ] Token usage recorded per user/tenant, rate limits in place for user-facing endpoints
- [ ] User-generated content checked with [moderation](models-and-moderation.md#moderation) where appropriate
