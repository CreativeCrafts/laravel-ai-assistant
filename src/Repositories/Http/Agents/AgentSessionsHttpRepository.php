<?php

declare(strict_types=1);

namespace CreativeCrafts\LaravelAiAssistant\Repositories\Http\Agents;

use CreativeCrafts\LaravelAiAssistant\Contracts\Agents\AgentSessionsRepositoryContract;
use CreativeCrafts\LaravelAiAssistant\Repositories\Http\AbstractHttpRepository;

/**
 * @internal Use Ai::agentSessions() instead.
 */
final readonly class AgentSessionsHttpRepository extends AbstractHttpRepository implements AgentSessionsRepositoryContract
{
    public function create(array $payload): array
    {
        return $this->postJson('agents/sessions', $payload);
    }

    public function stream(array $payload): iterable
    {
        yield from $this->streamJson('POST', 'agents/sessions', array_merge($payload, ['stream' => true]));
    }

    public function retrieve(string $sessionId): array
    {
        return $this->getJson($this->path('agents/sessions/%s', $sessionId));
    }

    public function update(string $sessionId, array $payload): array
    {
        return $this->postJson($this->path('agents/sessions/%s', $sessionId), $payload);
    }

    public function list(array $params = []): array
    {
        return $this->getJson('agents/sessions', $params);
    }

    public function delete(string $sessionId): array
    {
        return $this->deleteJson($this->path('agents/sessions/%s', $sessionId));
    }

    public function createEvent(string $sessionId, array $payload): array
    {
        return $this->postJson($this->path('agents/sessions/%s/events', $sessionId), $payload);
    }

    public function streamEvents(string $sessionId): iterable
    {
        yield from $this->streamJson('GET', $this->path('agents/sessions/%s/events', $sessionId));
    }

    public function listItems(string $sessionId, array $params = []): array
    {
        return $this->getJson($this->path('agents/sessions/%s/items', $sessionId), $params);
    }

    public function listTraces(string $sessionId, array $params = []): array
    {
        return $this->getJson($this->path('agents/sessions/%s/traces', $sessionId), $params);
    }

    public function listArtifacts(string $sessionId, array $params = []): array
    {
        return $this->getJson($this->path('agents/sessions/%s/artifacts', $sessionId), $params);
    }

    public function retrieveArtifact(string $sessionId, string $artifactId): array
    {
        return $this->getJson($this->path('agents/sessions/%s/artifacts/%s', $sessionId, $artifactId));
    }

    public function deleteArtifact(string $sessionId, string $artifactId): array
    {
        return $this->deleteJson($this->path('agents/sessions/%s/artifacts/%s', $sessionId, $artifactId));
    }

    public function artifactContent(string $sessionId, string $artifactId): array
    {
        return $this->download($this->path('agents/sessions/%s/artifacts/%s/content', $sessionId, $artifactId));
    }

    public function listTurns(string $sessionId, array $params = []): array
    {
        return $this->getJson($this->path('agents/sessions/%s/turns', $sessionId), $params);
    }

    public function retrieveTurn(string $sessionId, string $turnId): array
    {
        return $this->getJson($this->path('agents/sessions/%s/turns/%s', $sessionId, $turnId));
    }

    public function listTurnItems(string $sessionId, string $turnId, array $params = []): array
    {
        return $this->getJson($this->path('agents/sessions/%s/turns/%s/items', $sessionId, $turnId), $params);
    }

    public function listSubagents(string $sessionId, array $params = []): array
    {
        return $this->getJson($this->path('agents/sessions/%s/subagents', $sessionId), $params);
    }

    public function retrieveSubagent(string $sessionId, string $subagentId): array
    {
        return $this->getJson($this->path('agents/sessions/%s/subagents/%s', $sessionId, $subagentId));
    }

    public function listSubagentItems(string $sessionId, string $subagentId, array $params = []): array
    {
        return $this->getJson($this->path('agents/sessions/%s/subagents/%s/items', $sessionId, $subagentId), $params);
    }

    public function listSubagentTurns(string $sessionId, string $subagentId, array $params = []): array
    {
        return $this->getJson($this->path('agents/sessions/%s/subagents/%s/turns', $sessionId, $subagentId), $params);
    }

    public function retrieveSubagentTurn(string $sessionId, string $subagentId, string $turnId): array
    {
        return $this->getJson($this->path('agents/sessions/%s/subagents/%s/turns/%s', $sessionId, $subagentId, $turnId));
    }

    public function listSubagentTurnItems(string $sessionId, string $subagentId, string $turnId, array $params = []): array
    {
        return $this->getJson($this->path('agents/sessions/%s/subagents/%s/turns/%s/items', $sessionId, $subagentId, $turnId), $params);
    }

    protected function headers(): array
    {
        return ['OpenAI-Beta' => 'agents=v1'];
    }
}
