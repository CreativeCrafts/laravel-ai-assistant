<?php

declare(strict_types=1);

namespace CreativeCrafts\LaravelAiAssistant\Contracts\Admin;

/**
 * Project model and hosted tool permissions.
 * Requires an Admin API key (ai-assistant.admin_api_key / OPENAI_ADMIN_KEY).
 * Resolve through Ai::admin()->projectPermissions().
 */
interface ProjectPermissionsRepositoryContract
{
    /**
     * Returns model permissions for a project.
     *
     * GET /v1/organization/projects/{projectId}/model_permissions
     */
    public function retrieveModelPermissions(string $projectId): array;

    /**
     * Updates model permissions for a project.
     *
     * POST /v1/organization/projects/{projectId}/model_permissions
     *
     * @param array<string, mixed> $payload
     */
    public function updateModelPermissions(string $projectId, array $payload): array;

    /**
     * Deletes model permissions for a project.
     *
     * DELETE /v1/organization/projects/{projectId}/model_permissions
     */
    public function deleteModelPermissions(string $projectId): array;

    /**
     * Returns hosted tool permissions for a project.
     *
     * GET /v1/organization/projects/{projectId}/hosted_tool_permissions
     */
    public function retrieveHostedToolPermissions(string $projectId): array;

    /**
     * Updates hosted tool permissions for a project.
     *
     * POST /v1/organization/projects/{projectId}/hosted_tool_permissions
     *
     * @param array<string, mixed> $payload
     */
    public function updateHostedToolPermissions(string $projectId, array $payload): array;
}
