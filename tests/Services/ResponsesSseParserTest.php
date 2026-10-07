<?php

declare(strict_types=1);

use CreativeCrafts\LaravelAiAssistant\Repositories\Http\ResponsesHttpRepository;
use CreativeCrafts\LaravelAiAssistant\Services\ResponsesSseParser;
use CreativeCrafts\LaravelAiAssistant\Tests\DataFactories\ResponsesFactory;
use CreativeCrafts\LaravelAiAssistant\Transport\GuzzleOpenAITransport;
use GuzzleHttp\Client as GuzzleClient;
use GuzzleHttp\Psr7\Response as Psr7Response;

it('accumulates fragmented output_text deltas and marks typing on deltas', function () {
    $parser = new ResponsesSseParser();
    $lines = ResponsesFactory::streamingTextSse(['Hel', 'lo']);

    $events = iterator_to_array($parser->parseWithAccumulation($lines));

    // Find all delta events
    $deltaEvents = array_values(array_filter($events, fn ($e) => ($e['type'] ?? '') === 'response.output_text.delta'));
    expect($deltaEvents)->not->toBeEmpty();

    // The last delta accumulated should be 'Hello'
    $lastDelta = end($deltaEvents);
    expect($lastDelta['data']['accumulated'] ?? '')->toBe('Hello');
    expect($lastDelta['data']['typing'] ?? null)->toBeTrue();

    // There should be a final response.completed event
    $final = end($events);
    expect($final['type'] ?? '')->toBe('response.completed');
    expect($final['isFinal'] ?? false)->toBeTrue();
});

it('emits output_text.completed with typing false and respects provided text', function () {
    $parser = new ResponsesSseParser();
    $lines = [
        'event: response.output_text.delta',
        'data: ' . json_encode(['delta' => 'Hi'], JSON_UNESCAPED_SLASHES),
        '',
        'event: response.output_text.completed',
        'data: ' . json_encode(['text' => 'Hi'], JSON_UNESCAPED_SLASHES),
        '',
        'event: response.completed',
        'data: ' . json_encode(['type' => 'response.completed'], JSON_UNESCAPED_SLASHES),
        '',
    ];

    $events = iterator_to_array($parser->parseWithAccumulation($lines));

    $completed = array_values(array_filter($events, fn ($e) => ($e['type'] ?? '') === 'response.output_text.completed'))[0] ?? null;
    expect($completed)->not->toBeNull();
    expect($completed['data']['text'] ?? '')->toBe('Hi');
    expect($completed['data']['typing'] ?? true)->toBeFalse();
    expect($completed['isFinal'] ?? true)->toBeFalse();
});

it('passes through tool_call events and resets accumulator on final', function () {
    $parser = new ResponsesSseParser();

    $lines = [
        'event: response.output_text.delta',
        'data: ' . json_encode(['delta' => 'One'], JSON_UNESCAPED_SLASHES),
        '',
        'event: response.tool_call.created',
        'data: ' . json_encode(['tool_call' => ['id' => 'call_1', 'name' => 'lookup']], JSON_UNESCAPED_SLASHES),
        '',
        'event: response.completed',
        'data: ' . json_encode(['type' => 'response.completed'], JSON_UNESCAPED_SLASHES),
        '',
        // After final, a new delta starts fresh
        'event: response.output_text.delta',
        'data: ' . json_encode(['delta' => 'Two'], JSON_UNESCAPED_SLASHES),
        '',
    ];

    $events = iterator_to_array($parser->parseWithAccumulation($lines));

    // Ensure tool_call event was passed through
    $toolEvt = array_values(array_filter($events, fn ($e) => ($e['type'] ?? '') === 'response.tool_call.created'))[0] ?? null;
    expect($toolEvt)->not->toBeNull();

    // The delta after completion should have accumulated == 'Two'
    $last = end($events);
    expect($last['data']['accumulated'] ?? '')->toBe('Two');
});

it('splits frames on each event line when the blank separator lines are missing', function () {
    $parser = new ResponsesSseParser();

    // GuzzleOpenAITransport::streamSse() yields non-empty lines only
    $lines = [
        'event: response.output_text.delta',
        'data: ' . json_encode(['type' => 'response.output_text.delta', 'delta' => 'Hel']),
        'event: response.output_text.delta',
        'data: ' . json_encode(['type' => 'response.output_text.delta', 'delta' => 'lo']),
        'event: response.completed',
        'data: ' . json_encode(['type' => 'response.completed', 'response' => ['id' => 'resp_1']]),
    ];

    $events = iterator_to_array($parser->parseWithAccumulation($lines), false);

    expect(array_column($events, 'type'))->toBe([
        'response.output_text.delta',
        'response.output_text.delta',
        'response.completed',
    ])
        ->and($events[0]['data']['delta'])->toBe('Hel')
        ->and($events[1]['data']['accumulated'])->toBe('Hello')
        ->and($events[2]['data']['response']['id'])->toBe('resp_1')
        ->and($events[2]['isFinal'])->toBeTrue();
});

it('streams deltas end to end through the HTTP transport', function () {
    $body = '';
    foreach (['Hel', 'lo'] as $delta) {
        $body .= "event: response.output_text.delta\ndata: " . json_encode(['type' => 'response.output_text.delta', 'delta' => $delta]) . "\n\n";
    }
    $body .= "event: response.completed\ndata: " . json_encode(['type' => 'response.completed']) . "\n\n";

    $client = Mockery::mock(GuzzleClient::class);
    $client->shouldReceive('request')->once()->andReturn(new Psr7Response(200, [], $body));

    $repo = new ResponsesHttpRepository(new GuzzleOpenAITransport($client));
    $events = iterator_to_array((new ResponsesSseParser())->parseWithAccumulation($repo->streamResponse(['model' => 'gpt-test', 'input' => 'hi'])), false);

    $deltas = array_values(array_filter($events, fn (array $e) => $e['type'] === 'response.output_text.delta'));

    expect($deltas)->toHaveCount(2)
        ->and(end($deltas)['data']['accumulated'])->toBe('Hello')
        ->and(end($events)['type'])->toBe('response.completed');
});
