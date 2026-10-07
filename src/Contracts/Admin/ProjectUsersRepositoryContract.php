<?php

declare(strict_types=1);

namespace CreativeCrafts\LaravelAiAssistant\Contracts\Admin;

/**
 * Project members and their project role assignments.
 * Requires an Admin API key (ai-assistant.admin_api_key / OPENAI_ADMIN_KEY).
 * Resolve through Ai::admin()->projectUsers().
 */
interface ProjectUsersRepositoryContract
{
    /**
     * Adds a user to the project. Users must already be members of the organization to be added to a project.
     *
     * POST /v1/organization/projects/{projectId}/users
     *
     * @param array<string, mixed> $payload
     */
    public function create(string $projectId, array $payload): array;

    /**
     * Retrieves a user in the project.
     *
     * GET /v1/organization/projects/{projectId}/users/{userId}
     */
    public function retrieve(string $projectId, string $userId): array;

    /**
     * Modifies a user's role in the project.
     *
     * POST /v1/organization/projects/{projectId}/users/{userId}
     *
     * @param array<string, mixed> $payload
     */
    public function update(string $projectId, string $userId, array $payload): array;

    /**
     * Returns a list of users in the project.
     *
     * GET /v1/organization/projects/{projectId}/users
     *
     * @param array<string, mixed> $params Query parameters
     */
    public function list(string $projectId, array $params = []): array;

    /**
     * Deletes a user from the project.
     *
     * DELETE /v1/organization/projects/{projectId}/users/{userId}
     */
    public function delete(string $projectId, string $userId): array;

    /**
     * Assigns a project role to a user within a project.
     *
     * POST /v1/projects/{projectId}/users/{userId}/roles
     *
     * @param array<string, mixed> $payload
     */
    public function assignRole(string $projectId, string $userId, array $payload): array;

    /**
     * Retrieves a project role assigned to a user.
     *
     * GET /v1/projects/{projectId}/users/{userId}/roles/{roleId}
     */
    public function retrieveRole(string $projectId, string $userId, string $roleId): array;

    /**
     * Lists the project roles assigned to a user within a project.
     *
     * GET /v1/projects/{projectId}/users/{userId}/roles
     *
     * @param array<string, mixed> $params Query parameters
     */
    public function listRoles(string $projectId, string $userId, array $params = []): array;

    /**
     * Unassigns a project role from a user within a project.
     *
     * DELETE /v1/projects/{projectId}/users/{userId}/roles/{roleId}
     */
    public function unassignRole(string $projectId, string $userId, string $roleId): array;
}
