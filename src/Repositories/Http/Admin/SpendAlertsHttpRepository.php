<?php

declare(strict_types=1);

namespace CreativeCrafts\LaravelAiAssistant\Repositories\Http\Admin;

use CreativeCrafts\LaravelAiAssistant\Contracts\Admin\SpendAlertsRepositoryContract;

/**
 * @internal Use Ai::admin()->spendAlerts() instead.
 */
final readonly class SpendAlertsHttpRepository extends AbstractAdminHttpRepository implements SpendAlertsRepositoryContract
{
    public function create(array $payload): array
    {
        return $this->postJson('organization/spend_alerts', $payload);
    }

    public function retrieve(string $alertId): array
    {
        return $this->getJson($this->path('organization/spend_alerts/%s', $alertId));
    }

    public function update(string $alertId, array $payload): array
    {
        return $this->postJson($this->path('organization/spend_alerts/%s', $alertId), $payload);
    }

    public function list(array $params = []): array
    {
        return $this->getJson('organization/spend_alerts', $params);
    }

    public function delete(string $alertId): array
    {
        return $this->deleteJson($this->path('organization/spend_alerts/%s', $alertId));
    }

    public function createForProject(string $projectId, array $payload): array
    {
        return $this->postJson($this->path('organization/projects/%s/spend_alerts', $projectId), $payload);
    }

    public function retrieveForProject(string $projectId, string $alertId): array
    {
        return $this->getJson($this->path('organization/projects/%s/spend_alerts/%s', $projectId, $alertId));
    }

    public function updateForProject(string $projectId, string $alertId, array $payload): array
    {
        return $this->postJson($this->path('organization/projects/%s/spend_alerts/%s', $projectId, $alertId), $payload);
    }

    public function listForProject(string $projectId, array $params = []): array
    {
        return $this->getJson($this->path('organization/projects/%s/spend_alerts', $projectId), $params);
    }

    public function deleteForProject(string $projectId, string $alertId): array
    {
        return $this->deleteJson($this->path('organization/projects/%s/spend_alerts/%s', $projectId, $alertId));
    }
}
