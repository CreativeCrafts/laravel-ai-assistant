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

    /**
     * Convert the CustomFunctionData object to an array representation.
     *
     * This method transforms the object's properties into an associative array,
     * which can be used for various purposes such as API responses or data serialization.
     *
     * @return array An associative array containing the following keys:
     *               - 'name': The name of the custom function (string)
     *               - 'description': The description of the custom function (string)
     *               - 'parameters': An array of parameters for the custom function (array)
     */
    public function toArray(): array
    {
        return [
            'name' => $this->name,
            'description' => $this->description,
            'parameters' => $this->parameters,
        ];
    }
}
