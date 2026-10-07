# Chat sessions & tool calling

`Ai::chat()` returns a `ChatSession`: a stateful helper for conversational turns that adds tool (function)
calling, file search, code interpreter and JSON output on top of the Responses API. Each session creates an
OpenAI conversation on its first `send()` and keeps using it, so follow-up turns remember earlier ones.

## Quick one-liners: `Ai::quick()`

```php
use CreativeCrafts\LaravelAiAssistant\Facades\Ai;

$answer = Ai::quick('Give me three names for a Laravel package about invoices')->text;

// Array form: message, model, temperature, response_format ('text', 'json' or a JSON schema)
$result = Ai::quick([
    'message' => 'List three EU capitals as JSON: {"capitals": [...]}',
    'model' => 'gpt-5-mini',
    'temperature' => 0.2,
    'response_format' => 'json',
]);

$capitals = json_decode($result->text, true)['capitals'];
```

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

### `ChatResponseDto`

| Property | Description |
|---|---|
| `id` | Response id |
| `status` | Response status |
| `text` / `content` | The assistant's reply |
| `conversationId` | Conversation id |
| `raw` | The full OpenAI response |
| `toArray()` | All of the above as an array |

### Session methods

| Method | Description |
|---|---|
| `setUserMessage(string $text)` | Message for the next `send()` |
| `instructions(string $text)` | System instructions |
| `setDeveloperMessage(string $text)` | Developer message |
| `setModelName(string $model)` | Model |
| `setTemperature(float $t)` | Temperature |
| `setResponseFormatText()` / `setResponseFormatJson()` / `setResponseFormatJsonSchema(array $schema, ?string $name)` | Output format |
| `includeFunctionCallTool(string $name, string $description, array $parameters, bool $isStrict = true)` | Add a function tool |
| `includeFileSearchTool(array $vectorStoreIds = [])` | Enable file search over vector stores |
| `tools(): ToolsBuilder` | Full tools builder (function from callable, code interpreter, …) |
| `setToolChoice(string\|array $choice)` | `auto`, `none`, `required` or a specific function |
| `attachFiles(array $fileIds, ?bool $useFileSearch = null)` | Attach already-uploaded files |
| `attachUploadedFile(UploadedFile $file)` / `attachFilesFromStorage(array $paths)` / `addImageFromUploadedFile(UploadedFile $file)` | Upload and attach in one step |
| `send(): ChatResponseDto` | Send the turn |
| `stream()` / `streamText()` | Stream the turn ([guide](streaming.md)) |
| `continueWithToolResults(array $results): ChatResponseDto` | Return tool outputs to the model |

## Structured JSON output

```php
$response = Ai::chat('Classify this ticket: "I was charged twice for my subscription"')
    ->instructions('Classify support tickets.')
    ->setResponseFormatJsonSchema([
        'type' => 'object',
        'properties' => [
            'category' => ['type' => 'string', 'enum' => ['billing', 'technical', 'account', 'other']],
            'priority' => ['type' => 'string', 'enum' => ['low', 'normal', 'high']],
        ],
        'required' => ['category', 'priority'],
        'additionalProperties' => false,
    ], 'ticket_classification')
    ->send();

['category' => $category, 'priority' => $priority] = json_decode($response->text, true);
```

## Tool (function) calling

Tool calling has two parts: **describing** the tool to the model, and **running** it when the model asks.

### 1. Register the PHP implementation

Register tools once, for example in `AppServiceProvider::boot()`. A tool receives the decoded arguments as an
array and returns anything JSON-serialisable:

```php
use App\Models\Order;
use CreativeCrafts\LaravelAiAssistant\Services\ToolRegistry;

public function boot(): void
{
    $tools = $this->app->make(ToolRegistry::class);

    $tools->register('get_order_status', function (array $args): array {
        $order = Order::where('number', $args['order_number'])->first();

        return $order
            ? ['status' => $order->status, 'eta' => $order->eta?->toDateString()]
            : ['error' => 'Order not found'];
    });
}
```

### 2. Describe it on the session

