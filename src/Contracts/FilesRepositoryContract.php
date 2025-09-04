<?php

declare(strict_types=1);

namespace CreativeCrafts\LaravelAiAssistant\Contracts;

interface FilesRepositoryContract
{
    /**
     * Upload a file to OpenAI Files API.
     *
     * @param string $filePath
     * @param string $purpose
     * @return array File resource as array
     */
    public function upload(string $filePath, string $purpose = 'assistants/answers'): array;

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
