<?php

declare(strict_types=1);

namespace CreativeCrafts\LaravelAiAssistant\Repositories\Http\Admin;

use CreativeCrafts\LaravelAiAssistant\Contracts\Admin\RolesRepositoryContract;

/**
 * @internal Use Ai::admin()->roles() instead.
 */
final readonly class RolesHttpRepository extends AbstractAdminHttpRepository implements RolesRepositoryContract
{
    public function create(array $payload): array
    {
        return $this->postJson('organization/roles', $payload);
    }

    public function retrieve(string $roleId): array
    {
        return $this->getJson($this->path('organization/roles/%s', $roleId));
    }

    public function update(string $roleId, array $payload): array
    {
        return $this->postJson($this->path('organization/roles/%s', $roleId), $payload);
    }

    public function list(array $params = []): array
    {
        return $this->getJson('organization/roles', $params);
    }

    public function delete(string $roleId): array
    {
        return $this->deleteJson($this->path('organization/roles/%s', $roleId));
    }

    public function createForProject(string $projectId, array $payload): array
    {
        return $this->postJson($this->path('projects/%s/roles', $projectId), $payload);
    }

    public function retrieveForProject(string $projectId, string $roleId): array
    {
        return $this->getJson($this->path('projects/%s/roles/%s', $projectId, $roleId));
    }

    public function updateForProject(string $projectId, string $roleId, array $payload): array
    {
        return $this->postJson($this->path('projects/%s/roles/%s', $projectId, $roleId), $payload);
    }

    public function listForProject(string $projectId, array $params = []): array
    {
        return $this->getJson($this->path('projects/%s/roles', $projectId), $params);
    }

    public function deleteForProject(string $projectId, string $roleId): array
    {
        return $this->deleteJson($this->path('projects/%s/roles/%s', $projectId, $roleId));
    }
}
