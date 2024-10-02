<?php

declare(strict_types=1);

namespace CreativeCrafts\LaravelAiAssistant\Contract;

interface AssistantMessageDataContract
{
    public function __construct(
        string $message,
        string $role = 'user',
    );

    public function toArray(): array;
}
