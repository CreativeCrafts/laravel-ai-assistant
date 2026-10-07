<?php

declare(strict_types=1);

namespace CreativeCrafts\LaravelAiAssistant\Repositories\Http\Admin;

use CreativeCrafts\LaravelAiAssistant\Contracts\Admin\ProjectServiceAccountsRepositoryContract;

/**
 * @internal Use Ai::admin()->projectServiceAccounts() instead.
 */
final readonly class ProjectServiceAccountsHttpRepository extends AbstractAdminHttpRepository implements ProjectServiceAccountsRepositoryContract
{
    public function create(string $projectId, array $payload): array
    {
        return $this->postJson($this->path('organization/projects/%s/service_accounts', $projectId), $payload);
    }

    public function retrieve(string $projectId, string $serviceAccountId): array
    {
        return $this->getJson($this->path('organization/projects/%s/service_accounts/%s', $projectId, $serviceAccountId));
    }

    public function update(string $projectId, string $serviceAccountId, array $payload): array
    {
        return $this->postJson($this->path('organization/projects/%s/service_accounts/%s', $projectId, $serviceAccountId), $payload);
    }

    public function list(string $projectId, array $params = []): array
    {
        return $this->getJson($this->path('organization/projects/%s/service_accounts', $projectId), $params);
    }

    public function delete(string $projectId, string $serviceAccountId): array
    {
        return $this->deleteJson($this->path('organization/projects/%s/service_accounts/%s', $projectId, $serviceAccountId));
    }

    public function createApiKey(string $projectId, string $serviceAccountId, array $payload = []): array
    {
        return $this->postJson($this->path('organization/projects/%s/service_accounts/%s/api_keys', $projectId, $serviceAccountId), $payload);
    }
}
