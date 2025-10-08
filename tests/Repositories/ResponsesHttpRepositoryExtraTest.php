<?php

declare(strict_types=1);

use CreativeCrafts\LaravelAiAssistant\Repositories\Http\ResponsesHttpRepository;
use CreativeCrafts\LaravelAiAssistant\Exceptions\ApiResponseValidationException;
use GuzzleHttp\Client as GuzzleClient;
use GuzzleHttp\Psr7\Response as Psr7Response;

afterEach(function () {
    Mockery::close();
});

it('listResponses returns array on success and applies query params', function () {
    $client = Mockery::mock(GuzzleClient::class);

    $client->shouldReceive('get')
        ->once()
        ->withArgs(function (string $uri, array $options) {
            expect($uri)->toBe('/v1/responses?limit=2&before=abc');
            expect($options['headers'] ?? [])->toBeArray();
            return true;
        })
        ->andReturn(new Psr7Response(200, [], json_encode(['data' => [['id' => 'r1'], ['id' => 'r2']]])));

    $repo = new ResponsesHttpRepository($client);
    $out = $repo->listResponses(['limit' => 2, 'before' => 'abc']);

    expect($out['data'] ?? [])->toHaveCount(2);
});

it('listResponses throws on server error', function () {
    // Avoid multiple retry attempts so that we only assert one call
    config()->set('ai-assistant.responses.retry', [
        'enabled' => true,
        'max_attempts' => 1,
    ]);

    $client = Mockery::mock(GuzzleClient::class);

    $client->shouldReceive('get')
        ->once()
        ->andReturn(new Psr7Response(500, [], json_encode(['error' => ['message' => 'boom']])));

    $repo = new ResponsesHttpRepository($client);

    expect(fn () => $repo->listResponses())->toThrow(ApiResponseValidationException::class);
});

it('cancelResponse returns true on success', function () {
    config()->set('ai-assistant.responses.timeout', 7);

    $client = Mockery::mock(GuzzleClient::class);

    $client->shouldReceive('request')
        ->once()
        ->withArgs(function (string $method, string $uri, array $options) {
            expect($method)->toBe('POST');
            expect($uri)->toBe('/v1/responses/resp_42/cancel');
            expect((float)($options['timeout'] ?? 0))->toBe(7.0);
            expect(($options['headers']['Accept'] ?? ''))->toBe('application/json');
            return true;
        })
        ->andReturn(new Psr7Response(200, [], json_encode(['status' => 'canceled'])));

    $repo = new ResponsesHttpRepository($client);
    $ok = $repo->cancelResponse('resp_42');

    expect($ok)->toBeTrue();
});

it('cancelResponse throws on 404', function () {
    $client = Mockery::mock(GuzzleClient::class);

    $client->shouldReceive('request')
        ->once()
        ->andReturn(new Psr7Response(404, [], json_encode(['error' => ['message' => 'not found']])));

    $repo = new ResponsesHttpRepository($client);

    expect(fn () => $repo->cancelResponse('missing'))->toThrow(ApiResponseValidationException::class);
});

it('deleteResponse returns true on 204', function () {
    $client = Mockery::mock(GuzzleClient::class);

    $client->shouldReceive('delete')
        ->once()
        ->withArgs(function (string $uri, array $options) {
            expect($uri)->toBe('/v1/responses/resp_del_1');
            return true;
        })
        ->andReturn(new Psr7Response(204));

    $repo = new ResponsesHttpRepository($client);
    $ok = $repo->deleteResponse('resp_del_1');

    expect($ok)->toBeTrue();
});

it('deleteResponse throws on server error', function () {
    // Avoid multiple retry attempts so that we only assert one call
    config()->set('ai-assistant.responses.retry', [
        'enabled' => true,
        'max_attempts' => 1,
    ]);

    $client = Mockery::mock(GuzzleClient::class);

    $client->shouldReceive('delete')
        ->once()
        ->andReturn(new Psr7Response(500, [], json_encode(['error' => ['message' => 'boom']])));

    $repo = new ResponsesHttpRepository($client);

    expect(fn () => $repo->deleteResponse('resp_del_2'))->toThrow(ApiResponseValidationException::class);
});

it('streamResponse yields multiple delta events and final completed event lines', function () {
    config()->set('ai-assistant.responses.retry', [
        'enabled' => false,
    ]);

    $client = Mockery::mock(GuzzleClient::class);

    $sseBody = implode("\n", [
        'event: response.output_text.delta',
        'data: {"delta":"Hel"}',
        '',
        'event: response.output_text.delta',
        'data: {"delta":"lo"}',
        '',
        'event: response.completed',
        'data: {"type":"response.completed"}',
        '',
    ]);

    $client->shouldReceive('request')
        ->once()
        ->withArgs(function (string $method, string $uri, array $options) {
            expect($method)->toBe('POST');
            expect($uri)->toBe('/v1/responses');
            expect((bool)($options['stream'] ?? false))->toBeTrue();
            expect((bool)($options['json']['stream'] ?? false))->toBeTrue();
            expect(($options['headers']['Accept'] ?? ''))->toBe('text/event-stream');
            return true;
        })
        ->andReturn(new Psr7Response(200, [], $sseBody));

    $repo = new ResponsesHttpRepository($client);

    $lines = iterator_to_array($repo->streamResponse(['model' => 'gpt', 'input' => 'hi']));

    // Assert we have all event and data lines (non-empty ones)
    expect($lines)->toContain('event: response.output_text.delta')
        ->toContain('data: {"delta":"Hel"}')
        ->toContain('event: response.completed')
        ->toContain('data: {"type":"response.completed"}');
});
