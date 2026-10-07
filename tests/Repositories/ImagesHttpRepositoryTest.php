<?php

declare(strict_types=1);

use CreativeCrafts\LaravelAiAssistant\Repositories\Http\ImagesHttpRepository;
use CreativeCrafts\LaravelAiAssistant\Tests\Fakes\RecordingHttpClient;

beforeEach(function () {
    $this->fixture = __DIR__ . '/../fixtures/test-audio.mp3';
});

it('sends generate as POST /v1/images/generations', function () {
    $http = RecordingHttpClient::json(['id' => 'ok']);
    $repository = new ImagesHttpRepository($http->transport());

    expect($repository->generate(['probe' => 'value']))->toBe(['id' => 'ok']);

    $request = $http->lastRequest();
    expect($request->getMethod())->toBe('POST')
        ->and($http->lastUri())->toBe('https://api.openai.com/v1/images/generations')
        ->and($http->lastJson())->toBe(['probe' => 'value']);
});

it('sends streamGeneration as POST /v1/images/generations', function () {
    $http = RecordingHttpClient::sse('{"type":"probe.event"}');
    $repository = new ImagesHttpRepository($http->transport());

    expect(iterator_to_array($repository->streamGeneration(['probe' => 'value']), false))->toBe([['type' => 'probe.event']]);

    $request = $http->lastRequest();
    expect($request->getMethod())->toBe('POST')
        ->and($http->lastUri())->toBe('https://api.openai.com/v1/images/generations')
        ->and($http->lastJson())->toBe(['probe' => 'value', 'stream' => true])
        ->and($request->getHeaderLine('Accept'))->toBe('text/event-stream');
});

it('sends edit as POST /v1/images/edits', function () {
    $http = RecordingHttpClient::json(['id' => 'ok']);
    $repository = new ImagesHttpRepository($http->transport());

    expect($repository->edit(['image' => $this->fixture, 'mask' => $this->fixture, 'prompt' => 'probe']))->toBe(['id' => 'ok']);

    $request = $http->lastRequest();
    expect($request->getMethod())->toBe('POST')
        ->and($http->lastUri())->toBe('https://api.openai.com/v1/images/edits')
        ->and($request->getHeaderLine('Content-Type'))->toStartWith('multipart/form-data')
        ->and($http->lastBody())->toContain('name="image"; filename="test-audio.mp3"')
        ->and($http->lastBody())->toContain('name="mask"; filename="test-audio.mp3"')
        ->and($http->lastBody())->toContain('name="prompt"');
});

it('sends streamEdit as POST /v1/images/edits', function () {
    $http = RecordingHttpClient::sse('{"type":"probe.event"}');
    $repository = new ImagesHttpRepository($http->transport());

    expect(iterator_to_array($repository->streamEdit(['image' => $this->fixture, 'mask' => $this->fixture, 'prompt' => 'probe']), false))->toBe([['type' => 'probe.event']]);

    $request = $http->lastRequest();
    expect($request->getMethod())->toBe('POST')
        ->and($http->lastUri())->toBe('https://api.openai.com/v1/images/edits')
        ->and($request->getHeaderLine('Content-Type'))->toStartWith('multipart/form-data')
        ->and($http->lastBody())->toContain('name="image"; filename="test-audio.mp3"')
        ->and($http->lastBody())->toContain('name="mask"; filename="test-audio.mp3"')
        ->and($http->lastBody())->toContain('name="prompt"')
        ->and($http->lastBody())->toContain('name="stream"');
});

it('sends createVariation as POST /v1/images/variations', function () {
    $http = RecordingHttpClient::json(['id' => 'ok']);
    $repository = new ImagesHttpRepository($http->transport());

    expect($repository->createVariation(['image' => $this->fixture, 'prompt' => 'probe']))->toBe(['id' => 'ok']);

    $request = $http->lastRequest();
    expect($request->getMethod())->toBe('POST')
        ->and($http->lastUri())->toBe('https://api.openai.com/v1/images/variations')
        ->and($request->getHeaderLine('Content-Type'))->toStartWith('multipart/form-data')
        ->and($http->lastBody())->toContain('name="image"; filename="test-audio.mp3"')
        ->and($http->lastBody())->toContain('name="prompt"');
});
