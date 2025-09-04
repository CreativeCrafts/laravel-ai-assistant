<?php

declare(strict_types=1);

namespace CreativeCrafts\LaravelAiAssistant\Services\Storage;

use CreativeCrafts\LaravelAiAssistant\Contracts\Storage\ToolInvocationsStoreContract;
use InvalidArgumentException;

final class InMemoryToolInvocationsStore implements ToolInvocationsStoreContract
{
    /** @var array<string,array<string,mixed>> */
    private array $invocations = [];

    /** @var array<string,array<int,string>> response_id => [invocationIds...] */
    private array $byResponse = [];

    public function put(array $invocation): void
    {
        $id = isset($invocation['id']) && is_string($invocation['id']) && $invocation['id'] !== ''
            ? $invocation['id']
            : $this->generateId();
        $rawResponseId = $invocation['response_id'] ?? '';
        $responseId = is_string($rawResponseId) ? $rawResponseId : '';
        if ($responseId === '') {
            throw new InvalidArgumentException('Tool invocation must include response_id');
        }
        $invocation['id'] = $id;
        $this->invocations[$id] = $invocation;
        if (!isset($this->byResponse[$responseId])) {
            $this->byResponse[$responseId] = [];
        }
        if (!in_array($id, $this->byResponse[$responseId], true)) {
            $this->byResponse[$responseId][] = $id;
        }
    }

    public function get(?string $id): ?array
    {
        if ($id === null) {
            return null;
        }
        return $this->invocations[$id] ?? null;
    }

    public function listByResponse(string $responseId): array
    {
        $ids = $this->byResponse[$responseId] ?? [];
        return array_values(array_map(fn (string $id) => $this->invocations[$id], $ids));
    }

    public function delete(?string $id): bool
    {
        if ($id === null || !isset($this->invocations[$id])) {
            return false;
        }
        $rawResp = $this->invocations[$id]['response_id'] ?? '';
        $responseId = is_string($rawResp) ? $rawResp : '';
        unset($this->invocations[$id]);
        if ($responseId !== '' && isset($this->byResponse[$responseId])) {
            $this->byResponse[$responseId] = array_values(array_filter($this->byResponse[$responseId], fn ($v) => $v !== $id));
            if ($this->byResponse[$responseId] === []) {
                unset($this->byResponse[$responseId]);
            }
        }
        return true;
    }

    private function generateId(): string
    {
        return bin2hex(random_bytes(8));
    }
}
