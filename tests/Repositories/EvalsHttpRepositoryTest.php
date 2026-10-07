<?php

declare(strict_types=1);

use CreativeCrafts\LaravelAiAssistant\Repositories\Http\EvalsHttpRepository;
use CreativeCrafts\LaravelAiAssistant\Tests\Fakes\RecordingHttpClient;

it('sends create as POST /v1/evals', function () {
    $http = RecordingHttpClient::json(['id' => 'ok']);
    $repository = new EvalsHttpRepository($http->transport());

    expect($repository->create(['probe' => 'value']))->toBe(['id' => 'ok']);

    $request = $http->lastRequest();
    expect($request->getMethod())->toBe('POST')
        ->and($http->lastUri())->toBe('https://api.openai.com/v1/evals')
        ->and($http->lastJson())->toBe(['probe' => 'value']);
});

it('sends retrieve as GET /v1/evals/{evalId}', function () {
    $http = RecordingHttpClient::json(['id' => 'ok']);
    $repository = new EvalsHttpRepository($http->transport());

    expect($repository->retrieve('evalId_1'))->toBe(['id' => 'ok']);

    $request = $http->lastRequest();
    expect($request->getMethod())->toBe('GET')
        ->and($http->lastUri())->toBe('https://api.openai.com/v1/evals/evalId_1');
});

it('sends update as POST /v1/evals/{evalId}', function () {
    $http = RecordingHttpClient::json(['id' => 'ok']);
    $repository = new EvalsHttpRepository($http->transport());

    expect($repository->update('evalId_1', ['probe' => 'value']))->toBe(['id' => 'ok']);

    $request = $http->lastRequest();
    expect($request->getMethod())->toBe('POST')
        ->and($http->lastUri())->toBe('https://api.openai.com/v1/evals/evalId_1')
        ->and($http->lastJson())->toBe(['probe' => 'value']);
});

it('sends list as GET /v1/evals', function () {
    $http = RecordingHttpClient::json(['id' => 'ok']);
    $repository = new EvalsHttpRepository($http->transport());

    expect($repository->list(['limit' => 2]))->toBe(['id' => 'ok']);

    $request = $http->lastRequest();
    expect($request->getMethod())->toBe('GET')
        ->and($http->lastUri())->toBe('https://api.openai.com/v1/evals?limit=2');
});

it('sends delete as DELETE /v1/evals/{evalId}', function () {
    $http = RecordingHttpClient::json(['id' => 'ok']);
    $repository = new EvalsHttpRepository($http->transport());

    expect($repository->delete('evalId_1'))->toBe(['id' => 'ok']);

    $request = $http->lastRequest();
    expect($request->getMethod())->toBe('DELETE')
        ->and($http->lastUri())->toBe('https://api.openai.com/v1/evals/evalId_1');
});
