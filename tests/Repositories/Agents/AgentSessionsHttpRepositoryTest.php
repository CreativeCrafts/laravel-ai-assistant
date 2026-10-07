<?php

declare(strict_types=1);

use CreativeCrafts\LaravelAiAssistant\Repositories\Http\Agents\AgentSessionsHttpRepository;
use CreativeCrafts\LaravelAiAssistant\Tests\Fakes\RecordingHttpClient;
use GuzzleHttp\Psr7\Response;

it('sends create as POST /v1/agents/sessions', function () {
    $http = RecordingHttpClient::json(['id' => 'ok']);
    $repository = new AgentSessionsHttpRepository($http->transport());

    expect($repository->create(['probe' => 'value']))->toBe(['id' => 'ok']);

    $request = $http->lastRequest();
    expect($request->getMethod())->toBe('POST')
        ->and($http->lastUri())->toBe('https://api.openai.com/v1/agents/sessions')
        ->and($http->lastJson())->toBe(['probe' => 'value'])
        ->and($request->getHeaderLine('OpenAI-Beta'))->toBe('agents=v1');
});

it('sends stream as POST /v1/agents/sessions', function () {
    $http = RecordingHttpClient::sse('{"type":"probe.event"}');
    $repository = new AgentSessionsHttpRepository($http->transport());

    expect(iterator_to_array($repository->stream(['probe' => 'value']), false))->toBe([['type' => 'probe.event']]);

    $request = $http->lastRequest();
    expect($request->getMethod())->toBe('POST')
        ->and($http->lastUri())->toBe('https://api.openai.com/v1/agents/sessions')
        ->and($http->lastJson())->toBe(['probe' => 'value', 'stream' => true])
        ->and($request->getHeaderLine('Accept'))->toBe('text/event-stream')
        ->and($request->getHeaderLine('OpenAI-Beta'))->toBe('agents=v1');
});

it('sends retrieve as GET /v1/agents/sessions/{sessionId}', function () {
    $http = RecordingHttpClient::json(['id' => 'ok']);
    $repository = new AgentSessionsHttpRepository($http->transport());

    expect($repository->retrieve('sessionId_1'))->toBe(['id' => 'ok']);

    $request = $http->lastRequest();
    expect($request->getMethod())->toBe('GET')
        ->and($http->lastUri())->toBe('https://api.openai.com/v1/agents/sessions/sessionId_1')
        ->and($request->getHeaderLine('OpenAI-Beta'))->toBe('agents=v1');
});

it('sends update as POST /v1/agents/sessions/{sessionId}', function () {
    $http = RecordingHttpClient::json(['id' => 'ok']);
    $repository = new AgentSessionsHttpRepository($http->transport());

    expect($repository->update('sessionId_1', ['probe' => 'value']))->toBe(['id' => 'ok']);

    $request = $http->lastRequest();
    expect($request->getMethod())->toBe('POST')
        ->and($http->lastUri())->toBe('https://api.openai.com/v1/agents/sessions/sessionId_1')
        ->and($http->lastJson())->toBe(['probe' => 'value'])
        ->and($request->getHeaderLine('OpenAI-Beta'))->toBe('agents=v1');
});

it('sends list as GET /v1/agents/sessions', function () {
    $http = RecordingHttpClient::json(['id' => 'ok']);
    $repository = new AgentSessionsHttpRepository($http->transport());

    expect($repository->list(['limit' => 2]))->toBe(['id' => 'ok']);

    $request = $http->lastRequest();
    expect($request->getMethod())->toBe('GET')
        ->and($http->lastUri())->toBe('https://api.openai.com/v1/agents/sessions?limit=2')
        ->and($request->getHeaderLine('OpenAI-Beta'))->toBe('agents=v1');
});

it('sends delete as DELETE /v1/agents/sessions/{sessionId}', function () {
    $http = RecordingHttpClient::json(['id' => 'ok']);
    $repository = new AgentSessionsHttpRepository($http->transport());

    expect($repository->delete('sessionId_1'))->toBe(['id' => 'ok']);

    $request = $http->lastRequest();
    expect($request->getMethod())->toBe('DELETE')
        ->and($http->lastUri())->toBe('https://api.openai.com/v1/agents/sessions/sessionId_1')
        ->and($request->getHeaderLine('OpenAI-Beta'))->toBe('agents=v1');
});

