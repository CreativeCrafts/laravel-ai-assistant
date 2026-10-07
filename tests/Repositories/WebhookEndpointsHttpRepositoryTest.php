<?php

declare(strict_types=1);

use CreativeCrafts\LaravelAiAssistant\Repositories\Http\WebhookEndpointsHttpRepository;
use CreativeCrafts\LaravelAiAssistant\Tests\Fakes\RecordingHttpClient;

it('sends create as POST /v1/webhook_endpoints', function () {
    $http = RecordingHttpClient::json(['id' => 'ok']);
    $repository = new WebhookEndpointsHttpRepository($http->transport());

    expect($repository->create(['probe' => 'value']))->toBe(['id' => 'ok']);

    $request = $http->lastRequest();
    expect($request->getMethod())->toBe('POST')
        ->and($http->lastUri())->toBe('https://api.openai.com/v1/webhook_endpoints')
        ->and($http->lastJson())->toBe(['probe' => 'value']);
});

it('sends retrieve as GET /v1/webhook_endpoints/{webhookEndpointId}', function () {
    $http = RecordingHttpClient::json(['id' => 'ok']);
    $repository = new WebhookEndpointsHttpRepository($http->transport());

    expect($repository->retrieve('webhookEndpointId_1'))->toBe(['id' => 'ok']);

    $request = $http->lastRequest();
    expect($request->getMethod())->toBe('GET')
        ->and($http->lastUri())->toBe('https://api.openai.com/v1/webhook_endpoints/webhookEndpointId_1');
});

it('sends update as POST /v1/webhook_endpoints/{webhookEndpointId}', function () {
    $http = RecordingHttpClient::json(['id' => 'ok']);
    $repository = new WebhookEndpointsHttpRepository($http->transport());

    expect($repository->update('webhookEndpointId_1', ['probe' => 'value']))->toBe(['id' => 'ok']);

    $request = $http->lastRequest();
    expect($request->getMethod())->toBe('POST')
        ->and($http->lastUri())->toBe('https://api.openai.com/v1/webhook_endpoints/webhookEndpointId_1')
        ->and($http->lastJson())->toBe(['probe' => 'value']);
});

it('sends list as GET /v1/webhook_endpoints', function () {
    $http = RecordingHttpClient::json(['id' => 'ok']);
    $repository = new WebhookEndpointsHttpRepository($http->transport());

    expect($repository->list(['limit' => 2]))->toBe(['id' => 'ok']);

    $request = $http->lastRequest();
    expect($request->getMethod())->toBe('GET')
        ->and($http->lastUri())->toBe('https://api.openai.com/v1/webhook_endpoints?limit=2');
});

it('sends delete as DELETE /v1/webhook_endpoints/{webhookEndpointId}', function () {
    $http = RecordingHttpClient::json(['id' => 'ok']);
    $repository = new WebhookEndpointsHttpRepository($http->transport());

    expect($repository->delete('webhookEndpointId_1'))->toBe(['id' => 'ok']);

    $request = $http->lastRequest();
    expect($request->getMethod())->toBe('DELETE')
        ->and($http->lastUri())->toBe('https://api.openai.com/v1/webhook_endpoints/webhookEndpointId_1');
});

it('sends rotateSecret as POST /v1/webhook_endpoints/{webhookEndpointId}/rotate_secret', function () {
    $http = RecordingHttpClient::json(['id' => 'ok']);
    $repository = new WebhookEndpointsHttpRepository($http->transport());

    expect($repository->rotateSecret('webhookEndpointId_1'))->toBe(['id' => 'ok']);

    $request = $http->lastRequest();
    expect($request->getMethod())->toBe('POST')
        ->and($http->lastUri())->toBe('https://api.openai.com/v1/webhook_endpoints/webhookEndpointId_1/rotate_secret')
        ->and($http->lastBody())->toBe('');
});

it('sends test as POST /v1/webhook_endpoints/{webhookEndpointId}/test', function () {
    $http = RecordingHttpClient::json(['id' => 'ok']);
    $repository = new WebhookEndpointsHttpRepository($http->transport());

    expect($repository->test('webhookEndpointId_1', ['probe' => 'value']))->toBe(['id' => 'ok']);

    $request = $http->lastRequest();
    expect($request->getMethod())->toBe('POST')
        ->and($http->lastUri())->toBe('https://api.openai.com/v1/webhook_endpoints/webhookEndpointId_1/test')
        ->and($http->lastJson())->toBe(['probe' => 'value']);
});

it('sends listEventTypes as GET /v1/webhook_event_types', function () {
    $http = RecordingHttpClient::json(['id' => 'ok']);
    $repository = new WebhookEndpointsHttpRepository($http->transport());

    expect($repository->listEventTypes(['limit' => 2]))->toBe(['id' => 'ok']);

    $request = $http->lastRequest();
    expect($request->getMethod())->toBe('GET')
        ->and($http->lastUri())->toBe('https://api.openai.com/v1/webhook_event_types?limit=2');
});
