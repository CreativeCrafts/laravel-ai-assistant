<?php

declare(strict_types=1);

namespace CreativeCrafts\LaravelAiAssistant\Contracts;

/**
 * Containers API: sandboxed environments used by the Code Interpreter and shell tools.
 * Resolve through Ai::containers().
 */
interface ContainersRepositoryContract
{
    /**
     * Create Container.
     *
     * POST /v1/containers
     *
     * @param array<string, mixed> $payload
     */
    public function create(array $payload): array;

    /**
     * Retrieve Container.
     *
     * GET /v1/containers/{containerId}
     */
    public function retrieve(string $containerId): array;

    /**
     * List Containers.
     *
     * GET /v1/containers
     *
     * @param array<string, mixed> $params Query parameters
     */
    public function list(array $params = []): array;

    /**
     * Delete Container.
     *
     * DELETE /v1/containers/{containerId}
     */
    public function delete(string $containerId): array;
}
