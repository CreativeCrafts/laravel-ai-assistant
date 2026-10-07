<?php

declare(strict_types=1);

namespace CreativeCrafts\LaravelAiAssistant\Repositories\Http\Admin;

use CreativeCrafts\LaravelAiAssistant\Contracts\Admin\InvitesRepositoryContract;

/**
 * @internal Use Ai::admin()->invites() instead.
 */
final readonly class InvitesHttpRepository extends AbstractAdminHttpRepository implements InvitesRepositoryContract
{
    public function create(array $payload): array
    {
        return $this->postJson('organization/invites', $payload);
    }

    public function retrieve(string $inviteId): array
    {
        return $this->getJson($this->path('organization/invites/%s', $inviteId));
    }

    public function list(array $params = []): array
    {
        return $this->getJson('organization/invites', $params);
    }

    public function delete(string $inviteId): array
    {
        return $this->deleteJson($this->path('organization/invites/%s', $inviteId));
    }
}
