# Testing your application

Your test suite should never call OpenAI: it is slow, costs money and is not deterministic. Every API the
package talks to is resolved from Laravel's container through a contract, so you can swap it for a mock or a
fake in your tests.

> Bind mocks and fakes **before** the first AI call in a test (for example at the top of the test or in
> `beforeEach`), so the package picks them up when it resolves its services.

## Mock a low-level repository

```php
use CreativeCrafts\LaravelAiAssistant\Contracts\ModerationsRepositoryContract;
use Mockery\MockInterface;

it('rejects flagged comments', function () {
    $this->mock(ModerationsRepositoryContract::class, function (MockInterface $mock) {
        $mock->shouldReceive('create')
            ->once()
            ->withArgs(fn (array $payload) => $payload['input'] === 'something nasty')
            ->andReturn(['results' => [['flagged' => true, 'categories' => ['harassment' => true]]]]);
    });

    $this->post('/comments', ['body' => 'something nasty'])
        ->assertSessionHasErrors('body');
});
```

The same works for every accessor: `EmbeddingsRepositoryContract`, `FilesRepositoryContract`,
`BatchesRepositoryContract`, `ImagesRepositoryContract`, `AudioRepositoryContract`, `VideosRepositoryContract`,
`VectorStoresRepositoryContract`, … (see the [API reference](api-reference.md) for the full list).

```php
use CreativeCrafts\LaravelAiAssistant\Contracts\EmbeddingsRepositoryContract;

$this->mock(EmbeddingsRepositoryContract::class)
    ->shouldReceive('create')
    ->andReturn(['data' => [['embedding' => array_fill(0, 1536, 0.01)]], 'usage' => ['total_tokens' => 4]]);
```

## Fake text generation (`Ai::responses()`, `Ai::chat()`, `Ai::quick()`)

Text generation goes through two contracts: Conversations (a conversation is created per turn) and
Responses. Fake both:

```php
use CreativeCrafts\LaravelAiAssistant\Contracts\ConversationsRepositoryContract;
use CreativeCrafts\LaravelAiAssistant\Contracts\ResponsesRepositoryContract;
use CreativeCrafts\LaravelAiAssistant\Facades\Ai;
use Mockery\MockInterface;

function fakeAiReply(string $text): void
{
    test()->mock(ConversationsRepositoryContract::class, function (MockInterface $mock) {
        $mock->shouldReceive('createConversation')->andReturn(['id' => 'conv_test']);
    });

    test()->mock(ResponsesRepositoryContract::class, function (MockInterface $mock) use ($text) {
        $mock->shouldReceive('createResponse')->andReturn([
            'id' => 'resp_test',
            'object' => 'response',
            'status' => 'completed',
            'conversation' => ['id' => 'conv_test'],
            'output' => [[
                'type' => 'message',
                'role' => 'assistant',
                'content' => [['type' => 'output_text', 'text' => $text]],
            ]],
            'usage' => ['input_tokens' => 10, 'output_tokens' => 5, 'total_tokens' => 15],
        ]);
    });
}

it('summarises an article', function () {
    fakeAiReply('A short summary.');

    $summary = Ai::responses()->model('gpt-5-mini')->input()->message('Long article...')->send();

    expect($summary->text)->toBe('A short summary.');
});
```

Put helpers like `fakeAiReply()` in `tests/Pest.php` (or a trait for PHPUnit) to reuse them everywhere.

### Asserting what was sent

```php
$this->mock(ResponsesRepositoryContract::class)
    ->shouldReceive('createResponse')
    ->once()
    ->withArgs(function (array $payload) {
        expect($payload['model'])->toBe('gpt-5-mini')
            ->and($payload['instructions'])->toContain('Summarise');

        return true;
    })
    ->andReturn($fakeResponse);
```

## Fake streams

`ResponsesRepositoryContract::streamResponse()` returns raw SSE lines, one line per item. Return them in the
same shape, with an `event:` line, a `data:` line and an empty string between events:

```php
function sseEvent(array $event): array
{
    return ['event: ' . $event['type'], 'data: ' . json_encode($event), ''];
}

$this->mock(ResponsesRepositoryContract::class)
    ->shouldReceive('streamResponse')
    ->andReturn([
        ...sseEvent(['type' => 'response.output_text.delta', 'delta' => 'Hel']),
        ...sseEvent(['type' => 'response.output_text.delta', 'delta' => 'lo']),
        ...sseEvent(['type' => 'response.completed', 'response' => ['id' => 'resp_1']]),
    ]);

$text = '';
foreach (Ai::stream('Say hello') as $event) {
    if ($event->type === 'response.output_text.delta') {
        $text .= $event->data['delta'];
    }
}

expect($text)->toBe('Hello');
```

