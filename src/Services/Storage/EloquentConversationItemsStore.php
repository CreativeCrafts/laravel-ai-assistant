<?php

declare(strict_types=1);

namespace CreativeCrafts\LaravelAiAssistant\Services\Storage;

use CreativeCrafts\LaravelAiAssistant\Contracts\Storage\ConversationItemsStoreContract;
use CreativeCrafts\LaravelAiAssistant\Models\ConversationItem;
use InvalidArgumentException;

final class EloquentConversationItemsStore implements ConversationItemsStoreContract
{
    /**
     * Store or update a conversation item in the database.
     * Creates a new conversation item or updates an existing one based on the provided ID.
     * The item array must contain valid 'id' and 'conversation_id' string values.
     *
     * @param array $item The conversation item data containing:
     *                    - 'id' (string): Unique identifier for the conversation item
     *                    - 'conversation_id' (string): ID of the parent conversation
     *                    - 'role' (mixed, optional): Role of the conversation participant
     *                    - 'content' (mixed, optional): Content of the conversation item
     *                    - 'attachments' (mixed, optional): Any attachments associated with the item
     * @return void
     * @throws InvalidArgumentException When 'id' or 'conversation_id' are missing, empty, or not strings
     */
    public function put(array $item): void
    {
        $id = $item['id'] ?? '';
        $conversationId = $item['conversation_id'] ?? '';
        if (!is_string($id) || $id === '' || !is_string($conversationId) || $conversationId === '') {
            throw new InvalidArgumentException('Conversation item must include valid string id and conversation_id');
        }
        $data = [
            'conversation_id' => $conversationId,
            'role' => $item['role'] ?? null,
            'content' => $item['content'] ?? null,
            'attachments' => $item['attachments'] ?? null,
        ];
        ConversationItem::query()->updateOrCreate(['id' => $id], $data);
    }

    /**
     * Retrieve all conversation items belonging to a specific conversation.
     * Fetches all conversation items from the database that match the given conversation ID
     * and returns them as an array of normalized data structures.
     *
     * @param string $conversationId The unique identifier of the conversation to retrieve items for
     * @return array An array of conversation items, where each item is an associative array containing:
     *               - 'id' (string): The unique identifier of the conversation item
     *               - 'conversation_id' (string): The ID of the parent conversation
     *               - 'role' (mixed): The role of the conversation participant
     *               - 'content' (mixed): The content of the conversation item
     *               - 'attachments' (mixed): Any attachments associated with the item
     */
    public function listByConversation(string $conversationId): array
    {
        return ConversationItem::query()->where('conversation_id', $conversationId)->get()->map(function ($m) {
            $modelId = $m->getAttribute('id');
            return [
                'id' => $this->convertModelIdToString($modelId),
                'conversation_id' => $m->getAttribute('conversation_id'),
                'role' => $m->getAttribute('role'),
                'content' => $m->getAttribute('content'),
                'attachments' => $m->getAttribute('attachments'),
            ];
        })->all();
    }

    /**
     * Retrieve a single conversation item by its unique identifier.
     * Fetches a conversation item from the database using the provided ID
     * and returns it as a normalized data structure, or null if not found.
     *
     * @param string $id The unique identifier of the conversation item to retrieve
     * @return array|null An associative array containing the conversation item data if found:
     *                    - 'id' (string): The unique identifier of the conversation item
     *                    - 'conversation_id' (string): The ID of the parent conversation
     *                    - 'role' (mixed): The role of the conversation participant
     *                    - 'content' (mixed): The content of the conversation item
     *                    - 'attachments' (mixed): Any attachments associated with the item
     *                    Returns null if no conversation item with the given ID exists
     */
    public function get(string $id): ?array
    {
        $m = ConversationItem::query()->find($id);
        if (!$m) {
            return null;
        }
        $modelId = $m->getAttribute('id');
        return [
            'id' => $this->convertModelIdToString($modelId),
            'conversation_id' => $m->getAttribute('conversation_id'),
            'role' => $m->getAttribute('role'),
            'content' => $m->getAttribute('content'),
            'attachments' => $m->getAttribute('attachments'),
        ];
    }

    /**
     * Delete a conversation item from the database by its unique identifier.
     * Attempts to find and remove a conversation item with the specified ID.
     * If the item exists, it will be permanently deleted from the database.
     *
     * @param string $id The unique identifier of the conversation item to delete
     * @return bool Returns true if the conversation item was successfully deleted,
     *              false if the item was not found or deletion failed
     */
    public function delete(string $id): bool
    {
        $m = ConversationItem::query()->find($id);
        if (!$m) {
            return false;
        }
        return (bool)$m->delete();
    }

    /**
     * Safely converts a model ID to a string.
     * Handles various ID types that might be returned from Eloquent models,
     * ensuring a consistent string representation.
     *
     * @param mixed $modelId The model ID value to convert
     * @return string The ID as a string, or empty string if conversion fails
     */
    private function convertModelIdToString(mixed $modelId): string
    {
        if (is_string($modelId)) {
            return $modelId;
        }

        if (is_scalar($modelId)) {
            return (string)$modelId;
        }

        return '';
    }
}
