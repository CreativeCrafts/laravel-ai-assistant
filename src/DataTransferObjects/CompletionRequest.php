<?php

declare(strict_types=1);

namespace CreativeCrafts\LaravelAiAssistant\DataTransferObjects;

final readonly class CompletionRequest
{
    public function __construct(
        public array $payload
    ) {
    }

    public static function fromArray(array $payload): self
    {
        return new self($payload);
    }

    public function model(): ?string
    {
        return isset($this->payload['model']) ? (string)$this->payload['model'] : null;
    }

    public function prompt(): ?string
    {
        return isset($this->payload['prompt']) ? (string)$this->payload['prompt'] : null;
    }

    public function messages(): ?array
    {
        return isset($this->payload['messages']) && is_array($this->payload['messages']) ? $this->payload['messages'] : null;
    }

    public function toArray(): array
    {
        return $this->payload;
    }
}
