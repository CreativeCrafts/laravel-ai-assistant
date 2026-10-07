<?php

declare(strict_types=1);

namespace CreativeCrafts\LaravelAiAssistant\Repositories\Http\Admin;

use CreativeCrafts\LaravelAiAssistant\Contracts\Admin\DataRetentionRepositoryContract;

/**
 * @internal Use Ai::admin()->dataRetention() instead.
 */
final readonly class DataRetentionHttpRepository extends AbstractAdminHttpRepository implements DataRetentionRepositoryContract
{
    public function retrieve(): array
    {
        return $this->getJson('organization/data_retention');
    }

    public function update(array $payload): array
    {
        return $this->postJson('organization/data_retention', $payload);
    }

    public function retrieveForProject(string $projectId): array
    {
        return $this->getJson($this->path('organization/projects/%s/data_retention', $projectId));
    }

    public function updateForProject(string $projectId, array $payload): array
    {
        return $this->postJson($this->path('organization/projects/%s/data_retention', $projectId), $payload);
    }
}
