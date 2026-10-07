<?php

declare(strict_types=1);

use CreativeCrafts\LaravelAiAssistant\Repositories\Http\RealtimeHttpRepository;
use CreativeCrafts\LaravelAiAssistant\Tests\Fakes\RecordingHttpClient;

it('sends createClientSecret as POST /v1/realtime/client_secrets', function () {
    $http = RecordingHttpClient::json(['id' => 'ok']);
    $repository = new RealtimeHttpRepository($http->transport());

    expect($repository->createClientSecret(['probe' => 'value']))->toBe(['id' => 'ok']);

    $request = $http->lastRequest();
    expect($request->getMethod())->toBe('POST')
        ->and($http->lastUri())->toBe('https://api.openai.com/v1/realtime/client_secrets')
        ->and($http->lastJson())->toBe(['probe' => 'value']);
});

it('sends acceptCall as POST /v1/realtime/calls/{callId}/accept', function () {
    $http = RecordingHttpClient::json(['id' => 'ok']);
    $repository = new RealtimeHttpRepository($http->transport());

    expect($repository->acceptCall('callId_1', ['probe' => 'value']))->toBe(['id' => 'ok']);

    $request = $http->lastRequest();
    expect($request->getMethod())->toBe('POST')
        ->and($http->lastUri())->toBe('https://api.openai.com/v1/realtime/calls/callId_1/accept')
        ->and($http->lastJson())->toBe(['probe' => 'value']);
});

it('sends hangupCall as POST /v1/realtime/calls/{callId}/hangup', function () {
    $http = RecordingHttpClient::json(['id' => 'ok']);
    $repository = new RealtimeHttpRepository($http->transport());

    expect($repository->hangupCall('callId_1'))->toBe(['id' => 'ok']);

    $request = $http->lastRequest();
    expect($request->getMethod())->toBe('POST')
        ->and($http->lastUri())->toBe('https://api.openai.com/v1/realtime/calls/callId_1/hangup')
        ->and($http->lastBody())->toBe('');
});

it('sends referCall as POST /v1/realtime/calls/{callId}/refer', function () {
    $http = RecordingHttpClient::json(['id' => 'ok']);
    $repository = new RealtimeHttpRepository($http->transport());

    expect($repository->referCall('callId_1', ['probe' => 'value']))->toBe(['id' => 'ok']);

    $request = $http->lastRequest();
    expect($request->getMethod())->toBe('POST')
        ->and($http->lastUri())->toBe('https://api.openai.com/v1/realtime/calls/callId_1/refer')
        ->and($http->lastJson())->toBe(['probe' => 'value']);
});

it('sends rejectCall as POST /v1/realtime/calls/{callId}/reject', function () {
    $http = RecordingHttpClient::json(['id' => 'ok']);
    $repository = new RealtimeHttpRepository($http->transport());

    expect($repository->rejectCall('callId_1', ['probe' => 'value']))->toBe(['id' => 'ok']);

    $request = $http->lastRequest();
    expect($request->getMethod())->toBe('POST')
        ->and($http->lastUri())->toBe('https://api.openai.com/v1/realtime/calls/callId_1/reject')
        ->and($http->lastJson())->toBe(['probe' => 'value']);
});

it('sends createTranslationClientSecret as POST /v1/realtime/translations/client_secrets', function () {
    $http = RecordingHttpClient::json(['id' => 'ok']);
    $repository = new RealtimeHttpRepository($http->transport());

    expect($repository->createTranslationClientSecret(['probe' => 'value']))->toBe(['id' => 'ok']);

    $request = $http->lastRequest();
    expect($request->getMethod())->toBe('POST')
        ->and($http->lastUri())->toBe('https://api.openai.com/v1/realtime/translations/client_secrets')
        ->and($http->lastJson())->toBe(['probe' => 'value']);
});

it('sends createTranscriptionSession as POST /v1/realtime/transcription_sessions', function () {
    $http = RecordingHttpClient::json(['id' => 'ok']);
    $repository = new RealtimeHttpRepository($http->transport());

    expect($repository->createTranscriptionSession(['probe' => 'value']))->toBe(['id' => 'ok']);

    $request = $http->lastRequest();
    expect($request->getMethod())->toBe('POST')
        ->and($http->lastUri())->toBe('https://api.openai.com/v1/realtime/transcription_sessions')
        ->and($http->lastJson())->toBe(['probe' => 'value']);
});
