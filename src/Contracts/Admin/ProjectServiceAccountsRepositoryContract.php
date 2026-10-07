<?php

declare(strict_types=1);

namespace CreativeCrafts\LaravelAiAssistant\Contracts\Admin;

/**
 * Project service accounts and their API keys.
 * Requires an Admin API key (ai-assistant.admin_api_key / OPENAI_ADMIN_KEY).
 * Resolve through Ai::admin()->projectServiceAccounts().
 */
interface ProjectServiceAccountsRepositoryContract
{
    /**
     * Creates a new service account in the project. By default, this also returns an unredacted API key for the service account.
     *
     * POST /v1/organization/projects/{projectId}/service_accounts
     *
     * @param array<string, mixed> $payload
     */
    public function create(string $projectId, array $payload): array;

    /**
     * Retrieves a service account in the project.
     *
     * GET /v1/organization/projects/{projectId}/service_accounts/{serviceAccountId}
     */
    public function retrieve(string $projectId, string $serviceAccountId): array;

    /**
     * Updates a service account in the project.
     *
     * POST /v1/organization/projects/{projectId}/service_accounts/{serviceAccountId}
     *
     * @param array<string, mixed> $payload
     */
    public function update(string $projectId, string $serviceAccountId, array $payload): array;

    /**
     * Returns a list of service accounts in the project.
     *
     * GET /v1/organization/projects/{projectId}/service_accounts
     *
     * @param array<string, mixed> $params Query parameters
     */
    public function list(string $projectId, array $params = []): array;

    /**
     * Deletes a service account from the project.
     *
     * DELETE /v1/organization/projects/{projectId}/service_accounts/{serviceAccountId}
     */
    public function delete(string $projectId, string $serviceAccountId): array;

    /**
     * Creates an API key for a service account in the project.
     *
     * POST /v1/organization/projects/{projectId}/service_accounts/{serviceAccountId}/api_keys
     *
     * @param array<string, mixed> $payload
     */
    public function createApiKey(string $projectId, string $serviceAccountId, array $payload = []): array;
}
