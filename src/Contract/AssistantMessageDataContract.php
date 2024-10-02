<?php

namespace CreativeCrafts\LaravelAiAssistant\Contract;

interface AssistantMessageDataContract
{
    public function __construct(
        string $message,
        string $role = 'user',
    );

    public function toArray(): array;
}