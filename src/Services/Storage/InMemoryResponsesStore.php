<?php

declare(strict_types=1);

namespace CreativeCrafts\LaravelAiAssistant\Services\Storage;

use CreativeCrafts\LaravelAiAssistant\Contracts\Storage\ResponsesStoreContract;
use InvalidArgumentException;

final class InMemoryResponsesStore implements ResponsesStoreContract
{
    /** @var array<string,array<string,mixed>> */
    private array $responses = [];

    /** @var array<string,array<int,string>> conversation_id => [responseIds...] */
    private array $byConversation = [];

    public function put(array $response): void
    {
        $rawId = $response['id'] ?? '';
        $rawConvId = $response['conversation_id'] ?? '';
        $id = is_string($rawId) ? $rawId : '';
        $conversationId = is_string($rawConvId) ? $rawConvId : '';
        if ($id === '' || $conversationId === '') {
            throw new InvalidArgumentException('Response must include id and conversation_id');
        }
        $this->responses[$id] = $response;
        if (!isset($this->byConversation[$conversationId])) {
            $this->byConversation[$conversationId] = [];
        }
        if (!in_array($id, $this->byConversation[$conversationId], true)) {
            $this->byConversation[$conversationId][] = $id;
        }
    }

    public function get(string $id): ?array
    {
        return $this->responses[$id] ?? null;
    }

    public function listByConversation(string $conversationId): array
    {
        $ids = $this->byConversation[$conversationId] ?? [];
        return array_values(array_map(fn (string $id) => $this->responses[$id], $ids));
    }

    public function delete(string $id): bool
    {
        if (!isset($this->responses[$id])) {
            return false;
        }
        $rawConv = $this->responses[$id]['conversation_id'] ?? '';
        $conversationId = is_string($rawConv) ? $rawConv : '';
        unset($this->responses[$id]);
        if ($conversationId !== '' && isset($this->byConversation[$conversationId])) {
            $this->byConversation[$conversationId] = array_values(array_filter($this->byConversation[$conversationId], fn ($v) => $v !== $id));
            if ($this->byConversation[$conversationId] === []) {
                unset($this->byConversation[$conversationId]);
            }
        }
        return true;
    }
}
