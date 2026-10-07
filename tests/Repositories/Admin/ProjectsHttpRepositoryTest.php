<?php

declare(strict_types=1);

use CreativeCrafts\LaravelAiAssistant\Repositories\Http\Admin\ProjectsHttpRepository;
use CreativeCrafts\LaravelAiAssistant\Tests\Fakes\RecordingHttpClient;

it('sends create as POST /v1/organization/projects', function () {
    $http = RecordingHttpClient::json(['id' => 'ok']);
    $repository = new ProjectsHttpRepository($http->transport(), 'sk-admin-test');

    expect($repository->create(['probe' => 'value']))->toBe(['id' => 'ok']);

    $request = $http->lastRequest();
    expect($request->getMethod())->toBe('POST')
        ->and($http->lastUri())->toBe('https://api.openai.com/v1/organization/projects')
        ->and($http->lastJson())->toBe(['probe' => 'value'])
        ->and($request->getHeaderLine('Authorization'))->toBe('Bearer sk-admin-test');
});

it('sends retrieve as GET /v1/organization/projects/{projectId}', function () {
    $http = RecordingHttpClient::json(['id' => 'ok']);
    $repository = new ProjectsHttpRepository($http->transport(), 'sk-admin-test');

    expect($repository->retrieve('projectId_1'))->toBe(['id' => 'ok']);

    $request = $http->lastRequest();
    expect($request->getMethod())->toBe('GET')
        ->and($http->lastUri())->toBe('https://api.openai.com/v1/organization/projects/projectId_1')
        ->and($request->getHeaderLine('Authorization'))->toBe('Bearer sk-admin-test');
});

it('sends update as POST /v1/organization/projects/{projectId}', function () {
    $http = RecordingHttpClient::json(['id' => 'ok']);
    $repository = new ProjectsHttpRepository($http->transport(), 'sk-admin-test');

    expect($repository->update('projectId_1', ['probe' => 'value']))->toBe(['id' => 'ok']);

    $request = $http->lastRequest();
    expect($request->getMethod())->toBe('POST')
        ->and($http->lastUri())->toBe('https://api.openai.com/v1/organization/projects/projectId_1')
        ->and($http->lastJson())->toBe(['probe' => 'value'])
        ->and($request->getHeaderLine('Authorization'))->toBe('Bearer sk-admin-test');
});

it('sends list as GET /v1/organization/projects', function () {
    $http = RecordingHttpClient::json(['id' => 'ok']);
    $repository = new ProjectsHttpRepository($http->transport(), 'sk-admin-test');

    expect($repository->list(['limit' => 2]))->toBe(['id' => 'ok']);

    $request = $http->lastRequest();
    expect($request->getMethod())->toBe('GET')
        ->and($http->lastUri())->toBe('https://api.openai.com/v1/organization/projects?limit=2')
        ->and($request->getHeaderLine('Authorization'))->toBe('Bearer sk-admin-test');
});

it('sends archive as POST /v1/organization/projects/{projectId}/archive', function () {
    $http = RecordingHttpClient::json(['id' => 'ok']);
    $repository = new ProjectsHttpRepository($http->transport(), 'sk-admin-test');

    expect($repository->archive('projectId_1'))->toBe(['id' => 'ok']);

    $request = $http->lastRequest();
    expect($request->getMethod())->toBe('POST')
        ->and($http->lastUri())->toBe('https://api.openai.com/v1/organization/projects/projectId_1/archive')
        ->and($http->lastBody())->toBe('')
        ->and($request->getHeaderLine('Authorization'))->toBe('Bearer sk-admin-test');
});
