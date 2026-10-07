<?php

declare(strict_types=1);

use CreativeCrafts\LaravelAiAssistant\Repositories\Http\Admin\SpendLimitHttpRepository;
use CreativeCrafts\LaravelAiAssistant\Tests\Fakes\RecordingHttpClient;

it('sends retrieve as GET /v1/organization/spend_limit', function () {
    $http = RecordingHttpClient::json(['id' => 'ok']);
    $repository = new SpendLimitHttpRepository($http->transport(), 'sk-admin-test');

    expect($repository->retrieve())->toBe(['id' => 'ok']);

    $request = $http->lastRequest();
    expect($request->getMethod())->toBe('GET')
        ->and($http->lastUri())->toBe('https://api.openai.com/v1/organization/spend_limit')
        ->and($request->getHeaderLine('Authorization'))->toBe('Bearer sk-admin-test');
});

it('sends update as POST /v1/organization/spend_limit', function () {
    $http = RecordingHttpClient::json(['id' => 'ok']);
    $repository = new SpendLimitHttpRepository($http->transport(), 'sk-admin-test');

    expect($repository->update(['probe' => 'value']))->toBe(['id' => 'ok']);

    $request = $http->lastRequest();
    expect($request->getMethod())->toBe('POST')
        ->and($http->lastUri())->toBe('https://api.openai.com/v1/organization/spend_limit')
        ->and($http->lastJson())->toBe(['probe' => 'value'])
        ->and($request->getHeaderLine('Authorization'))->toBe('Bearer sk-admin-test');
});

it('sends delete as DELETE /v1/organization/spend_limit', function () {
    $http = RecordingHttpClient::json(['id' => 'ok']);
    $repository = new SpendLimitHttpRepository($http->transport(), 'sk-admin-test');

    expect($repository->delete())->toBe(['id' => 'ok']);

    $request = $http->lastRequest();
    expect($request->getMethod())->toBe('DELETE')
        ->and($http->lastUri())->toBe('https://api.openai.com/v1/organization/spend_limit')
        ->and($request->getHeaderLine('Authorization'))->toBe('Bearer sk-admin-test');
});

it('sends retrieveForProject as GET /v1/organization/projects/{projectId}/spend_limit', function () {
    $http = RecordingHttpClient::json(['id' => 'ok']);
    $repository = new SpendLimitHttpRepository($http->transport(), 'sk-admin-test');

    expect($repository->retrieveForProject('projectId_1'))->toBe(['id' => 'ok']);

    $request = $http->lastRequest();
    expect($request->getMethod())->toBe('GET')
        ->and($http->lastUri())->toBe('https://api.openai.com/v1/organization/projects/projectId_1/spend_limit')
        ->and($request->getHeaderLine('Authorization'))->toBe('Bearer sk-admin-test');
});

it('sends updateForProject as POST /v1/organization/projects/{projectId}/spend_limit', function () {
    $http = RecordingHttpClient::json(['id' => 'ok']);
    $repository = new SpendLimitHttpRepository($http->transport(), 'sk-admin-test');

    expect($repository->updateForProject('projectId_1', ['probe' => 'value']))->toBe(['id' => 'ok']);

    $request = $http->lastRequest();
    expect($request->getMethod())->toBe('POST')
        ->and($http->lastUri())->toBe('https://api.openai.com/v1/organization/projects/projectId_1/spend_limit')
        ->and($http->lastJson())->toBe(['probe' => 'value'])
        ->and($request->getHeaderLine('Authorization'))->toBe('Bearer sk-admin-test');
});

it('sends deleteForProject as DELETE /v1/organization/projects/{projectId}/spend_limit', function () {
    $http = RecordingHttpClient::json(['id' => 'ok']);
    $repository = new SpendLimitHttpRepository($http->transport(), 'sk-admin-test');

    expect($repository->deleteForProject('projectId_1'))->toBe(['id' => 'ok']);

    $request = $http->lastRequest();
    expect($request->getMethod())->toBe('DELETE')
        ->and($http->lastUri())->toBe('https://api.openai.com/v1/organization/projects/projectId_1/spend_limit')
        ->and($request->getHeaderLine('Authorization'))->toBe('Bearer sk-admin-test');
});
