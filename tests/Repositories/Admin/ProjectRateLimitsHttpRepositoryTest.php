<?php

declare(strict_types=1);

use CreativeCrafts\LaravelAiAssistant\Repositories\Http\Admin\ProjectRateLimitsHttpRepository;
use CreativeCrafts\LaravelAiAssistant\Tests\Fakes\RecordingHttpClient;

it('sends list as GET /v1/organization/projects/{projectId}/rate_limits', function () {
    $http = RecordingHttpClient::json(['id' => 'ok']);
    $repository = new ProjectRateLimitsHttpRepository($http->transport(), 'sk-admin-test');

    expect($repository->list('projectId_1', ['limit' => 2]))->toBe(['id' => 'ok']);

    $request = $http->lastRequest();
    expect($request->getMethod())->toBe('GET')
        ->and($http->lastUri())->toBe('https://api.openai.com/v1/organization/projects/projectId_1/rate_limits?limit=2')
        ->and($request->getHeaderLine('Authorization'))->toBe('Bearer sk-admin-test');
});

it('sends update as POST /v1/organization/projects/{projectId}/rate_limits/{rateLimitId}', function () {
    $http = RecordingHttpClient::json(['id' => 'ok']);
    $repository = new ProjectRateLimitsHttpRepository($http->transport(), 'sk-admin-test');

    expect($repository->update('projectId_1', 'rateLimitId_2', ['probe' => 'value']))->toBe(['id' => 'ok']);

    $request = $http->lastRequest();
    expect($request->getMethod())->toBe('POST')
        ->and($http->lastUri())->toBe('https://api.openai.com/v1/organization/projects/projectId_1/rate_limits/rateLimitId_2')
        ->and($http->lastJson())->toBe(['probe' => 'value'])
        ->and($request->getHeaderLine('Authorization'))->toBe('Bearer sk-admin-test');
});
