# Configuration

All settings live in `config/ai-assistant.php` (publish it with
`php artisan vendor:publish --tag=ai-assistant-config`). Almost every value can be set from `.env`, so you
rarely need to edit the file itself.

Validate your configuration at any time:

```bash
php artisan ai:config-validate           # human-readable report
php artisan ai:config-validate --strict  # treat warnings as errors
php artisan ai:config-validate --ci      # strict + JSON output, non-zero exit code on failure
```

## How configuration is resolved

From highest to lowest priority:

1. Runtime overrides: `config(['ai-assistant.model' => 'gpt-5-mini'])` and values from `.env`
2. The environment overlay for the current `APP_ENV`, shipped with the package in
   `config/environments/{development,testing,production}.php` (`local` uses the development overlay)
3. The base `config/ai-assistant.php`

The overlays only change defaults (for example shorter timeouts in testing); anything you set explicitly wins.

The `config/presets/` folder in the package contains three ready-made configuration files
(`simple.php`, `advanced.php`, `production.php`) you can copy over your published config as a starting point.

## Credentials

| Key | Env | Default | Notes |
|---|---|---|---|
| `api_key` | `OPENAI_API_KEY` | — | Required |
| `organization` | `OPENAI_ORGANIZATION` | `null` | Sent as `OpenAI-Organization` |
| `project` | `OPENAI_PROJECT` | `null` | Sent as `OpenAI-Project` |
| `admin_api_key` | `OPENAI_ADMIN_KEY` | `null` | Used by `Ai::admin()` and checkpoint permissions only |

## Models and generation defaults

| Key | Env | Default |
|---|---|---|
| `model` | `OPENAI_MODEL` (falls back to `OPENAI_CHAT_MODEL`) | `gpt-5` |
| `default_model` | `AI_RESPONSES_DEFAULT_MODEL` | `gpt-5` |
| `default_instructions` | `AI_DEFAULT_INSTRUCTIONS` | `''` |
| `chat_model` | `OPENAI_CHAT_MODEL` | `gpt-5` |
| `edit_model` | `OPENAI_EDIT_MODEL` | `gpt-5` |
| `audio_model` | `OPENAI_AUDIO_MODEL` | `whisper-1` |
| `temperature`, `top_p`, `max_completion_tokens`, `stop`, `presence_penalty`, `frequency_penalty` | — | `0.3`, `1`, `400`, `null`, `0`, `0` |

### Audio (`audio.*`)

| Key | Env | Default |
|---|---|---|
| `audio.models.transcription` | `OPENAI_AUDIO_TRANSCRIPTION_MODEL` | `whisper-1` |
| `audio.models.translation` | `OPENAI_AUDIO_TRANSLATION_MODEL` | `whisper-1` |
| `audio.models.speech` | `OPENAI_AUDIO_SPEECH_MODEL` | `tts-1` |
| `audio.voices.default` | `OPENAI_AUDIO_DEFAULT_VOICE` | `alloy` |
| `audio.file_size_limit_mb` | `OPENAI_AUDIO_FILE_SIZE_LIMIT_MB` | `25` |
| `audio.timeouts.{transcription,translation,speech}` | `OPENAI_AUDIO_*_TIMEOUT` | `120`, `120`, `60` seconds |

### Images (`image.*`)

| Key | Env | Default |
|---|---|---|
| `image.models.generation` | `OPENAI_IMAGE_GENERATION_MODEL` | `dall-e-3` |
| `image.models.edit` | `OPENAI_IMAGE_EDIT_MODEL` | `dall-e-2` |
| `image.models.variation` | `OPENAI_IMAGE_VARIATION_MODEL` | `dall-e-2` |
| `image.file_size_limit_mb` | `OPENAI_IMAGE_FILE_SIZE_LIMIT_MB` | `4` |
| `image.timeouts.*` | `OPENAI_IMAGE_*_TIMEOUT` | `120` seconds |

> Tip: set `OPENAI_IMAGE_GENERATION_MODEL=gpt-image-1` to use the newer image model through `Ai::responses()`.

## HTTP, timeouts and retries

| Key | Env | Default | Notes |
|---|---|---|---|
| `responses.timeout` | `AI_RESPONSES_TIMEOUT` | `120` | Seconds per request |
| `responses.max_output_tokens` | `AI_RESPONSES_MAX_OUTPUT_TOKENS` | `null` | |
| `responses.idempotency_enabled` | `AI_RESPONSES_IDEMPOTENCY` | `true` | Adds `Idempotency-Key` to safe-to-retry POSTs |
| `responses.retry.*` | `AI_RESPONSES_RETRY_*` | 3 attempts, 0.5 s initial delay, ×2 backoff, 8 s max, jitter | |
| `conversations.timeout` / `conversations.retry.*` | `AI_CONVERSATIONS_*` | as above | |
| `transport.max_retries` | `AI_TRANSPORT_MAX_RETRIES` | `2` | Retries on 409, 429 and 5xx |
| `transport.initial_delay_ms` / `transport.max_delay_ms` | `AI_TRANSPORT_*_DELAY_MS` | `200` / `2000` | |
| `connection_pool.*` | `AI_MAX_CONNECTIONS`, `AI_CONNECTION_TIMEOUT`, … | enabled | |

See [Error handling & retries](error-handling.md) for how these interact.

## Streaming (`streaming.*`)

