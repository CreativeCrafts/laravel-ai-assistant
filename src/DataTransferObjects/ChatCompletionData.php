<?php

declare(strict_types=1);

namespace CreativeCrafts\LaravelAiAssistant\DataTransferObjects;

use CreativeCrafts\LaravelAiAssistant\Contracts\ChatCompletionDataContract;

final readonly class ChatCompletionData implements ChatCompletionDataContract
{
    public function __construct(
        protected string $model,
        protected array $message,
        protected ?float $temperature,
        protected ?bool $store,
        protected ?string $reasoningEffort,
        protected ?array $metadata,
        protected ?int $maxCompletionTokens,
        protected ?int $numberOfCompletionChoices,
        protected ?array $outputTypes = null,
        protected ?array $audio = null,
        protected array|string $responseFormat = 'auto',
        protected array|string|null $stopSequences = null,
        protected bool $stream = false,
        protected ?array $streamOptions = null,
        protected int|float|null $topP = null
    ) {
    }

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
    public function toArray(): array
    {
        return array_merge(
            [
                'model' => $this->model,
                'message' => $this->message,
                'temperature' => $this->temperature,
                'store' => $this->store,
                'reasoning_effort' => $this->reasoningEffort,
                'n' => $this->numberOfCompletionChoices,
                'response_formats' => $this->responseFormat,
                'stop' => $this->stopSequences,
                'stream' => $this->stream,
                'stream_options' => $this->streamOptions,
                'top_p' => $this->topP,
            ],
            $this->metadata !== null ? [
                'metadata' => $this->metadata,
            ] : [],
            $this->maxCompletionTokens !== null ? [
                'max_completion_tokens' => $this->maxCompletionTokens,
            ] : [],
            $this->outputTypes !== null ? [
                'modalities' => $this->outputTypes,
            ] : [],
            $this->audio !== null ? [
                'audio' => $this->audio,
            ] : [],
        );
    }

    /**
     * Determines if the chat completion should be streamed.
     *
     * This method checks the internal 'stream' property to decide
     * whether the chat completion response should be streamed or not.
     *
     * @return bool Returns true if streaming is enabled, false otherwise.
     */
    public function shouldStream(): bool
    {
        return $this->stream;
    }
}
