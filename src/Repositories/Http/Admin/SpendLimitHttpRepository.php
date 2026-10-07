<?php

declare(strict_types=1);

namespace CreativeCrafts\LaravelAiAssistant\Repositories\Http\Admin;

use CreativeCrafts\LaravelAiAssistant\Contracts\Admin\SpendLimitRepositoryContract;

/**
 * @internal Use Ai::admin()->spendLimit() instead.
 */
final readonly class SpendLimitHttpRepository extends AbstractAdminHttpRepository implements SpendLimitRepositoryContract
{
    public function retrieve(): array
    {
        return $this->getJson('organization/spend_limit');
    }

    public function update(array $payload): array
    {
        return $this->postJson('organization/spend_limit', $payload);
    }

    public function delete(): array
    {
        return $this->deleteJson('organization/spend_limit');
    }

    public function retrieveForProject(string $projectId): array
    {
        return $this->getJson($this->path('organization/projects/%s/spend_limit', $projectId));
    }

    public function updateForProject(string $projectId, array $payload): array
    {
        return $this->postJson($this->path('organization/projects/%s/spend_limit', $projectId), $payload);
    }

    public function deleteForProject(string $projectId): array
    {
        return $this->deleteJson($this->path('organization/projects/%s/spend_limit', $projectId));
    }
}
