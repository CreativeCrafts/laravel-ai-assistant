<?php

declare(strict_types=1);

namespace CreativeCrafts\LaravelAiAssistant\DataTransferObjects;

use JsonSerializable;

final class ResponseEnvelope implements JsonSerializable
{
    /** @var array<string,mixed> */
    private array $raw;

    /**
     * @param array<string,mixed> $normalized
     */
    private function __construct(private readonly array $normalized)
    {
        $rawData = $normalized['raw'] ?? [];
        $this->raw = is_array($rawData) ? $rawData : [];
    }

    /**
     * @param array<string,mixed> $normalized
     */
    public static function fromArray(array $normalized): self
    {
        return new self($normalized);
    }

    public function id(): string
    {
        $value = $this->normalized['responseId'] ?? '';
        return is_scalar($value) ? (string)$value : '';
    }

    public function conversationId(): string
    {
        $value = $this->normalized['conversationId'] ?? '';
        return is_scalar($value) ? (string)$value : '';
    }

    public function contentText(): string
    {
        $value = $this->normalized['messages'] ?? '';
        return is_scalar($value) ? (string)$value : '';
    }

    /** @return array<int, array<string,mixed>> */
    public function blocks(): array
    {
        return is_array($this->normalized['messageBlocks'] ?? null) ? (array)$this->normalized['messageBlocks'] : [];
    }

    /** @return array<int, array{id?:string|null,name?:string|null,arguments?:mixed}> */
    public function toolCalls(): array
    {
        return is_array($this->normalized['toolCalls'] ?? null) ? (array)$this->normalized['toolCalls'] : [];
    }

    /** @return array<string,mixed> */
    public function usage(): array
    {
        return is_array($this->normalized['usage'] ?? null) ? (array)$this->normalized['usage'] : [];
    }

    public function status(): mixed
    {
        return $this->normalized['finishReason'] ?? null;
    }

    /** @return array<string,mixed> */
    public function raw(): array
    {
        return $this->raw;
    }

    /**
     * @return array{responseId:string,conversationId:string,messages:string,messageBlocks:array,toolCalls:array,usage:array,finishReason:mixed,raw:array}
     */
    public function toArray(): array
    {
        return [
            'responseId' => $this->id(),
            'conversationId' => $this->conversationId(),
            'messages' => $this->contentText(),
            'messageBlocks' => $this->blocks(),
            'toolCalls' => $this->toolCalls(),
            'usage' => $this->usage(),
            'finishReason' => $this->status(),
            'raw' => $this->raw(),
        ];
    }

    public function jsonSerialize(): array
    {
        return $this->toArray();
    }
}
