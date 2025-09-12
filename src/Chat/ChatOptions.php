<?php

declare(strict_types=1);

namespace CreativeCrafts\LaravelAiAssistant\Chat;

final class ChatOptions
{
    public function __construct(
        public ?string $model = null,
        public ?float $temperature = null,
        public ?float $topP = null,
        /** @var array<string,mixed>|null */
        public ?array $responseFormat = null,
        /** @var string|array|null 'auto'|'required'|'none'|['type'=>'function','name'=>'...'] */
        public string|array|null $toolChoice = null,
        /** @var string[] */
        public array $fileIds = [],
        /** @var string[] */
        public array $vectorStoreIds = [],
        /** @var array<string,mixed> */
        public array $metadata = [],
        public ?string $idempotencyKey = null,
        public ?int $timeoutSeconds = null,
    ) {
    }

    public static function make(): self
    {
        return new self();
    }

    public function withModel(?string $model): self
    {
        $this->model = $model;
        return $this;
    }

    public function withTemperature(?float $t): self
    {
        $this->temperature = $t;
        return $this;
    }

    public function withTopP(?float $v): self
    {
        $this->topP = $v;
        return $this;
    }

    /** @param array<string,mixed>|null $fmt */
    public function withResponseFormat(?array $fmt): self
    {
        $this->responseFormat = $fmt;
        return $this;
    }

    public function withToolChoice(string|array|null $choice): self
    {
        $this->toolChoice = $choice;
        return $this;
    }

    /** @param string[] $ids */
    public function withFileIds(array $ids): self
    {
        $this->fileIds = $ids;
        return $this;
    }

    /** @param string[] $ids */
    public function withVectorStoreIds(array $ids): self
    {
        $this->vectorStoreIds = $ids;
        return $this;
    }

    /** @param array<string,mixed> $meta */
    public function withMetadata(array $meta): self
    {
        $this->metadata = $meta;
        return $this;
    }

    public function withIdempotencyKey(?string $key): self
    {
        $this->idempotencyKey = $key;
        return $this;
    }

    public function withTimeoutSeconds(?int $seconds): self
    {
        $this->timeoutSeconds = $seconds;
        return $this;
    }

    /** @return array<string,mixed> */
    public function toArray(): array
    {
        return array_filter([
            'model' => $this->model,
            'temperature' => $this->temperature,
            'top_p' => $this->topP,
            'response_format' => $this->responseFormat,
            'tool_choice' => $this->toolChoice,
            'file_ids' => $this->fileIds !== [] ? $this->fileIds : null,
            'vector_store_ids' => $this->vectorStoreIds !== [] ? $this->vectorStoreIds : null,
            'metadata' => $this->metadata !== [] ? $this->metadata : null,
            'idempotency_key' => $this->idempotencyKey,
            'timeout_seconds' => $this->timeoutSeconds,
        ], static fn ($v) => $v !== null);
    }
}
