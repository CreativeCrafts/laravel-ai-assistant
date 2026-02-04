<?php

declare(strict_types=1);

use CreativeCrafts\LaravelAiAssistant\Repositories\Http\VectorStoreFileBatchesHttpRepository;
use CreativeCrafts\LaravelAiAssistant\Transport\GuzzleOpenAITransport;
use GuzzleHttp\Client as GuzzleClient;
use GuzzleHttp\Psr7\Response as Psr7Response;

afterEach(function () {
    Mockery::close();
});

it('creates vector store file batches with beta header', function () {
    $client = Mockery::mock(GuzzleClient::class);

    $client->shouldReceive('request')
        ->once()
        ->withArgs(function (string $method, string $uri, array $options) {
            expect($method)->toBe('POST');
            expect($uri)->toBe('/v1/vector_stores/vs_1/file_batches');
            expect($options['headers']['OpenAI-Beta'] ?? null)->toBe('assistants=v2');
            return true;
        })
        ->andReturn(new Psr7Response(200, [], json_encode(['id' => 'vsfb_1'])));

    $repo = new VectorStoreFileBatchesHttpRepository(new GuzzleOpenAITransport($client));
    $out = $repo->create('vs_1', ['file_ids' => ['file_1']]);

    expect($out['id'] ?? null)->toBe('vsfb_1');
});

it('retrieves vector store file batches with beta header', function () {
    $client = Mockery::mock(GuzzleClient::class);

    $client->shouldReceive('get')
        ->once()
        ->withArgs(function (string $uri, array $options) {
            expect($uri)->toBe('/v1/vector_stores/vs_2/file_batches/vsfb_2');
            expect($options['headers']['OpenAI-Beta'] ?? null)->toBe('assistants=v2');
            return true;
        })
        ->andReturn(new Psr7Response(200, [], json_encode(['id' => 'vsfb_2'])));

    $repo = new VectorStoreFileBatchesHttpRepository(new GuzzleOpenAITransport($client));
    $out = $repo->retrieve('vs_2', 'vsfb_2');

    expect($out['id'] ?? null)->toBe('vsfb_2');
});

it('cancels vector store file batches with beta header', function () {
    $client = Mockery::mock(GuzzleClient::class);

    $client->shouldReceive('request')
        ->once()
        ->withArgs(function (string $method, string $uri, array $options) {
            expect($method)->toBe('POST');
            expect($uri)->toBe('/v1/vector_stores/vs_3/file_batches/vsfb_3/cancel');
            expect($options['headers']['OpenAI-Beta'] ?? null)->toBe('assistants=v2');
            return true;
        })
        ->andReturn(new Psr7Response(200, [], json_encode(['status' => 'canceled'])));

    $repo = new VectorStoreFileBatchesHttpRepository(new GuzzleOpenAITransport($client));
    $out = $repo->cancel('vs_3', 'vsfb_3');

    expect($out['status'] ?? null)->toBe('canceled');
});

it('lists files in vector store file batches with beta header', function () {
    $client = Mockery::mock(GuzzleClient::class);

    $client->shouldReceive('get')
        ->once()
        ->withArgs(function (string $uri, array $options) {
            expect($uri)->toBe('/v1/vector_stores/vs_4/file_batches/vsfb_4/files?limit=1');
            expect($options['headers']['OpenAI-Beta'] ?? null)->toBe('assistants=v2');
            return true;
        })
        ->andReturn(new Psr7Response(200, [], json_encode(['data' => [['id' => 'vsf_1']]])));

    $repo = new VectorStoreFileBatchesHttpRepository(new GuzzleOpenAITransport($client));
    $out = $repo->listFiles('vs_4', 'vsfb_4', ['limit' => 1]);

    expect($out['data'] ?? [])->toHaveCount(1);
});
