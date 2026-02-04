<?php

declare(strict_types=1);

use CreativeCrafts\LaravelAiAssistant\Transport\GuzzleOpenAITransport;
use GuzzleHttp\Client as GuzzleClient;
use GuzzleHttp\Exception\ConnectException;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\Response as Psr7Response;

afterEach(function () {
    Mockery::close();
});

it('uses transport retry settings when responses retry config is absent', function () {
    config()->set('ai-assistant.responses.retry', []);
    config()->set('ai-assistant.transport', [
        'max_retries' => 1,
        'initial_delay_ms' => 0,
        'max_delay_ms' => 0,
    ]);

    $client = Mockery::mock(GuzzleClient::class);
    $calls = 0;
    $client->shouldReceive('request')
        ->twice()
        ->withArgs(function (string $method, string $uri, array $options) {
            expect($method)->toBe('POST');
            expect($uri)->toBe('/v1/moderations');
            return true;
        })
        ->andReturnUsing(function () use (&$calls) {
            $calls++;
            if ($calls === 1) {
                throw new ConnectException('network error', new Request('POST', '/v1/moderations'));
            }
            return new Psr7Response(200, [], json_encode(['id' => 'mod_1']));
        });

    $transport = new GuzzleOpenAITransport($client);
    $out = $transport->postJson('/v1/moderations', ['input' => 'hello'], idempotent: true);

    expect($out['id'] ?? null)->toBe('mod_1');
});
