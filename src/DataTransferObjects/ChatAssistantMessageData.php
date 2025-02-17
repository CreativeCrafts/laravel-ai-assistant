<?php

declare(strict_types=1);

namespace CreativeCrafts\LaravelAiAssistant\DataTransferObjects;

use CreativeCrafts\LaravelAiAssistant\Contracts\ChatAssistantMessageDataContract;

final readonly class ChatAssistantMessageData implements ChatAssistantMessageDataContract
{
    public function __construct(
        protected string $role,
        protected array|string|null $content = null,
        protected ?string $refusal = null,
        protected ?string $name = null,
        protected ?array $audio = null,
        protected ?array $toolCalls = null
    ) {
    }

    /**
     * Convert the ChatAssistantMessageData object to an array representation.
     *
     * This method creates an array containing the properties of the ChatAssistantMessageData object.
     * It includes 'role', 'refusal', and 'audio' by default, and conditionally adds 'content', 'name',
     * and 'toolCalls' if they are not null.
     *
     * @return array An associative array representing the ChatAssistantMessageData object with the following structure:
     *               - 'role' (string): The role of the chat assistant message.
     *               - 'refusal' (string|null): Any refusal message, if applicable.
     *               - 'audio' (array|null): Audio data, if available.
     *               - 'content' (array|string|null): The content of the message, if not null.
     *               - 'name' (string|null): The name associated with the message, if not null.
     *               - 'toolCalls' (array|null): Any tool calls associated with the message, if not null.
     */
    public function toArray(): array
    {
        return array_merge(
            [
                'role' => $this->role,
                'refusal' => $this->refusal,
                'audio' => $this->audio,
            ],
            $this->content !== null ? [
                'content' => $this->content,
            ] : [],
            $this->name !== null ? [
                'name' => $this->name,
            ] : [],
            $this->toolCalls !== null ? [
                'toolCalls' => $this->toolCalls,
            ] : []
        );
    }
}
