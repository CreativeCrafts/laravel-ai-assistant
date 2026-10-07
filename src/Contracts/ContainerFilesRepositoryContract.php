<?php

declare(strict_types=1);

namespace CreativeCrafts\LaravelAiAssistant\Contracts;

/**
 * Container Files API.
 * Resolve through Ai::containerFiles().
 */
interface ContainerFilesRepositoryContract
{
    /**
     * Create a Container File.
     *
     * POST /v1/containers/{containerId}/files
     *
     * @param array<string, mixed> $payload Multipart fields; file accepts a path, SplFileInfo, stream or explicit part
     */
    public function create(string $containerId, array $payload): array;

    /**
     * Retrieve Container File.
     *
     * GET /v1/containers/{containerId}/files/{fileId}
     */
    public function retrieve(string $containerId, string $fileId): array;

    /**
     * List Container files.
     *
     * GET /v1/containers/{containerId}/files
     *
     * @param array<string, mixed> $params Query parameters
     */
    public function list(string $containerId, array $params = []): array;

    /**
     * Delete Container File.
     *
     * DELETE /v1/containers/{containerId}/files/{fileId}
     */
    public function delete(string $containerId, string $fileId): array;

    /**
     * Retrieve Container File Content.
     *
     * GET /v1/containers/{containerId}/files/{fileId}/content
     *
     * @return array{content: string, content_type: string}
     */
    public function content(string $containerId, string $fileId): array;
}