it('sends createEvent as POST /v1/agents/sessions/{sessionId}/events', function () {
    $http = RecordingHttpClient::json(['id' => 'ok']);
    $repository = new AgentSessionsHttpRepository($http->transport());

    expect($repository->createEvent('sessionId_1', ['probe' => 'value']))->toBe(['id' => 'ok']);

    $request = $http->lastRequest();
    expect($request->getMethod())->toBe('POST')
        ->and($http->lastUri())->toBe('https://api.openai.com/v1/agents/sessions/sessionId_1/events')
        ->and($http->lastJson())->toBe(['probe' => 'value'])
        ->and($request->getHeaderLine('OpenAI-Beta'))->toBe('agents=v1');
});

it('sends streamEvents as GET /v1/agents/sessions/{sessionId}/events', function () {
    $http = RecordingHttpClient::sse('{"type":"probe.event"}');
    $repository = new AgentSessionsHttpRepository($http->transport());

    expect(iterator_to_array($repository->streamEvents('sessionId_1'), false))->toBe([['type' => 'probe.event']]);

    $request = $http->lastRequest();
    expect($request->getMethod())->toBe('GET')
        ->and($http->lastUri())->toBe('https://api.openai.com/v1/agents/sessions/sessionId_1/events')
        ->and($request->getHeaderLine('Accept'))->toBe('text/event-stream')
        ->and($request->getHeaderLine('OpenAI-Beta'))->toBe('agents=v1');
});

it('sends listItems as GET /v1/agents/sessions/{sessionId}/items', function () {
    $http = RecordingHttpClient::json(['id' => 'ok']);
    $repository = new AgentSessionsHttpRepository($http->transport());

    expect($repository->listItems('sessionId_1', ['limit' => 2]))->toBe(['id' => 'ok']);

    $request = $http->lastRequest();
    expect($request->getMethod())->toBe('GET')
        ->and($http->lastUri())->toBe('https://api.openai.com/v1/agents/sessions/sessionId_1/items?limit=2')
        ->and($request->getHeaderLine('OpenAI-Beta'))->toBe('agents=v1');
});

it('sends listTraces as GET /v1/agents/sessions/{sessionId}/traces', function () {
    $http = RecordingHttpClient::json(['id' => 'ok']);
    $repository = new AgentSessionsHttpRepository($http->transport());

    expect($repository->listTraces('sessionId_1', ['limit' => 2]))->toBe(['id' => 'ok']);

    $request = $http->lastRequest();
    expect($request->getMethod())->toBe('GET')
        ->and($http->lastUri())->toBe('https://api.openai.com/v1/agents/sessions/sessionId_1/traces?limit=2')
        ->and($request->getHeaderLine('OpenAI-Beta'))->toBe('agents=v1');
});

it('sends listArtifacts as GET /v1/agents/sessions/{sessionId}/artifacts', function () {
    $http = RecordingHttpClient::json(['id' => 'ok']);
    $repository = new AgentSessionsHttpRepository($http->transport());

    expect($repository->listArtifacts('sessionId_1', ['limit' => 2]))->toBe(['id' => 'ok']);

    $request = $http->lastRequest();
    expect($request->getMethod())->toBe('GET')
        ->and($http->lastUri())->toBe('https://api.openai.com/v1/agents/sessions/sessionId_1/artifacts?limit=2')
        ->and($request->getHeaderLine('OpenAI-Beta'))->toBe('agents=v1');
});

it('sends retrieveArtifact as GET /v1/agents/sessions/{sessionId}/artifacts/{artifactId}', function () {
    $http = RecordingHttpClient::json(['id' => 'ok']);
    $repository = new AgentSessionsHttpRepository($http->transport());

    expect($repository->retrieveArtifact('sessionId_1', 'artifactId_2'))->toBe(['id' => 'ok']);

    $request = $http->lastRequest();
    expect($request->getMethod())->toBe('GET')
        ->and($http->lastUri())->toBe('https://api.openai.com/v1/agents/sessions/sessionId_1/artifacts/artifactId_2')
        ->and($request->getHeaderLine('OpenAI-Beta'))->toBe('agents=v1');
});

