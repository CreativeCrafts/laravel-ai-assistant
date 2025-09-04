<?php

declare(strict_types=1);

namespace CreativeCrafts\LaravelAiAssistant\Contracts\Storage;

/**
 * Eloquent-ready/array contract for storing tool invocations emitted during a response.
 * Expected array shape:
 * [
 *   'id' => string|null, // optional local id
 *   'response_id' => string,
 *   'name' => string,
 *   'arguments' => array|string|null,
 *   'state' => string|null, // pending|running|completed|failed
 *   'result_summary' => string|array|null,
 * ]
 */
interface ToolInvocationsStoreContract
{
    /** @param array<string,mixed> $invocation */
    public function put(array $invocation): void;

    /**
     * Retrieve by local id if provided. Implementations may return null when id is null or not stored.
     */
    public function get(?string $id): ?array;

    /** @return array<int,array<string,mixed>> */
    public function listByResponse(string $responseId): array;

    public function delete(?string $id): bool;
}
