<?php

declare(strict_types=1);

use CreativeCrafts\LaravelAiAssistant\Repositories\Http\Admin\SpendAlertsHttpRepository;
use CreativeCrafts\LaravelAiAssistant\Tests\Fakes\RecordingHttpClient;

it('sends create as POST /v1/organization/spend_alerts', function () {
    $http = RecordingHttpClient::json(['id' => 'ok']);
    $repository = new SpendAlertsHttpRepository($http->transport(), 'sk-admin-test');

    expect($repository->create(['probe' => 'value']))->toBe(['id' => 'ok']);

    $request = $http->lastRequest();
    expect($request->getMethod())->toBe('POST')
        ->and($http->lastUri())->toBe('https://api.openai.com/v1/organization/spend_alerts')
        ->and($http->lastJson())->toBe(['probe' => 'value'])
        ->and($request->getHeaderLine('Authorization'))->toBe('Bearer sk-admin-test');
});

it('sends retrieve as GET /v1/organization/spend_alerts/{alertId}', function () {
    $http = RecordingHttpClient::json(['id' => 'ok']);
    $repository = new SpendAlertsHttpRepository($http->transport(), 'sk-admin-test');

    expect($repository->retrieve('alertId_1'))->toBe(['id' => 'ok']);

    $request = $http->lastRequest();
    expect($request->getMethod())->toBe('GET')
        ->and($http->lastUri())->toBe('https://api.openai.com/v1/organization/spend_alerts/alertId_1')
        ->and($request->getHeaderLine('Authorization'))->toBe('Bearer sk-admin-test');
});

it('sends update as POST /v1/organization/spend_alerts/{alertId}', function () {
    $http = RecordingHttpClient::json(['id' => 'ok']);
    $repository = new SpendAlertsHttpRepository($http->transport(), 'sk-admin-test');

    expect($repository->update('alertId_1', ['probe' => 'value']))->toBe(['id' => 'ok']);

    $request = $http->lastRequest();
    expect($request->getMethod())->toBe('POST')
        ->and($http->lastUri())->toBe('https://api.openai.com/v1/organization/spend_alerts/alertId_1')
        ->and($http->lastJson())->toBe(['probe' => 'value'])
        ->and($request->getHeaderLine('Authorization'))->toBe('Bearer sk-admin-test');
});

it('sends list as GET /v1/organization/spend_alerts', function () {
    $http = RecordingHttpClient::json(['id' => 'ok']);
    $repository = new SpendAlertsHttpRepository($http->transport(), 'sk-admin-test');

    expect($repository->list(['limit' => 2]))->toBe(['id' => 'ok']);

    $request = $http->lastRequest();
    expect($request->getMethod())->toBe('GET')
        ->and($http->lastUri())->toBe('https://api.openai.com/v1/organization/spend_alerts?limit=2')
        ->and($request->getHeaderLine('Authorization'))->toBe('Bearer sk-admin-test');
});

it('sends delete as DELETE /v1/organization/spend_alerts/{alertId}', function () {
    $http = RecordingHttpClient::json(['id' => 'ok']);
    $repository = new SpendAlertsHttpRepository($http->transport(), 'sk-admin-test');

    expect($repository->delete('alertId_1'))->toBe(['id' => 'ok']);

    $request = $http->lastRequest();
    expect($request->getMethod())->toBe('DELETE')
        ->and($http->lastUri())->toBe('https://api.openai.com/v1/organization/spend_alerts/alertId_1')
        ->and($request->getHeaderLine('Authorization'))->toBe('Bearer sk-admin-test');
});

it('sends createForProject as POST /v1/organization/projects/{projectId}/spend_alerts', function () {
    $http = RecordingHttpClient::json(['id' => 'ok']);
    $repository = new SpendAlertsHttpRepository($http->transport(), 'sk-admin-test');

    expect($repository->createForProject('projectId_1', ['probe' => 'value']))->toBe(['id' => 'ok']);

    $request = $http->lastRequest();
    expect($request->getMethod())->toBe('POST')
        ->and($http->lastUri())->toBe('https://api.openai.com/v1/organization/projects/projectId_1/spend_alerts')
        ->and($http->lastJson())->toBe(['probe' => 'value'])
        ->and($request->getHeaderLine('Authorization'))->toBe('Bearer sk-admin-test');
});

it('sends retrieveForProject as GET /v1/organization/projects/{projectId}/spend_alerts/{alertId}', function () {
    $http = RecordingHttpClient::json(['id' => 'ok']);
    $repository = new SpendAlertsHttpRepository($http->transport(), 'sk-admin-test');

    expect($repository->retrieveForProject('projectId_1', 'alertId_2'))->toBe(['id' => 'ok']);

    $request = $http->lastRequest();
    expect($request->getMethod())->toBe('GET')
        ->and($http->lastUri())->toBe('https://api.openai.com/v1/organization/projects/projectId_1/spend_alerts/alertId_2')
        ->and($request->getHeaderLine('Authorization'))->toBe('Bearer sk-admin-test');
});

it('sends updateForProject as POST /v1/organization/projects/{projectId}/spend_alerts/{alertId}', function () {
    $http = RecordingHttpClient::json(['id' => 'ok']);
    $repository = new SpendAlertsHttpRepository($http->transport(), 'sk-admin-test');

    expect($repository->updateForProject('projectId_1', 'alertId_2', ['probe' => 'value']))->toBe(['id' => 'ok']);

    $request = $http->lastRequest();
    expect($request->getMethod())->toBe('POST')
        ->and($http->lastUri())->toBe('https://api.openai.com/v1/organization/projects/projectId_1/spend_alerts/alertId_2')
        ->and($http->lastJson())->toBe(['probe' => 'value'])
        ->and($request->getHeaderLine('Authorization'))->toBe('Bearer sk-admin-test');
});

it('sends listForProject as GET /v1/organization/projects/{projectId}/spend_alerts', function () {
    $http = RecordingHttpClient::json(['id' => 'ok']);
    $repository = new SpendAlertsHttpRepository($http->transport(), 'sk-admin-test');

    expect($repository->listForProject('projectId_1', ['limit' => 2]))->toBe(['id' => 'ok']);

    $request = $http->lastRequest();
    expect($request->getMethod())->toBe('GET')
        ->and($http->lastUri())->toBe('https://api.openai.com/v1/organization/projects/projectId_1/spend_alerts?limit=2')
        ->and($request->getHeaderLine('Authorization'))->toBe('Bearer sk-admin-test');
});

it('sends deleteForProject as DELETE /v1/organization/projects/{projectId}/spend_alerts/{alertId}', function () {
    $http = RecordingHttpClient::json(['id' => 'ok']);
    $repository = new SpendAlertsHttpRepository($http->transport(), 'sk-admin-test');

    expect($repository->deleteForProject('projectId_1', 'alertId_2'))->toBe(['id' => 'ok']);

    $request = $http->lastRequest();
    expect($request->getMethod())->toBe('DELETE')
        ->and($http->lastUri())->toBe('https://api.openai.com/v1/organization/projects/projectId_1/spend_alerts/alertId_2')
        ->and($request->getHeaderLine('Authorization'))->toBe('Bearer sk-admin-test');
});
