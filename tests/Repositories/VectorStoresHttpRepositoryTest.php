<?php

declare(strict_types=1);

use CreativeCrafts\LaravelAiAssistant\Repositories\Http\VectorStoresHttpRepository;
use CreativeCrafts\LaravelAiAssistant\Transport\GuzzleOpenAITransport;
use GuzzleHttp\Client as GuzzleClient;
use GuzzleHttp\Psr7\Response as Psr7Response;

afterEach(function () {
    Mockery::close();
});

it('creates vector stores with beta header', function () {
    $client = Mockery::mock(GuzzleClient::class);

    $client->shouldReceive('request')
        ->once()
        ->withArgs(function (string $method, string $uri, array $options) {
            expect($method)->toBe('POST');
            expect($uri)->toBe('/v1/vector_stores');
            expect($options['headers']['OpenAI-Beta'] ?? null)->toBe('assistants=v2');
            return true;
        })
        ->andReturn(new Psr7Response(200, [], json_encode(['id' => 'vs_1'])));

    $repo = new VectorStoresHttpRepository(new GuzzleOpenAITransport($client));
    $out = $repo->create(['name' => 'docs']);

    expect($out['id'] ?? null)->toBe('vs_1');
});

it('lists vector stores with beta header', function () {
    $client = Mockery::mock(GuzzleClient::class);

    $client->shouldReceive('get')
        ->once()
        ->withArgs(function (string $uri, array $options) {
            expect($uri)->toBe('/v1/vector_stores?limit=2');
            expect($options['headers']['OpenAI-Beta'] ?? null)->toBe('assistants=v2');
            return true;
        })
        ->andReturn(new Psr7Response(200, [], json_encode(['data' => [['id' => 'vs_1'], ['id' => 'vs_2']]])));

    $repo = new VectorStoresHttpRepository(new GuzzleOpenAITransport($client));
    $out = $repo->list(['limit' => 2]);

    expect($out['data'] ?? [])->toHaveCount(2);
});

it('retrieves vector stores with beta header', function () {
    $client = Mockery::mock(GuzzleClient::class);

    $client->shouldReceive('get')
        ->once()
        ->withArgs(function (string $uri, array $options) {
            expect($uri)->toBe('/v1/vector_stores/vs_2');
            expect($options['headers']['OpenAI-Beta'] ?? null)->toBe('assistants=v2');
            return true;
        })
        ->andReturn(new Psr7Response(200, [], json_encode(['id' => 'vs_2'])));

    $repo = new VectorStoresHttpRepository(new GuzzleOpenAITransport($client));
    $out = $repo->retrieve('vs_2');

    expect($out['id'] ?? null)->toBe('vs_2');
});

it('updates vector stores with beta header', function () {
    $client = Mockery::mock(GuzzleClient::class);

    $client->shouldReceive('request')
        ->once()
        ->withArgs(function (string $method, string $uri, array $options) {
            expect($method)->toBe('POST');
            expect($uri)->toBe('/v1/vector_stores/vs_3');
            expect($options['headers']['OpenAI-Beta'] ?? null)->toBe('assistants=v2');
            return true;
        })
        ->andReturn(new Psr7Response(200, [], json_encode(['id' => 'vs_3', 'name' => 'updated'])));

    $repo = new VectorStoresHttpRepository(new GuzzleOpenAITransport($client));
    $out = $repo->update('vs_3', ['name' => 'updated']);

    expect($out['name'] ?? null)->toBe('updated');
});

it('deletes vector stores with beta header', function () {
    $client = Mockery::mock(GuzzleClient::class);

    $client->shouldReceive('delete')
        ->once()
        ->withArgs(function (string $uri, array $options) {
            expect($uri)->toBe('/v1/vector_stores/vs_4');
            expect($options['headers']['OpenAI-Beta'] ?? null)->toBe('assistants=v2');
            return true;
        })
        ->andReturn(new Psr7Response(200, [], json_encode(['deleted' => true])));

    $repo = new VectorStoresHttpRepository(new GuzzleOpenAITransport($client));
    $out = $repo->delete('vs_4');

    expect($out)->toBeTrue();
});
