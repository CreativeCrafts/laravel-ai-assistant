<?php

declare(strict_types=1);

namespace CreativeCrafts\LaravelAiAssistant\Contracts;

/**
 * @internal Low-level abstraction for batches operations. Do not use directly.
 */
interface BatchesRepositoryContract
{
    public function create(array $payload): array;

    public function retrieve(string $batchId): array;

    public function cancel(string $batchId): array;

    public function list(array $params = []): array;
}
