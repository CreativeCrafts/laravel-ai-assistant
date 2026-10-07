<?php

declare(strict_types=1);

use CreativeCrafts\LaravelAiAssistant\Repositories\Http\EvalRunsHttpRepository;
use CreativeCrafts\LaravelAiAssistant\Tests\Fakes\RecordingHttpClient;

it('sends create as POST /v1/evals/{evalId}/runs', function () {
    $http = RecordingHttpClient::json(['id' => 'ok']);
    $repository = new EvalRunsHttpRepository($http->transport());

    expect($repository->create('evalId_1', ['probe' => 'value']))->toBe(['id' => 'ok']);

    $request = $http->lastRequest();
    expect($request->getMethod())->toBe('POST')
        ->and($http->lastUri())->toBe('https://api.openai.com/v1/evals/evalId_1/runs')
        ->and($http->lastJson())->toBe(['probe' => 'value']);
});

it('sends retrieve as GET /v1/evals/{evalId}/runs/{runId}', function () {
    $http = RecordingHttpClient::json(['id' => 'ok']);
    $repository = new EvalRunsHttpRepository($http->transport());

    expect($repository->retrieve('evalId_1', 'runId_2'))->toBe(['id' => 'ok']);

    $request = $http->lastRequest();
    expect($request->getMethod())->toBe('GET')
        ->and($http->lastUri())->toBe('https://api.openai.com/v1/evals/evalId_1/runs/runId_2');
});

it('sends list as GET /v1/evals/{evalId}/runs', function () {
    $http = RecordingHttpClient::json(['id' => 'ok']);
    $repository = new EvalRunsHttpRepository($http->transport());

    expect($repository->list('evalId_1', ['limit' => 2]))->toBe(['id' => 'ok']);

    $request = $http->lastRequest();
    expect($request->getMethod())->toBe('GET')
        ->and($http->lastUri())->toBe('https://api.openai.com/v1/evals/evalId_1/runs?limit=2');
});

it('sends delete as DELETE /v1/evals/{evalId}/runs/{runId}', function () {
    $http = RecordingHttpClient::json(['id' => 'ok']);
    $repository = new EvalRunsHttpRepository($http->transport());

    expect($repository->delete('evalId_1', 'runId_2'))->toBe(['id' => 'ok']);

    $request = $http->lastRequest();
    expect($request->getMethod())->toBe('DELETE')
        ->and($http->lastUri())->toBe('https://api.openai.com/v1/evals/evalId_1/runs/runId_2');
});

it('sends cancel as POST /v1/evals/{evalId}/runs/{runId}/cancel', function () {
    $http = RecordingHttpClient::json(['id' => 'ok']);
    $repository = new EvalRunsHttpRepository($http->transport());

    expect($repository->cancel('evalId_1', 'runId_2'))->toBe(['id' => 'ok']);

    $request = $http->lastRequest();
    expect($request->getMethod())->toBe('POST')
        ->and($http->lastUri())->toBe('https://api.openai.com/v1/evals/evalId_1/runs/runId_2/cancel')
        ->and($http->lastBody())->toBe('');
});

it('sends listOutputItems as GET /v1/evals/{evalId}/runs/{runId}/output_items', function () {
    $http = RecordingHttpClient::json(['id' => 'ok']);
    $repository = new EvalRunsHttpRepository($http->transport());

    expect($repository->listOutputItems('evalId_1', 'runId_2', ['limit' => 2]))->toBe(['id' => 'ok']);

    $request = $http->lastRequest();
    expect($request->getMethod())->toBe('GET')
        ->and($http->lastUri())->toBe('https://api.openai.com/v1/evals/evalId_1/runs/runId_2/output_items?limit=2');
});

it('sends retrieveOutputItem as GET /v1/evals/{evalId}/runs/{runId}/output_items/{outputItemId}', function () {
    $http = RecordingHttpClient::json(['id' => 'ok']);
    $repository = new EvalRunsHttpRepository($http->transport());

    expect($repository->retrieveOutputItem('evalId_1', 'runId_2', 'outputItemId_3'))->toBe(['id' => 'ok']);

    $request = $http->lastRequest();
    expect($request->getMethod())->toBe('GET')
        ->and($http->lastUri())->toBe('https://api.openai.com/v1/evals/evalId_1/runs/runId_2/output_items/outputItemId_3');
});
