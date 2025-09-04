<?php

declare(strict_types=1);

namespace CreativeCrafts\LaravelAiAssistant\Contracts;

/**
 * Contract for text completion operations.
 *
 * This interface defines methods for generating text completions
 * and chat completions using the OpenAI API.
 */
interface TextCompletionContract
{
    /**
     * Generate text completion using the OpenAI API.
     *
     * This function sends a request to the OpenAI API for text completion
     * and returns the generated text.
     *
     * @param array $payload An array containing the necessary parameters for text completion.
     *                       This typically includes:
     *                       - 'model': The ID of the model to use for completion
     *                       - 'prompt': The prompt to generate completions for
     *                       - 'max_tokens': The maximum number of tokens to generate
     *                       - 'temperature': Controls randomness in the output
     *                       - Other optional parameters as per OpenAI API documentation
     *
     * @return string The generated text completion. Returns an empty string if no choices are returned.
     */
    public function textCompletion(array $payload): string;

    /**
     * Generate a streamed text completion using the OpenAI API.
     *
     * This function sends a request to the OpenAI API for streamed text completion
     * and returns the first generated text chunk.
     *
     * @param array $payload An array containing the necessary parameters for text completion.
     *                       This typically includes:
     *                       - 'model': The ID of the model to use for completion
     *                       - 'prompt': The prompt to generate completions for
     *                       - 'max_tokens': The maximum number of tokens to generate
     *                       - 'temperature': Controls randomness in the output
     *                       - Other optional parameters as per OpenAI API documentation
     *
     * @return string The first chunk of generated text completion. Returns an empty string if no choices are returned.
     */
    public function streamedCompletion(array $payload): string;

    /**
     * Generate a chat text completion using the OpenAI API.
     *
     * This function sends a request to the OpenAI API for chat text completion
     * and returns the message from the first choice in the response.
     *
     * @param array $payload An array containing the necessary parameters for chat completion.
     *                       This typically includes:
     *                       - 'model': The ID of the model to use for chat completion
     *                       - 'messages': An array of message objects representing the conversation history
     *                       - Other optional parameters as per OpenAI API documentation
     *
     * @return array An array representing the message from the first choice in the API response.
     *               Returns an empty array if no choices are returned.
     */
    public function chatTextCompletion(array $payload): array;

    /**
     * Generate a streamed chat completion using the OpenAI API.
     *
     * This function sends a request to the OpenAI API for streamed chat completion
     * and returns the first generated response chunk.
     *
     * @param array $payload An array containing the necessary parameters for chat completion.
     *                       This typically includes:
     *                       - 'model': The ID of the model to use for chat completion
     *                       - 'messages': An array of message objects representing the conversation history
     *                       - Other optional parameters as per OpenAI API documentation
     *
     * @return array An array representing the first chunk of the generated response.
     *               Returns an empty array if no choices are returned in the stream.
     */
    public function streamedChat(array $payload): array;
}
