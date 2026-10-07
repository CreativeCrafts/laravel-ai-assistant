<?php

declare(strict_types=1);

namespace CreativeCrafts\LaravelAiAssistant\Contracts\Admin;

/**
 * Organization and project spend limits.
 * Requires an Admin API key (ai-assistant.admin_api_key / OPENAI_ADMIN_KEY).
 * Resolve through Ai::admin()->spendLimit().
 */
interface SpendLimitRepositoryContract
{
    /**
     * Get the organization's hard spend limit.
     *
     * GET /v1/organization/spend_limit
     */
    public function retrieve(): array;

    /**
     * Create or replace the organization's hard spend limit.
     *
     * POST /v1/organization/spend_limit
     *
     * @param array<string, mixed> $payload
     */
    public function update(array $payload): array;

    /**
     * Delete the organization's hard spend limit.
     *
     * DELETE /v1/organization/spend_limit
     */
    public function delete(): array;

    /**
     * Get a project's hard spend limit.
     *
     * GET /v1/organization/projects/{projectId}/spend_limit
     */
    public function retrieveForProject(string $projectId): array;

    /**
     * Create or replace a project's hard spend limit.
     *
     * POST /v1/organization/projects/{projectId}/spend_limit
     *
     * @param array<string, mixed> $payload
     */
    public function updateForProject(string $projectId, array $payload): array;

    /**
     * Delete a project's hard spend limit.
     *
     * DELETE /v1/organization/projects/{projectId}/spend_limit
     */
    public function deleteForProject(string $projectId): array;
}
