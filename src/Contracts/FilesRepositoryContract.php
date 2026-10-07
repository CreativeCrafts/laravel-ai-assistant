<?php

declare(strict_types=1);

namespace CreativeCrafts\LaravelAiAssistant\Contracts;

/**
 * @internal Low-level abstraction for files operations. Do not use directly.
 * Use Ai::responses() or other builder methods instead.
 */
interface FilesRepositoryContract
{
    /**
     * Upload a file to OpenAI Files API.
     *
     * @param string $filePath
     * @param string $purpose
     * @param array<string, mixed> $params Extra form fields, e.g. ['expires_after' => ['anchor' => 'created_at', 'seconds' => 3600]]
     * @return array File resource as array
     */
    public function upload(string $filePath, string $purpose = 'assistants', array $params = []): array;

    /**
     * List files (GET /v1/files).
     *
     * @param array<string, mixed> $params Query parameters, e.g. purpose, limit, after, order
     */
    public function list(array $params = []): array;

    /**
     * Retrieve a file by id.
     *
     * @param string $fileId
     * @return array
     */
    public function retrieve(string $fileId): array;

    /**
     * Delete a file by id.
     *
     * @param string $fileId
     * @return bool
     */
    public function delete(string $fileId): bool;

    /**
     * Retrieve raw content for a file by id.
     *
     * @param string $fileId
     * @return array{content:string, content_type:string}
     */
    public function content(string $fileId): array;
}
