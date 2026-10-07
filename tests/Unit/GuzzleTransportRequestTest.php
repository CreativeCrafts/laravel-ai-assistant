<?php

declare(strict_types=1);

use CreativeCrafts\LaravelAiAssistant\Exceptions\ApiResponseValidationException;
use CreativeCrafts\LaravelAiAssistant\Transport\GuzzleOpenAITransport;
use GuzzleHttp\Client as GuzzleClient;
use GuzzleHttp\Psr7\Response as Psr7Response;

afterEach(function () {
    Mockery::close();
});

it('sends JSON requests with encoded query parameters', function () {
    $client = Mockery::mock(GuzzleClient::class);
    $client->shouldReceive('request')
        ->once()
        ->withArgs(function (string $method, string $uri, array $options) {
            expect($method)->toBe('POST')
                ->and($uri)->toBe('/v1/conversations/conv_1/items?include%5B%5D=message.output_text.logprobs')
                ->and($options['json'])->toBe(['items' => []])
                ->and($options['headers']['Content-Type'])->toBe('application/json')
                ->and($options['headers']['Accept'])->toBe('application/json');
            return true;
        })
        ->andReturn(new Psr7Response(200, ['Content-Type' => 'application/json'], json_encode(['object' => 'list'])));

    $out = (new GuzzleOpenAITransport($client))->request('POST', '/v1/conversations/conv_1/items', [
        'query' => ['include' => ['message.output_text.logprobs']],
        'json' => ['items' => []],
    ]);

    expect($out)->toBe(['object' => 'list']);
});

it('returns decoded bodies for DELETE requests', function () {
    $client = Mockery::mock(GuzzleClient::class);
    $client->shouldReceive('delete')
        ->once()
        ->withArgs(fn (string $uri, array $options) => $uri === '/v1/files/file_1')
        ->andReturn(new Psr7Response(200, [], json_encode(['id' => 'file_1', 'deleted' => true])));

    $out = (new GuzzleOpenAITransport($client))->request('DELETE', '/v1/files/file_1');

    expect($out)->toBe(['id' => 'file_1', 'deleted' => true]);
});

it('sends explicit multipart parts with repeated names', function () {
    $client = Mockery::mock(GuzzleClient::class);
    $client->shouldReceive('request')
        ->once()
        ->withArgs(function (string $method, string $uri, array $options) {
            expect($options['multipart'])->toBe([
                ['name' => 'known_speaker_names[]', 'contents' => 'agent'],
                ['name' => 'known_speaker_names[]', 'contents' => 'customer'],
                ['name' => 'stream', 'contents' => 'false'],
            ])->and($options['headers'])->not->toHaveKey('Content-Type');
            return true;
        })
        ->andReturn(new Psr7Response(200, [], json_encode(['text' => 'ok'])));

    (new GuzzleOpenAITransport($client))->request('POST', '/v1/audio/transcriptions', [
        'multipart' => [
            ['name' => 'known_speaker_names[]', 'contents' => 'agent'],
            ['name' => 'known_speaker_names[]', 'contents' => 'customer'],
            ['name' => 'stream', 'contents' => false],
        ],
    ]);
});

it('returns an empty array for empty acknowledgements', function () {
    $client = Mockery::mock(GuzzleClient::class);
    $client->shouldReceive('request')->once()->andReturn(new Psr7Response(202));

    expect((new GuzzleOpenAITransport($client))->request('POST', '/v1/realtime/calls/call_1/hangup'))->toBe([]);
});

it('returns raw content for binary and SDP responses and text for text bodies', function (string $contentType, array $expected) {
    $client = Mockery::mock(GuzzleClient::class);
    $client->shouldReceive('request')->once()->andReturn(new Psr7Response(200, ['Content-Type' => $contentType], 'BODY'));

    expect((new GuzzleOpenAITransport($client))->request('POST', '/v1/anything'))->toBe($expected);
})->with([
    'audio' => ['audio/mpeg', ['content' => 'BODY', 'content_type' => 'audio/mpeg']],
    'video' => ['video/mp4', ['content' => 'BODY', 'content_type' => 'video/mp4']],
    'sdp' => ['application/sdp', ['content' => 'BODY', 'content_type' => 'application/sdp']],
    'vtt' => ['text/vtt', ['text' => 'BODY']],
]);

it('throws API errors with type and code details', function () {
    $client = Mockery::mock(GuzzleClient::class);
    $client->shouldReceive('request')->once()->andReturn(new Psr7Response(400, ['Content-Type' => 'application/json'], json_encode([
        'error' => ['message' => 'Invalid file format', 'type' => 'invalid_request_error', 'code' => 'invalid_value'],
    ])));

    (new GuzzleOpenAITransport($client))->request('POST', '/v1/audio/transcriptions', ['multipart' => []]);
})->throws(ApiResponseValidationException::class, 'Invalid file format [type=invalid_request_error code=invalid_value]');

it('streams SSE lines for multipart requests', function () {
    $body = "data: {\"type\":\"transcript.text.segment\",\"speaker\":\"A\"}\n\n"
        . "data: {\"type\":\"transcript.text.done\",\"text\":\"Hi\"}\n\n";

    $client = Mockery::mock(GuzzleClient::class);
    $client->shouldReceive('request')
        ->once()
        ->withArgs(function (string $method, string $uri, array $options) {
            expect($options['stream'])->toBeTrue()
                ->and($options['headers']['Accept'])->toBe('text/event-stream')
                ->and($options['multipart'][0])->toBe(['name' => 'stream', 'contents' => 'true']);
            return true;
        })
        ->andReturn(new Psr7Response(200, ['Content-Type' => 'text/event-stream'], $body));

    $lines = iterator_to_array((new GuzzleOpenAITransport($client))->streamRequest('POST', '/v1/audio/transcriptions', [
        'multipart' => [['name' => 'stream', 'contents' => true]],
    ]), false);

    expect($lines)->toBe([
        'data: {"type":"transcript.text.segment","speaker":"A"}',
        'data: {"type":"transcript.text.done","text":"Hi"}',
    ]);
});

it('streams SSE lines for GET requests', function () {
    $client = Mockery::mock(GuzzleClient::class);
    $client->shouldReceive('get')
        ->once()
        ->withArgs(fn (string $uri, array $options) => $uri === '/v1/responses/resp_1?stream=true&starting_after=4' && $options['stream'] === true)
        ->andReturn(new Psr7Response(200, ['Content-Type' => 'text/event-stream'], "data: {\"type\":\"response.completed\"}\n"));

    $lines = iterator_to_array((new GuzzleOpenAITransport($client))->streamRequest('GET', '/v1/responses/resp_1', [
        'query' => ['stream' => true, 'starting_after' => 4],
    ]), false);

    expect($lines)->toBe(['data: {"type":"response.completed"}']);
});

it('adds an idempotency key for idempotent requests', function () {
    config()->set('ai-assistant.responses.idempotency_enabled', true);

    $client = Mockery::mock(GuzzleClient::class);
    $client->shouldReceive('request')
        ->once()
        ->withArgs(function (string $method, string $uri, array $options) {
            expect($options['headers']['Idempotency-Key'] ?? null)->toBe('fixed-key')
                ->and($options['json'])->toBe(['input' => 'x']);
            return true;
        })
        ->andReturn(new Psr7Response(200, [], json_encode(['id' => 'r'])));

    (new GuzzleOpenAITransport($client))->request('POST', '/v1/responses', [
        'json' => ['input' => 'x', '_idempotency_key' => 'fixed-key'],
        'idempotent' => true,
    ]);
});
