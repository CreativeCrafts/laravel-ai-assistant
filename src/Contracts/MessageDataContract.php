<?php

declare(strict_types=1);

namespace CreativeCrafts\LaravelAiAssistant\Contracts;

interface MessageDataContract
{
    public function __construct(
        string $message,
        string $role = 'user',
        string $toolCallId = '',
    );

    public function toArray(): array;
}
