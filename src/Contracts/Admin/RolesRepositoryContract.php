<?php

declare(strict_types=1);

namespace CreativeCrafts\LaravelAiAssistant\Contracts\Admin;

/**
 * Organization and project custom roles.
 * Requires an Admin API key (ai-assistant.admin_api_key / OPENAI_ADMIN_KEY).
 * Resolve through Ai::admin()->roles().
 */
interface RolesRepositoryContract
{
    /**
     * Creates a custom role for the organization.
     *
     * POST /v1/organization/roles
     *
     * @param array<string, mixed> $payload
     */
    public function create(array $payload): array;

    /**
     * Retrieves an organization role.
     *
     * GET /v1/organization/roles/{roleId}
     */
    public function retrieve(string $roleId): array;

    /**
     * Updates an existing organization role.
     *
     * POST /v1/organization/roles/{roleId}
     *
     * @param array<string, mixed> $payload
     */
    public function update(string $roleId, array $payload): array;

    /**
     * Lists the roles configured for the organization.
     *
     * GET /v1/organization/roles
     *
     * @param array<string, mixed> $params Query parameters
     */
    public function list(array $params = []): array;

    /**
     * Deletes a custom role from the organization.
     *
     * DELETE /v1/organization/roles/{roleId}
     */
    public function delete(string $roleId): array;

    /**
     * Creates a custom role for a project.
     *
     * POST /v1/projects/{projectId}/roles
     *
     * @param array<string, mixed> $payload
     */
    public function createForProject(string $projectId, array $payload): array;

    /**
     * Retrieves a project role.
     *
     * GET /v1/projects/{projectId}/roles/{roleId}
     */
    public function retrieveForProject(string $projectId, string $roleId): array;

    /**
     * Updates an existing project role.
     *
     * POST /v1/projects/{projectId}/roles/{roleId}
     *
     * @param array<string, mixed> $payload
     */
    public function updateForProject(string $projectId, string $roleId, array $payload): array;

    /**
     * Lists the roles configured for a project.
     *
     * GET /v1/projects/{projectId}/roles
     *
     * @param array<string, mixed> $params Query parameters
     */
    public function listForProject(string $projectId, array $params = []): array;

    /**
     * Deletes a custom role from a project.
     *
     * DELETE /v1/projects/{projectId}/roles/{roleId}
     */
    public function deleteForProject(string $projectId, string $roleId): array;
}
