<?php

declare(strict_types=1);

use CreativeCrafts\LaravelAiAssistant\Repositories\Http\VectorStoreFilesHttpRepository;
use CreativeCrafts\LaravelAiAssistant\Transport\GuzzleOpenAITransport;
use GuzzleHttp\Client as GuzzleClient;
use GuzzleHttp\Psr7\Response as Psr7Response;

afterEach(function () {
    Mockery::close();
});

it('creates vector store files with beta header', function () {
    $client = Mockery::mock(GuzzleClient::class);

    $client->shouldReceive('request')
        ->once()
        ->withArgs(function (string $method, string $uri, array $options) {
            expect($method)->toBe('POST');
            expect($uri)->toBe('/v1/vector_stores/vs_1/files');
            expect($options['headers']['OpenAI-Beta'] ?? null)->toBe('assistants=v2');
            return true;
        })
        ->andReturn(new Psr7Response(200, [], json_encode(['id' => 'vsf_1'])));

    $repo = new VectorStoreFilesHttpRepository(new GuzzleOpenAITransport($client));
    $out = $repo->create('vs_1', ['file_id' => 'file_1']);

    expect($out['id'] ?? null)->toBe('vsf_1');
});

it('lists vector store files with beta header', function () {
    $client = Mockery::mock(GuzzleClient::class);

    $client->shouldReceive('get')
        ->once()
        ->withArgs(function (string $uri, array $options) {
            expect($uri)->toBe('/v1/vector_stores/vs_2/files?limit=1');
            expect($options['headers']['OpenAI-Beta'] ?? null)->toBe('assistants=v2');
            return true;
        })
        ->andReturn(new Psr7Response(200, [], json_encode(['data' => [['id' => 'vsf_2']]])));

    $repo = new VectorStoreFilesHttpRepository(new GuzzleOpenAITransport($client));
    $out = $repo->list('vs_2', ['limit' => 1]);

    expect($out['data'] ?? [])->toHaveCount(1);
});

it('retrieves vector store files with beta header', function () {
    $client = Mockery::mock(GuzzleClient::class);

    $client->shouldReceive('get')
        ->once()
        ->withArgs(function (string $uri, array $options) {
            expect($uri)->toBe('/v1/vector_stores/vs_3/files/vsf_3');
            expect($options['headers']['OpenAI-Beta'] ?? null)->toBe('assistants=v2');
            return true;
        })
        ->andReturn(new Psr7Response(200, [], json_encode(['id' => 'vsf_3'])));

    $repo = new VectorStoreFilesHttpRepository(new GuzzleOpenAITransport($client));
    $out = $repo->retrieve('vs_3', 'vsf_3');

    expect($out['id'] ?? null)->toBe('vsf_3');
});

it('updates vector store files with beta header', function () {
    $client = Mockery::mock(GuzzleClient::class);

    $client->shouldReceive('request')
        ->once()
        ->withArgs(function (string $method, string $uri, array $options) {
            expect($method)->toBe('POST');
            expect($uri)->toBe('/v1/vector_stores/vs_4/files/vsf_4');
            expect($options['headers']['OpenAI-Beta'] ?? null)->toBe('assistants=v2');
            return true;
        })
        ->andReturn(new Psr7Response(200, [], json_encode(['id' => 'vsf_4', 'metadata' => ['tag' => 'x']])));

    $repo = new VectorStoreFilesHttpRepository(new GuzzleOpenAITransport($client));
    $out = $repo->update('vs_4', 'vsf_4', ['metadata' => ['tag' => 'x']]);

    expect($out['metadata']['tag'] ?? null)->toBe('x');
});

it('deletes vector store files with beta header', function () {
    $client = Mockery::mock(GuzzleClient::class);

    $client->shouldReceive('delete')
        ->once()
        ->withArgs(function (string $uri, array $options) {
            expect($uri)->toBe('/v1/vector_stores/vs_5/files/vsf_5');
            expect($options['headers']['OpenAI-Beta'] ?? null)->toBe('assistants=v2');
            return true;
        })
        ->andReturn(new Psr7Response(200, [], json_encode(['deleted' => true])));

    $repo = new VectorStoreFilesHttpRepository(new GuzzleOpenAITransport($client));
    $out = $repo->delete('vs_5', 'vsf_5');

    expect($out)->toBeTrue();
});

it('retrieves vector store file content with beta header', function () {
    $client = Mockery::mock(GuzzleClient::class);

    $client->shouldReceive('get')
        ->once()
        ->withArgs(function (string $uri, array $options) {
            expect($uri)->toBe('/v1/vector_stores/vs_6/files/vsf_6/content');
            expect($options['headers']['OpenAI-Beta'] ?? null)->toBe('assistants=v2');
            return true;
        })
        ->andReturn(new Psr7Response(200, ['Content-Type' => 'application/jsonl'], "row\n"));

    $repo = new VectorStoreFilesHttpRepository(new GuzzleOpenAITransport($client));
    $out = $repo->content('vs_6', 'vsf_6');

    expect($out['content'])->toBe("row\n")
        ->and($out['content_type'])->toBe('application/jsonl');
});
