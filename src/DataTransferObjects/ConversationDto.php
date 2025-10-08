<?php

declare(strict_types=1);

namespace CreativeCrafts\LaravelAiAssistant\DataTransferObjects;

final readonly class ConversationDto
{
    public function __construct(
        public string $id,
        public array $raw,
    ) {
    }

    public static function fromArray(array $data): self
    {
        return new self(
            id: (string)($data['id'] ?? ''),
            raw: $data,
        );
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'raw' => $this->raw,
        ];
    }
}
