<?php

declare(strict_types=1);

use CreativeCrafts\LaravelAiAssistant\Repositories\Http\Admin\ProjectApiKeysHttpRepository;
use CreativeCrafts\LaravelAiAssistant\Tests\Fakes\RecordingHttpClient;

it('sends retrieve as GET /v1/organization/projects/{projectId}/api_keys/{apiKeyId}', function () {
    $http = RecordingHttpClient::json(['id' => 'ok']);
    $repository = new ProjectApiKeysHttpRepository($http->transport(), 'sk-admin-test');

    expect($repository->retrieve('projectId_1', 'apiKeyId_2'))->toBe(['id' => 'ok']);

    $request = $http->lastRequest();
    expect($request->getMethod())->toBe('GET')
        ->and($http->lastUri())->toBe('https://api.openai.com/v1/organization/projects/projectId_1/api_keys/apiKeyId_2')
        ->and($request->getHeaderLine('Authorization'))->toBe('Bearer sk-admin-test');
});

it('sends list as GET /v1/organization/projects/{projectId}/api_keys', function () {
    $http = RecordingHttpClient::json(['id' => 'ok']);
    $repository = new ProjectApiKeysHttpRepository($http->transport(), 'sk-admin-test');

    expect($repository->list('projectId_1', ['limit' => 2]))->toBe(['id' => 'ok']);

    $request = $http->lastRequest();
    expect($request->getMethod())->toBe('GET')
        ->and($http->lastUri())->toBe('https://api.openai.com/v1/organization/projects/projectId_1/api_keys?limit=2')
        ->and($request->getHeaderLine('Authorization'))->toBe('Bearer sk-admin-test');
});

it('sends delete as DELETE /v1/organization/projects/{projectId}/api_keys/{apiKeyId}', function () {
    $http = RecordingHttpClient::json(['id' => 'ok']);
    $repository = new ProjectApiKeysHttpRepository($http->transport(), 'sk-admin-test');

    expect($repository->delete('projectId_1', 'apiKeyId_2'))->toBe(['id' => 'ok']);

    $request = $http->lastRequest();
    expect($request->getMethod())->toBe('DELETE')
        ->and($http->lastUri())->toBe('https://api.openai.com/v1/organization/projects/projectId_1/api_keys/apiKeyId_2')
        ->and($request->getHeaderLine('Authorization'))->toBe('Bearer sk-admin-test');
});
