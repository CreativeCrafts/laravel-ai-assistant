<?php

declare(strict_types=1);

namespace CreativeCrafts\LaravelAiAssistant\Services\Storage;

use CreativeCrafts\LaravelAiAssistant\Contracts\Storage\ToolInvocationsStoreContract;
use CreativeCrafts\LaravelAiAssistant\Models\ToolInvocation;
use InvalidArgumentException;
use Random\RandomException;

final class EloquentToolInvocationsStore implements ToolInvocationsStoreContract
{
    public function put(array $invocation): void
    {
        $id = (isset($invocation['id']) && is_string($invocation['id']) && $invocation['id'] !== '') ? $invocation['id'] : $this->generateId();
        $responseId = $invocation['response_id'] ?? '';
        $responseId = is_string($responseId) ? $responseId : '';
        if ($responseId === '') {
            throw new InvalidArgumentException('Tool invocation must include response_id');
        }
        $name = $invocation['name'] ?? '';
        $name = is_string($name) ? $name : '';
        $data = [
            'response_id' => $responseId,
            'name' => $name,
            'arguments' => $invocation['arguments'] ?? null,
            'state' => $invocation['state'] ?? null,
            'result_summary' => $invocation['result_summary'] ?? null,
        ];
        ToolInvocation::query()->updateOrCreate(['id' => $id], $data);
        $invocation['id'] = $id; // reflect assigned id when missing
    }

    public function listByResponse(string $responseId): array
    {
        return ToolInvocation::query()->where('response_id', $responseId)->get()->map(function ($m) {
            $modelId = $m->getAttribute('id');
            return [
                'id' => $this->convertModelIdToString($modelId),
                'response_id' => $m->getAttribute('response_id'),
                'name' => $m->getAttribute('name'),
                'arguments' => $m->getAttribute('arguments'),
                'state' => $m->getAttribute('state'),
                'result_summary' => $m->getAttribute('result_summary'),
            ];
        })->all();
    }

    public function get(?string $id): ?array
    {
        if ($id === null) {
            return null;
        }
        $m = ToolInvocation::query()->find($id);
        if (!$m) {
            return null;
        }
        $modelId = $m->getAttribute('id');
        return [
            'id' => $this->convertModelIdToString($modelId),
            'response_id' => $m->getAttribute('response_id'),
            'name' => $m->getAttribute('name'),
            'arguments' => $m->getAttribute('arguments'),
            'state' => $m->getAttribute('state'),
            'result_summary' => $m->getAttribute('result_summary'),
        ];
    }

    public function delete(?string $id): bool
    {
        if ($id === null) {
            return false;
        }
        $m = ToolInvocation::query()->find($id);
        if (!$m) {
            return false;
        }
        return (bool)$m->delete();
    }

    /**
     * @throws RandomException
     */
    private function generateId(): string
    {
        return bin2hex(random_bytes(8));
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
