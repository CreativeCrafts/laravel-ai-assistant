<?php

declare(strict_types=1);

namespace CreativeCrafts\LaravelAiAssistant\DataTransferObjects;

use CreativeCrafts\LaravelAiAssistant\Contracts\CreateAssistantDataContract;

final readonly class CreateAssistantData implements CreateAssistantDataContract
{
    public function __construct(
        protected string $model,
        protected float|int|null $topP = null,
        protected ?float $temperature = null,
        protected ?string $assistantDescription = null,
        protected ?string $assistantName = null,
        protected ?string $instructions = null,
        protected ?string $reasoningEffort = null,
        protected ?array $tools = null,
        protected ?array $toolResources = null,
        protected ?array $metadata = null,
        protected array|string $responseFormat = 'auto'
    ) {
    }

    /**
     * Convert the CreateAssistantData object to an array.
     *
     * This method transforms the object's properties into an associative array,
     * which can be used for API requests or other purposes that require a plain array structure.
     *
     * @return array An associative array containing the assistant's configuration:
     *               - 'model': The AI model to be used (string)
     *               - 'top_p': The top P sampling parameter (float|null)
     *               - 'temperature': The temperature parameter for response generation (float|null)
     *               - 'description': A description of the assistant (string|null)
     *               - 'name': The name of the assistant (string|null)
     *               - 'instructions': Instructions for the assistant (string|null)
     *               - 'reasoning_effort': The level of reasoning effort (string|null)
     *               - 'tools': Array of tools available to the assistant (array|null)
     *               - 'tool_resources': Resources for the tools (array|null)
     *               - 'response_format': The format for the assistant's responses (string|array)
     *               - 'metadata': Additional metadata for the assistant (array|null), if provided
     */
    public function toArray(): array
    {
        return array_merge(
            [
                'model' => $this->model,
                'top_p' => $this->topP,
                'temperature' => $this->temperature,
                'description' => $this->assistantDescription,
                'name' => $this->assistantName,
                'instructions' => $this->instructions,
                'tools' => $this->tools,
                'tool_resources' => $this->toolResources,
                'response_format' => $this->responseFormat,
            ],
            $this->metadata !== null ? [
                'metadata' => $this->metadata,
            ] : [],
            $this->reasoningEffort !== null && $this->reasoningEffort !== '' ? [
                'reasoning_effort' => $this->reasoningEffort,
            ] : []
        );
    }
}
