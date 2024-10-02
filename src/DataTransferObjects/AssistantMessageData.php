<?php

namespace CreativeCrafts\LaravelAiAssistant\DataTransferObjects;

use CreativeCrafts\LaravelAiAssistant\Contract\AssistantMessageDataContract;

final readonly class AssistantMessageData implements AssistantMessageDataContract
{

    public function __construct(
        protected string $message,
        protected string $role = 'user',
    ) {
    }

    public function toArray(): array
    {
        return [
            'role' => $this->role,
            'content' => $this->message,
        ];
    }
}