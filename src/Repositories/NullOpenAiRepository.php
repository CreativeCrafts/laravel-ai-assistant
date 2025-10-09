<?php

declare(strict_types=1);

namespace CreativeCrafts\LaravelAiAssistant\Repositories;

use CreativeCrafts\LaravelAiAssistant\Compat\OpenAI\Responses\Audio\TranscriptionResponse;
use CreativeCrafts\LaravelAiAssistant\Compat\OpenAI\Responses\Audio\TranslationResponse;
use CreativeCrafts\LaravelAiAssistant\Compat\OpenAI\Responses\Chat\CreateResponse as ChatResponse;
use CreativeCrafts\LaravelAiAssistant\Compat\OpenAI\Responses\Completions\CreateResponse as CompletionResponse;
use CreativeCrafts\LaravelAiAssistant\Contracts\OpenAiRepositoryContract;

/**
 * Null implementation of OpenAiRepositoryContract used when mock_responses=true.
 * Returns deterministic, in-memory DTOs without performing any network I/O.
 */
final class NullOpenAiRepository implements OpenAiRepositoryContract
{
    public function listThreadMessages(string $threadId): array
    {
        return [];
    }

    public function createCompletion(array $parameters): CompletionResponse
    {
        $res = new CompletionResponse();
        $text = $parameters['prompt'] ?? 'null mock completion';
        $res->choices = [(object) ['text' => (string) $text]];
        return $res;
    }

    public function createStreamedCompletion(array $parameters): iterable
    {
        // Return an empty iterable for simplicity
        return [];
    }

    public function createChatCompletion(array $parameters): ChatResponse
    {
        $res = new ChatResponse();
        $content = $parameters['messages'][0]['content'] ?? 'null mock chat';
        $res->choices = [
            (object) [
                'message' => (object) [
                    'role' => 'assistant',
                    'content' => (string) $content,
                ],
            ],
        ];
        return $res;
    }

    public function createStreamedChatCompletion(array $parameters): iterable
    {
        // Return an empty iterable for simplicity
        return [];
    }

    public function transcribeAudio(array $parameters): TranscriptionResponse
    {
        $res = new TranscriptionResponse();
        $res->text = 'mock transcription';
        return $res;
    }

    public function translateAudio(array $parameters): TranslationResponse
    {
        $res = new TranslationResponse();
        $res->text = 'mock translation';
        return $res;
    }
}
