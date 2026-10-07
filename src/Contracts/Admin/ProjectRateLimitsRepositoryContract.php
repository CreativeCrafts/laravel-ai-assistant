<?php

declare(strict_types=1);

namespace CreativeCrafts\LaravelAiAssistant\Contracts\Admin;

/**
 * Per-model project rate limits.
 * Requires an Admin API key (ai-assistant.admin_api_key / OPENAI_ADMIN_KEY).
 * Resolve through Ai::admin()->projectRateLimits().
 */
interface ProjectRateLimitsRepositoryContract
{
    /**
     * Returns the rate limits per model for a project.
     *
     * GET /v1/organization/projects/{projectId}/rate_limits
     *
     * @param array<string, mixed> $params Query parameters
     */
    public function list(string $projectId, array $params = []): array;

    /**
     * Updates a project rate limit.
     *
     * POST /v1/organization/projects/{projectId}/rate_limits/{rateLimitId}
     *
     * @param array<string, mixed> $payload
     */
    public function update(string $projectId, string $rateLimitId, array $payload): array;
}
