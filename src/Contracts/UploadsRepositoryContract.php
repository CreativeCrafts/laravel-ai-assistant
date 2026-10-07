<?php

declare(strict_types=1);

namespace CreativeCrafts\LaravelAiAssistant\Contracts;

/**
 * Uploads API: upload large files in parts (up to 8 GB).
 * Resolve through Ai::uploads().
 */
interface UploadsRepositoryContract
{
    /**
     * Creates an intermediate Upload object that you can add Parts to. Currently, an Upload can accept at most 8 GB in total and expires after an hour after you create it.
     *
     * POST /v1/uploads
     *
     * @param array<string, mixed> $payload
     */
    public function create(array $payload): array;

    /**
     * Adds a Part to an Upload object. A Part represents a chunk of bytes from the file you are trying to upload.
     *
     * POST /v1/uploads/{uploadId}/parts
     *
     * @param array<string, mixed> $payload Multipart fields; data accepts a path, SplFileInfo, stream or explicit part
     */
    public function addPart(string $uploadId, array $payload): array;

    /**
     * Completes the Upload.
     *
     * POST /v1/uploads/{uploadId}/complete
     *
     * @param array<string, mixed> $payload
     */
    public function complete(string $uploadId, array $payload): array;

    /**
     * Cancels the Upload. No Parts may be added after an Upload is cancelled.
     *
     * POST /v1/uploads/{uploadId}/cancel
     */
    public function cancel(string $uploadId): array;

    /**
     * Upload a local file of any size (up to 8 GB) in parts: create the Upload, add each part, then complete it.
     *
     * @param string $purpose The intended purpose of the file, e.g. 'batch', 'fine-tune', 'assistants', 'user_data'
     * @param int $partSize Bytes per part (max 64 MB)
     * @return array The completed Upload object; its 'file' key holds the created File
     */
    public function uploadFile(string $filePath, string $purpose, ?string $mimeType = null, int $partSize = 67108864): array;
}
