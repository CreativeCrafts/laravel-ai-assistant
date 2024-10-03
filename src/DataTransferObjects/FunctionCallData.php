<?php

declare(strict_types=1);

namespace CreativeCrafts\LaravelAiAssistant\DataTransferObjects;

use CreativeCrafts\LaravelAiAssistant\Contracts\FunctionCallDataContract;
use CreativeCrafts\LaravelAiAssistant\Contracts\FunctionCallParameterContract;

final readonly class FunctionCallData implements FunctionCallDataContract
{
    public function __construct(
        protected string $functionName,
        protected string $functionDescription = '',
        protected FunctionCallParameterContract|array $parameters = [],
        protected bool $isStrict = false,
        protected array $requiredParameters = [],
        protected bool $hasAdditionalProperties = false
    ) {
    }

    public function toArray(): array
    {
        return [
            'name' => $this->functionName,
            'description' => $this->functionDescription,
            'parameters' => is_array($this->parameters) ? $this->parameters : $this->parameters->toArray(),
            'strict' => $this->isStrict,
            'required' => $this->requiredParameters,
            'additionalProperties' => $this->hasAdditionalProperties,
        ];
    }
}
