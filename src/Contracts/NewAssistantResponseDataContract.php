<?php

declare(strict_types=1);

namespace CreativeCrafts\LaravelAiAssistant\Contracts;

interface NewAssistantResponseDataContract
{
    public function __construct(mixed $assistant);

    public function assistantId(): string;

    public function assistant(): mixed;
}
