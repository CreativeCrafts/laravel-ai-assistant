<?php

declare(strict_types=1);

namespace CreativeCrafts\LaravelAiAssistant\Contracts\Admin;

/**
 * Organization groups, their members and role assignments.
 * Requires an Admin API key (ai-assistant.admin_api_key / OPENAI_ADMIN_KEY).
 * Resolve through Ai::admin()->groups().
 */
interface GroupsRepositoryContract
{
    /**
     * Creates a new group in the organization.
     *
     * POST /v1/organization/groups
     *
     * @param array<string, mixed> $payload
     */
    public function create(array $payload): array;

    /**
     * Retrieves a group.
     *
     * GET /v1/organization/groups/{groupId}
     */
    public function retrieve(string $groupId): array;

    /**
     * Updates a group's information.
     *
     * POST /v1/organization/groups/{groupId}
     *
     * @param array<string, mixed> $payload
     */
    public function update(string $groupId, array $payload): array;

    /**
     * Lists all groups in the organization.
     *
     * GET /v1/organization/groups
     *
     * @param array<string, mixed> $params Query parameters
     */
    public function list(array $params = []): array;

    /**
     * Deletes a group from the organization.
     *
     * DELETE /v1/organization/groups/{groupId}
     */
    public function delete(string $groupId): array;

    /**
     * Adds a user to a group.
     *
     * POST /v1/organization/groups/{groupId}/users
     *
     * @param array<string, mixed> $payload
     */
    public function addUser(string $groupId, array $payload): array;

    /**
     * Retrieves a user in a group.
     *
     * GET /v1/organization/groups/{groupId}/users/{userId}
     */
    public function retrieveUser(string $groupId, string $userId): array;

    /**
     * Lists the users assigned to a group.
     *
     * GET /v1/organization/groups/{groupId}/users
     *
     * @param array<string, mixed> $params Query parameters
     */
    public function listUsers(string $groupId, array $params = []): array;

    /**
     * Removes a user from a group.
     *
     * DELETE /v1/organization/groups/{groupId}/users/{userId}
     */
    public function removeUser(string $groupId, string $userId): array;

    /**
     * Assigns an organization role to a group within the organization.
     *
     * POST /v1/organization/groups/{groupId}/roles
     *
     * @param array<string, mixed> $payload
     */
    public function assignRole(string $groupId, array $payload): array;

    /**
     * Retrieves an organization role assigned to a group.
     *
     * GET /v1/organization/groups/{groupId}/roles/{roleId}
     */
    public function retrieveRole(string $groupId, string $roleId): array;

    /**
     * Lists the organization roles assigned to a group within the organization.
     *
     * GET /v1/organization/groups/{groupId}/roles
     *
     * @param array<string, mixed> $params Query parameters
     */
    public function listRoles(string $groupId, array $params = []): array;

    /**
     * Unassigns an organization role from a group within the organization.
     *
     * DELETE /v1/organization/groups/{groupId}/roles/{roleId}
     */
    public function unassignRole(string $groupId, string $roleId): array;
}
