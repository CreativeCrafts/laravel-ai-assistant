<?php

declare(strict_types=1);

namespace CreativeCrafts\LaravelAiAssistant\DataTransferObjects;

use JsonSerializable;

final readonly class StreamingEventDto implements JsonSerializable
{
    public function __construct(
        public string $type,
        public array $data,
        public bool $isFinal = false,
    ) {
    }

    public static function fromArray(array $evt): self
    {
        return new self(
            type: (string)($evt['type'] ?? ''),
            data: (array)($evt['data'] ?? []),
            isFinal: (bool)($evt['isFinal'] ?? false),
        );
    }

    public function jsonSerialize(): array
    {
        return [
            'type' => $this->type,
            'data' => $this->data,
            'isFinal' => $this->isFinal,
        ];
    }

    /**
     * Convert the DTO to a plain array.
     */
    public function toArray(): array
    {
        return [
            'type' => $this->type,
            'data' => $this->data,
            'isFinal' => $this->isFinal,
        ];
    }
}
