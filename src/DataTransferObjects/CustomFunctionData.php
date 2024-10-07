<?php

declare(strict_types=1);

namespace CreativeCrafts\LaravelAiAssistant\DataTransferObjects;

use CreativeCrafts\LaravelAiAssistant\Contracts\CustomFunctionDataContract;

final class CustomFunctionData implements CustomFunctionDataContract
{
    public function __construct(
        protected string $name,
        protected string $description,
        protected array $parameters = [
            'type' => 'object',
            'properties' => [],
            'required' => [],
        ],
    ) {
    }

    public function toArray(): array
    {
        return [
            'name' => $this->name,
            'description' => $this->description,
            'parameters' => $this->parameters,
        ];
    }
}
