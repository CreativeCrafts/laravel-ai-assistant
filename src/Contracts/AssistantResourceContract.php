<?php

declare(strict_types=1);

namespace CreativeCrafts\LaravelAiAssistant\Contracts;

use CreativeCrafts\LaravelAiAssistant\Compat\OpenAI\Client;
use CreativeCrafts\LaravelAiAssistant\Compat\OpenAI\Responses\Assistants\AssistantResponse;
use CreativeCrafts\LaravelAiAssistant\Compat\OpenAI\Responses\Threads\Messages\ThreadMessageResponse;
use CreativeCrafts\LaravelAiAssistant\Compat\OpenAI\Responses\Threads\ThreadResponse;

interface AssistantResourceContract
{
    public function __construct(?Client $client = null);

    /**
     * Create a new assistant with the specified parameters.
     *
     * This function creates a new assistant using the OpenAI API client.
     *
     * @param array $parameters An array of parameters for creating the assistant.
     *                          This may include properties such as name, instructions,
     *                          tools, and model.
     *
     * @return AssistantResponse The response object containing details of the created assistant.
     */
    public function createAssistant(array $parameters): AssistantResponse;

    /**
     * Retrieve an assistant by its ID.
     *
     * This function fetches the details of a specific assistant using the OpenAI API client.
     *
     * @param string $assistantId The unique identifier of the assistant to retrieve.
     *
     * @return AssistantResponse The response object containing details of the retrieved assistant.
     */
    public function getAssistantViaId(string $assistantId): AssistantResponse;

    /**
     * Create a new thread with the specified parameters.
     *
     * This function creates a new thread using the OpenAI API client.
     *
     * @param array $parameters An array of parameters for creating the thread.
     *                          This may include properties such as messages or metadata.
     *
     * @return ThreadResponse The response object containing details of the created thread.
     */
    public function createThread(array $parameters): ThreadResponse;

    /**
     * Write a new message to a specific thread.
     *
     * This function creates a new message within an existing thread using the OpenAI API client.
     *
     * @param string $threadId    The unique identifier of the thread to which the message will be added.
     * @param array  $messageData An array containing the message data. This may include properties such as
     *                            'role' (e.g., 'user' or 'assistant') and 'content' (the message text).
     *
     * @return ThreadMessageResponse The response object containing details of the created message.
     */
    public function writeMessage(string $threadId, array $messageData): ThreadMessageResponse;

    /**
     * Run a message thread and wait for its completion.
     *
     * This function creates a new run for a specified thread, then continuously checks
     * the run's status until it is completed. It uses a polling mechanism with a 1-second
     * interval between status checks.
     *
     * @param string $threadId           The unique identifier of the thread to run.
     * @param array  $runThreadParameter An array of parameters for running the thread.
     *                                   This may include properties such as assistant_id,
     *                                   model, instructions, or tools.
     *
     * @return bool Returns true when the run is completed successfully.
     */
    public function runMessageThread(string $threadId, array $runThreadParameter): bool;

    /**
     * List messages for a specific thread and return the content of the first message.
     *
     * This function retrieves all messages for a given thread using the OpenAI API client,
     * and returns the text content of the first message in the list.
     *
     * @param string $threadId The unique identifier of the thread whose messages are to be listed.
     *
     * @return string The text content of the first message in the thread, or an empty string if no messages are found.
     */
    public function listMessages(string $threadId): string;

    /**
     * Transcribe audio to text using the OpenAI API.
     *
     * This function sends an audio file to the OpenAI API for transcription
     * and returns the transcribed text.
     *
     * @param array $payload An array containing the necessary information for transcription.
     *                       This typically includes:
     *                       - 'file': The audio file to be transcribed (required)
     *                       - 'model': The model to use for transcription (optional)
     *                       - 'prompt': An optional text to guide the model's style or continue a previous audio segment
     *                       - 'response_format': The format of the transcript output (optional)
     *                       - 'temperature': The sampling temperature to use (optional)
     *                       - 'language': The language of the input audio (optional)
     *
     * @return string The transcribed text from the audio file.
     */
    public function transcribeTo(array $payload): string;

    /**
     * Translate audio to text using the OpenAI API.
     *
     * This function sends an audio file to the OpenAI API for translation
     * and returns the translated text.
     *
     * @param array $payload An array containing the necessary information for translation.
     *                       This typically includes:
     *                       - 'file': The audio file to be translated (required)
     *                       - 'model': The model to use for translation (optional)
     *                       - 'prompt': An optional text to guide the model's style or continue a previous audio segment
     *                       - 'response_format': The format of the transcript output (optional)
     *                       - 'temperature': The sampling temperature to use (optional)
     *
     * @return string The translated text from the audio file.
     */
    public function translateTo(array $payload): string;

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
