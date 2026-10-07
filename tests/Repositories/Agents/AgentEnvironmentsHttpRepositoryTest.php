<?php

declare(strict_types=1);

use CreativeCrafts\LaravelAiAssistant\Repositories\Http\Agents\AgentEnvironmentsHttpRepository;
use CreativeCrafts\LaravelAiAssistant\Tests\Fakes\RecordingHttpClient;

it('sends retrieve as GET /v1/agents/environments/{environmentId}', function () {
    $http = RecordingHttpClient::json(['id' => 'ok']);
    $repository = new AgentEnvironmentsHttpRepository($http->transport());

    expect($repository->retrieve('environmentId_1'))->toBe(['id' => 'ok']);

    $request = $http->lastRequest();
    expect($request->getMethod())->toBe('GET')
        ->and($http->lastUri())->toBe('https://api.openai.com/v1/agents/environments/environmentId_1')
        ->and($request->getHeaderLine('OpenAI-Beta'))->toBe('agents=v1');
});

it('sends createFile as POST /v1/agents/environments/{environmentId}/files', function () {
    $http = RecordingHttpClient::json(['id' => 'ok']);
    $repository = new AgentEnvironmentsHttpRepository($http->transport());

    expect($repository->createFile('environmentId_1', ['probe' => 'value']))->toBe(['id' => 'ok']);

    $request = $http->lastRequest();
    expect($request->getMethod())->toBe('POST')
        ->and($http->lastUri())->toBe('https://api.openai.com/v1/agents/environments/environmentId_1/files')
        ->and($http->lastJson())->toBe(['probe' => 'value'])
        ->and($request->getHeaderLine('OpenAI-Beta'))->toBe('agents=v1');
});

it('sends listFiles as GET /v1/agents/environments/{environmentId}/files', function () {
    $http = RecordingHttpClient::json(['id' => 'ok']);
    $repository = new AgentEnvironmentsHttpRepository($http->transport());

    expect($repository->listFiles('environmentId_1', ['limit' => 2]))->toBe(['id' => 'ok']);

    $request = $http->lastRequest();
    expect($request->getMethod())->toBe('GET')
        ->and($http->lastUri())->toBe('https://api.openai.com/v1/agents/environments/environmentId_1/files?limit=2')
        ->and($request->getHeaderLine('OpenAI-Beta'))->toBe('agents=v1');
});

it('sends createTemplate as POST /v1/agents/environments/templates', function () {
    $http = RecordingHttpClient::json(['id' => 'ok']);
    $repository = new AgentEnvironmentsHttpRepository($http->transport());

    expect($repository->createTemplate(['probe' => 'value']))->toBe(['id' => 'ok']);

    $request = $http->lastRequest();
    expect($request->getMethod())->toBe('POST')
        ->and($http->lastUri())->toBe('https://api.openai.com/v1/agents/environments/templates')
        ->and($http->lastJson())->toBe(['probe' => 'value'])
        ->and($request->getHeaderLine('OpenAI-Beta'))->toBe('agents=v1');
});

it('sends retrieveTemplate as GET /v1/agents/environments/templates/{templateId}', function () {
    $http = RecordingHttpClient::json(['id' => 'ok']);
    $repository = new AgentEnvironmentsHttpRepository($http->transport());

    expect($repository->retrieveTemplate('templateId_1'))->toBe(['id' => 'ok']);

    $request = $http->lastRequest();
    expect($request->getMethod())->toBe('GET')
        ->and($http->lastUri())->toBe('https://api.openai.com/v1/agents/environments/templates/templateId_1')
        ->and($request->getHeaderLine('OpenAI-Beta'))->toBe('agents=v1');
});

it('sends updateTemplate as POST /v1/agents/environments/templates/{templateId}', function () {
    $http = RecordingHttpClient::json(['id' => 'ok']);
    $repository = new AgentEnvironmentsHttpRepository($http->transport());

    expect($repository->updateTemplate('templateId_1', ['probe' => 'value']))->toBe(['id' => 'ok']);

    $request = $http->lastRequest();
    expect($request->getMethod())->toBe('POST')
        ->and($http->lastUri())->toBe('https://api.openai.com/v1/agents/environments/templates/templateId_1')
        ->and($http->lastJson())->toBe(['probe' => 'value'])
        ->and($request->getHeaderLine('OpenAI-Beta'))->toBe('agents=v1');
});

it('sends listTemplates as GET /v1/agents/environments/templates', function () {
    $http = RecordingHttpClient::json(['id' => 'ok']);
    $repository = new AgentEnvironmentsHttpRepository($http->transport());

    expect($repository->listTemplates(['limit' => 2]))->toBe(['id' => 'ok']);

    $request = $http->lastRequest();
    expect($request->getMethod())->toBe('GET')
        ->and($http->lastUri())->toBe('https://api.openai.com/v1/agents/environments/templates?limit=2')
        ->and($request->getHeaderLine('OpenAI-Beta'))->toBe('agents=v1');
});

it('sends deleteTemplate as DELETE /v1/agents/environments/templates/{templateId}', function () {
    $http = RecordingHttpClient::json(['id' => 'ok']);
    $repository = new AgentEnvironmentsHttpRepository($http->transport());

    expect($repository->deleteTemplate('templateId_1'))->toBe(['id' => 'ok']);

    $request = $http->lastRequest();
    expect($request->getMethod())->toBe('DELETE')
        ->and($http->lastUri())->toBe('https://api.openai.com/v1/agents/environments/templates/templateId_1')
        ->and($request->getHeaderLine('OpenAI-Beta'))->toBe('agents=v1');
});
