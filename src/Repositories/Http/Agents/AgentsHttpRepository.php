<?php

declare(strict_types=1);

namespace CreativeCrafts\LaravelAiAssistant\Repositories\Http\Agents;

use CreativeCrafts\LaravelAiAssistant\Contracts\Agents\AgentsRepositoryContract;
use CreativeCrafts\LaravelAiAssistant\Repositories\Http\AbstractHttpRepository;

/**
 * @internal Use Ai::agents() instead.
 */
final readonly class AgentsHttpRepository extends AbstractHttpRepository implements AgentsRepositoryContract
{
    public function create(array $payload): array
    {
        return $this->postJson('agents', $payload);
    }

    public function retrieve(string $agentId): array
    {
        return $this->getJson($this->path('agents/%s', $agentId));
    }

    public function update(string $agentId, array $payload): array
    {
        return $this->postJson($this->path('agents/%s', $agentId), $payload);
    }

    public function list(array $params = []): array
    {
        return $this->getJson('agents', $params);
    }

    public function delete(string $agentId): array
    {
        return $this->deleteJson($this->path('agents/%s', $agentId));
    }

    protected function headers(): array
    {
        return ['OpenAI-Beta' => 'agents=v1'];
    }
}
