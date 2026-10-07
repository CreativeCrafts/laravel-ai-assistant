<?php

declare(strict_types=1);

namespace CreativeCrafts\LaravelAiAssistant\Contracts\Admin;

/**
 * Project API keys.
 * Requires an Admin API key (ai-assistant.admin_api_key / OPENAI_ADMIN_KEY).
 * Resolve through Ai::admin()->projectApiKeys().
 */
interface ProjectApiKeysRepositoryContract
{
    /**
     * Retrieves an API key in the project.
     *
     * GET /v1/organization/projects/{projectId}/api_keys/{apiKeyId}
     */
    public function retrieve(string $projectId, string $apiKeyId): array;

    /**
     * Returns a list of API keys in the project.
     *
     * GET /v1/organization/projects/{projectId}/api_keys
     *
     * @param array<string, mixed> $params Query parameters
     */
    public function list(string $projectId, array $params = []): array;

    /**
     * Deletes an API key from the project.
     *
     * DELETE /v1/organization/projects/{projectId}/api_keys/{apiKeyId}
     */
    public function delete(string $projectId, string $apiKeyId): array;
}
