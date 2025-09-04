<?php

declare(strict_types=1);

namespace CreativeCrafts\LaravelAiAssistant\Contracts;

interface ConversationsRepositoryContract
{
    /**
     * Create a new conversation.
     *
     * @param array $payload
     * @return array Conversation resource as array
     */
    public function createConversation(array $payload = []): array;

    /**
     * Retrieve a conversation.
     *
     * @param string $conversationId
     * @return array
     */
    public function getConversation(string $conversationId): array;

    /**
     * List items in a conversation.
     *
     * @param string $conversationId
     * @param array $params
     * @return array
     */
    public function listItems(string $conversationId, array $params = []): array;

    /**
     * Create items in a conversation (advanced insert/backfill).
     *
     * @param string $conversationId
     * @param array $items
     * @return array
     */
    public function createItems(string $conversationId, array $items): array;

    /**
     * Delete a single item from a conversation.
     *
     * @param string $conversationId
     * @param string $itemId
     * @return bool
     */
    public function deleteItem(string $conversationId, string $itemId): bool;
}
