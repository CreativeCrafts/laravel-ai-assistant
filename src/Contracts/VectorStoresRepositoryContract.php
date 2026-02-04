<?php

declare(strict_types=1);

namespace CreativeCrafts\LaravelAiAssistant\Contracts;

/**
 * @internal Low-level abstraction for vector stores operations. Do not use directly.
 */
interface VectorStoresRepositoryContract
{
    public function create(array $payload): array;

    public function retrieve(string $vectorStoreId): array;

    public function update(string $vectorStoreId, array $payload): array;

    public function delete(string $vectorStoreId): bool;

    public function list(array $params = []): array;
}
