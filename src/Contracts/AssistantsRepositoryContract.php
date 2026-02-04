<?php

declare(strict_types=1);

namespace CreativeCrafts\LaravelAiAssistant\Contracts;

/**
 * @internal Low-level abstraction for assistants operations. Do not use directly.
 */
interface AssistantsRepositoryContract
{
    public function create(array $payload): array;

    public function retrieve(string $assistantId): array;

    public function update(string $assistantId, array $payload): array;

    public function delete(string $assistantId): bool;

    public function list(array $params = []): array;
}
