<?php

declare(strict_types=1);

namespace CreativeCrafts\LaravelAiAssistant\Repositories\Http\Admin;

use CreativeCrafts\LaravelAiAssistant\Contracts\Admin\ProjectGroupsRepositoryContract;

/**
 * @internal Use Ai::admin()->projectGroups() instead.
 */
final readonly class ProjectGroupsHttpRepository extends AbstractAdminHttpRepository implements ProjectGroupsRepositoryContract
{
    public function create(string $projectId, array $payload): array
    {
        return $this->postJson($this->path('organization/projects/%s/groups', $projectId), $payload);
    }

    public function retrieve(string $projectId, string $groupId): array
    {
        return $this->getJson($this->path('organization/projects/%s/groups/%s', $projectId, $groupId));
    }

    public function list(string $projectId, array $params = []): array
    {
        return $this->getJson($this->path('organization/projects/%s/groups', $projectId), $params);
    }

    public function delete(string $projectId, string $groupId): array
    {
        return $this->deleteJson($this->path('organization/projects/%s/groups/%s', $projectId, $groupId));
    }

    public function assignRole(string $projectId, string $groupId, array $payload): array
    {
        return $this->postJson($this->path('projects/%s/groups/%s/roles', $projectId, $groupId), $payload);
    }

    public function retrieveRole(string $projectId, string $groupId, string $roleId): array
    {
        return $this->getJson($this->path('projects/%s/groups/%s/roles/%s', $projectId, $groupId, $roleId));
    }

    public function listRoles(string $projectId, string $groupId, array $params = []): array
    {
        return $this->getJson($this->path('projects/%s/groups/%s/roles', $projectId, $groupId), $params);
    }

    public function unassignRole(string $projectId, string $groupId, string $roleId): array
    {
        return $this->deleteJson($this->path('projects/%s/groups/%s/roles/%s', $projectId, $groupId, $roleId));
    }
}
