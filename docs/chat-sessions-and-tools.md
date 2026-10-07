# Chat sessions & tool calling

`Ai::chat()` and `Ai::quick()` are helpers for conversational turns that remember earlier turns. A chat
session can also call your PHP functions as tools, search a vector store, run code, read attached files and
return JSON.

For a Responses API parameter the session has no method for, send the payload yourself with
`app(ResponsesRepositoryContract::class)->createResponse([...])` (see
[Without a chat session](#without-a-chat-session)).

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

`response_format` accepts `'text'`, `'json'` (any JSON object) or `['name' => 'ticket', 'schema' => [...]]` for a
JSON schema (see [JSON output](#json-output)).

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
| `includeFunctionCallTool($name, $description, $parameters, $strict = true)` | Describe a function tool ([tool calling](#tool-function-calling)) |
| `setToolChoice(string\|array $choice)` | `auto`, `required`, `none` or `['type' => 'function', 'name' => '...']` |
| `includeFileSearchTool(array $vectorStoreIds)` | Search vector stores ([file search](#file-search-over-your-documents)) |
| `tools()` | `ToolsBuilder`, which adds `includeCodeInterpreterTool(array $fileIds)` ([code interpreter](#code-interpreter)) |
| `attachFiles(array $fileIds)` | Send uploaded files with the next turn ([files](#send-files-with-a-turn)) |
| `attachUploadedFile($file, $purpose)` / `attachFilesFromStorage($paths, $purpose)` | Upload, then attach to the next turn |
| `addImageFromUploadedFile($file, $purpose)` | Upload an image and send it with the next turn |
| `setResponseFormatJsonSchema(array $schema, $name)` / `setResponseFormatJson()` / `setResponseFormatText()` | Output format ([JSON output](#json-output)) |
| `send(): ChatResponseDto` | Send the turn and run any tools the model calls |
| `continueWithToolResults(array $results)` | Send tool results yourself ([manual results](#tool-calls-left-after-the-last-round)) |
| `stream()` / `streamText()` | Stream the turn ([guide](streaming.md)) |

Tools and the output format stay set for every later turn of the session. Attached files and images are sent
with the next turn only.

## Tool (function) calling

Tool calling has two parts: **describing** the tool to the model, and **running** it when the model asks. The
session describes the tool; the package's `ToolRegistry` runs it.

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

### 2. Describe the tool and send

```php
use CreativeCrafts\LaravelAiAssistant\Facades\Ai;

$session = Ai::chat('Where is my order A-1042?')
    ->instructions('You are a helpful shop assistant.')
    ->includeFunctionCallTool('get_order_status', 'Look up the shipping status of an order by its number', [
        'properties' => [
            'order_number' => ['type' => 'string', 'description' => 'Order number, e.g. A-1042'],
        ],
        'required' => ['order_number'],
    ]);

$response = $session->send();

echo $response->text; // "Your order A-1042 has shipped and should arrive on ..."
```

What `send()` does:

1. Sends the tool as `{type: function, name, description, parameters, strict}`. The parameters get
   `type: object` and `additionalProperties: false` added. A tool with no parameters gets an empty object schema.
2. When the model returns `function_call` items, runs each one through `ToolRegistry` with the decoded
   arguments. A tool that throws gets `{"error": "<message>"}` as its output, and a tool that isn't registered
   gets `{"error": "Tool not registered", "tool": "<name>"}`, so the model can recover.
3. Sends the results back as `function_call_output` items in the same conversation, with the same tools and
   output format, and repeats while the model keeps calling tools, up to `tool_calling.max_rounds` rounds
   (default 3, see [Configuration](configuration.md#tool-calling)).
4. Returns the final answer.

> `strict` defaults to `true` here. With strict mode every property must be listed in `required`. Use
> `['type' => ['string', 'null']]` for optional values, or pass `false` as the fourth argument.

Force or forbid tool use with `setToolChoice()`:

```php
$session->setToolChoice('required');                                        // must call some tool
$session->setToolChoice(['type' => 'function', 'name' => 'get_order_status']); // must call this tool
```

### Tool calls left after the last round

If the model is still calling tools after the last round, `send()` returns those calls instead of running more
rounds. Run them and send the results with `continueWithToolResults()`:

```php
use CreativeCrafts\LaravelAiAssistant\Services\ToolRegistry;

$registry = app(ToolRegistry::class);
$results = [];

foreach ($response->raw['toolCalls'] as $call) {          // ['id' => call_id, 'name', 'arguments' => JSON string]
    $results[] = [
        'tool_call_id' => $call['id'],
        'output' => $registry->call($call['name'], json_decode($call['arguments'], true) ?: []),
    ];
}

$response = $session->continueWithToolResults($results);
```

### Streaming and tools

`stream()` streams the model's events, including its `function_call` items, but does not run tools. Use
`send()` when the model may call tools.

### Test tools without the model

Because tools are plain callables, call them directly in tests with `app(ToolRegistry::class)->call($name, $args)`
(see [Testing](testing.md#test-your-tools-without-the-model)).

## File search over your documents

Upload files into a vector store (see [Embeddings & vector stores](embeddings-and-vector-stores.md)) and let
the model search them:

```php
$response = Ai::chat('What is our refund window for digital products?')
    ->instructions('Answer only from the provided documents. Cite the document name.')
    ->includeFileSearchTool(['vs_abc123'])
    ->send();
```

File search needs at least one vector store id. `includeFileSearchTool()` without ids throws an
`InvalidArgumentException` when the turn is sent.

## Send files with a turn

Attached files go to the model as `input_file` blocks with the next turn only. Upload with purpose `user_data`:

```php
$file = Ai::files()->upload($request->file('contract')->getRealPath(), 'user_data');

$response = Ai::chat('Summarise this contract and list the termination clauses.')
    ->attachFiles([$file['id']])
    ->send();

// Or upload and attach in one step
$response = Ai::chat('Summarise this contract.')
    ->attachUploadedFile($request->file('contract'), 'user_data')
    ->send();

// Images are sent as input_image blocks
$response = Ai::chat('What is in this photo?')
    ->addImageFromUploadedFile($request->file('photo'), 'vision')
    ->send();
```

`attachFilesFromStorage(['contracts/acme.pdf'], 'user_data')` uploads files from your default storage disk.
The upload methods default to purpose `assistants`, so pass the purpose explicitly. Attaching a file does not
make it searchable: for search across many documents, use a vector store and
[file search](#file-search-over-your-documents). See [Files & uploads](files-and-uploads.md) for upload options.

## Code interpreter

```php
$session = Ai::chat('Plot monthly revenue from the attached CSV and describe the trend.')->setModelName('gpt-5');
$session->tools()->includeCodeInterpreterTool(['file_abc123']);

$response = $session->send();
```

The tool is sent with an `auto` container holding those files. Files the code produces are stored in the
container; download them with `Ai::containerFiles()` (see [Agents, skills & containers](agents.md)).

## JSON output

```php
$response = Ai::chat("Classify this ticket: I was charged twice")
    ->setResponseFormatJsonSchema([
        'type' => 'object',
        'properties' => [
            'category' => ['type' => 'string', 'enum' => ['billing', 'shipping', 'other']],
        ],
        'required' => ['category'],
        'additionalProperties' => false,
    ], 'ticket')
    ->send();

$ticket = json_decode($response->text, true);   // ['category' => 'billing']
```

The format is sent as `text.format = {type: json_schema, name, schema}`. It stays set for the rest of the
session, including the answer that follows a tool call. `setResponseFormatJson()` asks for any JSON object, and
`setResponseFormatText()` switches back to plain text.

## Without a chat session

Send any Responses API payload with the repository. This covers parameters the session has no method for,
such as `previous_response_id`, `include` or `reasoning`. Tools go in the same flat shape:

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
        'properties' => ['order_number' => ['type' => 'string']],
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

echo outputText($response);
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

Hosted tools use the same payload: `['type' => 'file_search', 'vector_store_ids' => ['vs_abc123']]` and
`['type' => 'code_interpreter', 'container' => ['type' => 'auto', 'file_ids' => ['file_abc123']]]`.

## Choosing an entry point

| Need | Use |
|---|---|
| Conversational turns, tools, file search, code interpreter, attached files, JSON output | `Ai::chat()` |
| Audio, images, vision, structured output in one call | `Ai::responses()` |
| Any other Responses API parameter | `app(ResponsesRepositoryContract::class)->createResponse([...])` |
