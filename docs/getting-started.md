# Getting started

This guide takes you from `composer require` to your first AI response in a few minutes.

## Requirements

| | Supported |
|---|---|
| PHP | 8.2+ (8.3+ for Laravel 13) |
| Laravel | 10, 11, 12, 13 (the test suite runs on 12 and 13) |
| PHP extensions | `json`, `fileinfo` (recommended, for MIME detection on uploads) |

## 1. Install the package

```bash
composer require creativecrafts/laravel-ai-assistant
```

Laravel's package auto-discovery registers the service provider and the `Ai` facade for you.

## 2. Publish the configuration (and optional migrations)

The interactive installer publishes `config/ai-assistant.php`, the migrations, and writes your chosen
persistence driver and preset to `.env`:

```bash
php artisan ai:install                  # asks which persistence driver to use
php artisan ai:install --driver=memory  # no database tables needed
php artisan ai:install --driver=eloquent && php artisan migrate
```

Prefer to do it by hand?

```bash
php artisan vendor:publish --tag=ai-assistant-config
php artisan vendor:publish --tag=ai-assistant-migrations   # only for the eloquent driver
php artisan vendor:publish --tag=ai-assistant-models       # optional: customisable Eloquent model stubs
```

## 3. Add your API key

```env
OPENAI_API_KEY=sk-...

# Optional
OPENAI_ORGANIZATION=org_...        # sent as the OpenAI-Organization header
OPENAI_PROJECT=proj_...            # sent as the OpenAI-Project header
OPENAI_ADMIN_KEY=sk-admin-...      # only for the Administration API
OPENAI_MODEL=gpt-5                 # default model for text generation
```

Check that everything is wired up:

```bash
php artisan ai:config-validate
php artisan ai:test-connection
```

## 4. Send your first request

```php
use CreativeCrafts\LaravelAiAssistant\Facades\Ai;

$response = Ai::responses()
    ->input()
    ->message('Explain Laravel queues in two sentences.')
    ->send();

echo $response->text;
```

That's it. The same builder handles audio and images too; the package picks the right OpenAI endpoint
from what you put in:

```php
// Transcribe an audio file
$transcript = Ai::responses()
    ->input()
    ->audio(['file' => storage_path('app/meeting.mp3'), 'action' => 'transcribe'])
    ->send()
    ->text;

// Generate an image and save it
Ai::responses()
    ->input()
    ->image(['prompt' => 'A watercolor lighthouse at dawn'])
    ->send()
    ->saveImages(storage_path('app/images'));
```

## 5. Use it in a controller

```php
namespace App\Http\Controllers;

use CreativeCrafts\LaravelAiAssistant\Facades\Ai;
use Illuminate\Http\Request;

class SummaryController extends Controller
{
    public function __invoke(Request $request)
    {
        $validated = $request->validate(['text' => ['required', 'string', 'max:20000']]);

        $response = Ai::responses()
            ->instructions('Summarise the text in three bullet points.')
            ->model('gpt-5-mini')
            ->input()
            ->message($validated['text'])
            ->send();

        return response()->json(['summary' => $response->text]);
    }
}
```

Prefer dependency injection over facades? Resolve the manager or any repository contract from the container:

```php
use CreativeCrafts\LaravelAiAssistant\Contracts\EmbeddingsRepositoryContract;
use CreativeCrafts\LaravelAiAssistant\Services\AiManager;

class SearchService
{
    public function __construct(
        private AiManager $ai,
        private EmbeddingsRepositoryContract $embeddings,
    ) {}
}
```

## Choosing the right entry point

| You want to… | Use | Guide |
|---|---|---|
| Generate text, transcribe audio, create images with one fluent API | `Ai::responses()` | [Responses](responses.md) |
| A quick one-liner | `Ai::quick('...')` | [Chat sessions](chat-sessions-and-tools.md) |
| Function calling / tools, file search | `Ai::chat()` | [Chat sessions & tools](chat-sessions-and-tools.md) |
| Stream tokens to a UI | `Ai::stream()` / `->stream()` | [Streaming](streaming.md) |
| Multi-turn memory | `Ai::conversations()` | [Conversations](conversations.md) |
| Any other OpenAI endpoint | `Ai::embeddings()`, `Ai::batches()`, … | [Core concepts](core-concepts.md) |

## Next steps

- Read [Core concepts](core-concepts.md) to understand how the package is organised.
- Tune timeouts, retries and defaults in [Configuration](configuration.md).
- Before you ship, read [Error handling](error-handling.md) and [Testing](testing.md).
