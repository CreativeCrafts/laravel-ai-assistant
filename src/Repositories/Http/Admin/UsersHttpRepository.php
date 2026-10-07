<?php

declare(strict_types=1);

namespace CreativeCrafts\LaravelAiAssistant\Repositories\Http\Admin;

use CreativeCrafts\LaravelAiAssistant\Contracts\Admin\UsersRepositoryContract;

/**
 * @internal Use Ai::admin()->users() instead.
 */
final readonly class UsersHttpRepository extends AbstractAdminHttpRepository implements UsersRepositoryContract
{
    public function retrieve(string $userId): array
    {
        return $this->getJson($this->path('organization/users/%s', $userId));
    }

    public function update(string $userId, array $payload): array
    {
        return $this->postJson($this->path('organization/users/%s', $userId), $payload);
    }

    public function list(array $params = []): array
    {
        return $this->getJson('organization/users', $params);
    }

    public function delete(string $userId): array
    {
        return $this->deleteJson($this->path('organization/users/%s', $userId));
    }

    public function assignRole(string $userId, array $payload): array
    {
        return $this->postJson($this->path('organization/users/%s/roles', $userId), $payload);
    }

    public function retrieveRole(string $userId, string $roleId): array
    {
        return $this->getJson($this->path('organization/users/%s/roles/%s', $userId, $roleId));
    }

    public function listRoles(string $userId, array $params = []): array
    {
        return $this->getJson($this->path('organization/users/%s/roles', $userId), $params);
    }

    public function unassignRole(string $userId, string $roleId): array
    {
        return $this->deleteJson($this->path('organization/users/%s/roles/%s', $userId, $roleId));
    }
}
