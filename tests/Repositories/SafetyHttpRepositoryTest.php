<?php

declare(strict_types=1);

use CreativeCrafts\LaravelAiAssistant\Repositories\Http\SafetyHttpRepository;
use CreativeCrafts\LaravelAiAssistant\Tests\Fakes\RecordingHttpClient;

it('sends retrieveAlert as GET /v1/safety/alerts/{alertId}', function () {
    $http = RecordingHttpClient::json(['id' => 'ok']);
    $repository = new SafetyHttpRepository($http->transport());

    expect($repository->retrieveAlert('alertId_1'))->toBe(['id' => 'ok']);

    $request = $http->lastRequest();
    expect($request->getMethod())->toBe('GET')
        ->and($http->lastUri())->toBe('https://api.openai.com/v1/safety/alerts/alertId_1');
});

it('sends retrieveCase as GET /v1/safety/cases/{caseId}', function () {
    $http = RecordingHttpClient::json(['id' => 'ok']);
    $repository = new SafetyHttpRepository($http->transport());

    expect($repository->retrieveCase('caseId_1'))->toBe(['id' => 'ok']);

    $request = $http->lastRequest();
    expect($request->getMethod())->toBe('GET')
        ->and($http->lastUri())->toBe('https://api.openai.com/v1/safety/cases/caseId_1');
});
