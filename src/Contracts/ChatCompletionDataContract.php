<?php

declare(strict_types=1);

namespace CreativeCrafts\LaravelAiAssistant\Contracts;

interface ChatCompletionDataContract
{
    public function __construct(
        string $model,
        array $message,
        ?float $temperature,
        ?bool $store,
        ?string $reasoningEffort,
        ?array $metadata,
        ?int $maxCompletionTokens,
        ?int $numberOfCompletionChoices,
        ?array $outputTypes = null,
        ?array $audio = null,
        array|string $responseFormat = 'auto',
        string|array|null $stopSequences = null,
        bool $stream = false,
        ?array $streamOptions = null,
        int|float|null $topP = null,
    );

    /**
     * Converts the ChatCompletionData object to an array representation.
     *
     * This method creates an array containing all the properties of the ChatCompletionData object.
     * It includes all non-null properties and conditionally adds metadata, maxCompletionTokens,
     * outputTypes, and audio if they are not null.
     *
     * @return array An associative array representation of the ChatCompletionData object, containing:
     *               - model: The AI model to be used
     *               - message: The input message for the chat completion
     *               - temperature: The sampling temperature to use
     *               - store: Whether to store the completion
     *               - reasoning_effort: The effort level for reasoning
     *               - number_of_completion_choices: The number of completion choices to generate
     *               - response_formats: The format of the response
     *               - stop_sequences: Sequences where the API will stop generating further tokens
     *               - stream: Whether to stream the response
     *               - stream_options: Options for streaming
     *               - top_p: The top p sampling parameter
     *               - metadata: Additional metadata (if provided)
     *               - max_completion_tokens: Maximum number of tokens to generate (if specified)
     *               - modalities: Output types or modalities (if specified)
     *               - audio: Audio-related data (if provided)
     */
    public function toArray(): array;

    /**
     * Determines if the chat completion should be streamed.
     *
     * This method checks the internal 'stream' property to decide
     * whether the chat completion response should be streamed or not.
     *
     * @return bool Returns true if streaming is enabled, false otherwise.
     */
    public function shouldStream(): bool;
}
