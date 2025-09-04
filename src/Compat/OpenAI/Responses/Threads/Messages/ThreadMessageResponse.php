<?php

declare(strict_types=1);

namespace CreativeCrafts\LaravelAiAssistant\Compat\OpenAI\Responses\Threads\Messages;

use CreativeCrafts\LaravelAiAssistant\Compat\OpenAI\Responses\Meta\MetaInformation;

final class ThreadMessageResponse
{
    public string $id;
    public string $object;
    public int $created_at;
    public string $thread_id;
    public string $role;
    /** @var array<int,mixed> */
    public array $content = [];
    public ?string $assistant_id = null;
    public ?string $run_id = null;
    /** @var array<int,mixed> */
    public array $attachments = [];
    /** @var array<string,mixed> */
    public array $metadata = [];

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
        $self->object = is_string($attributes['object'] ?? null) ? $attributes['object'] : 'thread.message';
        $self->created_at = is_int($attributes['created_at'] ?? null) ? $attributes['created_at'] : time();
        $self->thread_id = is_string($attributes['thread_id'] ?? null) ? $attributes['thread_id'] : '';
        $self->role = is_string($attributes['role'] ?? null) ? $attributes['role'] : 'user';
        $self->content = is_array($attributes['content'] ?? null) ? $attributes['content'] : [];
        $self->assistant_id = isset($attributes['assistant_id']) && is_string($attributes['assistant_id']) ? $attributes['assistant_id'] : null;
        $self->run_id = isset($attributes['run_id']) && is_string($attributes['run_id']) ? $attributes['run_id'] : null;
        $self->attachments = is_array($attributes['attachments'] ?? null) ? $attributes['attachments'] : [];
        $self->metadata = is_array($attributes['metadata'] ?? null) ? $attributes['metadata'] : [];
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
            'thread_id' => $this->thread_id,
            'role' => $this->role,
            'content' => $this->content,
            'assistant_id' => $this->assistant_id,
            'run_id' => $this->run_id,
            'attachments' => $this->attachments,
            'metadata' => $this->metadata,
        ];
    }
}
