<?php

declare(strict_types=1);

use CreativeCrafts\LaravelAiAssistant\Repositories\Http\FineTuningJobsHttpRepository;
use CreativeCrafts\LaravelAiAssistant\Tests\Fakes\RecordingHttpClient;

it('sends create as POST /v1/fine_tuning/jobs', function () {
    $http = RecordingHttpClient::json(['id' => 'ok']);
    $repository = new FineTuningJobsHttpRepository($http->transport());

    expect($repository->create(['probe' => 'value']))->toBe(['id' => 'ok']);

    $request = $http->lastRequest();
    expect($request->getMethod())->toBe('POST')
        ->and($http->lastUri())->toBe('https://api.openai.com/v1/fine_tuning/jobs')
        ->and($http->lastJson())->toBe(['probe' => 'value']);
});

it('sends retrieve as GET /v1/fine_tuning/jobs/{fineTuningJobId}', function () {
    $http = RecordingHttpClient::json(['id' => 'ok']);
    $repository = new FineTuningJobsHttpRepository($http->transport());

    expect($repository->retrieve('fineTuningJobId_1'))->toBe(['id' => 'ok']);

    $request = $http->lastRequest();
    expect($request->getMethod())->toBe('GET')
        ->and($http->lastUri())->toBe('https://api.openai.com/v1/fine_tuning/jobs/fineTuningJobId_1');
});

it('sends list as GET /v1/fine_tuning/jobs', function () {
    $http = RecordingHttpClient::json(['id' => 'ok']);
    $repository = new FineTuningJobsHttpRepository($http->transport());

    expect($repository->list(['limit' => 2]))->toBe(['id' => 'ok']);

    $request = $http->lastRequest();
    expect($request->getMethod())->toBe('GET')
        ->and($http->lastUri())->toBe('https://api.openai.com/v1/fine_tuning/jobs?limit=2');
});

it('sends cancel as POST /v1/fine_tuning/jobs/{fineTuningJobId}/cancel', function () {
    $http = RecordingHttpClient::json(['id' => 'ok']);
    $repository = new FineTuningJobsHttpRepository($http->transport());

    expect($repository->cancel('fineTuningJobId_1'))->toBe(['id' => 'ok']);

    $request = $http->lastRequest();
    expect($request->getMethod())->toBe('POST')
        ->and($http->lastUri())->toBe('https://api.openai.com/v1/fine_tuning/jobs/fineTuningJobId_1/cancel')
        ->and($http->lastBody())->toBe('');
});

it('sends pause as POST /v1/fine_tuning/jobs/{fineTuningJobId}/pause', function () {
    $http = RecordingHttpClient::json(['id' => 'ok']);
    $repository = new FineTuningJobsHttpRepository($http->transport());

    expect($repository->pause('fineTuningJobId_1'))->toBe(['id' => 'ok']);

    $request = $http->lastRequest();
    expect($request->getMethod())->toBe('POST')
        ->and($http->lastUri())->toBe('https://api.openai.com/v1/fine_tuning/jobs/fineTuningJobId_1/pause')
        ->and($http->lastBody())->toBe('');
});

it('sends resume as POST /v1/fine_tuning/jobs/{fineTuningJobId}/resume', function () {
    $http = RecordingHttpClient::json(['id' => 'ok']);
    $repository = new FineTuningJobsHttpRepository($http->transport());

    expect($repository->resume('fineTuningJobId_1'))->toBe(['id' => 'ok']);

    $request = $http->lastRequest();
    expect($request->getMethod())->toBe('POST')
        ->and($http->lastUri())->toBe('https://api.openai.com/v1/fine_tuning/jobs/fineTuningJobId_1/resume')
        ->and($http->lastBody())->toBe('');
});

it('sends listEvents as GET /v1/fine_tuning/jobs/{fineTuningJobId}/events', function () {
    $http = RecordingHttpClient::json(['id' => 'ok']);
    $repository = new FineTuningJobsHttpRepository($http->transport());

    expect($repository->listEvents('fineTuningJobId_1', ['limit' => 2]))->toBe(['id' => 'ok']);

    $request = $http->lastRequest();
    expect($request->getMethod())->toBe('GET')
        ->and($http->lastUri())->toBe('https://api.openai.com/v1/fine_tuning/jobs/fineTuningJobId_1/events?limit=2');
});

it('sends listCheckpoints as GET /v1/fine_tuning/jobs/{fineTuningJobId}/checkpoints', function () {
    $http = RecordingHttpClient::json(['id' => 'ok']);
    $repository = new FineTuningJobsHttpRepository($http->transport());

    expect($repository->listCheckpoints('fineTuningJobId_1', ['limit' => 2]))->toBe(['id' => 'ok']);

    $request = $http->lastRequest();
    expect($request->getMethod())->toBe('GET')
        ->and($http->lastUri())->toBe('https://api.openai.com/v1/fine_tuning/jobs/fineTuningJobId_1/checkpoints?limit=2');
});
