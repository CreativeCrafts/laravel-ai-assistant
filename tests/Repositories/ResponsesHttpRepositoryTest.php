<?php

declare(strict_types=1);

use CreativeCrafts\LaravelAiAssistant\Exceptions\ApiResponseValidationException;
use CreativeCrafts\LaravelAiAssistant\Repositories\Http\ResponsesHttpRepository;
use CreativeCrafts\LaravelAiAssistant\Transport\GuzzleOpenAITransport;
use GuzzleHttp\Client as GuzzleClient;
use GuzzleHttp\Exception\ConnectException;
use GuzzleHttp\Psr7\Response as Psr7Response;
use GuzzleHttp\Psr7\Request;

afterEach(function () {
    Mockery::close();
});

it('createResponse sends idempotency key, preserves across retries, and applies timeout', function () {
    // Configure retry to avoid sleeping
    config()?->set('ai-assistant.responses.retry', [
        'enabled' => true,
        'max_attempts' => 2,
        'initial_delay' => 0.0,
        'backoff_multiplier' => 1.0,
        'max_delay' => 0.0,
        'jitter' => false,
    ]);
    config()?->set('ai-assistant.responses.timeout', 5);

    $client = Mockery::mock(GuzzleClient::class);

    $firstKey = null;
    $secondKey = null;
    $firstTimeout = null;
    $secondTimeout = null;
    $invocation = 0;

    $client->shouldReceive('request')
        ->twice()
        ->withArgs(function (string $method, string $uri, array $options) use (&$firstKey, &$secondKey, &$firstTimeout, &$secondTimeout) {
            // capture timeout and header
            $timeout = $options['timeout'] ?? null;
            $key = $options['headers']['Idempotency-Key'] ?? null;
            if ($firstKey === null) {
                $firstKey = $key;
                $firstTimeout = $timeout;
            } else {
                $secondKey = $key;
                $secondTimeout = $timeout;
            }
            // Basic shape assertions
            expect($method)->toBe('POST');
            expect($uri)->toBe('/v1/responses');
            expect($options['json'] ?? [])->toBeArray();
            return true;
        })
        ->andReturnUsing(function () use (&$invocation) {
            $invocation++;
            if ($invocation === 1) {
                throw new ConnectException('simulated network error', new Request('POST', '/v1/responses'));
            }
            return new Psr7Response(200, [], json_encode(['ok' => true]));
        });

    $repo = new ResponsesHttpRepository(new GuzzleOpenAITransport($client));
    $result = $repo->createResponse(['model' => 'gpt-test', 'input' => 'hi']);

    expect($result)->toBe(['ok' => true])
        ->and($firstKey)->not->toBeNull()
        ->and($secondKey)->not->toBeNull()
        ->and($firstKey)->toBe($secondKey)
        ->and((float)$firstTimeout)->toBe(5.0)
        ->and((float)$secondTimeout)->toBe(5.0);
});

it('streamResponse uses sse timeout and preserves idempotency on retry', function () {
    config()->set('ai-assistant.responses.retry', [
        'enabled' => true,
        'max_attempts' => 2,
        'initial_delay' => 0.0,
        'backoff_multiplier' => 1.0,
        'max_delay' => 0.0,
        'jitter' => false,
    ]);
    config()->set('ai-assistant.responses.timeout', 10);
    config()->set('ai-assistant.streaming.sse_timeout', 15);

    $client = Mockery::mock(GuzzleClient::class);

    $keys = [];
    $timeouts = [];
    $i = 0;
    $client->shouldReceive('request')
        ->twice()
        ->withArgs(function (string $method, string $uri, array $options) use (&$keys, &$timeouts) {
            $keys[] = $options['headers']['Idempotency-Key'] ?? null;
            $timeouts[] = $options['timeout'] ?? null;
            expect($method)->toBe('POST')
                ->and($uri)->toBe('/v1/responses')
                ->and((bool)($options['stream'] ?? false))->toBeTrue()
                ->and((bool)($options['json']['stream'] ?? false))->toBeTrue();
            return true;
        })
        ->andReturnUsing(function () use (&$i) {
            $i++;
            if ($i === 1) {
                throw new ConnectException('simulated network error', new Request('POST', '/v1/responses'));
            }
            // Provide a tiny SSE body
            $body = "event: response.completed\ndata: {\"type\":\"response.completed\"}\n\n";
            return new Psr7Response(200, [], $body);
        });

    $repo = new ResponsesHttpRepository(new GuzzleOpenAITransport($client));
    $generator = $repo->streamResponse(['model' => 'gpt-test', 'input' => 'hi']);

    // Iterate a single chunk to trigger the request and verify
    foreach ($generator as $line) {
        expect($line)->not->toBeEmpty();
        break;
    }

    expect($keys)->toHaveCount(2)
        ->and($keys[0])->not->toBeNull()
        ->and($keys[0])->toBe($keys[1])
        ->and($timeouts)->toHaveCount(2)
        ->and((float)$timeouts[0])->toBe(15.0)
        ->and((float)$timeouts[1])->toBe(15.0);
});

it('getResponse applies responses timeout', function () {
    config()->set('ai-assistant.responses.timeout', 12);

    $client = Mockery::mock(GuzzleClient::class);
    $client->shouldReceive('get')
        ->once()
        ->withArgs(function (string $uri, array $options) {
            expect($uri)->toBe('/v1/responses/resp_123')
                ->and((float)($options['timeout'] ?? null))->toBe(12.0);
            return true;
        })
        ->andReturn(new Psr7Response(200, [], json_encode(['id' => 'resp_123'])));

    $repo = new ResponsesHttpRepository(new GuzzleOpenAITransport($client));
    $data = $repo->getResponse('resp_123');
    expect($data['id'])->toBe('resp_123');
});

it('transport error results in ApiResponseValidationException', function () {
    // Disable retry or keep attempts at 1 so that the first transport error surfaces as ApiResponseValidationException
    config()->set('ai-assistant.responses.retry', [
        'enabled' => true,
        'max_attempts' => 1,
        'initial_delay' => 0.0,
        'backoff_multiplier' => 1.0,
        'max_delay' => 0.0,
        'jitter' => false,
    ]);

    $client = Mockery::mock(GuzzleClient::class);
    $client->shouldReceive('request')
        ->once()
        ->andThrow(new ConnectException('network timeout', new Request('POST', '/v1/responses')));

    $repo = new ResponsesHttpRepository(new GuzzleOpenAITransport($client));
    expect(fn () => $repo->createResponse(['model' => 'gpt-test', 'input' => 'hello']))
        ->toThrow(ApiResponseValidationException::class);
});
