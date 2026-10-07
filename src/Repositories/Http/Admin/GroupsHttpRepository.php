<?php

declare(strict_types=1);

namespace CreativeCrafts\LaravelAiAssistant\Repositories\Http\Admin;

use CreativeCrafts\LaravelAiAssistant\Contracts\Admin\GroupsRepositoryContract;

/**
 * @internal Use Ai::admin()->groups() instead.
 */
final readonly class GroupsHttpRepository extends AbstractAdminHttpRepository implements GroupsRepositoryContract
{
    public function create(array $payload): array
    {
        return $this->postJson('organization/groups', $payload);
    }

    public function retrieve(string $groupId): array
    {
        return $this->getJson($this->path('organization/groups/%s', $groupId));
    }

    public function update(string $groupId, array $payload): array
    {
        return $this->postJson($this->path('organization/groups/%s', $groupId), $payload);
    }

    public function list(array $params = []): array
    {
        return $this->getJson('organization/groups', $params);
    }

    public function delete(string $groupId): array
    {
        return $this->deleteJson($this->path('organization/groups/%s', $groupId));
    }

    public function addUser(string $groupId, array $payload): array
    {
        return $this->postJson($this->path('organization/groups/%s/users', $groupId), $payload);
    }

    public function retrieveUser(string $groupId, string $userId): array
    {
        return $this->getJson($this->path('organization/groups/%s/users/%s', $groupId, $userId));
    }

    public function listUsers(string $groupId, array $params = []): array
    {
        return $this->getJson($this->path('organization/groups/%s/users', $groupId), $params);
    }

    public function removeUser(string $groupId, string $userId): array
    {
        return $this->deleteJson($this->path('organization/groups/%s/users/%s', $groupId, $userId));
    }

    public function assignRole(string $groupId, array $payload): array
    {
        return $this->postJson($this->path('organization/groups/%s/roles', $groupId), $payload);
    }

    public function retrieveRole(string $groupId, string $roleId): array
    {
        return $this->getJson($this->path('organization/groups/%s/roles/%s', $groupId, $roleId));
    }

    public function listRoles(string $groupId, array $params = []): array
    {
        return $this->getJson($this->path('organization/groups/%s/roles', $groupId), $params);
    }

    public function unassignRole(string $groupId, string $roleId): array
    {
        return $this->deleteJson($this->path('organization/groups/%s/roles/%s', $groupId, $roleId));
    }
}
