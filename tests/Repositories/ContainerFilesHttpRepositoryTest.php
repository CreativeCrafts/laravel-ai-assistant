<?php

declare(strict_types=1);

use CreativeCrafts\LaravelAiAssistant\Repositories\Http\ContainerFilesHttpRepository;
use CreativeCrafts\LaravelAiAssistant\Tests\Fakes\RecordingHttpClient;
use GuzzleHttp\Psr7\Response;

beforeEach(function () {
    $this->fixture = __DIR__ . '/../fixtures/test-audio.mp3';
});

it('sends create as POST /v1/containers/{containerId}/files', function () {
    $http = RecordingHttpClient::json(['id' => 'ok']);
    $repository = new ContainerFilesHttpRepository($http->transport());

    expect($repository->create('containerId_1', ['file' => $this->fixture, 'prompt' => 'probe']))->toBe(['id' => 'ok']);

    $request = $http->lastRequest();
    expect($request->getMethod())->toBe('POST')
        ->and($http->lastUri())->toBe('https://api.openai.com/v1/containers/containerId_1/files')
        ->and($request->getHeaderLine('Content-Type'))->toStartWith('multipart/form-data')
        ->and($http->lastBody())->toContain('name="file"; filename="test-audio.mp3"')
        ->and($http->lastBody())->toContain('name="prompt"');
});

it('sends retrieve as GET /v1/containers/{containerId}/files/{fileId}', function () {
    $http = RecordingHttpClient::json(['id' => 'ok']);
    $repository = new ContainerFilesHttpRepository($http->transport());

    expect($repository->retrieve('containerId_1', 'fileId_2'))->toBe(['id' => 'ok']);

    $request = $http->lastRequest();
    expect($request->getMethod())->toBe('GET')
        ->and($http->lastUri())->toBe('https://api.openai.com/v1/containers/containerId_1/files/fileId_2');
});

it('sends list as GET /v1/containers/{containerId}/files', function () {
    $http = RecordingHttpClient::json(['id' => 'ok']);
    $repository = new ContainerFilesHttpRepository($http->transport());

    expect($repository->list('containerId_1', ['limit' => 2]))->toBe(['id' => 'ok']);

    $request = $http->lastRequest();
    expect($request->getMethod())->toBe('GET')
        ->and($http->lastUri())->toBe('https://api.openai.com/v1/containers/containerId_1/files?limit=2');
});

it('sends delete as DELETE /v1/containers/{containerId}/files/{fileId}', function () {
    $http = RecordingHttpClient::json(['id' => 'ok']);
    $repository = new ContainerFilesHttpRepository($http->transport());

    expect($repository->delete('containerId_1', 'fileId_2'))->toBe(['id' => 'ok']);

    $request = $http->lastRequest();
    expect($request->getMethod())->toBe('DELETE')
        ->and($http->lastUri())->toBe('https://api.openai.com/v1/containers/containerId_1/files/fileId_2');
});

it('sends content as GET /v1/containers/{containerId}/files/{fileId}/content', function () {
    $http = new RecordingHttpClient(new Response(200, ['Content-Type' => 'application/octet-stream'], 'BYTES'));
    $repository = new ContainerFilesHttpRepository($http->transport());

    expect($repository->content('containerId_1', 'fileId_2'))->toBe(['content' => 'BYTES', 'content_type' => 'application/octet-stream']);

    $request = $http->lastRequest();
    expect($request->getMethod())->toBe('GET')
        ->and($http->lastUri())->toBe('https://api.openai.com/v1/containers/containerId_1/files/fileId_2/content');
});
