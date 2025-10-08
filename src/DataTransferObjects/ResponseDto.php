<?php

declare(strict_types=1);

namespace CreativeCrafts\LaravelAiAssistant\DataTransferObjects;

final readonly class ResponseDto
{
    public function __construct(
        public string $id,
        public string $status,
        public ?string $text,
        public array $raw,
        public ?string $conversationId = null,
    ) {
    }

    public static function fromArray(array $data): self
    {
        $text = self::extractText($data);
        $convId = isset($data['conversationId']) ? (string)$data['conversationId'] : (isset($data['conversation']['id']) ? (string)$data['conversation']['id'] : null);
        return new self(
            id: (string)($data['id'] ?? ''),
            status: (string)($data['status'] ?? ($data['response']['status'] ?? 'unknown')),
            text: $text,
            raw: $data,
            conversationId: $convId,
        );
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'status' => $this->status,
            'text' => $this->text,
            'conversation_id' => $this->conversationId,
            'raw' => $this->raw,
        ];
    }

    private static function extractText(array $data): ?string
    {
        if (isset($data['output_text'])) {
            return (string)$data['output_text'];
        }
        if (isset($data['messages']) && is_string($data['messages'])) {
            return $data['messages'];
        }
        if (isset($data['content'])) {
            return (string)$data['content'];
        }
        return null;
    }
}
