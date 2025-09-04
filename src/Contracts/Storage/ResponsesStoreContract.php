<?php

declare(strict_types=1);

namespace CreativeCrafts\LaravelAiAssistant\Contracts\Storage;

/**
 * Eloquent-ready/array contract for storing responses metadata.
 * Expected array shape:
 * [
 *   'id' => string,
 *   'conversation_id' => string,
 *   'status' => string|null,
 *   'output_summary' => string|null,
 *   'token_usage' => array|null, // {input: int, output: int, total: int}
 *   'timings' => array|null, // e.g., {started_at, completed_at, latency_ms}
 *   'error' => array|string|null,
 * ]
 */
interface ResponsesStoreContract
{
    /** @param array<string,mixed> $response */
    public function put(array $response): void;

    public function get(string $id): ?array;

    /** @return array<int,array<string,mixed>> */
    public function listByConversation(string $conversationId): array;

    public function delete(string $id): bool;
}