it('sends deleteArtifact as DELETE /v1/agents/sessions/{sessionId}/artifacts/{artifactId}', function () {
    $http = RecordingHttpClient::json(['id' => 'ok']);
    $repository = new AgentSessionsHttpRepository($http->transport());

    expect($repository->deleteArtifact('sessionId_1', 'artifactId_2'))->toBe(['id' => 'ok']);

    $request = $http->lastRequest();
    expect($request->getMethod())->toBe('DELETE')
        ->and($http->lastUri())->toBe('https://api.openai.com/v1/agents/sessions/sessionId_1/artifacts/artifactId_2')
        ->and($request->getHeaderLine('OpenAI-Beta'))->toBe('agents=v1');
});

it('sends artifactContent as GET /v1/agents/sessions/{sessionId}/artifacts/{artifactId}/content', function () {
    $http = new RecordingHttpClient(new Response(200, ['Content-Type' => 'application/octet-stream'], 'BYTES'));
    $repository = new AgentSessionsHttpRepository($http->transport());

    expect($repository->artifactContent('sessionId_1', 'artifactId_2'))->toBe(['content' => 'BYTES', 'content_type' => 'application/octet-stream']);

    $request = $http->lastRequest();
    expect($request->getMethod())->toBe('GET')
        ->and($http->lastUri())->toBe('https://api.openai.com/v1/agents/sessions/sessionId_1/artifacts/artifactId_2/content')
        ->and($request->getHeaderLine('OpenAI-Beta'))->toBe('agents=v1');
});

it('sends listTurns as GET /v1/agents/sessions/{sessionId}/turns', function () {
    $http = RecordingHttpClient::json(['id' => 'ok']);
    $repository = new AgentSessionsHttpRepository($http->transport());

    expect($repository->listTurns('sessionId_1', ['limit' => 2]))->toBe(['id' => 'ok']);

    $request = $http->lastRequest();
    expect($request->getMethod())->toBe('GET')
        ->and($http->lastUri())->toBe('https://api.openai.com/v1/agents/sessions/sessionId_1/turns?limit=2')
        ->and($request->getHeaderLine('OpenAI-Beta'))->toBe('agents=v1');
});

it('sends retrieveTurn as GET /v1/agents/sessions/{sessionId}/turns/{turnId}', function () {
    $http = RecordingHttpClient::json(['id' => 'ok']);
    $repository = new AgentSessionsHttpRepository($http->transport());

    expect($repository->retrieveTurn('sessionId_1', 'turnId_2'))->toBe(['id' => 'ok']);

    $request = $http->lastRequest();
    expect($request->getMethod())->toBe('GET')
        ->and($http->lastUri())->toBe('https://api.openai.com/v1/agents/sessions/sessionId_1/turns/turnId_2')
        ->and($request->getHeaderLine('OpenAI-Beta'))->toBe('agents=v1');
});

it('sends listTurnItems as GET /v1/agents/sessions/{sessionId}/turns/{turnId}/items', function () {
    $http = RecordingHttpClient::json(['id' => 'ok']);
    $repository = new AgentSessionsHttpRepository($http->transport());

    expect($repository->listTurnItems('sessionId_1', 'turnId_2', ['limit' => 2]))->toBe(['id' => 'ok']);

    $request = $http->lastRequest();
    expect($request->getMethod())->toBe('GET')
        ->and($http->lastUri())->toBe('https://api.openai.com/v1/agents/sessions/sessionId_1/turns/turnId_2/items?limit=2')
        ->and($request->getHeaderLine('OpenAI-Beta'))->toBe('agents=v1');
});

it('sends listSubagents as GET /v1/agents/sessions/{sessionId}/subagents', function () {
    $http = RecordingHttpClient::json(['id' => 'ok']);
    $repository = new AgentSessionsHttpRepository($http->transport());

    expect($repository->listSubagents('sessionId_1', ['limit' => 2]))->toBe(['id' => 'ok']);

    $request = $http->lastRequest();
    expect($request->getMethod())->toBe('GET')
        ->and($http->lastUri())->toBe('https://api.openai.com/v1/agents/sessions/sessionId_1/subagents?limit=2')
        ->and($request->getHeaderLine('OpenAI-Beta'))->toBe('agents=v1');
});

