<?php

declare(strict_types=1);

namespace CreativeCrafts\LaravelAiAssistant\Services\Storage;

use CreativeCrafts\LaravelAiAssistant\Contracts\Storage\ConversationsStoreContract;
use InvalidArgumentException;

final class InMemoryConversationsStore implements ConversationsStoreContract
{
    /** @var array<string,array<string,mixed>> */
    private array $conversations = [];

    public function put(array $conversation): void
    {
        $rawId = $conversation['id'] ?? '';
        $id = is_string($rawId) ? $rawId : '';
        if ($id === '') {
            throw new InvalidArgumentException('Conversation must include an id');
        }
        $this->conversations[$id] = $conversation;
    }

    public function get(string $id): ?array
    {
        return $this->conversations[$id] ?? null;
    }

    public function all(): array
    {
        return array_values($this->conversations);
    }

    public function delete(string $id): bool
    {
        if (!isset($this->conversations[$id])) {
            return false;
        }
        unset($this->conversations[$id]);
        return true;
    }
}
