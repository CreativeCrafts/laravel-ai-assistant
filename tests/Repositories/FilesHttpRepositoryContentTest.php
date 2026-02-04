<?php

declare(strict_types=1);

use CreativeCrafts\LaravelAiAssistant\Repositories\Http\FilesHttpRepository;
use CreativeCrafts\LaravelAiAssistant\Transport\GuzzleOpenAITransport;
use GuzzleHttp\Client as GuzzleClient;
use GuzzleHttp\Psr7\Response as Psr7Response;

afterEach(function () {
    Mockery::close();
});

it('retrieves file content as raw bytes', function () {
    $client = Mockery::mock(GuzzleClient::class);

    $client->shouldReceive('get')
        ->once()
        ->withArgs(function (string $uri, array $options) {
            expect($uri)->toBe('/v1/files/file_1/content');
            return true;
        })
        ->andReturn(new Psr7Response(200, ['Content-Type' => 'application/jsonl'], "line1\nline2\n"));

    $repo = new FilesHttpRepository(new GuzzleOpenAITransport($client));
    $out = $repo->content('file_1');

    expect($out['content'])->toBe("line1\nline2\n")
        ->and($out['content_type'])->toBe('application/jsonl');
});
