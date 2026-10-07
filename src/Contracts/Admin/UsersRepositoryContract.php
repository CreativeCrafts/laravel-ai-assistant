<?php

declare(strict_types=1);

namespace CreativeCrafts\LaravelAiAssistant\Contracts\Admin;

/**
 * Organization users and their role assignments.
 * Requires an Admin API key (ai-assistant.admin_api_key / OPENAI_ADMIN_KEY).
 * Resolve through Ai::admin()->users().
 */
interface UsersRepositoryContract
{
    /**
     * Retrieves a user by their identifier.
     *
     * GET /v1/organization/users/{userId}
     */
    public function retrieve(string $userId): array;

    /**
     * Modifies a user's role in the organization.
     *
     * POST /v1/organization/users/{userId}
     *
     * @param array<string, mixed> $payload
     */
    public function update(string $userId, array $payload): array;

    /**
     * Lists all of the users in the organization.
     *
     * GET /v1/organization/users
     *
     * @param array<string, mixed> $params Query parameters
     */
    public function list(array $params = []): array;

    /**
     * Deletes a user from the organization.
     *
     * DELETE /v1/organization/users/{userId}
     */
    public function delete(string $userId): array;

    /**
     * Assigns an organization role to a user within the organization.
     *
     * POST /v1/organization/users/{userId}/roles
     *
     * @param array<string, mixed> $payload
     */
    public function assignRole(string $userId, array $payload): array;

    /**
     * Retrieves an organization role assigned to a user.
     *
     * GET /v1/organization/users/{userId}/roles/{roleId}
     */
    public function retrieveRole(string $userId, string $roleId): array;

    /**
     * Lists the organization roles assigned to a user within the organization.
     *
     * GET /v1/organization/users/{userId}/roles
     *
     * @param array<string, mixed> $params Query parameters
     */
    public function listRoles(string $userId, array $params = []): array;

    /**
     * Unassigns an organization role from a user within the organization.
     *
     * DELETE /v1/organization/users/{userId}/roles/{roleId}
     */
    public function unassignRole(string $userId, string $roleId): array;
}
