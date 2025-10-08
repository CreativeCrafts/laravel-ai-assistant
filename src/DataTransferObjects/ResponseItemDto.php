<?php

declare(strict_types=1);

namespace CreativeCrafts\LaravelAiAssistant\DataTransferObjects;

final readonly class ResponseItemDto
{
    public function __construct(
        public string $id,
        public ?string $type,
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
            content: $content,
            raw: $data,
        );
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'type' => $this->type,
            'content' => $this->content,
            'raw' => $this->raw,
        ];
    }
}
