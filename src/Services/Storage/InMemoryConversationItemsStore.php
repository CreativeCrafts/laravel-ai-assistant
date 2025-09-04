<?php

declare(strict_types=1);

namespace CreativeCrafts\LaravelAiAssistant\Services\Storage;

use CreativeCrafts\LaravelAiAssistant\Contracts\Storage\ConversationItemsStoreContract;
use InvalidArgumentException;

final class InMemoryConversationItemsStore implements ConversationItemsStoreContract
{
    /** @var array<string,array<string,mixed>> */
    private array $items = [];

    /** @var array<string,array<int,string>> conversation_id => [itemIds...] */
    private array $byConversation = [];

    public function put(array $item): void
    {
        $rawId = $item['id'] ?? '';
        $rawConvId = $item['conversation_id'] ?? '';
        $id = is_string($rawId) ? $rawId : '';
        $conversationId = is_string($rawConvId) ? $rawConvId : '';
        if ($id === '' || $conversationId === '') {
            throw new InvalidArgumentException('Conversation item must include id and conversation_id');
        }
        $this->items[$id] = $item;
        if (!isset($this->byConversation[$conversationId])) {
            $this->byConversation[$conversationId] = [];
        }
        if (!in_array($id, $this->byConversation[$conversationId], true)) {
            $this->byConversation[$conversationId][] = $id;
        }
    }

    public function get(string $id): ?array
    {
        return $this->items[$id] ?? null;
    }

    public function listByConversation(string $conversationId): array
    {
        $ids = $this->byConversation[$conversationId] ?? [];
        return array_values(array_map(fn (string $id) => $this->items[$id], $ids));
    }

    public function delete(string $id): bool
    {
        if (!isset($this->items[$id])) {
            return false;
        }
        $rawConv = $this->items[$id]['conversation_id'] ?? '';
        $conversationId = is_string($rawConv) ? $rawConv : '';
        unset($this->items[$id]);
        if ($conversationId !== '' && isset($this->byConversation[$conversationId])) {
            $this->byConversation[$conversationId] = array_values(array_filter($this->byConversation[$conversationId], fn ($v) => $v !== $id));
            if ($this->byConversation[$conversationId] === []) {
                unset($this->byConversation[$conversationId]);
            }
        }
        return true;
    }
}
