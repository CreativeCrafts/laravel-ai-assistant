<?php

declare(strict_types=1);

use CreativeCrafts\LaravelAiAssistant\Repositories\Http\BatchesHttpRepository;
use CreativeCrafts\LaravelAiAssistant\Transport\GuzzleOpenAITransport;
use GuzzleHttp\Client as GuzzleClient;
use GuzzleHttp\Psr7\Response as Psr7Response;

afterEach(function () {
    Mockery::close();
});

it('creates batches', function () {
    $client = Mockery::mock(GuzzleClient::class);

    $client->shouldReceive('request')
        ->once()
        ->withArgs(function (string $method, string $uri, array $options) {
            expect($method)->toBe('POST');
            expect($uri)->toBe('/v1/batches');
            expect($options['json']['input_file_id'] ?? null)->toBe('file_1');
            return true;
        })
        ->andReturn(new Psr7Response(200, [], json_encode(['id' => 'batch_1'])));

    $repo = new BatchesHttpRepository(new GuzzleOpenAITransport($client));
    $out = $repo->create(['input_file_id' => 'file_1']);

    expect($out['id'] ?? null)->toBe('batch_1');
});

it('lists batches with query params', function () {
    $client = Mockery::mock(GuzzleClient::class);

    $client->shouldReceive('get')
        ->once()
        ->withArgs(function (string $uri, array $options) {
            expect($uri)->toBe('/v1/batches?limit=2');
            return true;
        })
        ->andReturn(new Psr7Response(200, [], json_encode(['data' => [['id' => 'b1'], ['id' => 'b2']]])));

    $repo = new BatchesHttpRepository(new GuzzleOpenAITransport($client));
    $out = $repo->list(['limit' => 2]);

    expect($out['data'] ?? [])->toHaveCount(2);
});

it('retrieves batches', function () {
    $client = Mockery::mock(GuzzleClient::class);

    $client->shouldReceive('get')
        ->once()
        ->withArgs(function (string $uri, array $options) {
            expect($uri)->toBe('/v1/batches/batch_2');
            return true;
        })
        ->andReturn(new Psr7Response(200, [], json_encode(['id' => 'batch_2'])));

    $repo = new BatchesHttpRepository(new GuzzleOpenAITransport($client));
    $out = $repo->retrieve('batch_2');

    expect($out['id'] ?? null)->toBe('batch_2');
});

it('cancels batches', function () {
    $client = Mockery::mock(GuzzleClient::class);

    $client->shouldReceive('request')
        ->once()
        ->withArgs(function (string $method, string $uri, array $options) {
            expect($method)->toBe('POST');
            expect($uri)->toBe('/v1/batches/batch_3/cancel');
            return true;
        })
        ->andReturn(new Psr7Response(200, [], json_encode(['status' => 'canceled'])));

    $repo = new BatchesHttpRepository(new GuzzleOpenAITransport($client));
    $out = $repo->cancel('batch_3');

    expect($out['status'] ?? null)->toBe('canceled');
});
