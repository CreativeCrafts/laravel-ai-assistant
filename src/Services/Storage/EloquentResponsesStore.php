<?php

declare(strict_types=1);

namespace CreativeCrafts\LaravelAiAssistant\Services\Storage;

use CreativeCrafts\LaravelAiAssistant\Contracts\Storage\ResponsesStoreContract;
use CreativeCrafts\LaravelAiAssistant\Models\ResponseRecord;
use InvalidArgumentException;

final class EloquentResponsesStore implements ResponsesStoreContract
{
    public function put(array $response): void
    {
        $rawId = $response['id'] ?? '';
        $rawConversationId = $response['conversation_id'] ?? '';
        $id = is_string($rawId) ? $rawId : '';
        $conversationId = is_string($rawConversationId) ? $rawConversationId : '';
        if ($id === '' || $conversationId === '') {
            throw new InvalidArgumentException('Response must include id and conversation_id');
        }
        $data = [
            'conversation_id' => $conversationId,
            'status' => $response['status'] ?? null,
            'output_summary' => $response['output_summary'] ?? null,
            'token_usage' => $response['token_usage'] ?? null,
            'timings' => $response['timings'] ?? null,
            'error' => $response['error'] ?? null,
        ];
        ResponseRecord::query()->updateOrCreate(['id' => $id], $data);
    }

    public function listByConversation(string $conversationId): array
    {
        return ResponseRecord::query()->where('conversation_id', $conversationId)->get()->map(function ($m) {
            $modelId = $m->getAttribute('id');
            return [
                'id' => $this->convertModelIdToString($modelId),
                'conversation_id' => $m->getAttribute('conversation_id'),
                'status' => $m->getAttribute('status'),
                'output_summary' => $m->getAttribute('output_summary'),
                'token_usage' => $m->getAttribute('token_usage'),
                'timings' => $m->getAttribute('timings'),
                'error' => $m->getAttribute('error'),
            ];
        })->all();
    }

    public function get(string $id): ?array
    {
        $m = ResponseRecord::query()->find($id);
        if (!$m) {
            return null;
        }
        $modelId = $m->getAttribute('id');
        return [
            'id' => $this->convertModelIdToString($modelId),
            'conversation_id' => $m->getAttribute('conversation_id'),
            'status' => $m->getAttribute('status'),
            'output_summary' => $m->getAttribute('output_summary'),
            'token_usage' => $m->getAttribute('token_usage'),
            'timings' => $m->getAttribute('timings'),
            'error' => $m->getAttribute('error'),
        ];
    }

    public function delete(string $id): bool
    {
        $m = ResponseRecord::query()->find($id);
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
