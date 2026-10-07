<?php

declare(strict_types=1);

use CreativeCrafts\LaravelAiAssistant\Repositories\Http\Admin\ProjectUsersHttpRepository;
use CreativeCrafts\LaravelAiAssistant\Tests\Fakes\RecordingHttpClient;

it('sends create as POST /v1/organization/projects/{projectId}/users', function () {
    $http = RecordingHttpClient::json(['id' => 'ok']);
    $repository = new ProjectUsersHttpRepository($http->transport(), 'sk-admin-test');

    expect($repository->create('projectId_1', ['probe' => 'value']))->toBe(['id' => 'ok']);

    $request = $http->lastRequest();
    expect($request->getMethod())->toBe('POST')
        ->and($http->lastUri())->toBe('https://api.openai.com/v1/organization/projects/projectId_1/users')
        ->and($http->lastJson())->toBe(['probe' => 'value'])
        ->and($request->getHeaderLine('Authorization'))->toBe('Bearer sk-admin-test');
});

it('sends retrieve as GET /v1/organization/projects/{projectId}/users/{userId}', function () {
    $http = RecordingHttpClient::json(['id' => 'ok']);
    $repository = new ProjectUsersHttpRepository($http->transport(), 'sk-admin-test');

    expect($repository->retrieve('projectId_1', 'userId_2'))->toBe(['id' => 'ok']);

    $request = $http->lastRequest();
    expect($request->getMethod())->toBe('GET')
        ->and($http->lastUri())->toBe('https://api.openai.com/v1/organization/projects/projectId_1/users/userId_2')
        ->and($request->getHeaderLine('Authorization'))->toBe('Bearer sk-admin-test');
});

it('sends update as POST /v1/organization/projects/{projectId}/users/{userId}', function () {
    $http = RecordingHttpClient::json(['id' => 'ok']);
    $repository = new ProjectUsersHttpRepository($http->transport(), 'sk-admin-test');

    expect($repository->update('projectId_1', 'userId_2', ['probe' => 'value']))->toBe(['id' => 'ok']);

    $request = $http->lastRequest();
    expect($request->getMethod())->toBe('POST')
        ->and($http->lastUri())->toBe('https://api.openai.com/v1/organization/projects/projectId_1/users/userId_2')
        ->and($http->lastJson())->toBe(['probe' => 'value'])
        ->and($request->getHeaderLine('Authorization'))->toBe('Bearer sk-admin-test');
});

it('sends list as GET /v1/organization/projects/{projectId}/users', function () {
    $http = RecordingHttpClient::json(['id' => 'ok']);
    $repository = new ProjectUsersHttpRepository($http->transport(), 'sk-admin-test');

    expect($repository->list('projectId_1', ['limit' => 2]))->toBe(['id' => 'ok']);

    $request = $http->lastRequest();
    expect($request->getMethod())->toBe('GET')
        ->and($http->lastUri())->toBe('https://api.openai.com/v1/organization/projects/projectId_1/users?limit=2')
        ->and($request->getHeaderLine('Authorization'))->toBe('Bearer sk-admin-test');
});

it('sends delete as DELETE /v1/organization/projects/{projectId}/users/{userId}', function () {
    $http = RecordingHttpClient::json(['id' => 'ok']);
    $repository = new ProjectUsersHttpRepository($http->transport(), 'sk-admin-test');

    expect($repository->delete('projectId_1', 'userId_2'))->toBe(['id' => 'ok']);

    $request = $http->lastRequest();
    expect($request->getMethod())->toBe('DELETE')
        ->and($http->lastUri())->toBe('https://api.openai.com/v1/organization/projects/projectId_1/users/userId_2')
        ->and($request->getHeaderLine('Authorization'))->toBe('Bearer sk-admin-test');
});

it('sends assignRole as POST /v1/projects/{projectId}/users/{userId}/roles', function () {
    $http = RecordingHttpClient::json(['id' => 'ok']);
    $repository = new ProjectUsersHttpRepository($http->transport(), 'sk-admin-test');

    expect($repository->assignRole('projectId_1', 'userId_2', ['probe' => 'value']))->toBe(['id' => 'ok']);

    $request = $http->lastRequest();
    expect($request->getMethod())->toBe('POST')
        ->and($http->lastUri())->toBe('https://api.openai.com/v1/projects/projectId_1/users/userId_2/roles')
        ->and($http->lastJson())->toBe(['probe' => 'value'])
        ->and($request->getHeaderLine('Authorization'))->toBe('Bearer sk-admin-test');
});

it('sends retrieveRole as GET /v1/projects/{projectId}/users/{userId}/roles/{roleId}', function () {
    $http = RecordingHttpClient::json(['id' => 'ok']);
    $repository = new ProjectUsersHttpRepository($http->transport(), 'sk-admin-test');

    expect($repository->retrieveRole('projectId_1', 'userId_2', 'roleId_3'))->toBe(['id' => 'ok']);

    $request = $http->lastRequest();
    expect($request->getMethod())->toBe('GET')
        ->and($http->lastUri())->toBe('https://api.openai.com/v1/projects/projectId_1/users/userId_2/roles/roleId_3')
        ->and($request->getHeaderLine('Authorization'))->toBe('Bearer sk-admin-test');
});

it('sends listRoles as GET /v1/projects/{projectId}/users/{userId}/roles', function () {
    $http = RecordingHttpClient::json(['id' => 'ok']);
    $repository = new ProjectUsersHttpRepository($http->transport(), 'sk-admin-test');

    expect($repository->listRoles('projectId_1', 'userId_2', ['limit' => 2]))->toBe(['id' => 'ok']);

    $request = $http->lastRequest();
    expect($request->getMethod())->toBe('GET')
        ->and($http->lastUri())->toBe('https://api.openai.com/v1/projects/projectId_1/users/userId_2/roles?limit=2')
        ->and($request->getHeaderLine('Authorization'))->toBe('Bearer sk-admin-test');
});

it('sends unassignRole as DELETE /v1/projects/{projectId}/users/{userId}/roles/{roleId}', function () {
    $http = RecordingHttpClient::json(['id' => 'ok']);
    $repository = new ProjectUsersHttpRepository($http->transport(), 'sk-admin-test');

    expect($repository->unassignRole('projectId_1', 'userId_2', 'roleId_3'))->toBe(['id' => 'ok']);

    $request = $http->lastRequest();
    expect($request->getMethod())->toBe('DELETE')
        ->and($http->lastUri())->toBe('https://api.openai.com/v1/projects/projectId_1/users/userId_2/roles/roleId_3')
        ->and($request->getHeaderLine('Authorization'))->toBe('Bearer sk-admin-test');
});
