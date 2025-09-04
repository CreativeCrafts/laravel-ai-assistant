<?php

declare(strict_types=1);

namespace CreativeCrafts\LaravelAiAssistant\Compat\OpenAI\Responses\Threads;

use CreativeCrafts\LaravelAiAssistant\Compat\OpenAI\Responses\Meta\MetaInformation;

final class ThreadResponse
{
    public string $id;
    public string $object;
    public int $created_at;
    /** @var array<string,mixed>|null */
    public ?array $tool_resources = null;
    /** @var array<string,mixed>|null */
    public ?array $metadata = null;

    private function __construct()
    {
    }

    /**
     * @param array<string,mixed> $attributes
     */
    public static function from(array $attributes, MetaInformation $meta): self
    {
        $self = new self();
        $self->id = is_string($attributes['id'] ?? null) ? $attributes['id'] : '';
        $self->object = is_string($attributes['object'] ?? null) ? $attributes['object'] : 'thread';
        $self->created_at = is_int($attributes['created_at'] ?? null) ? $attributes['created_at'] : time();
        $self->tool_resources = isset($attributes['tool_resources']) && is_array($attributes['tool_resources']) ? $attributes['tool_resources'] : null;
        $self->metadata = isset($attributes['metadata']) && is_array($attributes['metadata']) ? $attributes['metadata'] : null;
        return $self;
    }

    /**
     * @return array<string,mixed>
     */
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'object' => $this->object,
            'created_at' => $this->created_at,
            'tool_resources' => $this->tool_resources,
            'metadata' => $this->metadata,
        ];
    }
}
