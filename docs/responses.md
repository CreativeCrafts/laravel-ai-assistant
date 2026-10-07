# Responses API (`Ai::responses()`)

`Ai::responses()` is the package's main entry point. One fluent builder covers text, vision, audio and image
work: you describe the input, and the package routes the request to the right OpenAI endpoint and gives you
back a `ResponseDto`.

```php
use CreativeCrafts\LaravelAiAssistant\Facades\Ai;

$response = Ai::responses()
    ->model('gpt-5-mini')
    ->instructions('You are a concise Laravel expert.')
    ->input()
    ->message('What is the difference between jobs and events?')
    ->send();

echo $response->text;
```

## Builder options

| Method | Description |
|---|---|
| `model(string $model)` | Model to use. Always set it for text requests: when omitted the unified builder falls back to `gpt-4o-mini` |
| `instructions(string $text)` | System/developer instructions |
| `temperature(float $t)` | `0`–`2`; throws `InvalidArgumentException` outside that range |
| `maxCompletionTokens(int $n)` | Upper bound on generated tokens (must be ≥ 1) |
| `responseFormat(array\|string $format)` | `'text'`, `'json_object'` or a full `json_schema` format (see below) |
| `toolChoice(array\|string $choice)` | `'auto'`, `'none'`, `'required'` or a specific tool |
| `modalities(array $modalities)` | e.g. `['text', 'audio']` |
| `inConversation(string $conversationId)` | Attach the turn to an OpenAI conversation ([guide](conversations.md)) |
| `input()` | The unified `InputBuilder` (`message`, `messages`, `imageInput`, `audio`, `audioInput`, `image`) |
| `withMessages(array $messages)` | Pass pre-built OpenAI messages |
| `send()` | Execute and return a `ResponseDto` |
| `stream()` | Stream events instead ([guide](streaming.md)) |

## Reading the result: `ResponseDto`

| Property / method | Description |
|---|---|
| `$response->id` | Response id (`resp_...`) |
| `$response->status` | `completed`, `failed`, `incomplete`, … |
| `$response->text` | Generated text (or transcript for audio) |
| `$response->type` | Which route served the request: `response_api`, `audio_transcription`, `audio_translation`, `audio_speech`, `image_generation`, `image_edit`, `image_variation`, … |
| `$response->conversationId` | Conversation the turn belongs to, if any |
| `$response->metadata` | Route-specific extras (model, image count, diarization speakers, …) |
| `$response->raw` | The untouched OpenAI response |
| `isText()`, `isAudio()`, `isImage()` | Quick type checks |
| `saveAudio(string $path): bool` | Write generated speech to disk |
| `saveImages(string $dir): array` | Write generated images, returns the saved paths |
| `diarization(): ?DiarizedTranscription` | Speaker segments when the audio was diarized |
| `toArray()` | Everything as an array |

## Text

### A single message

```php
$response = Ai::responses()
    ->model('gpt-5-mini')
    ->input()
    ->message('Write a haiku about Laravel')
    ->send();
```

### A full message list

Use `withMessages()` (or `input()->messages()`) to send several user messages, or mixed text and image
parts, in one turn. Combine it with `instructions()`: the messages are then converted to Responses API
input items.

```php
$reply = Ai::responses()
    ->model('gpt-5')
    ->instructions('You are a friendly support agent for Acme. Answer in the customer\'s language.')
    ->withMessages([
        ['role' => 'user', 'content' => 'Order #1042 arrived damaged.'],
        ['role' => 'user', 'content' => [
            ['type' => 'input_text', 'text' => 'Here is a photo of the box:'],
            ['type' => 'input_image', 'image_url' => $photoUrl],
        ]],
    ])
    ->send();
```

To carry a chat history across turns, let OpenAI store it for you with [Conversations](conversations.md)
instead of resending previous messages.

### Structured output (JSON schema)

Ask for JSON that matches a schema and decode it straight into an array:

```php
$response = Ai::responses()
    ->model('gpt-5-mini')
    ->responseFormat([
        'type' => 'json_schema',
        'name' => 'invoice',
        'strict' => true,
        'schema' => [
            'type' => 'object',
            'properties' => [
                'vendor' => ['type' => 'string'],
                'total' => ['type' => 'number'],
                'currency' => ['type' => 'string'],
                'due_date' => ['type' => 'string', 'description' => 'ISO 8601 date'],
            ],
            'required' => ['vendor', 'total', 'currency', 'due_date'],
            'additionalProperties' => false,
        ],
    ])
    ->input()
    ->message("Extract the invoice details:\n\n" . $invoiceText)
    ->send();

$invoice = json_decode($response->text, true, flags: JSON_THROW_ON_ERROR);
```

For free-form JSON use `->responseFormat('json_object')` and mention "JSON" in your instructions.

### Vision: ask about an image

```php
$response = Ai::responses()
    ->model('gpt-5-mini')
    ->input()
    ->imageInput([
        'role' => 'user',
        'content' => [
            ['type' => 'input_text', 'text' => 'What is in this picture? Answer in one sentence.'],
            ['type' => 'input_image', 'image_url' => 'https://example.com/photo.jpg'],
        ],
    ])
    ->send();
```

`image_url` also accepts a data URL, which is handy for user uploads:

```php
$file = $request->file('photo');
$dataUrl = 'data:' . $file->getMimeType() . ';base64,' . base64_encode($file->get());
```

## Audio

The `audio()` input routes to the Audio API based on `action`. Full details in the [Audio guide](audio.md).

