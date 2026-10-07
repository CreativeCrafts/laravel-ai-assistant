<?php

declare(strict_types=1);

use CreativeCrafts\LaravelAiAssistant\Repositories\Http\FineTuningCheckpointPermissionsHttpRepository;
use CreativeCrafts\LaravelAiAssistant\Tests\Fakes\RecordingHttpClient;

it('sends create as POST /v1/fine_tuning/checkpoints/{checkpoint}/permissions', function () {
    $http = RecordingHttpClient::json(['id' => 'ok']);
    $repository = new FineTuningCheckpointPermissionsHttpRepository($http->transport(), 'sk-admin-test');

    expect($repository->create('checkpoint_1', ['probe' => 'value']))->toBe(['id' => 'ok']);

    $request = $http->lastRequest();
    expect($request->getMethod())->toBe('POST')
        ->and($http->lastUri())->toBe('https://api.openai.com/v1/fine_tuning/checkpoints/checkpoint_1/permissions')
        ->and($http->lastJson())->toBe(['probe' => 'value'])
        ->and($request->getHeaderLine('Authorization'))->toBe('Bearer sk-admin-test');
});

it('sends list as GET /v1/fine_tuning/checkpoints/{checkpoint}/permissions', function () {
    $http = RecordingHttpClient::json(['id' => 'ok']);
    $repository = new FineTuningCheckpointPermissionsHttpRepository($http->transport(), 'sk-admin-test');

    expect($repository->list('checkpoint_1', ['limit' => 2]))->toBe(['id' => 'ok']);

    $request = $http->lastRequest();
    expect($request->getMethod())->toBe('GET')
        ->and($http->lastUri())->toBe('https://api.openai.com/v1/fine_tuning/checkpoints/checkpoint_1/permissions?limit=2')
        ->and($request->getHeaderLine('Authorization'))->toBe('Bearer sk-admin-test');
});

it('sends delete as DELETE /v1/fine_tuning/checkpoints/{checkpoint}/permissions/{permissionId}', function () {
    $http = RecordingHttpClient::json(['id' => 'ok']);
    $repository = new FineTuningCheckpointPermissionsHttpRepository($http->transport(), 'sk-admin-test');

    expect($repository->delete('checkpoint_1', 'permissionId_2'))->toBe(['id' => 'ok']);

    $request = $http->lastRequest();
    expect($request->getMethod())->toBe('DELETE')
        ->and($http->lastUri())->toBe('https://api.openai.com/v1/fine_tuning/checkpoints/checkpoint_1/permissions/permissionId_2')
        ->and($request->getHeaderLine('Authorization'))->toBe('Bearer sk-admin-test');
});
