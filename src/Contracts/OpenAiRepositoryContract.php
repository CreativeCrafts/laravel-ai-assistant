<?php

declare(strict_types=1);

namespace CreativeCrafts\LaravelAiAssistant\Contracts;

use CreativeCrafts\LaravelAiAssistant\Compat\OpenAI\Responses\Assistants\AssistantResponse;
use CreativeCrafts\LaravelAiAssistant\Compat\OpenAI\Responses\Audio\TranscriptionResponse;
use CreativeCrafts\LaravelAiAssistant\Compat\OpenAI\Responses\Audio\TranslationResponse;
use CreativeCrafts\LaravelAiAssistant\Compat\OpenAI\Responses\Chat\CreateResponse as ChatResponse;
use CreativeCrafts\LaravelAiAssistant\Compat\OpenAI\Responses\Completions\CreateResponse as CompletionResponse;
use CreativeCrafts\LaravelAiAssistant\Compat\OpenAI\Responses\Threads\Messages\ThreadMessageResponse;
use CreativeCrafts\LaravelAiAssistant\Compat\OpenAI\Responses\Threads\Runs\ThreadRunResponse;
use CreativeCrafts\LaravelAiAssistant\Compat\OpenAI\Responses\Threads\ThreadResponse;

/**
 * Repository contract for OpenAI API operations.
 *
 * This interface abstracts the OpenAI API client interactions,
 * providing a clean abstraction layer for API operations.
 */
interface OpenAiRepositoryContract
{
    /**
     * Create a new assistant.
     *
     * @deprecated Assistants API is deprecated. Use Responses + Conversations via OpenAIClientFacade instead. See docs/Migrate_from_AssistantAPI_to_ResponseAPI.md
     * @param array $parameters Parameters for creating the assistant
     * @return AssistantResponse
     */
    public function createAssistant(array $parameters): AssistantResponse;

    /**
     * Retrieve an assistant by ID.
     *
     * @deprecated Assistants API is deprecated. Use Responses + Conversations via OpenAIClientFacade instead. See docs/Migrate_from_AssistantAPI_to_ResponseAPI.md
     * @param string $assistantId The assistant ID
     * @return AssistantResponse
     */
    public function retrieveAssistant(string $assistantId): AssistantResponse;

    /**
     * Create a new thread.
     *
     * @deprecated Threads API is deprecated. Use Conversations via OpenAIClientFacade instead. See docs/Migrate_from_AssistantAPI_to_ResponseAPI.md
     * @param array $parameters Parameters for creating the thread
     * @return ThreadResponse
     */
    public function createThread(array $parameters): ThreadResponse;

    /**
     * Create a message in a thread.
     *
     * @deprecated Threads/Messages API is deprecated. Use Conversations items via OpenAIClientFacade instead. See docs/Migrate_from_AssistantAPI_to_ResponseAPI.md
     * @param string $threadId The thread ID
     * @param array $messageData The message data
     * @return ThreadMessageResponse
     */
    public function createThreadMessage(string $threadId, array $messageData): ThreadMessageResponse;

    /**
     * Create a run for a thread.
     *
     * @deprecated Runs API is deprecated. Use Responses via OpenAIClientFacade instead. See docs/Migrate_from_AssistantAPI_to_ResponseAPI.md
     * @param string $threadId The thread ID
     * @param array $parameters Run parameters
     * @return ThreadRunResponse
     */
    public function createThreadRun(string $threadId, array $parameters): ThreadRunResponse;

    /**
     * Retrieve a thread run.
     *
     * @deprecated Runs API is deprecated. Use Responses via OpenAIClientFacade instead. See docs/Migrate_from_AssistantAPI_to_ResponseAPI.md
     * @param string $threadId The thread ID
     * @param string $runId The run ID
     * @return ThreadRunResponse
     */
    public function retrieveThreadRun(string $threadId, string $runId): ThreadRunResponse;

    /**
     * List messages in a thread.
     *
     * @deprecated Threads/Messages API is deprecated. Use Conversations items via OpenAIClientFacade instead. See docs/Migrate_from_AssistantAPI_to_ResponseAPI.md
     * @param string $threadId The thread ID
     * @return array
     */
    public function listThreadMessages(string $threadId): array;

    /**
     * Create a text completion.
     *
     * @param array $parameters Completion parameters
     * @return CompletionResponse
     */
    public function createCompletion(array $parameters): CompletionResponse;

    /**
     * Create a streamed text completion.
     *
     * @param array $parameters Completion parameters
     * @return iterable
     */
    public function createStreamedCompletion(array $parameters): iterable;

    /**
     * Create a chat completion.
     *
     * @param array $parameters Chat completion parameters
     * @return ChatResponse
     */
    public function createChatCompletion(array $parameters): ChatResponse;

    /**
     * Create a streamed chat completion.
     *
     * @param array $parameters Chat completion parameters
     * @return iterable
     */
    public function createStreamedChatCompletion(array $parameters): iterable;

    /**
     * Transcribe audio to text.
     *
     * @param array $parameters Transcription parameters
     * @return TranscriptionResponse
     */
    public function transcribeAudio(array $parameters): TranscriptionResponse;

    /**
     * Translate audio to text.
     *
     * @param array $parameters Translation parameters
     * @return TranslationResponse
     */
    public function translateAudio(array $parameters): TranslationResponse;
}
