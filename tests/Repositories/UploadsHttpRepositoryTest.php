<?php

declare(strict_types=1);

use CreativeCrafts\LaravelAiAssistant\Repositories\Http\UploadsHttpRepository;
use CreativeCrafts\LaravelAiAssistant\Tests\Fakes\RecordingHttpClient;

beforeEach(function () {
    $this->fixture = __DIR__ . '/../fixtures/test-audio.mp3';
});

it('sends create as POST /v1/uploads', function () {
    $http = RecordingHttpClient::json(['id' => 'ok']);
    $repository = new UploadsHttpRepository($http->transport());

    expect($repository->create(['probe' => 'value']))->toBe(['id' => 'ok']);

    $request = $http->lastRequest();
    expect($request->getMethod())->toBe('POST')
        ->and($http->lastUri())->toBe('https://api.openai.com/v1/uploads')
        ->and($http->lastJson())->toBe(['probe' => 'value']);
});

it('sends addPart as POST /v1/uploads/{uploadId}/parts', function () {
    $http = RecordingHttpClient::json(['id' => 'ok']);
    $repository = new UploadsHttpRepository($http->transport());

    expect($repository->addPart('uploadId_1', ['data' => $this->fixture, 'prompt' => 'probe']))->toBe(['id' => 'ok']);

    $request = $http->lastRequest();
    expect($request->getMethod())->toBe('POST')
        ->and($http->lastUri())->toBe('https://api.openai.com/v1/uploads/uploadId_1/parts')
        ->and($request->getHeaderLine('Content-Type'))->toStartWith('multipart/form-data')
        ->and($http->lastBody())->toContain('name="data"; filename="test-audio.mp3"')
        ->and($http->lastBody())->toContain('name="prompt"');
});

it('sends complete as POST /v1/uploads/{uploadId}/complete', function () {
    $http = RecordingHttpClient::json(['id' => 'ok']);
    $repository = new UploadsHttpRepository($http->transport());

    expect($repository->complete('uploadId_1', ['probe' => 'value']))->toBe(['id' => 'ok']);

    $request = $http->lastRequest();
    expect($request->getMethod())->toBe('POST')
        ->and($http->lastUri())->toBe('https://api.openai.com/v1/uploads/uploadId_1/complete')
        ->and($http->lastJson())->toBe(['probe' => 'value']);
});

it('sends cancel as POST /v1/uploads/{uploadId}/cancel', function () {
    $http = RecordingHttpClient::json(['id' => 'ok']);
    $repository = new UploadsHttpRepository($http->transport());

    expect($repository->cancel('uploadId_1'))->toBe(['id' => 'ok']);

    $request = $http->lastRequest();
    expect($request->getMethod())->toBe('POST')
        ->and($http->lastUri())->toBe('https://api.openai.com/v1/uploads/uploadId_1/cancel')
        ->and($http->lastBody())->toBe('');
});
