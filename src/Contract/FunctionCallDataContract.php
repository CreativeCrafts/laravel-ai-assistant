<?php

declare(strict_types=1);

namespace CreativeCrafts\LaravelAiAssistant\Contract;

interface FunctionCallDataContract
{
    public function __construct(
        string $functionName,
        string $functionDescription = '',
        FunctionCallParameterContract|array $parameters = [],
        bool $isStrict = false,
        array $requiredParameters = [],
        bool $hasAdditionalProperties = false,
    );

    public function toArray(): array;
}
