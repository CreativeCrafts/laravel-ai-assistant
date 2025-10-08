<?php

declare(strict_types=1);

namespace CreativeCrafts\LaravelAiAssistant\DataTransferObjects;

final readonly class ConversationItemDto
{
    public function __construct(
        public string $id,
        public ?string $type,
        public ?string $role,
        public mixed $content,
        public array $raw,
    ) {
    }

    public static function fromArray(array $data): self
    {
        $content = $data['content'] ?? ($data['message']['content'] ?? null);
        return new self(
            id: (string)($data['id'] ?? ''),
            type: isset($data['type']) ? (string)$data['type'] : null,
            role: isset($data['role']) ? (string)$data['role'] : null,
            content: $content,
            raw: $data,
        );
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'type' => $this->type,
            'role' => $this->role,
            'content' => $this->content,
            'raw' => $this->raw,
        ];
    }
}
