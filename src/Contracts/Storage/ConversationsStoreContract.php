<?php

declare(strict_types=1);

namespace CreativeCrafts\LaravelAiAssistant\Contracts\Storage;

/**
 * Eloquent-ready/array contract for storing conversations.
 * Expected array shape:
 * [
 *   'id' => string, // OpenAI conversation.id
 *   'user_id' => string|null,
 *   'title' => string|null,
 *   'status' => string|null,
 *   'metadata' => array|null,
 * ]
 */
interface ConversationsStoreContract
{
    /** @param array<string,mixed> $conversation */
    public function put(array $conversation): void;

    public function get(string $id): ?array;

    /** @return array<int,array<string,mixed>> */
    public function all(): array;

    public function delete(string $id): bool;
}
