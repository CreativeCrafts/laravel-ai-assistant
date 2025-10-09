<?php

declare(strict_types=1);

namespace CreativeCrafts\LaravelAiAssistant\Services\Storage;

use CreativeCrafts\LaravelAiAssistant\Contracts\Storage\AssistantsStoreContract;
use CreativeCrafts\LaravelAiAssistant\Models\ConversationPreset;
use InvalidArgumentException;

final class EloquentAssistantsStore implements AssistantsStoreContract
{
    /**
     * Store or update an assistant profile in the database.
     *
     * Creates a new assistant profile or updates an existing one based on the provided ID.
     * The assistant data is validated to ensure it contains a valid string ID before processing.
     *
     * @param array $assistant An associative array containing assistant data with the following keys:
     *                        - 'Id' (string, required): Unique identifier for the assistant
     *                        - 'name' (string|null, optional): Display name of the assistant
     *                        - 'default_model' (string|null, optional): Default AI model to use
     *                        - 'default_instructions' (string|null, optional): Default system instructions
     *                        - 'tools' (array|null, optional): Available tools for the assistant
     *                        - 'metadata' (array|null, optional): Additional metadata for the assistant
     *
     * @return void
     *
     * @throws InvalidArgumentException When the assistant array does not contain a valid string ID
     */
    public function put(array $assistant): void
    {
        $id = $assistant['id'] ?? '';
        if (!is_string($id) || $id === '') {
            throw new InvalidArgumentException('Assistant must include a valid string id');
        }
        $data = [
            'name' => $assistant['name'] ?? null,
            'default_model' => $assistant['default_model'] ?? null,
            'default_instructions' => $assistant['default_instructions'] ?? null,
            'tools' => $assistant['tools'] ?? null,
            'metadata' => $assistant['metadata'] ?? null,
        ];
        ConversationPreset::query()->updateOrCreate(['id' => $id], $data);
    }

        /**
     * Retrieve an assistant profile by its unique identifier.
     *
     * Searches for an assistant profile in the database using the provided ID.
     * If found, returns the assistant data as an associative array with all
     * relevant fields. Returns null if no assistant is found with the given ID.
     *
     * @param string $id The unique identifier of the assistant to retrieve
     *
     * @return array|null An associative array containing assistant data with the following keys:
     *                   - 'id' (string): The assistant's unique identifier
     *                   - 'name' (mixed): Display name of the assistant
     *                   - 'default_model' (mixed): Default AI model to use
     *                   - 'default_instructions' (mixed): Default system instructions
     *                   - 'tools' (mixed): Available tools for the assistant
     *                   - 'metadata' (mixed): Additional metadata for the assistant
     *                   Returns null if no assistant is found with the given ID
     */
    public function get(string $id): ?array
    {
        $assistantProfile = ConversationPreset::query()->find($id);
        if (!$assistantProfile) {
            return null;
        }

        $modelId = $assistantProfile->getAttribute('id');
        $stringId = '';
        if (is_string($modelId)) {
            $stringId = $modelId;
        } elseif (is_scalar($modelId)) {
            $stringId = (string) $modelId;
        }

        return [
            'id' => $stringId,
            'name' => $assistantProfile->getAttribute('name'),
            'default_model' => $assistantProfile->getAttribute('default_model'),
            'default_instructions' => $assistantProfile->getAttribute('default_instructions'),
            'tools' => $assistantProfile->getAttribute('tools'),
            'metadata' => $assistantProfile->getAttribute('metadata'),
        ];
    }

    /**
     * Retrieve all assistant profiles from the database.
     *
     * Fetches all assistant profiles stored in the database and transforms them into
     * a standardised array format. Each assistant's ID is converted to a string to
     * ensure consistent data types across the application.
     *
     * @return array An array of associative arrays, where each element represents an assistant profile
     *               containing the following keys:
     *               - 'id' (string): The assistant's unique identifier (converted to string)
     *               - 'name' (mixed): Display name of the assistant
     *               - 'default_model' (mixed): Default AI model to use
     *               - 'default_instructions' (mixed): Default system instructions
     *               - 'tools' (mixed): Available tools for the assistant
     *               - 'metadata' (mixed): Additional metadata for the assistant
     *               Returns an empty array if no assistant profiles are found
     */
    public function all(): array
    {
        return ConversationPreset::query()->get()->map(function ($assistantProfile) {
            $modelId = $assistantProfile->getAttribute('id');
            $stringId = '';
            if (is_string($modelId)) {
                $stringId = $modelId;
            } elseif (is_scalar($modelId)) {
                $stringId = (string) $modelId;
            }
            return [
                'id' => $stringId,
                'name' => $assistantProfile->getAttribute('name'),
                'default_model' => $assistantProfile->getAttribute('default_model'),
                'default_instructions' => $assistantProfile->getAttribute('default_instructions'),
                'tools' => $assistantProfile->getAttribute('tools'),
                'metadata' => $assistantProfile->getAttribute('metadata'),
            ];
        })->all();
    }

    /**
     * Delete an assistant profile from the database.
     *
     * Searches for an assistant profile by its unique identifier and removes it
     * from the database if found. The operation is performed as a soft or hard
     * delete depending on the model configuration.
     *
     * @param string $id The unique identifier of the assistant profile to delete
     *
     * @return bool Returns true if the assistant profile was found and successfully deleted,
     *              false if no assistant profile was found with the given ID
     */
    public function delete(string $id): bool
    {
        $assistantProfile = ConversationPreset::query()->find($id);
        if (!$assistantProfile) {
            return false;
        }
        return (bool)$assistantProfile->delete();
    }
}
