<?php

declare(strict_types=1);

namespace CreativeCrafts\LaravelAiAssistant\Contracts\Admin;

/**
 * Admin API keys.
 * Requires an Admin API key (ai-assistant.admin_api_key / OPENAI_ADMIN_KEY).
 * Resolve through Ai::admin()->apiKeys().
 */
interface AdminApiKeysRepositoryContract
{
    /**
     * Create an organization admin API key.
     *
     * POST /v1/organization/admin_api_keys
     *
     * @param array<string, mixed> $payload
     */
    public function create(array $payload): array;

    /**
     * Retrieve a single organization API key.
     *
     * GET /v1/organization/admin_api_keys/{keyId}
     */
    public function retrieve(string $keyId): array;

    /**
     * List organization API keys.
     *
     * GET /v1/organization/admin_api_keys
     *
     * @param array<string, mixed> $params Query parameters
     */
    public function list(array $params = []): array;

    /**
     * Delete an organization admin API key.
     *
     * DELETE /v1/organization/admin_api_keys/{keyId}
     */
    public function delete(string $keyId): array;
}
