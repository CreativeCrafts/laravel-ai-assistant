<?php

declare(strict_types=1);

use CreativeCrafts\LaravelAiAssistant\Repositories\Http\ChatCompletionsHttpRepository;
use CreativeCrafts\LaravelAiAssistant\Tests\Fakes\RecordingHttpClient;

it('sends create as POST /v1/chat/completions', function () {
    $http = RecordingHttpClient::json(['id' => 'ok']);
    $repository = new ChatCompletionsHttpRepository($http->transport());

    expect($repository->create(['probe' => 'value']))->toBe(['id' => 'ok']);

    $request = $http->lastRequest();
    expect($request->getMethod())->toBe('POST')
        ->and($http->lastUri())->toBe('https://api.openai.com/v1/chat/completions')
        ->and($http->lastJson())->toBe(['probe' => 'value']);
});

it('sends stream as POST /v1/chat/completions', function () {
    $http = RecordingHttpClient::sse('{"type":"probe.event"}');
    $repository = new ChatCompletionsHttpRepository($http->transport());

    expect(iterator_to_array($repository->stream(['probe' => 'value']), false))->toBe([['type' => 'probe.event']]);

    $request = $http->lastRequest();
    expect($request->getMethod())->toBe('POST')
        ->and($http->lastUri())->toBe('https://api.openai.com/v1/chat/completions')
        ->and($http->lastJson())->toBe(['probe' => 'value', 'stream' => true])
        ->and($request->getHeaderLine('Accept'))->toBe('text/event-stream');
});

it('sends retrieve as GET /v1/chat/completions/{completionId}', function () {
    $http = RecordingHttpClient::json(['id' => 'ok']);
    $repository = new ChatCompletionsHttpRepository($http->transport());

    expect($repository->retrieve('completionId_1'))->toBe(['id' => 'ok']);

    $request = $http->lastRequest();
    expect($request->getMethod())->toBe('GET')
        ->and($http->lastUri())->toBe('https://api.openai.com/v1/chat/completions/completionId_1');
});

it('sends update as POST /v1/chat/completions/{completionId}', function () {
    $http = RecordingHttpClient::json(['id' => 'ok']);
    $repository = new ChatCompletionsHttpRepository($http->transport());

    expect($repository->update('completionId_1', ['probe' => 'value']))->toBe(['id' => 'ok']);

    $request = $http->lastRequest();
    expect($request->getMethod())->toBe('POST')
        ->and($http->lastUri())->toBe('https://api.openai.com/v1/chat/completions/completionId_1')
        ->and($http->lastJson())->toBe(['probe' => 'value']);
});

it('sends list as GET /v1/chat/completions', function () {
    $http = RecordingHttpClient::json(['id' => 'ok']);
    $repository = new ChatCompletionsHttpRepository($http->transport());

    expect($repository->list(['limit' => 2]))->toBe(['id' => 'ok']);

    $request = $http->lastRequest();
    expect($request->getMethod())->toBe('GET')
        ->and($http->lastUri())->toBe('https://api.openai.com/v1/chat/completions?limit=2');
});

it('sends delete as DELETE /v1/chat/completions/{completionId}', function () {
    $http = RecordingHttpClient::json(['id' => 'ok']);
    $repository = new ChatCompletionsHttpRepository($http->transport());

    expect($repository->delete('completionId_1'))->toBe(['id' => 'ok']);

    $request = $http->lastRequest();
    expect($request->getMethod())->toBe('DELETE')
        ->and($http->lastUri())->toBe('https://api.openai.com/v1/chat/completions/completionId_1');
});

it('sends listMessages as GET /v1/chat/completions/{completionId}/messages', function () {
    $http = RecordingHttpClient::json(['id' => 'ok']);
    $repository = new ChatCompletionsHttpRepository($http->transport());

    expect($repository->listMessages('completionId_1', ['limit' => 2]))->toBe(['id' => 'ok']);

    $request = $http->lastRequest();
    expect($request->getMethod())->toBe('GET')
        ->and($http->lastUri())->toBe('https://api.openai.com/v1/chat/completions/completionId_1/messages?limit=2');
});
