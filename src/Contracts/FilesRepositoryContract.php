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
     * @return array File resource as array
     */
    public function upload(string $filePath, string $purpose = 'assistants'): array;

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
}
