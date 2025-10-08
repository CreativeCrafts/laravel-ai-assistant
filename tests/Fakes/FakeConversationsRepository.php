<?php

declare(strict_types=1);

namespace CreativeCrafts\LaravelAiAssistant\Tests\Fakes;

use CreativeCrafts\LaravelAiAssistant\Contracts\ConversationsRepositoryContract;

final class FakeConversationsRepository implements ConversationsRepositoryContract
{
    /** @var array<string,array> */
    private array $conversations = [];
    /** @var array<string,array<int,array>> */
    private array $items = [];

    public function updateConversation(string $conversationId, array $payload): array
    {
        $current = $this->conversations[$conversationId] ?? ['id' => $conversationId];
        $updated = array_replace($current, $payload);
        $this->conversations[$conversationId] = $updated;
        return $updated;
    }

    public function deleteConversation(string $conversationId): bool
    {
        unset($this->conversations[$conversationId], $this->items[$conversationId]);
        return true;
    }

    public function createConversation(array $payload = []): array
    {
        $id = $payload['id'] ?? ('conv_' . str_pad(bin2hex(random_bytes(12)), 24, '0'));
        $conv = ['id' => $id, 'metadata' => $payload['metadata'] ?? []];
        $this->conversations[$id] = $conv;
        $this->items[$id] = $this->items[$id] ?? [];
        return $conv;
    }

    public function getConversation(string $conversationId): array
    {
        return $this->conversations[$conversationId] ?? ['id' => $conversationId];
    }

    public function listItems(string $conversationId, array $params = []): array
    {
        $data = $this->items[$conversationId] ?? [];
        return ['data' => $data, 'object' => 'list'];
    }

    public function createItems(string $conversationId, array $items): array
    {
        $this->items[$conversationId] = array_merge($this->items[$conversationId] ?? [], $items);
        return ['object' => 'list', 'data' => $items];
    }

    public function deleteItem(string $conversationId, string $itemId): bool
    {
        if (!isset($this->items[$conversationId])) {
            return false;
        }
        $this->items[$conversationId] = array_values(array_filter(
            $this->items[$conversationId],
            fn ($it) => ($it['id'] ?? null) !== $itemId
        ));
        return true;
    }
}
