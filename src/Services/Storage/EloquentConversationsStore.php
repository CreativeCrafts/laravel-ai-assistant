<?php

declare(strict_types=1);

namespace CreativeCrafts\LaravelAiAssistant\Services\Storage;

use CreativeCrafts\LaravelAiAssistant\Contracts\Storage\ConversationsStoreContract;
use CreativeCrafts\LaravelAiAssistant\Models\Conversation;
use InvalidArgumentException;

final class EloquentConversationsStore implements ConversationsStoreContract
{
    /**
     * Store or update a conversation in the database.
     * Creates a new conversation record or updates an existing one based on the provided ID.
     * The conversation data is validated to ensure it contains a valid string ID before processing.
     *
     * @param array $conversation The conversation data array containing:
     *                           - 'id' (string, required): Unique identifier for the conversation
     *                           - 'user_id' (mixed, optional): ID of the user associated with the conversation
     *                           - 'title' (mixed, optional): Title or name of the conversation
     *                           - 'status' (mixed, optional): Current status of the conversation
     *                           - 'metadata' (mixed, optional): Additional metadata for the conversation
     * @return void
     * @throws InvalidArgumentException When the conversation array does not contain a valid string ID
     */
    public function put(array $conversation): void
    {
        $rawId = $conversation['id'] ?? '';
        $id = is_string($rawId) ? $rawId : '';
        if ($id === '') {
            throw new InvalidArgumentException('Conversation must include an id');
        }
        $data = [
            'user_id' => $conversation['user_id'] ?? null,
            'title' => $conversation['title'] ?? null,
            'status' => $conversation['status'] ?? null,
            'metadata' => $conversation['metadata'] ?? null,
        ];
        Conversation::query()->updateOrCreate(['id' => $id], $data);
    }

    /**
     * Retrieve all conversations from the database.
     * Fetches all conversation records from the database and returns them as an array
     * of associative arrays. Each conversation is transformed to include all relevant
     * attributes with consistent data types, particularly ensuring the ID is properly
     * converted to a string format.
     *
     * @return array An array of associative arrays, where each element represents a conversation
     *               and contains the following keys:
     *               - 'id' (string): The conversation's unique identifier as a string
     *               - 'user_id' (mixed): ID of the user associated with the conversation
     *               - 'title' (mixed): Title or name of the conversation
     *               - 'status' (mixed): Current status of the conversation
     *               - 'metadata' (mixed): Additional metadata for the conversation
     *               Returns an empty array if no conversations exist
     */
    public function all(): array
    {
        return Conversation::query()->get()->map(function ($m) {
            $modelId = $m->getAttribute('id');
            return [
                'id' => $this->convertModelIdToString($modelId),
                'user_id' => $m->getAttribute('user_id'),
                'title' => $m->getAttribute('title'),
                'status' => $m->getAttribute('status'),
                'metadata' => $m->getAttribute('metadata'),
            ];
        })->all();
    }

    /**
     * Retrieve a conversation from the database by its ID.
     * Searches for a conversation record with the specified ID and returns its data
     * as an array. If no conversation is found, returns null. The returned array
     * contains all conversation attributes, including ID, user association, title,
     * status, and metadata.
     *
     * @param string $id The unique identifier of the conversation to retrieve
     * @return array|null An associative array containing conversation data with keys:
     *                    - 'id' (string): The conversation's unique identifier
     *                    - 'user_id' (mixed): ID of the user associated with the conversation
     *                    - 'title' (mixed): Title or name of the conversation
     *                    - 'status' (mixed): Current status of the conversation
     *                    - 'metadata' (mixed): Additional metadata for the conversation
     *                    Returns null if no conversation with the given ID exists
     */
    public function get(string $id): ?array
    {
        $m = Conversation::query()->find($id);
        if (!$m) {
            return null;
        }
        $idAttr = $m->getAttribute('id');
        $idStr = is_string($idAttr) ? $idAttr : '';
        return [
            'id' => $idStr,
            'user_id' => $m->getAttribute('user_id'),
            'title' => $m->getAttribute('title'),
            'status' => $m->getAttribute('status'),
            'metadata' => $m->getAttribute('metadata'),
        ];
    }

    public function delete(string $id): bool
    {
        $m = Conversation::query()->find($id);
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
