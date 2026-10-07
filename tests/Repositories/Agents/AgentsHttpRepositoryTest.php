<?php

declare(strict_types=1);

use CreativeCrafts\LaravelAiAssistant\Repositories\Http\Agents\AgentsHttpRepository;
use CreativeCrafts\LaravelAiAssistant\Tests\Fakes\RecordingHttpClient;

it('sends create as POST /v1/agents', function () {
    $http = RecordingHttpClient::json(['id' => 'ok']);
    $repository = new AgentsHttpRepository($http->transport());

    expect($repository->create(['probe' => 'value']))->toBe(['id' => 'ok']);

    $request = $http->lastRequest();
    expect($request->getMethod())->toBe('POST')
        ->and($http->lastUri())->toBe('https://api.openai.com/v1/agents')
        ->and($http->lastJson())->toBe(['probe' => 'value'])
        ->and($request->getHeaderLine('OpenAI-Beta'))->toBe('agents=v1');
});

it('sends retrieve as GET /v1/agents/{agentId}', function () {
    $http = RecordingHttpClient::json(['id' => 'ok']);
    $repository = new AgentsHttpRepository($http->transport());

    expect($repository->retrieve('agentId_1'))->toBe(['id' => 'ok']);

    $request = $http->lastRequest();
    expect($request->getMethod())->toBe('GET')
        ->and($http->lastUri())->toBe('https://api.openai.com/v1/agents/agentId_1')
        ->and($request->getHeaderLine('OpenAI-Beta'))->toBe('agents=v1');
});

it('sends update as POST /v1/agents/{agentId}', function () {
    $http = RecordingHttpClient::json(['id' => 'ok']);
    $repository = new AgentsHttpRepository($http->transport());

    expect($repository->update('agentId_1', ['probe' => 'value']))->toBe(['id' => 'ok']);

    $request = $http->lastRequest();
    expect($request->getMethod())->toBe('POST')
        ->and($http->lastUri())->toBe('https://api.openai.com/v1/agents/agentId_1')
        ->and($http->lastJson())->toBe(['probe' => 'value'])
        ->and($request->getHeaderLine('OpenAI-Beta'))->toBe('agents=v1');
});

it('sends list as GET /v1/agents', function () {
    $http = RecordingHttpClient::json(['id' => 'ok']);
    $repository = new AgentsHttpRepository($http->transport());

    expect($repository->list(['limit' => 2]))->toBe(['id' => 'ok']);

    $request = $http->lastRequest();
    expect($request->getMethod())->toBe('GET')
        ->and($http->lastUri())->toBe('https://api.openai.com/v1/agents?limit=2')
        ->and($request->getHeaderLine('OpenAI-Beta'))->toBe('agents=v1');
});

it('sends delete as DELETE /v1/agents/{agentId}', function () {
    $http = RecordingHttpClient::json(['id' => 'ok']);
    $repository = new AgentsHttpRepository($http->transport());

    expect($repository->delete('agentId_1'))->toBe(['id' => 'ok']);

    $request = $http->lastRequest();
    expect($request->getMethod())->toBe('DELETE')
        ->and($http->lastUri())->toBe('https://api.openai.com/v1/agents/agentId_1')
        ->and($request->getHeaderLine('OpenAI-Beta'))->toBe('agents=v1');
});
