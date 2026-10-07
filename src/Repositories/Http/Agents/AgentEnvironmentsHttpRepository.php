<?php

declare(strict_types=1);

namespace CreativeCrafts\LaravelAiAssistant\Repositories\Http\Agents;

use CreativeCrafts\LaravelAiAssistant\Contracts\Agents\AgentEnvironmentsRepositoryContract;
use CreativeCrafts\LaravelAiAssistant\Repositories\Http\AbstractHttpRepository;

/**
 * @internal Use Ai::agentEnvironments() instead.
 */
final readonly class AgentEnvironmentsHttpRepository extends AbstractHttpRepository implements AgentEnvironmentsRepositoryContract
{
    public function retrieve(string $environmentId): array
    {
        return $this->getJson($this->path('agents/environments/%s', $environmentId));
    }

    public function createFile(string $environmentId, array $payload): array
    {
        return $this->postJson($this->path('agents/environments/%s/files', $environmentId), $payload);
    }

    public function listFiles(string $environmentId, array $params = []): array
    {
        return $this->getJson($this->path('agents/environments/%s/files', $environmentId), $params);
    }

    public function createTemplate(array $payload): array
    {
        return $this->postJson('agents/environments/templates', $payload);
    }

    public function retrieveTemplate(string $templateId): array
    {
        return $this->getJson($this->path('agents/environments/templates/%s', $templateId));
    }

    public function updateTemplate(string $templateId, array $payload): array
    {
        return $this->postJson($this->path('agents/environments/templates/%s', $templateId), $payload);
    }

    public function listTemplates(array $params = []): array
    {
        return $this->getJson('agents/environments/templates', $params);
    }

    public function deleteTemplate(string $templateId): array
    {
        return $this->deleteJson($this->path('agents/environments/templates/%s', $templateId));
    }

    protected function headers(): array
    {
        return ['OpenAI-Beta' => 'agents=v1'];
    }
}
