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

    /**
     * Convert the FunctionCallData object to an array representation.
     *
     * This method transforms the object's properties into an associative array,
     * which can be used for serialization or data transfer purposes.
     *
     * @return array An associative array containing the following keys:
     *               - 'name': The name of the function (string)
     *               - 'description': The description of the function (string)
     *               - 'parameters': The function parameters, either as an array or converted to array (array)
     *               - 'strict': Whether the function call is strict or not (boolean)
     *               - 'required': An array of required parameters (array)
     *               - 'additionalProperties': Whether additional properties are allowed (boolean)
     */
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
