<?php

declare(strict_types=1);

namespace CreativeCrafts\LaravelAiAssistant\Contracts\Admin;

/**
 * Organization projects.
 * Requires an Admin API key (ai-assistant.admin_api_key / OPENAI_ADMIN_KEY).
 * Resolve through Ai::admin()->projects().
 */
interface ProjectsRepositoryContract
{
    /**
     * Create a new project in the organization. Projects can be created and archived, but cannot be deleted.
     *
     * POST /v1/organization/projects
     *
     * @param array<string, mixed> $payload
     */
    public function create(array $payload): array;

    /**
     * Retrieves a project.
     *
     * GET /v1/organization/projects/{projectId}
     */
    public function retrieve(string $projectId): array;

    /**
     * Modifies a project in the organization.
     *
     * POST /v1/organization/projects/{projectId}
     *
     * @param array<string, mixed> $payload
     */
    public function update(string $projectId, array $payload): array;

    /**
     * Returns a list of projects.
     *
     * GET /v1/organization/projects
     *
     * @param array<string, mixed> $params Query parameters
     */
    public function list(array $params = []): array;

    /**
     * Archives a project in the organization. Archived projects cannot be used or updated.
     *
     * POST /v1/organization/projects/{projectId}/archive
     */
    public function archive(string $projectId): array;
}
