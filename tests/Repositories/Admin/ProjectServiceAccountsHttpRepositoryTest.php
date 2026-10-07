<?php

declare(strict_types=1);

use CreativeCrafts\LaravelAiAssistant\Repositories\Http\Admin\ProjectServiceAccountsHttpRepository;
use CreativeCrafts\LaravelAiAssistant\Tests\Fakes\RecordingHttpClient;

it('sends create as POST /v1/organization/projects/{projectId}/service_accounts', function () {
    $http = RecordingHttpClient::json(['id' => 'ok']);
    $repository = new ProjectServiceAccountsHttpRepository($http->transport(), 'sk-admin-test');

    expect($repository->create('projectId_1', ['probe' => 'value']))->toBe(['id' => 'ok']);

    $request = $http->lastRequest();
    expect($request->getMethod())->toBe('POST')
        ->and($http->lastUri())->toBe('https://api.openai.com/v1/organization/projects/projectId_1/service_accounts')
        ->and($http->lastJson())->toBe(['probe' => 'value'])
        ->and($request->getHeaderLine('Authorization'))->toBe('Bearer sk-admin-test');
});

it('sends retrieve as GET /v1/organization/projects/{projectId}/service_accounts/{serviceAccountId}', function () {
    $http = RecordingHttpClient::json(['id' => 'ok']);
    $repository = new ProjectServiceAccountsHttpRepository($http->transport(), 'sk-admin-test');

    expect($repository->retrieve('projectId_1', 'serviceAccountId_2'))->toBe(['id' => 'ok']);

    $request = $http->lastRequest();
    expect($request->getMethod())->toBe('GET')
        ->and($http->lastUri())->toBe('https://api.openai.com/v1/organization/projects/projectId_1/service_accounts/serviceAccountId_2')
        ->and($request->getHeaderLine('Authorization'))->toBe('Bearer sk-admin-test');
});

it('sends update as POST /v1/organization/projects/{projectId}/service_accounts/{serviceAccountId}', function () {
    $http = RecordingHttpClient::json(['id' => 'ok']);
    $repository = new ProjectServiceAccountsHttpRepository($http->transport(), 'sk-admin-test');

    expect($repository->update('projectId_1', 'serviceAccountId_2', ['probe' => 'value']))->toBe(['id' => 'ok']);

    $request = $http->lastRequest();
    expect($request->getMethod())->toBe('POST')
        ->and($http->lastUri())->toBe('https://api.openai.com/v1/organization/projects/projectId_1/service_accounts/serviceAccountId_2')
        ->and($http->lastJson())->toBe(['probe' => 'value'])
        ->and($request->getHeaderLine('Authorization'))->toBe('Bearer sk-admin-test');
});

it('sends list as GET /v1/organization/projects/{projectId}/service_accounts', function () {
    $http = RecordingHttpClient::json(['id' => 'ok']);
    $repository = new ProjectServiceAccountsHttpRepository($http->transport(), 'sk-admin-test');

    expect($repository->list('projectId_1', ['limit' => 2]))->toBe(['id' => 'ok']);

    $request = $http->lastRequest();
    expect($request->getMethod())->toBe('GET')
        ->and($http->lastUri())->toBe('https://api.openai.com/v1/organization/projects/projectId_1/service_accounts?limit=2')
        ->and($request->getHeaderLine('Authorization'))->toBe('Bearer sk-admin-test');
});

it('sends delete as DELETE /v1/organization/projects/{projectId}/service_accounts/{serviceAccountId}', function () {
    $http = RecordingHttpClient::json(['id' => 'ok']);
    $repository = new ProjectServiceAccountsHttpRepository($http->transport(), 'sk-admin-test');

    expect($repository->delete('projectId_1', 'serviceAccountId_2'))->toBe(['id' => 'ok']);

    $request = $http->lastRequest();
    expect($request->getMethod())->toBe('DELETE')
        ->and($http->lastUri())->toBe('https://api.openai.com/v1/organization/projects/projectId_1/service_accounts/serviceAccountId_2')
        ->and($request->getHeaderLine('Authorization'))->toBe('Bearer sk-admin-test');
});

it('sends createApiKey as POST /v1/organization/projects/{projectId}/service_accounts/{serviceAccountId}/api_keys', function () {
    $http = RecordingHttpClient::json(['id' => 'ok']);
    $repository = new ProjectServiceAccountsHttpRepository($http->transport(), 'sk-admin-test');

    expect($repository->createApiKey('projectId_1', 'serviceAccountId_2', ['probe' => 'value']))->toBe(['id' => 'ok']);

    $request = $http->lastRequest();
    expect($request->getMethod())->toBe('POST')
        ->and($http->lastUri())->toBe('https://api.openai.com/v1/organization/projects/projectId_1/service_accounts/serviceAccountId_2/api_keys')
        ->and($http->lastJson())->toBe(['probe' => 'value'])
        ->and($request->getHeaderLine('Authorization'))->toBe('Bearer sk-admin-test');
});
