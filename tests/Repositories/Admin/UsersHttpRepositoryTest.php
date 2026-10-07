<?php

declare(strict_types=1);

use CreativeCrafts\LaravelAiAssistant\Repositories\Http\Admin\UsersHttpRepository;
use CreativeCrafts\LaravelAiAssistant\Tests\Fakes\RecordingHttpClient;

it('sends retrieve as GET /v1/organization/users/{userId}', function () {
    $http = RecordingHttpClient::json(['id' => 'ok']);
    $repository = new UsersHttpRepository($http->transport(), 'sk-admin-test');

    expect($repository->retrieve('userId_1'))->toBe(['id' => 'ok']);

    $request = $http->lastRequest();
    expect($request->getMethod())->toBe('GET')
        ->and($http->lastUri())->toBe('https://api.openai.com/v1/organization/users/userId_1')
        ->and($request->getHeaderLine('Authorization'))->toBe('Bearer sk-admin-test');
});

it('sends update as POST /v1/organization/users/{userId}', function () {
    $http = RecordingHttpClient::json(['id' => 'ok']);
    $repository = new UsersHttpRepository($http->transport(), 'sk-admin-test');

    expect($repository->update('userId_1', ['probe' => 'value']))->toBe(['id' => 'ok']);

    $request = $http->lastRequest();
    expect($request->getMethod())->toBe('POST')
        ->and($http->lastUri())->toBe('https://api.openai.com/v1/organization/users/userId_1')
        ->and($http->lastJson())->toBe(['probe' => 'value'])
        ->and($request->getHeaderLine('Authorization'))->toBe('Bearer sk-admin-test');
});

it('sends list as GET /v1/organization/users', function () {
    $http = RecordingHttpClient::json(['id' => 'ok']);
    $repository = new UsersHttpRepository($http->transport(), 'sk-admin-test');

    expect($repository->list(['limit' => 2]))->toBe(['id' => 'ok']);

    $request = $http->lastRequest();
    expect($request->getMethod())->toBe('GET')
        ->and($http->lastUri())->toBe('https://api.openai.com/v1/organization/users?limit=2')
        ->and($request->getHeaderLine('Authorization'))->toBe('Bearer sk-admin-test');
});

it('sends delete as DELETE /v1/organization/users/{userId}', function () {
    $http = RecordingHttpClient::json(['id' => 'ok']);
    $repository = new UsersHttpRepository($http->transport(), 'sk-admin-test');

    expect($repository->delete('userId_1'))->toBe(['id' => 'ok']);

    $request = $http->lastRequest();
    expect($request->getMethod())->toBe('DELETE')
        ->and($http->lastUri())->toBe('https://api.openai.com/v1/organization/users/userId_1')
        ->and($request->getHeaderLine('Authorization'))->toBe('Bearer sk-admin-test');
});

it('sends assignRole as POST /v1/organization/users/{userId}/roles', function () {
    $http = RecordingHttpClient::json(['id' => 'ok']);
    $repository = new UsersHttpRepository($http->transport(), 'sk-admin-test');

    expect($repository->assignRole('userId_1', ['probe' => 'value']))->toBe(['id' => 'ok']);

    $request = $http->lastRequest();
    expect($request->getMethod())->toBe('POST')
        ->and($http->lastUri())->toBe('https://api.openai.com/v1/organization/users/userId_1/roles')
        ->and($http->lastJson())->toBe(['probe' => 'value'])
        ->and($request->getHeaderLine('Authorization'))->toBe('Bearer sk-admin-test');
});

it('sends retrieveRole as GET /v1/organization/users/{userId}/roles/{roleId}', function () {
    $http = RecordingHttpClient::json(['id' => 'ok']);
    $repository = new UsersHttpRepository($http->transport(), 'sk-admin-test');

    expect($repository->retrieveRole('userId_1', 'roleId_2'))->toBe(['id' => 'ok']);

    $request = $http->lastRequest();
    expect($request->getMethod())->toBe('GET')
        ->and($http->lastUri())->toBe('https://api.openai.com/v1/organization/users/userId_1/roles/roleId_2')
        ->and($request->getHeaderLine('Authorization'))->toBe('Bearer sk-admin-test');
});

it('sends listRoles as GET /v1/organization/users/{userId}/roles', function () {
    $http = RecordingHttpClient::json(['id' => 'ok']);
    $repository = new UsersHttpRepository($http->transport(), 'sk-admin-test');

    expect($repository->listRoles('userId_1', ['limit' => 2]))->toBe(['id' => 'ok']);

    $request = $http->lastRequest();
    expect($request->getMethod())->toBe('GET')
        ->and($http->lastUri())->toBe('https://api.openai.com/v1/organization/users/userId_1/roles?limit=2')
        ->and($request->getHeaderLine('Authorization'))->toBe('Bearer sk-admin-test');
});

it('sends unassignRole as DELETE /v1/organization/users/{userId}/roles/{roleId}', function () {
    $http = RecordingHttpClient::json(['id' => 'ok']);
    $repository = new UsersHttpRepository($http->transport(), 'sk-admin-test');

    expect($repository->unassignRole('userId_1', 'roleId_2'))->toBe(['id' => 'ok']);

    $request = $http->lastRequest();
    expect($request->getMethod())->toBe('DELETE')
        ->and($http->lastUri())->toBe('https://api.openai.com/v1/organization/users/userId_1/roles/roleId_2')
        ->and($request->getHeaderLine('Authorization'))->toBe('Bearer sk-admin-test');
});
