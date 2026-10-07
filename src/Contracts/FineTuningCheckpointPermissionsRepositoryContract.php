<?php

declare(strict_types=1);

namespace CreativeCrafts\LaravelAiAssistant\Contracts;

/**
 * Fine-tuning checkpoint permissions API: share fine-tuned model checkpoints with other projects. Requires an Admin API key.
 * Resolve through Ai::fineTuningCheckpointPermissions().
 */
interface FineTuningCheckpointPermissionsRepositoryContract
{
    /**
     * Note: Calling this endpoint requires an admin API key.
     *
     * POST /v1/fine_tuning/checkpoints/{checkpoint}/permissions
     *
     * @param array<string, mixed> $payload
     */
    public function create(string $checkpoint, array $payload): array;

    /**
     * Note: This endpoint requires an admin API key.
     *
     * GET /v1/fine_tuning/checkpoints/{checkpoint}/permissions
     *
     * @param array<string, mixed> $params Query parameters
     */
    public function list(string $checkpoint, array $params = []): array;

    /**
     * Note: This endpoint requires an admin API key.
     *
     * DELETE /v1/fine_tuning/checkpoints/{checkpoint}/permissions/{permissionId}
     */
    public function delete(string $checkpoint, string $permissionId): array;
}
