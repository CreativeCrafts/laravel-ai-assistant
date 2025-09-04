<?php

declare(strict_types=1);

namespace CreativeCrafts\LaravelAiAssistant\Contracts\Storage;

/**
 * Eloquent-ready/array contract for storing assistant profiles locally.
 * Expected array shape:
 * [
 *   'id' => string,
 *   'name' => string|null,
 *   'default_model' => string|null,
 *   'default_instructions' => string|null,
 *   'tools' => array|null, // array of tool schemas
 *   'metadata' => array|null,
 * ]
 */
interface AssistantsStoreContract
{
    /** @param array<string,mixed> $assistant */
    public function put(array $assistant): void;

    public function get(string $id): ?array;

    /** @return array<int,array<string,mixed>> */
    public function all(): array;

    public function delete(string $id): bool;
}