| Key | Env | Default |
|---|---|---|
| `streaming.enabled` | `AI_STREAMING_ENABLED` | `true` |
| `streaming.timeout` / `streaming.sse_timeout` | `AI_STREAMING_TIMEOUT` / `AI_STREAMING_SSE_TIMEOUT` | `120` |
| `streaming.buffer_size` / `streaming.chunk_size` | `AI_STREAMING_BUFFER_SIZE` / `AI_STREAMING_CHUNK_SIZE` | `8192` / `1024` |
| `streaming.max_response_size` | `AI_MAX_RESPONSE_SIZE_MB` | `50` |

## Tool calling

| Key | Env | Default | Notes |
|---|---|---|---|
| `tool_calling.max_rounds` | `AI_TOOL_CALLING_MAX_ROUNDS` | `3` | How many tool-call round trips are executed automatically |
| `tool_calling.executor` | `AI_TOOL_CALLING_EXECUTOR` | `sync` | `sync` or `queue` (runs tools through `ExecuteToolCallJob`) |
| `tool_calling.parallel` | `AI_TOOL_CALLING_PARALLEL` | `false` | |
| `tools.allowlist` | `AI_TOOLS_ALLOWLIST` | `[]` | Comma- or pipe-separated tool names |
| `tools.schemas` | `AI_TOOLS_SCHEMAS` | `[]` | JSON map of tool name → schema |

## Unified routing (`routing.*`)

`Ai::responses()` decides which OpenAI endpoint to call by checking your input against this priority list:

```php
'endpoint_priority' => [
    'audio_transcription', 'audio_translation', 'audio_speech',
    'image_generation', 'image_edit', 'image_variation',
    'chat_completion', 'response_api_image_input', 'response_api',
],
```

Override it with a comma-separated `AI_ROUTING_ENDPOINT_PRIORITY`. `AI_ROUTING_CONFLICT_BEHAVIOR` controls what
happens when input matches more than one endpoint: `error` (default), `warn` or `silent`.

## Persistence

| Key | Env | Default |
|---|---|---|
| `persistence.driver` | `AI_ASSISTANT_PERSISTENCE_DRIVER` | `memory` |

`memory` keeps conversation bookkeeping in the request; `eloquent` stores conversations, items, response
records and tool invocations in your database (publish and run the migrations first). See
[Operations](operations.md#persistence).

## Webhooks (`webhooks.*`)

| Key | Env | Default |
|---|---|---|
| `webhooks.enabled` | `AI_WEBHOOKS_ENABLED` | `false` |
| `webhooks.signing_secret` | `AI_WEBHOOKS_SIGNING_SECRET` | `''` |
| `webhooks.path` | `AI_WEBHOOKS_PATH` | `/ai-assistant/webhook` |
| `webhooks.require_timestamp` | `AI_WEBHOOKS_REQUIRE_TIMESTAMP` | `false` |
| `webhooks.max_skew_seconds` | `AI_WEBHOOKS_MAX_SKEW_SECONDS` | `300` |
| `webhooks.route.name` | `AI_WEBHOOKS_ROUTE_NAME` | `ai-assistant.webhook` |
| `webhooks.middleware` | `AI_WEBHOOKS_MIDDLEWARE` | `[]` |
| `webhooks.responses.*` | `AI_WEBHOOKS_RESPONSES_*` | enabled, events `response.created/completed/failed` |

Full details in [Webhooks](webhooks.md).

## Operations

| Section | Env prefix | What it controls |
|---|---|---|
| `health_checks.*` | `AI_HEALTH_CHECK*` | Health check routes and thresholds |
| `metrics.*` | `AI_METRICS_*`, `AI_TRACK_*` | Response times, token usage, error rates |
| `error_reporting.*` | `AI_ERROR_*` | Error reporting, sampling and scrubbing of sensitive data |
| `memory_monitoring.*` | `AI_MEMORY_*` | Memory tracking for long streams |
| `background_jobs.*` | `AI_BACKGROUND_JOBS_ENABLED`, `AI_QUEUE_NAME`, `AI_QUEUE_CONNECTION`, `AI_JOB_*` | Queue used for long-running operations |
| `cache.*` | `AI_ASSISTANT_CACHE_*` | Store, TTLs, compression, encryption, stampede protection |
| `lazy_loading.*` | `AI_LAZY_*`, `AI_DEFER_CLIENT_CREATION` | Deferred client creation |
| `deprecations.emit` | `AI_ASSISTANT_EMIT_DEPRECATIONS` | Emit deprecation notices for legacy APIs |
| `mock_responses` | `AI_ASSISTANT_MOCK` | Return mock responses (never enable in production) |

See [Operations](operations.md) for how to use them.

## Example `.env` for production

```env
OPENAI_API_KEY=sk-...
OPENAI_PROJECT=proj_...
OPENAI_MODEL=gpt-5-mini

AI_ASSISTANT_PERSISTENCE_DRIVER=eloquent

AI_RESPONSES_TIMEOUT=90
AI_RESPONSES_RETRY_MAX_ATTEMPTS=4
AI_TRANSPORT_MAX_RETRIES=3

AI_WEBHOOKS_ENABLED=true
AI_WEBHOOKS_SIGNING_SECRET=whsec_...

AI_BACKGROUND_JOBS_ENABLED=true
AI_QUEUE_CONNECTION=redis
AI_QUEUE_NAME=ai

AI_ASSISTANT_CACHE_STORE=redis
AI_HEALTH_CHECK_MIDDLEWARE=auth.basic
```
