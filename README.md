# Laravel AI Assistant

[![Latest Version on Packagist](https://img.shields.io/packagist/v/creativecrafts/laravel-ai-assistant.svg?style=flat-square)](https://packagist.org/packages/creativecrafts/laravel-ai-assistant)
[![GitHub Tests Action Status](https://img.shields.io/github/actions/workflow/status/creativecrafts/laravel-ai-assistant/run-tests.yml?branch=main&label=tests&style=flat-square)](https://github.com/creativecrafts/laravel-ai-assistant/actions?query=workflow%3Arun-tests+branch%3Amain)
[![GitHub Code Style Action Status](https://img.shields.io/github/actions/workflow/status/creativecrafts/laravel-ai-assistant/fix-php-code-style-issues.yml?branch=main&label=code%20style&style=flat-square)](https://github.com/creativecrafts/laravel-ai-assistant/actions?query=workflow%3A"Fix+PHP+code+style+issues"+branch%3Amain)
[![Total Downloads](https://img.shields.io/packagist/dt/creativecrafts/laravel-ai-assistant.svg?style=flat-square)](https://packagist.org/packages/creativecrafts/laravel-ai-assistant)

The complete OpenAI API for Laravel. A fluent, Laravel-native way to generate text, stream answers, call your
own PHP functions as tools, transcribe and diarize audio, create images and videos, search your documents,
run batches and fine-tunes, build realtime voice agents and manage your OpenAI organisation.

```php
use CreativeCrafts\LaravelAiAssistant\Facades\Ai;

echo Ai::responses()
    ->model('gpt-5-mini')
    ->input()
    ->message('Explain Laravel queues in two sentences.')
    ->send()
    ->text;
```

**[Read the full documentation →](docs/README.md)**

---

## Highlights

- **One fluent builder**, `Ai::responses()`, for text, vision, audio and images, routed to the right endpoint automatically
- **Every OpenAI endpoint**: Responses, Conversations, Chat Completions, Audio, Images, Videos (Sora), Embeddings, Files, Uploads, Vector stores, Batches, Fine-tuning, Evals, Realtime, Agents, Skills, Containers, ChatKit, Webhooks and the Administration API
- **Streaming** to the CLI, the browser (SSE, Inertia + React) or websockets (Laravel Reverb)
- **Tool calling** with plain PHP callables registered in a `ToolRegistry`
- **Speaker diarization**: who spoke when, with named speakers, transcripts and WebVTT captions
- **Production-ready**: retries with backoff, idempotency keys, signed webhooks, health checks, observability, caching
- **Testable**: every API is a container-bound contract you can mock
- New OpenAI parameters work without a package update: payloads are passed through untouched

## Requirements

PHP 8.2+ and Laravel 10, 11, 12 or 13 (Laravel 13 requires PHP 8.3+). The test suite runs on Laravel 12 and 13.

## Installation

```bash
composer require creativecrafts/laravel-ai-assistant
php artisan ai:install
```

```env
OPENAI_API_KEY=sk-...
```

Check your setup with `php artisan ai:test-connection`. See [Getting started](docs/getting-started.md) for
all options.

## Quick start

### Text

```php
$response = Ai::responses()
    ->model('gpt-5-mini')
    ->instructions('You are a concise Laravel expert.')
    ->input()
    ->message('When should I use events instead of jobs?')
    ->send();

$response->text;
```

### Structured output

```php
$response = Ai::responses()
    ->model('gpt-5-mini')
    ->responseFormat([
        'type' => 'json_schema',
        'name' => 'invoice',
        'strict' => true,
        'schema' => [
            'type' => 'object',
            'properties' => ['vendor' => ['type' => 'string'], 'total' => ['type' => 'number']],
            'required' => ['vendor', 'total'],
            'additionalProperties' => false,
        ],
    ])
    ->input()
    ->message("Extract the vendor and total:\n" . $invoiceText)
    ->send();

$invoice = json_decode($response->text, true);
```

### Streaming

```php
use CreativeCrafts\LaravelAiAssistant\Contracts\ResponsesRepositoryContract;
use CreativeCrafts\LaravelAiAssistant\Support\ServerSentEvents;

$lines = app(ResponsesRepositoryContract::class)->streamResponse([
    'model' => 'gpt-5-mini',
    'input' => 'Tell me about Laravel Reverb',
]);

foreach (ServerSentEvents::decode($lines) as $event) {
    if ($event['type'] === 'response.output_text.delta') {
        echo $event['delta'];
    }
}
```

`Ai::stream()` has a known issue in this release; see the [Streaming guide](docs/streaming.md) for details and
for streaming to Inertia + React or Laravel Reverb.

### Multi-turn conversations

```php
$conversationId = Ai::conversations()->start(['user_id' => (string) $user->id]);

Ai::responses()->inConversation($conversationId)->model('gpt-5-mini')
    ->input()->message('My name is Ada.')->send();

Ai::responses()->inConversation($conversationId)->model('gpt-5-mini')
    ->input()->message('What is my name?')->send()->text;   // "Your name is Ada."
```

### Tool calling

```php
use CreativeCrafts\LaravelAiAssistant\Contracts\ResponsesRepositoryContract;
use CreativeCrafts\LaravelAiAssistant\Services\ToolRegistry;

// AppServiceProvider::boot(): the PHP implementation
app(ToolRegistry::class)->register('get_order_status', fn (array $args) => Order::statusFor($args['order_number']));

// Describe the tool in the Responses API format and let the model call it
$response = app(ResponsesRepositoryContract::class)->createResponse([
    'model' => 'gpt-5-mini',
    'input' => 'Where is my order A-1042?',
    'tools' => [[
        'type' => 'function',
        'name' => 'get_order_status',
        'description' => 'Look up an order by number',
        'parameters' => [
            'type' => 'object',
            'properties' => ['order_number' => ['type' => 'string']],
            'required' => ['order_number'],
            'additionalProperties' => false,
        ],
        'strict' => true,
    ]],
]);
```

Run each `function_call` item with the registry and send back `function_call_output` items. The full loop is
in [Tool calling](docs/chat-sessions-and-tools.md#tool-function-calling).

### Audio

```php
// Speech to text
$text = Ai::responses()->input()
    ->audio(['file' => storage_path('app/meeting.mp3'), 'action' => 'transcribe'])
    ->send()->text;

// Text to speech
Ai::responses()->input()
    ->audio(['text' => 'Your order has shipped!', 'action' => 'speech', 'voice' => 'nova'])
    ->send()->saveAudio(storage_path('app/audio/shipped.mp3'));

// Who spoke when
$call = Ai::diarize(storage_path('app/calls/support.mp3'))
    ->knownSpeaker('agent', storage_path('app/voices/agent.wav'))
    ->send();

$call->speakingShare();   // ['agent' => 61.2, 'A' => 38.8]
$call->toTranscript();
```

### Images and video

```php
Ai::responses()->input()
    ->image(['prompt' => 'A watercolor lighthouse at dawn'])
    ->send()->saveImages(storage_path('app/images'));

$video = Ai::videos()->create(['model' => 'sora-2', 'prompt' => 'A drone shot over Lagos at sunset']);
```

### Embeddings and document search

```php
$vector = Ai::embeddings()->create(['model' => 'text-embedding-3-small', 'input' => 'Refund policy'])['data'][0]['embedding'];

$result = app(ResponsesRepositoryContract::class)->createResponse([
    'model' => 'gpt-5-mini',
    'input' => 'How long do refunds take?',
    'tools' => [['type' => 'file_search', 'vector_store_ids' => ['vs_help_centre']]],
]);
```

## Documentation

| | |
|---|---|
| **Basics** | [Getting started](docs/getting-started.md) · [Core concepts](docs/core-concepts.md) · [Configuration](docs/configuration.md) |
| **Generating content** | [Responses](docs/responses.md) · [Chat sessions & tools](docs/chat-sessions-and-tools.md) · [Streaming](docs/streaming.md) · [Conversations](docs/conversations.md) · [Chat Completions](docs/chat-completions.md) |
| **Media** | [Audio](docs/audio.md) · [Speaker diarization](docs/speaker-diarization.md) · [Images](docs/images.md) · [Videos](docs/videos.md) |
| **Knowledge & data** | [Embeddings & vector stores](docs/embeddings-and-vector-stores.md) · [Files & uploads](docs/files-and-uploads.md) · [Batches](docs/batches.md) · [Fine-tuning & evals](docs/fine-tuning-and-evals.md) · [Models & moderation](docs/models-and-moderation.md) |
| **Realtime & platform** | [Realtime & Live](docs/realtime.md) · [Agents, skills & containers](docs/agents.md) · [Webhooks](docs/webhooks.md) · [Administration](docs/administration.md) |
| **Production** | [Error handling](docs/error-handling.md) · [Testing](docs/testing.md) · [Operations](docs/operations.md) · [API reference](docs/api-reference.md) |

## OpenAI API coverage

Every endpoint is available through a repository on the `Ai` facade. Methods take the API's JSON body and
query parameters as arrays and return the decoded response.

| API | Accessor | Guide |
|---|---|---|
| Responses | `Ai::responses()` | [Responses](docs/responses.md) |
| Conversations | `Ai::conversations()` | [Conversations](docs/conversations.md) |
| Chat Completions / Completions | `Ai::chatCompletions()`, `Ai::completions()` | [Chat Completions](docs/chat-completions.md) |
| Embeddings | `Ai::embeddings()` | [Embeddings](docs/embeddings-and-vector-stores.md) |
| Audio | `Ai::audio()`, `Ai::diarize()` | [Audio](docs/audio.md), [Diarization](docs/speaker-diarization.md) |
| Images | `Ai::images()` | [Images](docs/images.md) |
| Videos (Sora) | `Ai::videos()` | [Videos](docs/videos.md) |
| Models | `Ai::models()` | [Models](docs/models-and-moderation.md) |
| Moderations, Decisions, Content provenance, Safety | `Ai::moderations()`, `Ai::decisions()`, `Ai::contentProvenanceChecks()`, `Ai::safety()` | [Moderation](docs/models-and-moderation.md) |
| Files, Uploads | `Ai::files()`, `Ai::uploads()` | [Files](docs/files-and-uploads.md) |
| Vector stores | `Ai::vectorStores()`, `Ai::vectorStoreFiles()`, `Ai::vectorStoreFileBatches()` | [Vector stores](docs/embeddings-and-vector-stores.md) |
| Batches | `Ai::batches()` | [Batches](docs/batches.md) |
| Fine-tuning, Graders, Evals | `Ai::fineTuningJobs()`, `Ai::fineTuningCheckpointPermissions()`, `Ai::graders()`, `Ai::evals()`, `Ai::evalRuns()` | [Fine-tuning & evals](docs/fine-tuning-and-evals.md) |
| Realtime, Live | `Ai::realtime()`, `Ai::realtimeSessions()`, `Ai::live()` | [Realtime](docs/realtime.md) |
| Agents (beta), Skills, Containers, ChatKit (beta) | `Ai::agents()`, `Ai::agentSessions()`, `Ai::agentEnvironments()`, `Ai::vaults()`, `Ai::skills()`, `Ai::containers()`, `Ai::containerFiles()`, `Ai::chatKit()` | [Agents](docs/agents.md) |
| Webhook endpoints | `Ai::webhookEndpoints()` | [Webhooks](docs/webhooks.md) |
| Administration | `Ai::admin()->…` | [Administration](docs/administration.md) |
| Assistants (deprecated) | `Ai::assistants()` | OpenAI is shutting it down on August 26, 2026; use Responses + Conversations |

Anything newer can be called through the transport directly:

```php
app(\CreativeCrafts\LaravelAiAssistant\Transport\OpenAITransport::class)
    ->request('GET', '/v1/some/new/endpoint', ['query' => ['limit' => 10]]);
```

## Testing

```bash
composer test        # Pest
composer analyse     # PHPStan
composer format      # Pint
```

Integration tests under `tests/Integration` are skipped unless a valid API key is configured. To fake the
package in your own application's tests, see [Testing your application](docs/testing.md).

## Upgrading

- [`UPGRADE.md`](UPGRADE.md): breaking changes between releases
- [`MIGRATION.md`](MIGRATION.md): moving from the legacy `AiAssistant` facade to `Ai::responses()`
- [`CHANGELOG.md`](CHANGELOG.md): release notes

## Contributing

See [`CONTRIBUTING.md`](CONTRIBUTING.md).

## License

The MIT License (MIT). See [`LICENSE.md`](LICENSE.md).
