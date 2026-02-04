<?php

declare(strict_types=1);

use CreativeCrafts\LaravelAiAssistant\Repositories\Http\RealtimeSessionsHttpRepository;
use CreativeCrafts\LaravelAiAssistant\Transport\GuzzleOpenAITransport;
use GuzzleHttp\Client as GuzzleClient;
use GuzzleHttp\Psr7\Response as Psr7Response;

afterEach(function () {
    Mockery::close();
});

it('creates realtime sessions', function () {
    $client = Mockery::mock(GuzzleClient::class);

    $client->shouldReceive('request')
        ->once()
        ->withArgs(function (string $method, string $uri, array $options) {
            expect($method)->toBe('POST');
            expect($uri)->toBe('/v1/realtime/sessions');
            expect($options['json']['model'] ?? null)->toBe('gpt-4o-realtime-preview');
            return true;
        })
        ->andReturn(new Psr7Response(200, [], json_encode(['id' => 'rt_1'])));

    $repo = new RealtimeSessionsHttpRepository(new GuzzleOpenAITransport($client));
    $out = $repo->create(['model' => 'gpt-4o-realtime-preview']);

    expect($out['id'] ?? null)->toBe('rt_1');
});
