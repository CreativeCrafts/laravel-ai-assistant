<?php

declare(strict_types=1);

namespace CreativeCrafts\LaravelAiAssistant\Services\Storage;

use CreativeCrafts\LaravelAiAssistant\Contracts\Storage\AssistantsStoreContract;
use InvalidArgumentException;

final class InMemoryAssistantsStore implements AssistantsStoreContract
{
    /** @var array<string,array<string,mixed>> */
    private array $assistants = [];

    public function put(array $assistant): void
    {
        $rawId = $assistant['id'] ?? '';
        $id = is_string($rawId) ? $rawId : '';
        if ($id === '') {
            throw new InvalidArgumentException('Assistant must include an id');
        }
        $this->assistants[$id] = $assistant;
    }

    public function get(string $id): ?array
    {
        return $this->assistants[$id] ?? null;
    }

    public function all(): array
    {
        return array_values($this->assistants);
    }

    public function delete(string $id): bool
    {
        if (!isset($this->assistants[$id])) {
            return false;
        }
        unset($this->assistants[$id]);
        return true;
    }
}
