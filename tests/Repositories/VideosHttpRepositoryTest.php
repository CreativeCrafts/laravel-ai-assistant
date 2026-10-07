<?php

declare(strict_types=1);

use CreativeCrafts\LaravelAiAssistant\Repositories\Http\VideosHttpRepository;
use CreativeCrafts\LaravelAiAssistant\Tests\Fakes\RecordingHttpClient;
use GuzzleHttp\Psr7\Response;

beforeEach(function () {
    $this->fixture = __DIR__ . '/../fixtures/test-audio.mp3';
});

it('sends create as POST /v1/videos', function () {
    $http = RecordingHttpClient::json(['id' => 'ok']);
    $repository = new VideosHttpRepository($http->transport());

    expect($repository->create(['input_reference' => $this->fixture, 'prompt' => 'probe']))->toBe(['id' => 'ok']);

    $request = $http->lastRequest();
    expect($request->getMethod())->toBe('POST')
        ->and($http->lastUri())->toBe('https://api.openai.com/v1/videos')
        ->and($request->getHeaderLine('Content-Type'))->toStartWith('multipart/form-data')
        ->and($http->lastBody())->toContain('name="input_reference"; filename="test-audio.mp3"')
        ->and($http->lastBody())->toContain('name="prompt"');
});

it('sends retrieve as GET /v1/videos/{videoId}', function () {
    $http = RecordingHttpClient::json(['id' => 'ok']);
    $repository = new VideosHttpRepository($http->transport());

    expect($repository->retrieve('videoId_1'))->toBe(['id' => 'ok']);

    $request = $http->lastRequest();
    expect($request->getMethod())->toBe('GET')
        ->and($http->lastUri())->toBe('https://api.openai.com/v1/videos/videoId_1');
});

it('sends list as GET /v1/videos', function () {
    $http = RecordingHttpClient::json(['id' => 'ok']);
    $repository = new VideosHttpRepository($http->transport());

    expect($repository->list(['limit' => 2]))->toBe(['id' => 'ok']);

    $request = $http->lastRequest();
    expect($request->getMethod())->toBe('GET')
        ->and($http->lastUri())->toBe('https://api.openai.com/v1/videos?limit=2');
});

it('sends delete as DELETE /v1/videos/{videoId}', function () {
    $http = RecordingHttpClient::json(['id' => 'ok']);
    $repository = new VideosHttpRepository($http->transport());

    expect($repository->delete('videoId_1'))->toBe(['id' => 'ok']);

    $request = $http->lastRequest();
    expect($request->getMethod())->toBe('DELETE')
        ->and($http->lastUri())->toBe('https://api.openai.com/v1/videos/videoId_1');
});

it('sends remix as POST /v1/videos/{videoId}/remix', function () {
    $http = RecordingHttpClient::json(['id' => 'ok']);
    $repository = new VideosHttpRepository($http->transport());

    expect($repository->remix('videoId_1', ['prompt' => 'probe']))->toBe(['id' => 'ok']);

    $request = $http->lastRequest();
    expect($request->getMethod())->toBe('POST')
        ->and($http->lastUri())->toBe('https://api.openai.com/v1/videos/videoId_1/remix')
        ->and($request->getHeaderLine('Content-Type'))->toStartWith('multipart/form-data')
        ->and($http->lastBody())->toContain('name="prompt"');
});

it('sends downloadContent as GET /v1/videos/{videoId}/content', function () {
    $http = new RecordingHttpClient(new Response(200, ['Content-Type' => 'application/octet-stream'], 'BYTES'));
    $repository = new VideosHttpRepository($http->transport());

    expect($repository->downloadContent('videoId_1', ['limit' => 2]))->toBe(['content' => 'BYTES', 'content_type' => 'application/octet-stream']);

    $request = $http->lastRequest();
    expect($request->getMethod())->toBe('GET')
        ->and($http->lastUri())->toBe('https://api.openai.com/v1/videos/videoId_1/content?limit=2');
});

it('sends edit as POST /v1/videos/edits', function () {
    $http = RecordingHttpClient::json(['id' => 'ok']);
    $repository = new VideosHttpRepository($http->transport());

    expect($repository->edit(['video' => $this->fixture, 'prompt' => 'probe']))->toBe(['id' => 'ok']);

    $request = $http->lastRequest();
    expect($request->getMethod())->toBe('POST')
        ->and($http->lastUri())->toBe('https://api.openai.com/v1/videos/edits')
        ->and($request->getHeaderLine('Content-Type'))->toStartWith('multipart/form-data')
        ->and($http->lastBody())->toContain('name="video"; filename="test-audio.mp3"')
        ->and($http->lastBody())->toContain('name="prompt"');
});

it('sends extend as POST /v1/videos/extensions', function () {
    $http = RecordingHttpClient::json(['id' => 'ok']);
    $repository = new VideosHttpRepository($http->transport());

    expect($repository->extend(['video' => $this->fixture, 'prompt' => 'probe']))->toBe(['id' => 'ok']);

    $request = $http->lastRequest();
    expect($request->getMethod())->toBe('POST')
        ->and($http->lastUri())->toBe('https://api.openai.com/v1/videos/extensions')
        ->and($request->getHeaderLine('Content-Type'))->toStartWith('multipart/form-data')
        ->and($http->lastBody())->toContain('name="video"; filename="test-audio.mp3"')
        ->and($http->lastBody())->toContain('name="prompt"');
});

it('sends createCharacter as POST /v1/videos/characters', function () {
    $http = RecordingHttpClient::json(['id' => 'ok']);
    $repository = new VideosHttpRepository($http->transport());

    expect($repository->createCharacter(['video' => $this->fixture, 'prompt' => 'probe']))->toBe(['id' => 'ok']);

    $request = $http->lastRequest();
    expect($request->getMethod())->toBe('POST')
        ->and($http->lastUri())->toBe('https://api.openai.com/v1/videos/characters')
        ->and($request->getHeaderLine('Content-Type'))->toStartWith('multipart/form-data')
        ->and($http->lastBody())->toContain('name="video"; filename="test-audio.mp3"')
        ->and($http->lastBody())->toContain('name="prompt"');
});

it('sends retrieveCharacter as GET /v1/videos/characters/{characterId}', function () {
    $http = RecordingHttpClient::json(['id' => 'ok']);
    $repository = new VideosHttpRepository($http->transport());

    expect($repository->retrieveCharacter('characterId_1'))->toBe(['id' => 'ok']);

    $request = $http->lastRequest();
    expect($request->getMethod())->toBe('GET')
        ->and($http->lastUri())->toBe('https://api.openai.com/v1/videos/characters/characterId_1');
});
