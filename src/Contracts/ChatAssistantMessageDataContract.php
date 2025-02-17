<?php

declare(strict_types=1);

namespace CreativeCrafts\LaravelAiAssistant\Contracts;

interface ChatAssistantMessageDataContract
{
    public function __construct(
        string $role,
        string|array|null $content = null,
        ?string $refusal = null,
        ?string $name = null,
        ?array $audio = null,
        ?array $toolCalls = null,
    );

    public function toArray(): array;
}
