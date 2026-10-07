<?php

declare(strict_types=1);

use CreativeCrafts\LaravelAiAssistant\Repositories\Http\Admin\GroupsHttpRepository;
use CreativeCrafts\LaravelAiAssistant\Tests\Fakes\RecordingHttpClient;

it('sends create as POST /v1/organization/groups', function () {
    $http = RecordingHttpClient::json(['id' => 'ok']);
    $repository = new GroupsHttpRepository($http->transport(), 'sk-admin-test');

    expect($repository->create(['probe' => 'value']))->toBe(['id' => 'ok']);

    $request = $http->lastRequest();
    expect($request->getMethod())->toBe('POST')
        ->and($http->lastUri())->toBe('https://api.openai.com/v1/organization/groups')
        ->and($http->lastJson())->toBe(['probe' => 'value'])
        ->and($request->getHeaderLine('Authorization'))->toBe('Bearer sk-admin-test');
});

it('sends retrieve as GET /v1/organization/groups/{groupId}', function () {
    $http = RecordingHttpClient::json(['id' => 'ok']);
    $repository = new GroupsHttpRepository($http->transport(), 'sk-admin-test');

    expect($repository->retrieve('groupId_1'))->toBe(['id' => 'ok']);

    $request = $http->lastRequest();
    expect($request->getMethod())->toBe('GET')
        ->and($http->lastUri())->toBe('https://api.openai.com/v1/organization/groups/groupId_1')
        ->and($request->getHeaderLine('Authorization'))->toBe('Bearer sk-admin-test');
});

it('sends update as POST /v1/organization/groups/{groupId}', function () {
    $http = RecordingHttpClient::json(['id' => 'ok']);
    $repository = new GroupsHttpRepository($http->transport(), 'sk-admin-test');

    expect($repository->update('groupId_1', ['probe' => 'value']))->toBe(['id' => 'ok']);

    $request = $http->lastRequest();
    expect($request->getMethod())->toBe('POST')
        ->and($http->lastUri())->toBe('https://api.openai.com/v1/organization/groups/groupId_1')
        ->and($http->lastJson())->toBe(['probe' => 'value'])
        ->and($request->getHeaderLine('Authorization'))->toBe('Bearer sk-admin-test');
});

it('sends list as GET /v1/organization/groups', function () {
    $http = RecordingHttpClient::json(['id' => 'ok']);
    $repository = new GroupsHttpRepository($http->transport(), 'sk-admin-test');

    expect($repository->list(['limit' => 2]))->toBe(['id' => 'ok']);

    $request = $http->lastRequest();
    expect($request->getMethod())->toBe('GET')
        ->and($http->lastUri())->toBe('https://api.openai.com/v1/organization/groups?limit=2')
        ->and($request->getHeaderLine('Authorization'))->toBe('Bearer sk-admin-test');
});

it('sends delete as DELETE /v1/organization/groups/{groupId}', function () {
    $http = RecordingHttpClient::json(['id' => 'ok']);
    $repository = new GroupsHttpRepository($http->transport(), 'sk-admin-test');

    expect($repository->delete('groupId_1'))->toBe(['id' => 'ok']);

    $request = $http->lastRequest();
    expect($request->getMethod())->toBe('DELETE')
        ->and($http->lastUri())->toBe('https://api.openai.com/v1/organization/groups/groupId_1')
        ->and($request->getHeaderLine('Authorization'))->toBe('Bearer sk-admin-test');
});

it('sends addUser as POST /v1/organization/groups/{groupId}/users', function () {
    $http = RecordingHttpClient::json(['id' => 'ok']);
    $repository = new GroupsHttpRepository($http->transport(), 'sk-admin-test');

    expect($repository->addUser('groupId_1', ['probe' => 'value']))->toBe(['id' => 'ok']);

    $request = $http->lastRequest();
    expect($request->getMethod())->toBe('POST')
        ->and($http->lastUri())->toBe('https://api.openai.com/v1/organization/groups/groupId_1/users')
        ->and($http->lastJson())->toBe(['probe' => 'value'])
        ->and($request->getHeaderLine('Authorization'))->toBe('Bearer sk-admin-test');
});

