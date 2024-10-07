<?php

declare(strict_types=1);

namespace CreativeCrafts\LaravelAiAssistant\Contracts;

interface FunctionCallParameterContract
{
    public function __construct(
        string $type,
        array $properties = [],
    );

    public function toArray(): array;
}
