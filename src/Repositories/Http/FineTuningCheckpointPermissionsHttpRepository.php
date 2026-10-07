<?php

declare(strict_types=1);

namespace CreativeCrafts\LaravelAiAssistant\Repositories\Http;

use CreativeCrafts\LaravelAiAssistant\Contracts\FineTuningCheckpointPermissionsRepositoryContract;
use CreativeCrafts\LaravelAiAssistant\Repositories\Http\Admin\AbstractAdminHttpRepository;

/**
 * @internal Use Ai::fineTuningCheckpointPermissions() instead.
 */
final readonly class FineTuningCheckpointPermissionsHttpRepository extends AbstractAdminHttpRepository implements FineTuningCheckpointPermissionsRepositoryContract
{
    public function create(string $checkpoint, array $payload): array
    {
        return $this->postJson($this->path('fine_tuning/checkpoints/%s/permissions', $checkpoint), $payload);
    }

    public function list(string $checkpoint, array $params = []): array
    {
        return $this->getJson($this->path('fine_tuning/checkpoints/%s/permissions', $checkpoint), $params);
    }

    public function delete(string $checkpoint, string $permissionId): array
    {
        return $this->deleteJson($this->path('fine_tuning/checkpoints/%s/permissions/%s', $checkpoint, $permissionId));
    }
}
