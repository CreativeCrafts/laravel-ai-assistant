# Chat Completions & legacy Completions

OpenAI recommends the [Responses API](responses.md) for new work, but Chat Completions is fully supported
for existing code, compatible providers and features that are only available there.

## Create a chat completion

```php
use CreativeCrafts\LaravelAiAssistant\Facades\Ai;

$completion = Ai::chatCompletions()->create([
    'model' => 'gpt-5-mini',
    'messages' => [
        ['role' => 'developer', 'content' => 'You are a helpful assistant.'],
        ['role' => 'user', 'content' => 'Name three Laravel first-party packages.'],
    ],
]);

echo $completion['choices'][0]['message']['content'];
$completion['usage']['total_tokens'];
```

### Structured output

```php
$completion = Ai::chatCompletions()->create([
    'model' => 'gpt-5-mini',
    'messages' => [['role' => 'user', 'content' => 'Extract: "Ada, 36, Lagos"']],
    'response_format' => [
        'type' => 'json_schema',
        'json_schema' => [
            'name' => 'person',
            'strict' => true,
            'schema' => [
                'type' => 'object',
                'properties' => [
                    'name' => ['type' => 'string'],
                    'age' => ['type' => 'integer'],
                    'city' => ['type' => 'string'],
                ],
                'required' => ['name', 'age', 'city'],
                'additionalProperties' => false,
            ],
        ],
    ],
]);

$person = json_decode($completion['choices'][0]['message']['content'], true);
```

### Tools

```php
$completion = Ai::chatCompletions()->create([
    'model' => 'gpt-5-mini',
    'messages' => $messages,
    'tools' => [[
        'type' => 'function',
        'function' => [
            'name' => 'get_weather',
            'description' => 'Current weather for a city',
            'parameters' => [
                'type' => 'object',
                'properties' => ['city' => ['type' => 'string']],
                'required' => ['city'],
            ],
        ],
    ]],
]);

foreach ($completion['choices'][0]['message']['tool_calls'] ?? [] as $call) {
    $args = json_decode($call['function']['arguments'], true);
    $messages[] = $completion['choices'][0]['message'];
    $messages[] = ['role' => 'tool', 'tool_call_id' => $call['id'], 'content' => json_encode(weather($args['city']))];
}
```

## Streaming

```php
foreach (Ai::chatCompletions()->stream([
    'model' => 'gpt-5-mini',
    'messages' => [['role' => 'user', 'content' => 'Write a haiku about Eloquent']],
    'stream_options' => ['include_usage' => true],
]) as $chunk) {
    echo $chunk['choices'][0]['delta']['content'] ?? '';
}
```

## Stored completions

Completions created with `'store' => true` can be listed, inspected, tagged and deleted:

```php
Ai::chatCompletions()->list(['model' => 'gpt-5-mini', 'metadata' => ['feature' => 'support'], 'limit' => 20]);
Ai::chatCompletions()->retrieve('chatcmpl_123');
Ai::chatCompletions()->listMessages('chatcmpl_123');
Ai::chatCompletions()->update('chatcmpl_123', ['metadata' => ['reviewed' => 'true']]);
Ai::chatCompletions()->delete('chatcmpl_123');
```

## Legacy Completions

For older instruct models:

```php
$result = Ai::completions()->create([
    'model' => 'gpt-3.5-turbo-instruct',
    'prompt' => 'Write a tagline for an ice cream shop:',
    'max_tokens' => 30,
]);

echo $result['choices'][0]['text'];

foreach (Ai::completions()->stream(['model' => 'gpt-3.5-turbo-instruct', 'prompt' => 'Count to 3']) as $chunk) {
    echo $chunk['choices'][0]['text'] ?? '';
}
```

## The `Ai::complete()` front door

`Ai::complete()` is a mode/transport switch over the Responses API: pass a Responses API payload, choose
`Mode::CHAT` (full result as an array) or `Mode::TEXT` (just the text), and `Transport::SYNC` or
`Transport::STREAM` (accumulated from a stream):

```php
use CreativeCrafts\LaravelAiAssistant\DataTransferObjects\CompletionRequest;
use CreativeCrafts\LaravelAiAssistant\Enums\Mode;
use CreativeCrafts\LaravelAiAssistant\Enums\Transport;

$result = Ai::complete(
    Mode::CHAT,
    Transport::SYNC,
    CompletionRequest::fromArray(['model' => 'gpt-5-mini', 'input' => 'Say hi']),
);

$result->toArray();   // CompletionResult: `text` and/or `data`
echo $result;         // stringable
```

## Methods

| Method | Endpoint |
|---|---|
| `Ai::chatCompletions()->create(array $payload)` | `POST /v1/chat/completions` |
| `Ai::chatCompletions()->stream(array $payload)` | same, with `stream: true` |
| `Ai::chatCompletions()->list(array $params = [])` | `GET /v1/chat/completions` |
| `Ai::chatCompletions()->retrieve(string $id)` | `GET /v1/chat/completions/{id}` |
| `Ai::chatCompletions()->update(string $id, array $payload)` | `POST /v1/chat/completions/{id}` |
| `Ai::chatCompletions()->delete(string $id)` | `DELETE /v1/chat/completions/{id}` |
| `Ai::chatCompletions()->listMessages(string $id, array $params = [])` | `GET /v1/chat/completions/{id}/messages` |
| `Ai::completions()->create(array $payload)` / `stream(array $payload)` | `POST /v1/completions` |
