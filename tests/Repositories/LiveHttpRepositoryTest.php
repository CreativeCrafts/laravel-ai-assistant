<?php

declare(strict_types=1);

use CreativeCrafts\LaravelAiAssistant\Repositories\Http\LiveHttpRepository;
use CreativeCrafts\LaravelAiAssistant\Tests\Fakes\RecordingHttpClient;
use GuzzleHttp\Psr7\Response;

it('sends create as POST /v1/live/sessions', function () {
    $http = RecordingHttpClient::json(['id' => 'ok']);
    $repository = new LiveHttpRepository($http->transport());

    expect($repository->create(['probe' => 'value']))->toBe(['id' => 'ok']);

    $request = $http->lastRequest();
    expect($request->getMethod())->toBe('POST')
        ->and($http->lastUri())->toBe('https://api.openai.com/v1/live/sessions')
        ->and($http->lastJson())->toBe(['probe' => 'value']);
});

it('sends accept as POST /v1/live/sessions/{sessionId}/accept', function () {
    $http = RecordingHttpClient::json(['id' => 'ok']);
    $repository = new LiveHttpRepository($http->transport());

    expect($repository->accept('sessionId_1', ['probe' => 'value']))->toBe(['id' => 'ok']);

    $request = $http->lastRequest();
    expect($request->getMethod())->toBe('POST')
        ->and($http->lastUri())->toBe('https://api.openai.com/v1/live/sessions/sessionId_1/accept')
        ->and($http->lastJson())->toBe(['probe' => 'value']);
});

it('sends hangup as POST /v1/live/sessions/{sessionId}/hangup', function () {
    $http = RecordingHttpClient::json(['id' => 'ok']);
    $repository = new LiveHttpRepository($http->transport());

    expect($repository->hangup('sessionId_1'))->toBe(['id' => 'ok']);

    $request = $http->lastRequest();
    expect($request->getMethod())->toBe('POST')
        ->and($http->lastUri())->toBe('https://api.openai.com/v1/live/sessions/sessionId_1/hangup')
        ->and($http->lastBody())->toBe('');
});

it('sends refer as POST /v1/live/sessions/{sessionId}/refer', function () {
    $http = RecordingHttpClient::json(['id' => 'ok']);
    $repository = new LiveHttpRepository($http->transport());

    expect($repository->refer('sessionId_1', ['probe' => 'value']))->toBe(['id' => 'ok']);

    $request = $http->lastRequest();
    expect($request->getMethod())->toBe('POST')
        ->and($http->lastUri())->toBe('https://api.openai.com/v1/live/sessions/sessionId_1/refer')
        ->and($http->lastJson())->toBe(['probe' => 'value']);
});

it('sends reject as POST /v1/live/sessions/{sessionId}/reject', function () {
    $http = RecordingHttpClient::json(['id' => 'ok']);
    $repository = new LiveHttpRepository($http->transport());

    expect($repository->reject('sessionId_1', ['probe' => 'value']))->toBe(['id' => 'ok']);

    $request = $http->lastRequest();
    expect($request->getMethod())->toBe('POST')
        ->and($http->lastUri())->toBe('https://api.openai.com/v1/live/sessions/sessionId_1/reject')
        ->and($http->lastJson())->toBe(['probe' => 'value']);
});

it('sends fork as POST /v1/live/sessions/{sessionId}/fork', function () {
    $http = RecordingHttpClient::json(['id' => 'ok']);
    $repository = new LiveHttpRepository($http->transport());

    expect($repository->fork('sessionId_1', ['probe' => 'value']))->toBe(['id' => 'ok']);

    $request = $http->lastRequest();
    expect($request->getMethod())->toBe('POST')
        ->and($http->lastUri())->toBe('https://api.openai.com/v1/live/sessions/sessionId_1/fork')
        ->and($http->lastJson())->toBe(['probe' => 'value']);
});

it('sends downloadRecording as GET /v1/live/sessions/{sessionId}/content', function () {
    $http = new RecordingHttpClient(new Response(200, ['Content-Type' => 'application/octet-stream'], 'BYTES'));
    $repository = new LiveHttpRepository($http->transport());

    expect($repository->downloadRecording('sessionId_1'))->toBe(['content' => 'BYTES', 'content_type' => 'application/octet-stream']);

    $request = $http->lastRequest();
    expect($request->getMethod())->toBe('GET')
        ->and($http->lastUri())->toBe('https://api.openai.com/v1/live/sessions/sessionId_1/content');
});
