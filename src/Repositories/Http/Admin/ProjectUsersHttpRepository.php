<?php

declare(strict_types=1);

namespace CreativeCrafts\LaravelAiAssistant\Repositories\Http\Admin;

use CreativeCrafts\LaravelAiAssistant\Contracts\Admin\ProjectUsersRepositoryContract;

/**
 * @internal Use Ai::admin()->projectUsers() instead.
 */
final readonly class ProjectUsersHttpRepository extends AbstractAdminHttpRepository implements ProjectUsersRepositoryContract
{
    public function create(string $projectId, array $payload): array
    {
        return $this->postJson($this->path('organization/projects/%s/users', $projectId), $payload);
    }

    public function retrieve(string $projectId, string $userId): array
    {
        return $this->getJson($this->path('organization/projects/%s/users/%s', $projectId, $userId));
    }

    public function update(string $projectId, string $userId, array $payload): array
    {
        return $this->postJson($this->path('organization/projects/%s/users/%s', $projectId, $userId), $payload);
    }

    public function list(string $projectId, array $params = []): array
    {
        return $this->getJson($this->path('organization/projects/%s/users', $projectId), $params);
    }

    public function delete(string $projectId, string $userId): array
    {
        return $this->deleteJson($this->path('organization/projects/%s/users/%s', $projectId, $userId));
    }

    public function assignRole(string $projectId, string $userId, array $payload): array
    {
        return $this->postJson($this->path('projects/%s/users/%s/roles', $projectId, $userId), $payload);
    }

    public function retrieveRole(string $projectId, string $userId, string $roleId): array
    {
        return $this->getJson($this->path('projects/%s/users/%s/roles/%s', $projectId, $userId, $roleId));
    }

    public function listRoles(string $projectId, string $userId, array $params = []): array
    {
        return $this->getJson($this->path('projects/%s/users/%s/roles', $projectId, $userId), $params);
    }

    public function unassignRole(string $projectId, string $userId, string $roleId): array
    {
        return $this->deleteJson($this->path('projects/%s/users/%s/roles/%s', $projectId, $userId, $roleId));
    }
}
