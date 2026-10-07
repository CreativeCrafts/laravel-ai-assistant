<?php

declare(strict_types=1);

use CreativeCrafts\LaravelAiAssistant\Repositories\Http\ContainersHttpRepository;
use CreativeCrafts\LaravelAiAssistant\Tests\Fakes\RecordingHttpClient;

it('sends create as POST /v1/containers', function () {
    $http = RecordingHttpClient::json(['id' => 'ok']);
    $repository = new ContainersHttpRepository($http->transport());

    expect($repository->create(['probe' => 'value']))->toBe(['id' => 'ok']);

    $request = $http->lastRequest();
    expect($request->getMethod())->toBe('POST')
        ->and($http->lastUri())->toBe('https://api.openai.com/v1/containers')
        ->and($http->lastJson())->toBe(['probe' => 'value']);
});

it('sends retrieve as GET /v1/containers/{containerId}', function () {
    $http = RecordingHttpClient::json(['id' => 'ok']);
    $repository = new ContainersHttpRepository($http->transport());

    expect($repository->retrieve('containerId_1'))->toBe(['id' => 'ok']);

    $request = $http->lastRequest();
    expect($request->getMethod())->toBe('GET')
        ->and($http->lastUri())->toBe('https://api.openai.com/v1/containers/containerId_1');
});

it('sends list as GET /v1/containers', function () {
    $http = RecordingHttpClient::json(['id' => 'ok']);
    $repository = new ContainersHttpRepository($http->transport());

    expect($repository->list(['limit' => 2]))->toBe(['id' => 'ok']);

    $request = $http->lastRequest();
    expect($request->getMethod())->toBe('GET')
        ->and($http->lastUri())->toBe('https://api.openai.com/v1/containers?limit=2');
});

it('sends delete as DELETE /v1/containers/{containerId}', function () {
    $http = RecordingHttpClient::json(['id' => 'ok']);
    $repository = new ContainersHttpRepository($http->transport());

    expect($repository->delete('containerId_1'))->toBe(['id' => 'ok']);

    $request = $http->lastRequest();
    expect($request->getMethod())->toBe('DELETE')
        ->and($http->lastUri())->toBe('https://api.openai.com/v1/containers/containerId_1');
});
