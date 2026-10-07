<?php

declare(strict_types=1);

namespace CreativeCrafts\LaravelAiAssistant\Contracts\Admin;

/**
 * Organization and project data retention settings.
 * Requires an Admin API key (ai-assistant.admin_api_key / OPENAI_ADMIN_KEY).
 * Resolve through Ai::admin()->dataRetention().
 */
interface DataRetentionRepositoryContract
{
    /**
     * Retrieves organization data retention controls.
     *
     * GET /v1/organization/data_retention
     */
    public function retrieve(): array;

    /**
     * Updates organization data retention controls.
     *
     * POST /v1/organization/data_retention
     *
     * @param array<string, mixed> $payload
     */
    public function update(array $payload): array;

    /**
     * Retrieves project data retention controls.
     *
     * GET /v1/organization/projects/{projectId}/data_retention
     */
    public function retrieveForProject(string $projectId): array;

    /**
     * Updates project data retention controls.
     *
     * POST /v1/organization/projects/{projectId}/data_retention
     *
     * @param array<string, mixed> $payload
     */
    public function updateForProject(string $projectId, array $payload): array;
}
