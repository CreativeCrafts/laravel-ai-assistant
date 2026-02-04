<?php

declare(strict_types=1);

use CreativeCrafts\LaravelAiAssistant\Repositories\Http\ModerationsHttpRepository;
use CreativeCrafts\LaravelAiAssistant\Transport\GuzzleOpenAITransport;
use GuzzleHttp\Client as GuzzleClient;
use GuzzleHttp\Psr7\Response as Psr7Response;

afterEach(function () {
    Mockery::close();
});

it('creates moderation requests', function () {
    $client = Mockery::mock(GuzzleClient::class);

    $client->shouldReceive('request')
        ->once()
        ->withArgs(function (string $method, string $uri, array $options) {
            expect($method)->toBe('POST');
            expect($uri)->toBe('/v1/moderations');
            expect($options['json']['input'] ?? null)->toBe('hello');
            return true;
        })
        ->andReturn(new Psr7Response(200, [], json_encode(['id' => 'mod_1'])));

    $repo = new ModerationsHttpRepository(new GuzzleOpenAITransport($client));
    $out = $repo->create(['input' => 'hello']);

    expect($out['id'] ?? null)->toBe('mod_1');
});
