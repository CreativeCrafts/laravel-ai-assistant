<?php

declare(strict_types=1);

use CreativeCrafts\LaravelAiAssistant\Repositories\Http\AssistantsHttpRepository;
use CreativeCrafts\LaravelAiAssistant\Transport\GuzzleOpenAITransport;
use GuzzleHttp\Client as GuzzleClient;
use GuzzleHttp\Psr7\Response as Psr7Response;

afterEach(function () {
    Mockery::close();
});

it('creates assistants with beta header', function () {
    $client = Mockery::mock(GuzzleClient::class);

    $client->shouldReceive('request')
        ->once()
        ->withArgs(function (string $method, string $uri, array $options) {
            expect($method)->toBe('POST');
            expect($uri)->toBe('/v1/assistants');
            expect($options['headers']['OpenAI-Beta'] ?? null)->toBe('assistants=v2');
            return true;
        })
        ->andReturn(new Psr7Response(200, [], json_encode(['id' => 'asst_1'])));

    $repo = new AssistantsHttpRepository(new GuzzleOpenAITransport($client));
    $out = $repo->create(['model' => 'gpt-4o-mini']);

    expect($out['id'] ?? null)->toBe('asst_1');
});

it('lists assistants with beta header', function () {
    $client = Mockery::mock(GuzzleClient::class);

    $client->shouldReceive('get')
        ->once()
        ->withArgs(function (string $uri, array $options) {
            expect($uri)->toBe('/v1/assistants?limit=2');
            expect($options['headers']['OpenAI-Beta'] ?? null)->toBe('assistants=v2');
            return true;
        })
        ->andReturn(new Psr7Response(200, [], json_encode(['data' => [['id' => 'asst_1'], ['id' => 'asst_2']]])));

    $repo = new AssistantsHttpRepository(new GuzzleOpenAITransport($client));
    $out = $repo->list(['limit' => 2]);

    expect($out['data'] ?? [])->toHaveCount(2);
});

it('retrieves assistants with beta header', function () {
    $client = Mockery::mock(GuzzleClient::class);

    $client->shouldReceive('get')
        ->once()
        ->withArgs(function (string $uri, array $options) {
            expect($uri)->toBe('/v1/assistants/asst_2');
            expect($options['headers']['OpenAI-Beta'] ?? null)->toBe('assistants=v2');
            return true;
        })
        ->andReturn(new Psr7Response(200, [], json_encode(['id' => 'asst_2'])));

    $repo = new AssistantsHttpRepository(new GuzzleOpenAITransport($client));
    $out = $repo->retrieve('asst_2');

    expect($out['id'] ?? null)->toBe('asst_2');
});

it('updates assistants with beta header', function () {
    $client = Mockery::mock(GuzzleClient::class);

    $client->shouldReceive('request')
        ->once()
        ->withArgs(function (string $method, string $uri, array $options) {
            expect($method)->toBe('POST');
            expect($uri)->toBe('/v1/assistants/asst_3');
            expect($options['headers']['OpenAI-Beta'] ?? null)->toBe('assistants=v2');
            return true;
        })
        ->andReturn(new Psr7Response(200, [], json_encode(['id' => 'asst_3', 'name' => 'Updated'])));

    $repo = new AssistantsHttpRepository(new GuzzleOpenAITransport($client));
    $out = $repo->update('asst_3', ['name' => 'Updated']);

    expect($out['name'] ?? null)->toBe('Updated');
});

it('deletes assistants with beta header', function () {
    $client = Mockery::mock(GuzzleClient::class);

    $client->shouldReceive('delete')
        ->once()
        ->withArgs(function (string $uri, array $options) {
            expect($uri)->toBe('/v1/assistants/asst_4');
            expect($options['headers']['OpenAI-Beta'] ?? null)->toBe('assistants=v2');
            return true;
        })
        ->andReturn(new Psr7Response(200, [], json_encode(['deleted' => true])));

    $repo = new AssistantsHttpRepository(new GuzzleOpenAITransport($client));
    $out = $repo->delete('asst_4');

    expect($out)->toBeTrue();
});