`Ai::stream()` also creates a conversation first, so mock `ConversationsRepositoryContract::createConversation`
as in `fakeAiReply()` above.

For low-level streaming endpoints (`Ai::chatCompletions()->stream()`, `Ai::audio()->streamTranscription()`, …)
return already-decoded event arrays:

```php
$this->mock(AudioRepositoryContract::class)
    ->shouldReceive('streamTranscription')
    ->andReturn([
        ['type' => 'transcript.text.delta', 'delta' => 'Hello'],
        ['type' => 'transcript.text.done', 'text' => 'Hello'],
    ]);
```

## Test your tools without the model

Tools are plain callables in the `ToolRegistry`, so test them directly:

```php
use CreativeCrafts\LaravelAiAssistant\Services\ToolRegistry;

it('returns the order status tool result', function () {
    $order = Order::factory()->create(['number' => 'A-1', 'status' => 'shipped']);

    $result = app(ToolRegistry::class)->call('get_order_status', ['order_number' => 'A-1']);

    expect($result['status'])->toBe('shipped');
});
```

## Wrap the package behind your own service

For larger apps, depend on a small interface of your own and fake that. Your tests then describe your
domain, not OpenAI payloads:

```php
interface Summariser
{
    public function summarise(string $text): string;
}

final class OpenAiSummariser implements Summariser
{
    public function summarise(string $text): string
    {
        return Ai::responses()->model('gpt-5-mini')
            ->instructions('Summarise in two sentences.')
            ->input()->message($text)
            ->send()->text ?? '';
    }
}

// AppServiceProvider: $this->app->bind(Summariser::class, OpenAiSummariser::class);
// Test: $this->mock(Summariser::class)->shouldReceive('summarise')->andReturn('Short.');
```

## Webhooks

The webhook route is registered at boot, and the package checks the signing secret at boot too, so enable
webhooks for the whole test run in `phpunit.xml` (or `.env.testing`) rather than with `config()` inside a test:

```xml
<env name="AI_WEBHOOKS_ENABLED" value="true"/>
<env name="AI_WEBHOOKS_SIGNING_SECRET" value="whsec_MDEyMzQ1Njc4OWFiY2RlZjAxMjM0NTY3ODlhYmNkZWY="/>
```

Then sign a payload with `StandardWebhookSignature::sign()`, post it, and assert your listeners ran:

```php
use CreativeCrafts\LaravelAiAssistant\Events\OpenAiWebhookReceived;
use CreativeCrafts\LaravelAiAssistant\Support\StandardWebhookSignature;
use Illuminate\Support\Facades\Event;

it('dispatches batch results import', function () {
    Event::fake([OpenAiWebhookReceived::class]);

    $body = json_encode(['id' => 'evt_1', 'object' => 'event', 'type' => 'batch.completed', 'data' => ['id' => 'batch_1']]);
    $id = 'msg_1';
    $timestamp = (string) time();
    $signature = StandardWebhookSignature::sign($body, $id, $timestamp, config('ai-assistant.webhooks.signing_secret'));

    $this->call('POST', '/ai-assistant/webhook', [], [], [], [
        'HTTP_WEBHOOK_ID' => $id,
        'HTTP_WEBHOOK_TIMESTAMP' => $timestamp,
        'HTTP_WEBHOOK_SIGNATURE' => "v1,{$signature}",
        'CONTENT_TYPE' => 'application/json',
    ], $body)->assertOk();

    Event::assertDispatched(OpenAiWebhookReceived::class, fn ($e) => $e->type === 'batch.completed');
});
```

## Integration tests against the real API

Keep a small, opt-in suite that hits OpenAI, and skip it unless a key is present:

```php
it('talks to OpenAI', function () {
    $text = Ai::responses()->model('gpt-5-mini')->input()->message('Reply with the word pong')->send()->text;

    expect(strtolower($text))->toContain('pong');
})->skip(fn () => ! env('OPENAI_API_KEY_INTEGRATION'), 'No integration key');
```
