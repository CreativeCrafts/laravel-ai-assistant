<?php

declare(strict_types=1);

namespace CreativeCrafts\LaravelAiAssistant\Contracts;

/**
 * @internal Low-level abstraction for vector store files operations. Do not use directly.
 */
interface VectorStoreFilesRepositoryContract
{
    public function create(string $vectorStoreId, array $payload): array;

    public function retrieve(string $vectorStoreId, string $fileId): array;

    public function update(string $vectorStoreId, string $fileId, array $payload): array;

    public function delete(string $vectorStoreId, string $fileId): bool;

    public function list(string $vectorStoreId, array $params = []): array;

    public function content(string $vectorStoreId, string $fileId): array;
}