it('sends retrieveUser as GET /v1/organization/groups/{groupId}/users/{userId}', function () {
    $http = RecordingHttpClient::json(['id' => 'ok']);
    $repository = new GroupsHttpRepository($http->transport(), 'sk-admin-test');

    expect($repository->retrieveUser('groupId_1', 'userId_2'))->toBe(['id' => 'ok']);

    $request = $http->lastRequest();
    expect($request->getMethod())->toBe('GET')
        ->and($http->lastUri())->toBe('https://api.openai.com/v1/organization/groups/groupId_1/users/userId_2')
        ->and($request->getHeaderLine('Authorization'))->toBe('Bearer sk-admin-test');
});

it('sends listUsers as GET /v1/organization/groups/{groupId}/users', function () {
    $http = RecordingHttpClient::json(['id' => 'ok']);
    $repository = new GroupsHttpRepository($http->transport(), 'sk-admin-test');

    expect($repository->listUsers('groupId_1', ['limit' => 2]))->toBe(['id' => 'ok']);

    $request = $http->lastRequest();
    expect($request->getMethod())->toBe('GET')
        ->and($http->lastUri())->toBe('https://api.openai.com/v1/organization/groups/groupId_1/users?limit=2')
        ->and($request->getHeaderLine('Authorization'))->toBe('Bearer sk-admin-test');
});

it('sends removeUser as DELETE /v1/organization/groups/{groupId}/users/{userId}', function () {
    $http = RecordingHttpClient::json(['id' => 'ok']);
    $repository = new GroupsHttpRepository($http->transport(), 'sk-admin-test');

    expect($repository->removeUser('groupId_1', 'userId_2'))->toBe(['id' => 'ok']);

    $request = $http->lastRequest();
    expect($request->getMethod())->toBe('DELETE')
        ->and($http->lastUri())->toBe('https://api.openai.com/v1/organization/groups/groupId_1/users/userId_2')
        ->and($request->getHeaderLine('Authorization'))->toBe('Bearer sk-admin-test');
});

it('sends assignRole as POST /v1/organization/groups/{groupId}/roles', function () {
    $http = RecordingHttpClient::json(['id' => 'ok']);
    $repository = new GroupsHttpRepository($http->transport(), 'sk-admin-test');

    expect($repository->assignRole('groupId_1', ['probe' => 'value']))->toBe(['id' => 'ok']);

    $request = $http->lastRequest();
    expect($request->getMethod())->toBe('POST')
        ->and($http->lastUri())->toBe('https://api.openai.com/v1/organization/groups/groupId_1/roles')
        ->and($http->lastJson())->toBe(['probe' => 'value'])
        ->and($request->getHeaderLine('Authorization'))->toBe('Bearer sk-admin-test');
});

it('sends retrieveRole as GET /v1/organization/groups/{groupId}/roles/{roleId}', function () {
    $http = RecordingHttpClient::json(['id' => 'ok']);
    $repository = new GroupsHttpRepository($http->transport(), 'sk-admin-test');

    expect($repository->retrieveRole('groupId_1', 'roleId_2'))->toBe(['id' => 'ok']);

    $request = $http->lastRequest();
    expect($request->getMethod())->toBe('GET')
        ->and($http->lastUri())->toBe('https://api.openai.com/v1/organization/groups/groupId_1/roles/roleId_2')
        ->and($request->getHeaderLine('Authorization'))->toBe('Bearer sk-admin-test');
});

it('sends listRoles as GET /v1/organization/groups/{groupId}/roles', function () {
    $http = RecordingHttpClient::json(['id' => 'ok']);
    $repository = new GroupsHttpRepository($http->transport(), 'sk-admin-test');

    expect($repository->listRoles('groupId_1', ['limit' => 2]))->toBe(['id' => 'ok']);

    $request = $http->lastRequest();
    expect($request->getMethod())->toBe('GET')
        ->and($http->lastUri())->toBe('https://api.openai.com/v1/organization/groups/groupId_1/roles?limit=2')
        ->and($request->getHeaderLine('Authorization'))->toBe('Bearer sk-admin-test');
});

it('sends unassignRole as DELETE /v1/organization/groups/{groupId}/roles/{roleId}', function () {
    $http = RecordingHttpClient::json(['id' => 'ok']);
    $repository = new GroupsHttpRepository($http->transport(), 'sk-admin-test');

    expect($repository->unassignRole('groupId_1', 'roleId_2'))->toBe(['id' => 'ok']);

    $request = $http->lastRequest();
    expect($request->getMethod())->toBe('DELETE')
        ->and($http->lastUri())->toBe('https://api.openai.com/v1/organization/groups/groupId_1/roles/roleId_2')
        ->and($request->getHeaderLine('Authorization'))->toBe('Bearer sk-admin-test');
});
