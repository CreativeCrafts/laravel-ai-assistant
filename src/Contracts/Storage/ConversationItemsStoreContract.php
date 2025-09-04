<?php

declare(strict_types=1);

namespace CreativeCrafts\LaravelAiAssistant\Contracts\Storage;

/**
 * Eloquent-ready/array contract for storing conversation items (messages and tool results).
 * Expected array shape:
 * [
 *   'id' => string, // OpenAI item id
 *   'conversation_id' => string,
 *   'role' => string|null, // 'user' | 'assistant' | 'tool'
 *   'content' => array|string|null, // structured blocks or plain text
 *   'attachments' => array|null,
 *   'created_at' => int|string|null, // timestamp or RFC3339
 * ]
 */
interface ConversationItemsStoreContract
{
    /** @param array<string,mixed> $item */
    public function put(array $item): void;

    public function get(string $id): ?array;

    /** @return array<int,array<string,mixed>> */
    public function listByConversation(string $conversationId): array;

    public function delete(string $id): bool;
}