it('sends retrieveSubagent as GET /v1/agents/sessions/{sessionId}/subagents/{subagentId}', function () {
    $http = RecordingHttpClient::json(['id' => 'ok']);
    $repository = new AgentSessionsHttpRepository($http->transport());

    expect($repository->retrieveSubagent('sessionId_1', 'subagentId_2'))->toBe(['id' => 'ok']);

    $request = $http->lastRequest();
    expect($request->getMethod())->toBe('GET')
        ->and($http->lastUri())->toBe('https://api.openai.com/v1/agents/sessions/sessionId_1/subagents/subagentId_2')
        ->and($request->getHeaderLine('OpenAI-Beta'))->toBe('agents=v1');
});

it('sends listSubagentItems as GET /v1/agents/sessions/{sessionId}/subagents/{subagentId}/items', function () {
    $http = RecordingHttpClient::json(['id' => 'ok']);
    $repository = new AgentSessionsHttpRepository($http->transport());

    expect($repository->listSubagentItems('sessionId_1', 'subagentId_2', ['limit' => 2]))->toBe(['id' => 'ok']);

    $request = $http->lastRequest();
    expect($request->getMethod())->toBe('GET')
        ->and($http->lastUri())->toBe('https://api.openai.com/v1/agents/sessions/sessionId_1/subagents/subagentId_2/items?limit=2')
        ->and($request->getHeaderLine('OpenAI-Beta'))->toBe('agents=v1');
});

it('sends listSubagentTurns as GET /v1/agents/sessions/{sessionId}/subagents/{subagentId}/turns', function () {
    $http = RecordingHttpClient::json(['id' => 'ok']);
    $repository = new AgentSessionsHttpRepository($http->transport());

    expect($repository->listSubagentTurns('sessionId_1', 'subagentId_2', ['limit' => 2]))->toBe(['id' => 'ok']);

    $request = $http->lastRequest();
    expect($request->getMethod())->toBe('GET')
        ->and($http->lastUri())->toBe('https://api.openai.com/v1/agents/sessions/sessionId_1/subagents/subagentId_2/turns?limit=2')
        ->and($request->getHeaderLine('OpenAI-Beta'))->toBe('agents=v1');
});

it('sends retrieveSubagentTurn as GET /v1/agents/sessions/{sessionId}/subagents/{subagentId}/turns/{turnId}', function () {
    $http = RecordingHttpClient::json(['id' => 'ok']);
    $repository = new AgentSessionsHttpRepository($http->transport());

    expect($repository->retrieveSubagentTurn('sessionId_1', 'subagentId_2', 'turnId_3'))->toBe(['id' => 'ok']);

    $request = $http->lastRequest();
    expect($request->getMethod())->toBe('GET')
        ->and($http->lastUri())->toBe('https://api.openai.com/v1/agents/sessions/sessionId_1/subagents/subagentId_2/turns/turnId_3')
        ->and($request->getHeaderLine('OpenAI-Beta'))->toBe('agents=v1');
});

it('sends listSubagentTurnItems as GET /v1/agents/sessions/{sessionId}/subagents/{subagentId}/turns/{turnId}/items', function () {
    $http = RecordingHttpClient::json(['id' => 'ok']);
    $repository = new AgentSessionsHttpRepository($http->transport());

    expect($repository->listSubagentTurnItems('sessionId_1', 'subagentId_2', 'turnId_3', ['limit' => 2]))->toBe(['id' => 'ok']);

    $request = $http->lastRequest();
    expect($request->getMethod())->toBe('GET')
        ->and($http->lastUri())->toBe('https://api.openai.com/v1/agents/sessions/sessionId_1/subagents/subagentId_2/turns/turnId_3/items?limit=2')
        ->and($request->getHeaderLine('OpenAI-Beta'))->toBe('agents=v1');
});
