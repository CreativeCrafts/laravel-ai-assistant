# Chat sessions & tool calling

This guide covers two things:

- `Ai::chat()` and `Ai::quick()`: small helpers for conversational text turns that remember earlier turns.
- Tool calling, file search, code interpreter and file inputs, which you drive through the Responses API
  payload directly (see [why](#current-limitations-of-chatsession)).

## Quick one-liners: `Ai::quick()`

```php
use CreativeCrafts\LaravelAiAssistant\Facades\Ai;

$answer = Ai::quick('Give me three names for a Laravel package about invoices')->text;

// Array form: message, model and temperature
$result = Ai::quick([
    'message' => 'Give me one tip for faster Eloquent queries.',
    'model' => 'gpt-5-mini',
    'temperature' => 0.2,
]);
```

For JSON output use `Ai::responses()->responseFormat()` instead of the `response_format` key (see
[Structured output](responses.md#structured-output-json-schema)).

## A chat session

The argument to `Ai::chat()` is the **user message**. Use `instructions()` for the system prompt.

```php
$session = Ai::chat('How do I schedule a job every five minutes?')
    ->instructions('You are a senior Laravel developer. Reply with short code examples.')
    ->setModelName('gpt-5-mini')
    ->setTemperature(0.2);

$response = $session->send();

echo $response->text;            // the assistant's reply
echo $response->conversationId;  // conversation this session is using

// Follow-up turn in the same conversation
$followUp = $session->setUserMessage('And how do I prevent overlapping runs?')->send();
```

The session creates an OpenAI conversation on its first `send()` and reuses it for later turns, so the model
remembers earlier messages.

### `ChatResponseDto`

| Property | Description |
|---|---|
| `text` / `content` | The assistant's reply |
| `conversationId` | Conversation id |
| `raw` | The package's normalised result: `responseId`, `conversationId`, `messages`, `toolCalls`, `usage`, `finishReason` and the untouched OpenAI response under `raw` |
| `id` / `status` | Not filled for chat turns (empty string and `unknown`). Read `$response->raw['responseId']` and `$response->raw['finishReason']` instead |
| `toArray()` | All of the above as an array |

```php
$responseId = $response->raw['responseId'];
$usage = $response->raw['usage'];            // ['input_tokens' => ..., 'output_tokens' => ..., ...]
```

### Session methods

| Method | Description |
|---|---|
| `setUserMessage(string $text)` | Message for the next `send()` |
| `instructions(string $text)` | System instructions |
| `setDeveloperMessage(string $text)` | Alias of `instructions()` |
| `setModelName(string $model)` | Model |
| `setTemperature(float $t)` | Temperature |
| `send(): ChatResponseDto` | Send the turn |
| `stream()` / `streamText()` | Stream the turn ([guide](streaming.md)) |

## Tool (function) calling

Tool calling has two parts: **describing** the tool to the model, and **running** it when the model asks.
Describe tools in the Responses API format, run them with the package's `ToolRegistry`, and send the results
back with `previous_response_id`.

### 1. Register the PHP implementation

Register tools once, for example in `AppServiceProvider::boot()`. A tool receives the decoded arguments as an
array and returns anything JSON-serialisable:

```php
use App\Models\Order;
use CreativeCrafts\LaravelAiAssistant\Services\ToolRegistry;

public function boot(): void
{
    $this->app->make(ToolRegistry::class)->register('get_order_status', function (array $args): array {
        $order = Order::where('number', $args['order_number'])->first();

        return $order
            ? ['status' => $order->status, 'eta' => $order->eta?->toDateString()]
            : ['error' => 'Order not found'];
    });
}
```

`ToolRegistry` is a singleton, so tools registered at boot are available everywhere.

### 2. Describe the tool and run the loop

```php
use CreativeCrafts\LaravelAiAssistant\Contracts\ResponsesRepositoryContract;
use CreativeCrafts\LaravelAiAssistant\Services\ToolRegistry;

$responses = app(ResponsesRepositoryContract::class);
$registry = app(ToolRegistry::class);

$instructions = 'You are a helpful shop assistant.';
$tools = [[
    'type' => 'function',
    'name' => 'get_order_status',
    'description' => 'Look up the shipping status of an order by its number',
    'strict' => true,
    'parameters' => [
        'type' => 'object',
        'properties' => [
            'order_number' => ['type' => 'string', 'description' => 'Order number, e.g. A-1042'],
        ],
        'required' => ['order_number'],
        'additionalProperties' => false,
    ],
]];

$response = $responses->createResponse([
    'model' => 'gpt-5-mini',
    'instructions' => $instructions,
    'tools' => $tools,
    'input' => 'Where is my order A-1042?',
]);

// Run requested tools until the model answers in text (with a safety limit)
for ($round = 0; $round < 5; $round++) {
    $calls = array_filter($response['output'] ?? [], fn (array $item) => ($item['type'] ?? null) === 'function_call');

    if ($calls === []) {
        break;
    }

    $outputs = [];
    foreach ($calls as $call) {
        $args = json_decode($call['arguments'] ?? '{}', true) ?: [];

        $result = $registry->has($call['name'])
            ? $registry->call($call['name'], $args)
            : ['error' => "Unknown tool {$call['name']}"];

        $outputs[] = [
            'type' => 'function_call_output',
            'call_id' => $call['call_id'],
            'output' => json_encode($result),
        ];
    }

    $response = $responses->createResponse([
        'model' => 'gpt-5-mini',
        'instructions' => $instructions,          // instructions are not carried over, send them again
        'tools' => $tools,
        'previous_response_id' => $response['id'],
        'input' => $outputs,
    ]);
}

echo outputText($response); // "Your order A-1042 has shipped and should arrive on ..."
```

The raw API response has no `output_text` shortcut, so collect the text from the message items:

```php
function outputText(array $response): string
{
    $text = '';
    foreach ($response['output'] ?? [] as $item) {
        if (($item['type'] ?? null) !== 'message') {
            continue;
        }
        foreach ($item['content'] ?? [] as $part) {
            if (($part['type'] ?? null) === 'output_text') {
                $text .= $part['text'];
            }
        }
    }

    return $text;
}
```

> With `strict: true`, every property must be listed in `required` and `additionalProperties` must be
> `false`. Use `['type' => ['string', 'null']]` for optional values.

To keep the exchange in an OpenAI conversation instead of chaining `previous_response_id`, send
`'conversation' => $conversationId` on every request (see [Conversations](conversations.md)).

### Test tools without the model

Because tools are plain callables, call them directly in tests with `app(ToolRegistry::class)->call($name, $args)`
(see [Testing](testing.md#test-your-tools-without-the-model)).

## File search over your documents

Upload files into a vector store (see [Embeddings & vector stores](embeddings-and-vector-stores.md)) and let
the model search them:

```php
$response = app(ResponsesRepositoryContract::class)->createResponse([
    'model' => 'gpt-5-mini',
    'instructions' => 'Answer only from the provided documents. Cite the document name.',
    'tools' => [['type' => 'file_search', 'vector_store_ids' => ['vs_abc123']]],
    'input' => 'What is our refund window for digital products?',
]);

echo outputText($response);
```

Add `'include' => ['file_search_call.results']` to get the matched chunks back in the response.

## Send a file with a single turn

Upload the file with purpose `user_data` and reference it as an `input_file` block. The `inputItems()` builder
sends the item as is:

```php
$file = Ai::files()->upload($request->file('contract')->getRealPath(), 'user_data');

$builder = Ai::responses()->model('gpt-5-mini');

$builder->inputItems()->appendRaw([
    'role' => 'user',
    'content' => [
        ['type' => 'input_file', 'file_id' => $file['id']],
        ['type' => 'input_text', 'text' => 'Summarise this contract and list the termination clauses.'],
    ],
]);

echo $builder->send()->text;
```

See [Files & uploads](files-and-uploads.md) for upload options.

## Code interpreter

```php
$response = app(ResponsesRepositoryContract::class)->createResponse([
    'model' => 'gpt-5',
    'tools' => [[
        'type' => 'code_interpreter',
        'container' => ['type' => 'auto', 'file_ids' => ['file_abc123']],
    ]],
    'input' => 'Plot monthly revenue from the attached CSV and describe the trend.',
]);
```

Files the code produces are stored in the container; download them with `Ai::containerFiles()` (see
[Agents, skills & containers](agents.md)).

## Current limitations of `ChatSession`

`ChatSession` (and the legacy `AiAssistant` it wraps) still has methods for tools, attachments and JSON
output, but they send request shapes from the older Chat Completions and Assistants APIs, which the Responses
API rejects or ignores. Until they are updated, use the alternatives below:

| `ChatSession` method | Use instead |
|---|---|
| `includeFunctionCallTool()`, `tools()`, `setToolChoice()`, `continueWithToolResults()` | [Tool calling](#tool-function-calling) above |
| `includeFileSearchTool()` | [File search](#file-search-over-your-documents) above |
| `tools()->includeCodeInterpreterTool()` | [Code interpreter](#code-interpreter) above |
| `attachFiles()`, `attachUploadedFile()`, `attachFilesFromStorage()`, `addImageFromUploadedFile()` | [Send a file with a single turn](#send-a-file-with-a-single-turn), or `input()->imageInput()` for [vision](responses.md#vision-ask-about-an-image) |
| `setResponseFormatJson()`, `setResponseFormatJsonSchema()`, `setResponseFormatText()`, `Ai::quick()` `response_format` | `Ai::responses()->responseFormat()` ([structured output](responses.md#structured-output-json-schema)) |

## Choosing an entry point

| Need | Use |
|---|---|
| Simple text turns that remember context | `Ai::chat()` or `Ai::responses()->inConversation()` |
| Audio, images, vision, structured output | `Ai::responses()` |
| Tools, file search, code interpreter, every other Responses API parameter | `app(ResponsesRepositoryContract::class)->createResponse([...])` |
