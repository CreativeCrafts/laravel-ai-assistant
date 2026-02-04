<?php

declare(strict_types=1);

namespace CreativeCrafts\LaravelAiAssistant\Contracts;

/**
 * @internal Low-level abstraction for vector store file batches operations. Do not use directly.
 */
interface VectorStoreFileBatchesRepositoryContract
{
    public function create(string $vectorStoreId, array $payload): array;

    public function retrieve(string $vectorStoreId, string $batchId): array;

    public function cancel(string $vectorStoreId, string $batchId): array;

    public function listFiles(string $vectorStoreId, string $batchId, array $params = []): array;
}
