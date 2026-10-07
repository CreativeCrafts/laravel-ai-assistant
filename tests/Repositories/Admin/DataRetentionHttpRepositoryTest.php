<?php

declare(strict_types=1);

use CreativeCrafts\LaravelAiAssistant\Repositories\Http\Admin\DataRetentionHttpRepository;
use CreativeCrafts\LaravelAiAssistant\Tests\Fakes\RecordingHttpClient;

it('sends retrieve as GET /v1/organization/data_retention', function () {
    $http = RecordingHttpClient::json(['id' => 'ok']);
    $repository = new DataRetentionHttpRepository($http->transport(), 'sk-admin-test');

    expect($repository->retrieve())->toBe(['id' => 'ok']);

    $request = $http->lastRequest();
    expect($request->getMethod())->toBe('GET')
        ->and($http->lastUri())->toBe('https://api.openai.com/v1/organization/data_retention')
        ->and($request->getHeaderLine('Authorization'))->toBe('Bearer sk-admin-test');
});

it('sends update as POST /v1/organization/data_retention', function () {
    $http = RecordingHttpClient::json(['id' => 'ok']);
    $repository = new DataRetentionHttpRepository($http->transport(), 'sk-admin-test');

    expect($repository->update(['probe' => 'value']))->toBe(['id' => 'ok']);

    $request = $http->lastRequest();
    expect($request->getMethod())->toBe('POST')
        ->and($http->lastUri())->toBe('https://api.openai.com/v1/organization/data_retention')
        ->and($http->lastJson())->toBe(['probe' => 'value'])
        ->and($request->getHeaderLine('Authorization'))->toBe('Bearer sk-admin-test');
});

it('sends retrieveForProject as GET /v1/organization/projects/{projectId}/data_retention', function () {
    $http = RecordingHttpClient::json(['id' => 'ok']);
    $repository = new DataRetentionHttpRepository($http->transport(), 'sk-admin-test');

    expect($repository->retrieveForProject('projectId_1'))->toBe(['id' => 'ok']);

    $request = $http->lastRequest();
    expect($request->getMethod())->toBe('GET')
        ->and($http->lastUri())->toBe('https://api.openai.com/v1/organization/projects/projectId_1/data_retention')
        ->and($request->getHeaderLine('Authorization'))->toBe('Bearer sk-admin-test');
});

it('sends updateForProject as POST /v1/organization/projects/{projectId}/data_retention', function () {
    $http = RecordingHttpClient::json(['id' => 'ok']);
    $repository = new DataRetentionHttpRepository($http->transport(), 'sk-admin-test');

    expect($repository->updateForProject('projectId_1', ['probe' => 'value']))->toBe(['id' => 'ok']);

    $request = $http->lastRequest();
    expect($request->getMethod())->toBe('POST')
        ->and($http->lastUri())->toBe('https://api.openai.com/v1/organization/projects/projectId_1/data_retention')
        ->and($http->lastJson())->toBe(['probe' => 'value'])
        ->and($request->getHeaderLine('Authorization'))->toBe('Bearer sk-admin-test');
});
