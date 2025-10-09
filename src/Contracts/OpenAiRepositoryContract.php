<?php

declare(strict_types=1);

namespace CreativeCrafts\LaravelAiAssistant\Contracts;

use CreativeCrafts\LaravelAiAssistant\Compat\OpenAI\Responses\Audio\TranscriptionResponse;
use CreativeCrafts\LaravelAiAssistant\Compat\OpenAI\Responses\Audio\TranslationResponse;
use CreativeCrafts\LaravelAiAssistant\Compat\OpenAI\Responses\Chat\CreateResponse as ChatResponse;
use CreativeCrafts\LaravelAiAssistant\Compat\OpenAI\Responses\Completions\CreateResponse as CompletionResponse;

/**
 * Repository contract for OpenAI API operations.
 *
 * This interface abstracts the OpenAI API client interactions,
 * providing a clean abstraction layer for API operations.
 */
interface OpenAiRepositoryContract
{
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
