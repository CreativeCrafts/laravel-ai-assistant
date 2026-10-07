<?php

declare(strict_types=1);

namespace CreativeCrafts\LaravelAiAssistant\Contracts\Admin;

/**
 * External storage connections (customer-managed buckets).
 * Requires an Admin API key (ai-assistant.admin_api_key / OPENAI_ADMIN_KEY).
 * Resolve through Ai::admin()->externalStorage().
 */
interface ExternalStorageRepositoryContract
{
    /**
     * Register one customer-managed external storage configuration.
     *
     * POST /v1/organization/external_storage
     *
     * @param array<string, mixed> $payload
     */
    public function create(array $payload): array;

    /**
     * Get one customer-managed external storage configuration.
     *
     * GET /v1/organization/external_storage/{externalStorageId}
     */
    public function retrieve(string $externalStorageId): array;

    /**
     * List the organization's customer-managed external storage configurations.
     *
     * GET /v1/organization/external_storage
     *
     * @param array<string, mixed> $params Query parameters
     */
    public function list(array $params = []): array;

    /**
     * Disconnect a customer-managed external storage configuration. Removing the project's last configuration restores organization-default retention if customer-managed retention was active.
     *
     * DELETE /v1/organization/external_storage/{externalStorageId}
     */
    public function delete(string $externalStorageId): array;

    /**
     * Validate one customer-managed external storage configuration.
     *
     * POST /v1/organization/external_storage/{externalStorageId}/validate
     */
    public function validate(string $externalStorageId): array;
}
