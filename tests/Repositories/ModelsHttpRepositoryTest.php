<?php

declare(strict_types=1);

use CreativeCrafts\LaravelAiAssistant\Repositories\Http\ModelsHttpRepository;
use CreativeCrafts\LaravelAiAssistant\Tests\Fakes\RecordingHttpClient;

it('sends list as GET /v1/models', function () {
    $http = RecordingHttpClient::json(['id' => 'ok']);
    $repository = new ModelsHttpRepository($http->transport());

    expect($repository->list())->toBe(['id' => 'ok']);

    $request = $http->lastRequest();
    expect($request->getMethod())->toBe('GET')
        ->and($http->lastUri())->toBe('https://api.openai.com/v1/models');
});

it('sends retrieve as GET /v1/models/{model}', function () {
    $http = RecordingHttpClient::json(['id' => 'ok']);
    $repository = new ModelsHttpRepository($http->transport());

    expect($repository->retrieve('model_1'))->toBe(['id' => 'ok']);

    $request = $http->lastRequest();
    expect($request->getMethod())->toBe('GET')
        ->and($http->lastUri())->toBe('https://api.openai.com/v1/models/model_1');
});

it('sends delete as DELETE /v1/models/{model}', function () {
    $http = RecordingHttpClient::json(['id' => 'ok']);
    $repository = new ModelsHttpRepository($http->transport());

    expect($repository->delete('model_1'))->toBe(['id' => 'ok']);

    $request = $http->lastRequest();
    expect($request->getMethod())->toBe('DELETE')
        ->and($http->lastUri())->toBe('https://api.openai.com/v1/models/model_1');
});
