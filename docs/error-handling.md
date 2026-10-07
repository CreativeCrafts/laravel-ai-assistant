# Error handling & retries

## What gets thrown

| Exception | When | Useful data |
|---|---|---|
| `Exceptions\ApiResponseValidationException` | OpenAI returned an error (4xx/5xx), or the connection failed after retries | `getCode()` is the HTTP status (`502` for network failures); the message includes OpenAI's `type`, `code` and `param` |
| `InvalidArgumentException` | Invalid input caught before the request (missing message, unsupported file type, value out of range, tool not on the allowlist) | Message explains what to fix |
| `RuntimeException` | `Ai::responses()->send()` wraps API failures in it | `getPrevious()` holds the original exception |
| `Exceptions\FileValidationException` | A file is missing, unreadable, too large or of an unsupported type | |
| `Exceptions\AudioTranscriptionException`, `AudioTranslationException`, `AudioSpeechException` | Audio requests failed | |
| `Exceptions\ImageGenerationException`, `ImageEditException`, `ImageVariationException` | Image requests failed | |
| `Exceptions\ResponseCanceledException` | A stream received `response.canceled` | |
| `Exceptions\InvalidApiKeyException`, `ConfigurationValidationException` | Missing/invalid API key or configuration at boot | |

All exception classes live in `CreativeCrafts\LaravelAiAssistant\Exceptions`.

## Handling errors in a controller

```php
use CreativeCrafts\LaravelAiAssistant\Exceptions\ApiResponseValidationException;
use CreativeCrafts\LaravelAiAssistant\Facades\Ai;

try {
    $text = Ai::responses()->model('gpt-5-mini')->input()->message($prompt)->send()->text;
} catch (InvalidArgumentException $e) {
    return back()->withErrors(['prompt' => $e->getMessage()]);
} catch (RuntimeException $e) {
    $api = $e->getPrevious();

    if ($api instanceof ApiResponseValidationException && $api->getCode() === 429) {
        return back()->withErrors(['prompt' => 'We are busy right now, please try again in a minute.']);
    }

    report($e);

    return back()->withErrors(['prompt' => 'The AI service is unavailable. Please try again.']);
}
```

Low-level repositories (`Ai::embeddings()`, `Ai::files()`, …) throw `ApiResponseValidationException` directly:

```php
try {
    Ai::files()->retrieve($fileId);
} catch (ApiResponseValidationException $e) {
    if ($e->getCode() === 404) {
        // the file was deleted
    }
    throw $e;
}
```

## Global handling

Map package exceptions to responses once, in `bootstrap/app.php`:

```php
use CreativeCrafts\LaravelAiAssistant\Exceptions\ApiResponseValidationException;

->withExceptions(function (Exceptions $exceptions) {
    $exceptions->render(function (ApiResponseValidationException $e, Request $request) {
        if ($request->expectsJson()) {
            return response()->json(['message' => 'AI service error.'], $e->getCode() === 429 ? 429 : 503);
        }
    });
})
```

## Automatic retries

The HTTP transport retries **409, 429 and 5xx responses and network errors** with exponential backoff and
jitter:

- `GET` and `DELETE` requests are always retried.
- `POST` requests are retried only when they are safe to repeat. Responses and Conversations writes are sent
  with an `Idempotency-Key` header so a retry can never create a duplicate.

| Setting | Env | Default |
|---|---|---|
| `responses.retry.enabled` | `AI_RESPONSES_RETRY_ENABLED` | `true` |
| `responses.retry.max_attempts` | `AI_RESPONSES_RETRY_MAX_ATTEMPTS` | `3` |
| `responses.retry.initial_delay` | `AI_RESPONSES_RETRY_INITIAL_DELAY` | `0.5` s |
| `responses.retry.backoff_multiplier` | `AI_RESPONSES_RETRY_BACKOFF_MULTIPLIER` | `2.0` |
| `responses.retry.max_delay` | `AI_RESPONSES_RETRY_MAX_DELAY` | `8.0` s |
| `responses.retry.jitter` | `AI_RESPONSES_RETRY_JITTER` | `true` |
| `responses.idempotency_enabled` | `AI_RESPONSES_IDEMPOTENCY` | `true` |

## Retrying in queued jobs

For long or bulk work, let the queue handle retries too:

```php
class GenerateProductDescription implements ShouldQueue
{
    use Queueable;

    public int $tries = 5;
    public int $timeout = 180;

    public function backoff(): array
    {
        return [10, 30, 60, 120];
    }

    public function handle(): void
    {
        // ...
    }

    public function failed(Throwable $e): void
    {
        $this->product->update(['description_status' => 'failed']);
    }
}
```

Throttle bursty jobs to stay under your rate limits:

```php
use Illuminate\Queue\Middleware\RateLimited;

public function middleware(): array
{
    return [new RateLimited('openai')];
}

// AppServiceProvider::boot()
RateLimiter::for('openai', fn () => Limit::perMinute(300));
```

## Timeouts

| Setting | Env | Default |
|---|---|---|
| `responses.timeout` | `AI_RESPONSES_TIMEOUT` | `120` s |
| `conversations.timeout` | `AI_CONVERSATIONS_TIMEOUT` | `60` s |
| `streaming.timeout` | `AI_STREAMING_TIMEOUT` | `120` s |
| `audio.timeouts.*` | `OPENAI_AUDIO_*_TIMEOUT` | `120` / `120` / `60` s |
| `image.timeouts.*` | `OPENAI_IMAGE_*_TIMEOUT` | `120` s |

Make sure your PHP `max_execution_time`, queue job `$timeout` and web server timeouts are longer than the
request timeouts you configure, especially for reasoning models and streaming.

## Debugging tips

- `php artisan ai:test-connection --detailed` checks credentials and connectivity.
- OpenAI's error message is included verbatim in the exception message, e.g.
  `Invalid 'input': string too long [type=invalid_request_error code=string_above_max_length param=input]`.
- Enable request/response logging with the observability settings (see [Operations](operations.md)).
