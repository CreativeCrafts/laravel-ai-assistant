<?php

declare(strict_types=1);

use CreativeCrafts\LaravelAiAssistant\Repositories\Http\Admin\ProjectPermissionsHttpRepository;
use CreativeCrafts\LaravelAiAssistant\Tests\Fakes\RecordingHttpClient;

it('sends retrieveModelPermissions as GET /v1/organization/projects/{projectId}/model_permissions', function () {
    $http = RecordingHttpClient::json(['id' => 'ok']);
    $repository = new ProjectPermissionsHttpRepository($http->transport(), 'sk-admin-test');

    expect($repository->retrieveModelPermissions('projectId_1'))->toBe(['id' => 'ok']);

    $request = $http->lastRequest();
    expect($request->getMethod())->toBe('GET')
        ->and($http->lastUri())->toBe('https://api.openai.com/v1/organization/projects/projectId_1/model_permissions')
        ->and($request->getHeaderLine('Authorization'))->toBe('Bearer sk-admin-test');
});

it('sends updateModelPermissions as POST /v1/organization/projects/{projectId}/model_permissions', function () {
    $http = RecordingHttpClient::json(['id' => 'ok']);
    $repository = new ProjectPermissionsHttpRepository($http->transport(), 'sk-admin-test');

    expect($repository->updateModelPermissions('projectId_1', ['probe' => 'value']))->toBe(['id' => 'ok']);

    $request = $http->lastRequest();
    expect($request->getMethod())->toBe('POST')
        ->and($http->lastUri())->toBe('https://api.openai.com/v1/organization/projects/projectId_1/model_permissions')
        ->and($http->lastJson())->toBe(['probe' => 'value'])
        ->and($request->getHeaderLine('Authorization'))->toBe('Bearer sk-admin-test');
});

it('sends deleteModelPermissions as DELETE /v1/organization/projects/{projectId}/model_permissions', function () {
    $http = RecordingHttpClient::json(['id' => 'ok']);
    $repository = new ProjectPermissionsHttpRepository($http->transport(), 'sk-admin-test');

    expect($repository->deleteModelPermissions('projectId_1'))->toBe(['id' => 'ok']);

    $request = $http->lastRequest();
    expect($request->getMethod())->toBe('DELETE')
        ->and($http->lastUri())->toBe('https://api.openai.com/v1/organization/projects/projectId_1/model_permissions')
        ->and($request->getHeaderLine('Authorization'))->toBe('Bearer sk-admin-test');
});

it('sends retrieveHostedToolPermissions as GET /v1/organization/projects/{projectId}/hosted_tool_permissions', function () {
    $http = RecordingHttpClient::json(['id' => 'ok']);
    $repository = new ProjectPermissionsHttpRepository($http->transport(), 'sk-admin-test');

    expect($repository->retrieveHostedToolPermissions('projectId_1'))->toBe(['id' => 'ok']);

    $request = $http->lastRequest();
    expect($request->getMethod())->toBe('GET')
        ->and($http->lastUri())->toBe('https://api.openai.com/v1/organization/projects/projectId_1/hosted_tool_permissions')
        ->and($request->getHeaderLine('Authorization'))->toBe('Bearer sk-admin-test');
});

it('sends updateHostedToolPermissions as POST /v1/organization/projects/{projectId}/hosted_tool_permissions', function () {
    $http = RecordingHttpClient::json(['id' => 'ok']);
    $repository = new ProjectPermissionsHttpRepository($http->transport(), 'sk-admin-test');

    expect($repository->updateHostedToolPermissions('projectId_1', ['probe' => 'value']))->toBe(['id' => 'ok']);

    $request = $http->lastRequest();
    expect($request->getMethod())->toBe('POST')
        ->and($http->lastUri())->toBe('https://api.openai.com/v1/organization/projects/projectId_1/hosted_tool_permissions')
        ->and($http->lastJson())->toBe(['probe' => 'value'])
        ->and($request->getHeaderLine('Authorization'))->toBe('Bearer sk-admin-test');
});
