<?php

declare(strict_types=1);

namespace CreativeCrafts\LaravelAiAssistant\Contracts\Admin;

/**
 * Groups with access to a project and their project role assignments.
 * Requires an Admin API key (ai-assistant.admin_api_key / OPENAI_ADMIN_KEY).
 * Resolve through Ai::admin()->projectGroups().
 */
interface ProjectGroupsRepositoryContract
{
    /**
     * Grants a group access to a project.
     *
     * POST /v1/organization/projects/{projectId}/groups
     *
     * @param array<string, mixed> $payload
     */
    public function create(string $projectId, array $payload): array;

    /**
     * Retrieves a project's group.
     *
     * GET /v1/organization/projects/{projectId}/groups/{groupId}
     */
    public function retrieve(string $projectId, string $groupId): array;

    /**
     * Lists the groups that have access to a project.
     *
     * GET /v1/organization/projects/{projectId}/groups
     *
     * @param array<string, mixed> $params Query parameters
     */
    public function list(string $projectId, array $params = []): array;

    /**
     * Revokes a group's access to a project.
     *
     * DELETE /v1/organization/projects/{projectId}/groups/{groupId}
     */
    public function delete(string $projectId, string $groupId): array;

    /**
     * Assigns a project role to a group within a project.
     *
     * POST /v1/projects/{projectId}/groups/{groupId}/roles
     *
     * @param array<string, mixed> $payload
     */
    public function assignRole(string $projectId, string $groupId, array $payload): array;

    /**
     * Retrieves a project role assigned to a group.
     *
     * GET /v1/projects/{projectId}/groups/{groupId}/roles/{roleId}
     */
    public function retrieveRole(string $projectId, string $groupId, string $roleId): array;

    /**
     * Lists the project roles assigned to a group within a project.
     *
     * GET /v1/projects/{projectId}/groups/{groupId}/roles
     *
     * @param array<string, mixed> $params Query parameters
     */
    public function listRoles(string $projectId, string $groupId, array $params = []): array;

    /**
     * Unassigns a project role from a group within a project.
     *
     * DELETE /v1/projects/{projectId}/groups/{groupId}/roles/{roleId}
     */
    public function unassignRole(string $projectId, string $groupId, string $roleId): array;
}