```php
$response = Ai::chat('Where is my order A-1042?')
    ->instructions('You are a helpful shop assistant.')
    ->includeFunctionCallTool(
        'get_order_status',
        'Look up the shipping status of an order by its number',
        [
            'properties' => [
                'order_number' => ['type' => 'string', 'description' => 'Order number, e.g. A-1042'],
            ],
            'required' => ['order_number'],
        ],
    )
    ->send();

echo $response->text; // "Your order A-1042 has shipped and should arrive on ..."
```

When the model calls a registered tool, the package runs it, sends the result back and continues, up to
`tool_calling.max_rounds` times (default 3). Unregistered tools get a graceful error result instead of
crashing the request.

> With `$isStrict = true` (the default on `ChatSession`), every property must be listed in `required`.
> Use `['type' => ['string', 'null']]` for optional values.

### Generate the schema from a callable

`ToolsBuilder::includeFunctionFromCallable()` reflects the parameters of a closure or method and builds the
JSON schema for you (`string`, `int`, `float`, `bool` and `array` are mapped automatically):

```php
$session = Ai::chat('What is 18% VAT on 249.99?');

$session->tools()->includeFunctionFromCallable(
    fn (float $amount, float $rate) => round($amount * $rate / 100, 2),
    exportedName: 'calculate_vat',
    description: 'Calculate VAT for an amount and a percentage rate',
);
```

Remember to register a tool with the same name in the `ToolRegistry` so it can be executed.

### Run tools on a queue

Set `AI_TOOL_CALLING_EXECUTOR=queue` to run each tool through the `ExecuteToolCallJob` job instead of inline.
Combine it with Horizon to get retries, timeouts and monitoring for slow tools.

### Restrict which tools can be used

```env
AI_TOOLS_ALLOWLIST=get_order_status,calculate_vat
```

Adding a function tool that is not on a non-empty allowlist throws an `InvalidArgumentException`.

### Handling tool calls yourself

If you prefer to execute tools manually, read the tool calls from the raw response and send results back
with `continueWithToolResults()`:

```php
$session = Ai::chat('What is the weather in Lagos and Berlin?')
    ->includeFunctionCallTool('get_weather', 'Current weather for a city', [
        'properties' => ['city' => ['type' => 'string']],
        'required' => ['city'],
    ]);

$response = $session->send();

$results = [];
foreach ($response->raw['output'] ?? [] as $item) {
    if (in_array($item['type'] ?? null, ['function_call', 'tool_call'], true)) {
        $args = json_decode($item['arguments'] ?? '{}', true);
        $results[] = [
            'tool_call_id' => $item['call_id'] ?? $item['id'],
            'output' => app(WeatherService::class)->current($args['city']),
        ];
    }
}

if ($results !== []) {
    $response = $session->continueWithToolResults($results);
}
```

## File search over your documents

Upload files into a vector store (see [Embeddings & vector stores](embeddings-and-vector-stores.md)) and let
the model search them:

```php
$answer = Ai::chat('What is our refund window for digital products?')
    ->instructions('Answer only from the provided documents. Cite the document name.')
    ->includeFileSearchTool(['vs_abc123'])
    ->send();
```

Attach files to a single turn instead:

```php
$response = Ai::chat('Summarise the attached contract and list the termination clauses.')
    ->attachUploadedFile($request->file('contract'))
    ->send();

$response = Ai::chat('Compare these two reports.')
    ->attachFilesFromStorage(['reports/q1.pdf', 'reports/q2.pdf'])   // paths on the default disk
    ->send();
```

## Code interpreter

```php
$session = Ai::chat('Plot monthly revenue from the attached CSV and describe the trend.');

$session->tools()->includeCodeInterpreterTool(['file_abc123']);

$response = $session->send();
```

## Choosing between `Ai::chat()` and `Ai::responses()`

| Need | Use |
|---|---|
| Tools, file search, code interpreter | `Ai::chat()` |
| Audio, images, vision, routing by input | `Ai::responses()` |
| Full control over every Responses API parameter | `app(ResponsesRepositoryContract::class)->createResponse([...])` |
