<?php

declare(strict_types=1);

namespace CreativeCrafts\LaravelAiAssistant\Repositories\Http\Admin;

use CreativeCrafts\LaravelAiAssistant\Contracts\Admin\ProjectPermissionsRepositoryContract;

/**
 * @internal Use Ai::admin()->projectPermissions() instead.
 */
final readonly class ProjectPermissionsHttpRepository extends AbstractAdminHttpRepository implements ProjectPermissionsRepositoryContract
{
    public function retrieveModelPermissions(string $projectId): array
    {
        return $this->getJson($this->path('organization/projects/%s/model_permissions', $projectId));
    }

    public function updateModelPermissions(string $projectId, array $payload): array
    {
        return $this->postJson($this->path('organization/projects/%s/model_permissions', $projectId), $payload);
    }

    public function deleteModelPermissions(string $projectId): array
    {
        return $this->deleteJson($this->path('organization/projects/%s/model_permissions', $projectId));
    }

    public function retrieveHostedToolPermissions(string $projectId): array
    {
        return $this->getJson($this->path('organization/projects/%s/hosted_tool_permissions', $projectId));
    }

    public function updateHostedToolPermissions(string $projectId, array $payload): array
    {
        return $this->postJson($this->path('organization/projects/%s/hosted_tool_permissions', $projectId), $payload);
    }
}
