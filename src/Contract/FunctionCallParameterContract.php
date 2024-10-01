<?php

declare(strict_types=1);

namespace CreativeCrafts\LaravelAiAssistant\Contract;

interface FunctionCallParameterContract
{
    public function __construct(
        string $type,
        array $properties = [],
    );

    public function toArray(): array;
}