```php
// Speech to text
$transcript = Ai::responses()
    ->input()
    ->audio(['file' => storage_path('app/interview.mp3'), 'action' => 'transcribe', 'language' => 'en'])
    ->send();

// Any language to English
$english = Ai::responses()
    ->input()
    ->audio(['file' => storage_path('app/spanish.mp3'), 'action' => 'translate'])
    ->send();

// Text to speech
Ai::responses()
    ->input()
    ->audio(['text' => 'Your order has shipped!', 'action' => 'speech', 'voice' => 'nova'])
    ->send()
    ->saveAudio(storage_path('app/audio/shipped.mp3'));

// Who spoke when
$call = Ai::responses()
    ->input()
    ->audio(['file' => storage_path('app/call.mp3'), 'action' => 'diarize'])
    ->send();

echo $call->diarization()?->toTranscript();
```

## Images

```php
// Generate
$images = Ai::responses()
    ->input()
    ->image(['prompt' => 'Isometric illustration of a Laravel server room', 'size' => '1024x1024'])
    ->send()
    ->saveImages(storage_path('app/images'));

// Edit (image + prompt)
Ai::responses()
    ->input()
    ->image([
        'image' => storage_path('app/images/room.png'),
        'mask' => storage_path('app/images/mask.png'),
        'prompt' => 'Add a large window with a city view',
    ])
    ->send();

// Variation (image without a prompt)
Ai::responses()
    ->input()
    ->image(['image' => storage_path('app/images/logo.png'), 'n' => 3])
    ->send();
```

More options in the [Images guide](images.md).

## How routing works

| Input | Endpoint |
|---|---|
| `audio()` with `file` + `action: transcribe` (or `diarize`) | `POST /v1/audio/transcriptions` |
| `audio()` with `file` + `action: translate` | `POST /v1/audio/translations` |
| `audio()` with `text` + `action: speech` | `POST /v1/audio/speech` |
| `image()` with `prompt` only | `POST /v1/images/generations` |
| `image()` with `image` + `prompt` | `POST /v1/images/edits` |
| `image()` with `image` only | `POST /v1/images/variations` |
| `audioInput()` (audio inside a chat) | `POST /v1/chat/completions` |
| `imageInput()` | `POST /v1/responses` (vision) |
| `message()` / `messages()` | `POST /v1/responses` |

The order and conflict handling are configurable, see [Configuration](configuration.md#unified-routing-routing).

## Stored responses

Responses are stored by OpenAI (unless you send `store: false`), so you can fetch and manage them later:

```php
$responses = Ai::responses();

$responses->retrieve('resp_123');
$responses->retrieve('resp_123', ['include' => ['message.output_text.logprobs']]);
$responses->listInputItems('resp_123', ['limit' => 50, 'order' => 'asc']);
$responses->cancel('resp_123');   // background responses only, returns bool
$responses->delete('resp_123');   // returns bool
```

### Background responses and resuming a stream

Long-running reasoning requests can run in the background. Create them with the low-level repository, then
poll, cancel, or reconnect to the event stream after a dropped connection:

```php
use CreativeCrafts\LaravelAiAssistant\Contracts\ResponsesRepositoryContract;

$response = app(ResponsesRepositoryContract::class)->createResponse([
    'model' => 'gpt-5',
    'input' => 'Write a detailed migration plan from MySQL 5.7 to 8.4',
    'background' => true,
]);

// Later (for example in a queued job)
$current = Ai::responses()->retrieve($response['id']);

// Resume streaming after the last sequence number you processed
foreach (Ai::responses()->resume($response['id'], startingAfter: $lastSequenceNumber) as $event) {
    if ($event['type'] === 'response.output_text.delta') {
        echo $event['delta'];
    }
}
```

Combine this with [webhooks](webhooks.md) (`response.completed`) to avoid polling altogether.

### Count tokens before sending

```php
$count = Ai::responses()->countInputTokens([
    'model' => 'gpt-5',
    'input' => $longPrompt,
]);

if ($count['input_tokens'] > 100_000) {
    // trim, summarise or reject
}
```

### Compact a long conversation

```php
$compacted = Ai::responses()->compact([
    'model' => 'gpt-5',
    'input' => $longHistoryItems,
]);
```

Use the returned output as the input for the next turn to stay within the context window.

## Full Responses API access

Need a parameter the builder doesn't expose (`reasoning`, `tools` with hosted tools, `include`, `store`,
`prompt_cache_key`, …)? Call the repository with the raw payload:

```php
use CreativeCrafts\LaravelAiAssistant\Contracts\ResponsesRepositoryContract;

$responses = app(ResponsesRepositoryContract::class);

$result = $responses->createResponse([
    'model' => 'gpt-5',
    'reasoning' => ['effort' => 'high'],
    'tools' => [['type' => 'web_search']],
    'input' => 'What changed in the latest Laravel release?',
]);

foreach ($responses->streamResponse(['model' => 'gpt-5-mini', 'input' => 'Hi!']) as $event) {
    // decoded SSE events
}

$responses->listResponses(['limit' => 20]);
```

<!-- Extra request headers (createResponse()/streamResponse() $headers and ResponsesBuilder::withHeaders()) are being added in a follow-up release; document them here once they ship. -->

## Errors

`send()` wraps failures with a helpful message:

- `InvalidArgumentException`: missing or invalid input (for example no `message`, an unsupported audio
  format, or a temperature out of range)
- `RuntimeException`: the API call failed; the original exception (usually `ApiResponseValidationException`
  with the HTTP status as its code) is available via `getPrevious()`

See [Error handling](error-handling.md).
