<?php

declare(strict_types=1);

use CreativeCrafts\LaravelAiAssistant\Repositories\Http\ChatKitHttpRepository;
use CreativeCrafts\LaravelAiAssistant\Tests\Fakes\RecordingHttpClient;

it('sends createSession as POST /v1/chatkit/sessions', function () {
    $http = RecordingHttpClient::json(['id' => 'ok']);
    $repository = new ChatKitHttpRepository($http->transport());

    expect($repository->createSession(['probe' => 'value']))->toBe(['id' => 'ok']);

    $request = $http->lastRequest();
    expect($request->getMethod())->toBe('POST')
        ->and($http->lastUri())->toBe('https://api.openai.com/v1/chatkit/sessions')
        ->and($http->lastJson())->toBe(['probe' => 'value'])
        ->and($request->getHeaderLine('OpenAI-Beta'))->toBe('chatkit_beta=v1');
});

it('sends cancelSession as POST /v1/chatkit/sessions/{sessionId}/cancel', function () {
    $http = RecordingHttpClient::json(['id' => 'ok']);
    $repository = new ChatKitHttpRepository($http->transport());

    expect($repository->cancelSession('sessionId_1'))->toBe(['id' => 'ok']);

    $request = $http->lastRequest();
    expect($request->getMethod())->toBe('POST')
        ->and($http->lastUri())->toBe('https://api.openai.com/v1/chatkit/sessions/sessionId_1/cancel')
        ->and($http->lastBody())->toBe('')
        ->and($request->getHeaderLine('OpenAI-Beta'))->toBe('chatkit_beta=v1');
});

it('sends retrieveThread as GET /v1/chatkit/threads/{threadId}', function () {
    $http = RecordingHttpClient::json(['id' => 'ok']);
    $repository = new ChatKitHttpRepository($http->transport());

    expect($repository->retrieveThread('threadId_1'))->toBe(['id' => 'ok']);

    $request = $http->lastRequest();
    expect($request->getMethod())->toBe('GET')
        ->and($http->lastUri())->toBe('https://api.openai.com/v1/chatkit/threads/threadId_1')
        ->and($request->getHeaderLine('OpenAI-Beta'))->toBe('chatkit_beta=v1');
});

it('sends listThreads as GET /v1/chatkit/threads', function () {
    $http = RecordingHttpClient::json(['id' => 'ok']);
    $repository = new ChatKitHttpRepository($http->transport());

    expect($repository->listThreads(['limit' => 2]))->toBe(['id' => 'ok']);

    $request = $http->lastRequest();
    expect($request->getMethod())->toBe('GET')
        ->and($http->lastUri())->toBe('https://api.openai.com/v1/chatkit/threads?limit=2')
        ->and($request->getHeaderLine('OpenAI-Beta'))->toBe('chatkit_beta=v1');
});

it('sends deleteThread as DELETE /v1/chatkit/threads/{threadId}', function () {
    $http = RecordingHttpClient::json(['id' => 'ok']);
    $repository = new ChatKitHttpRepository($http->transport());

    expect($repository->deleteThread('threadId_1'))->toBe(['id' => 'ok']);

    $request = $http->lastRequest();
    expect($request->getMethod())->toBe('DELETE')
        ->and($http->lastUri())->toBe('https://api.openai.com/v1/chatkit/threads/threadId_1')
        ->and($request->getHeaderLine('OpenAI-Beta'))->toBe('chatkit_beta=v1');
});

it('sends listThreadItems as GET /v1/chatkit/threads/{threadId}/items', function () {
    $http = RecordingHttpClient::json(['id' => 'ok']);
    $repository = new ChatKitHttpRepository($http->transport());

    expect($repository->listThreadItems('threadId_1', ['limit' => 2]))->toBe(['id' => 'ok']);

    $request = $http->lastRequest();
    expect($request->getMethod())->toBe('GET')
        ->and($http->lastUri())->toBe('https://api.openai.com/v1/chatkit/threads/threadId_1/items?limit=2')
        ->and($request->getHeaderLine('OpenAI-Beta'))->toBe('chatkit_beta=v1');
});
