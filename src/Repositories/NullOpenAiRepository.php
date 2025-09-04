<?php

declare(strict_types=1);

namespace CreativeCrafts\LaravelAiAssistant\Repositories;

use CreativeCrafts\LaravelAiAssistant\Compat\OpenAI\Responses\Assistants\AssistantResponse;
use CreativeCrafts\LaravelAiAssistant\Compat\OpenAI\Responses\Audio\TranscriptionResponse;
use CreativeCrafts\LaravelAiAssistant\Compat\OpenAI\Responses\Audio\TranslationResponse;
use CreativeCrafts\LaravelAiAssistant\Compat\OpenAI\Responses\Chat\CreateResponse as ChatResponse;
use CreativeCrafts\LaravelAiAssistant\Compat\OpenAI\Responses\Completions\CreateResponse as CompletionResponse;
use CreativeCrafts\LaravelAiAssistant\Compat\OpenAI\Responses\Meta\MetaInformation;
use CreativeCrafts\LaravelAiAssistant\Compat\OpenAI\Responses\Threads\Messages\ThreadMessageResponse;
use CreativeCrafts\LaravelAiAssistant\Compat\OpenAI\Responses\Threads\Runs\ThreadRunResponse;
use CreativeCrafts\LaravelAiAssistant\Compat\OpenAI\Responses\Threads\ThreadResponse;
use CreativeCrafts\LaravelAiAssistant\Contracts\OpenAiRepositoryContract;

/**
 * Null implementation of OpenAiRepositoryContract used when mock_responses=true.
 * Returns deterministic, in-memory DTOs without performing any network I/O.
 */
final class NullOpenAiRepository implements OpenAiRepositoryContract
{
    public function createAssistant(array $parameters): AssistantResponse
    {
        $res = new AssistantResponse();
        $res->id = $parameters['id'] ?? 'asst_null';
        return $res;
    }

    public function retrieveAssistant(string $assistantId): AssistantResponse
    {
        $res = new AssistantResponse();
        $res->id = $assistantId !== '' ? $assistantId : 'asst_null';
        return $res;
    }

    public function createThread(array $parameters): ThreadResponse
    {
        return ThreadResponse::from([
            'id' => $parameters['id'] ?? 'thread_null',
            'object' => 'thread',
            'created_at' => time(),
            'tool_resources' => $parameters['tool_resources'] ?? null,
            'metadata' => $parameters['metadata'] ?? null,
        ], MetaInformation::from([]));
    }

    public function createThreadMessage(string $threadId, array $messageData): ThreadMessageResponse
    {
        return ThreadMessageResponse::from([
            'id' => $messageData['id'] ?? 'msg_null',
            'object' => 'thread.message',
            'created_at' => time(),
            'thread_id' => $threadId,
            'role' => $messageData['role'] ?? 'user',
            'content' => $messageData['content'] ?? [],
            'assistant_id' => $messageData['assistant_id'] ?? null,
            'run_id' => $messageData['run_id'] ?? null,
            'attachments' => $messageData['attachments'] ?? [],
            'metadata' => $messageData['metadata'] ?? [],
        ], MetaInformation::from([]));
    }

    public function createThreadRun(string $threadId, array $parameters): ThreadRunResponse
    {
        $res = new ThreadRunResponse();
        $res->id = $parameters['id'] ?? 'run_null';
        $res->status = $parameters['status'] ?? 'completed';
        return $res;
    }

    public function retrieveThreadRun(string $threadId, string $runId): ThreadRunResponse
    {
        $res = new ThreadRunResponse();
        $res->id = $runId !== '' ? $runId : 'run_null';
        $res->status = 'completed';
        return $res;
    }

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
