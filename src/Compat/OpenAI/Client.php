<?php

declare(strict_types=1);

namespace CreativeCrafts\LaravelAiAssistant\Compat\OpenAI;

use CreativeCrafts\LaravelAiAssistant\Compat\OpenAI\Responses\Assistants\AssistantResponse;
use CreativeCrafts\LaravelAiAssistant\Compat\OpenAI\Responses\Audio\TranscriptionResponse;
use CreativeCrafts\LaravelAiAssistant\Compat\OpenAI\Responses\Audio\TranslationResponse;
use CreativeCrafts\LaravelAiAssistant\Compat\OpenAI\Responses\Chat\CreateResponse as ChatResponse;
use CreativeCrafts\LaravelAiAssistant\Compat\OpenAI\Responses\Completions\CreateResponse as CompletionResponse;
use CreativeCrafts\LaravelAiAssistant\Compat\OpenAI\Responses\Meta\MetaInformation;
use CreativeCrafts\LaravelAiAssistant\Compat\OpenAI\Responses\Threads\Messages\ThreadMessageListResponse;
use CreativeCrafts\LaravelAiAssistant\Compat\OpenAI\Responses\Threads\Messages\ThreadMessageResponse;
use CreativeCrafts\LaravelAiAssistant\Compat\OpenAI\Responses\Threads\Runs\ThreadRunResponse;
use CreativeCrafts\LaravelAiAssistant\Compat\OpenAI\Responses\Threads\ThreadResponse;

class Client
{
    // This is a compatibility stub to allow existing code/tests to typehint OpenAI\Client
    // No implementation is required because tests mock methods on this class.

    public function assistants(): AssistantsResource
    {
        return new AssistantsResource();
    }

    public function threads(): ThreadsResource
    {
        return new ThreadsResource();
    }

    public function completions(): CompletionsResource
    {
        return new CompletionsResource();
    }

    public function chat(): ChatResource
    {
        return new ChatResource();
    }

    public function audio(): AudioResource
    {
        return new AudioResource();
    }
}

class AssistantsResource
{
    public function create(array $parameters): AssistantResponse
    {
        $response = new AssistantResponse();
        $response->id = 'test_id';
        return $response;
    }

    public function retrieve(string $assistantId): AssistantResponse
    {
        $response = new AssistantResponse();
        $response->id = $assistantId;
        return $response;
    }
}

class ThreadsResource
{
    public function create(array $parameters): ThreadResponse
    {
        return ThreadResponse::from(['id' => 'thread_test_id'], MetaInformation::from([]));
    }

    public function messages(): ThreadMessagesResource
    {
        return new ThreadMessagesResource();
    }

    public function runs(): ThreadRunsResource
    {
        return new ThreadRunsResource();
    }
}

class ThreadMessagesResource
{
    public function create(string $threadId, array $parameters): ThreadMessageResponse
    {
        return ThreadMessageResponse::from([
            'id' => 'message_test_id',
            'thread_id' => $threadId,
            'content' => []
        ], MetaInformation::from([]));
    }

    public function list(string $threadId): ThreadMessageListResponse
    {
        return new ThreadMessageListResponse(['data' => []]);
    }
}

class ThreadRunsResource
{
    public function create(string $threadId, array $parameters): ThreadRunResponse
    {
        $response = new ThreadRunResponse();
        $response->id = 'run_test_id';
        $response->status = 'queued';
        return $response;
    }

    public function retrieve(string $threadId, string $runId): ThreadRunResponse
    {
        $response = new ThreadRunResponse();
        $response->id = $runId;
        $response->status = 'completed';
        return $response;
    }
}

class CompletionsResource
{
    public function create(array $parameters): CompletionResponse
    {
        $response = new CompletionResponse();
        $response->choices = [
            (object) ['text' => 'Mock completion text', 'finish_reason' => 'stop']
        ];
        return $response;
    }

    public function createStreamed(array $parameters): iterable
    {
        return [
            (object) ['choices' => [(object) ['text' => 'Mock', 'finish_reason' => null]]],
            (object) ['choices' => [(object) ['text' => ' streamed', 'finish_reason' => null]]],
            (object) ['choices' => [(object) ['text' => ' text', 'finish_reason' => 'stop']]]
        ]; // Mock implementation for testing
    }
}

class ChatResource
{
    public function create(array $parameters): ChatResponse
    {
        $response = new ChatResponse();
        $response->choices = [
            (object) [
                'message' => (object) [
                    'role' => 'assistant',
                    'content' => 'Mock chat response'
                ],
                'finish_reason' => 'stop'
            ]
        ];
        return $response;
    }

    public function createStreamed(array $parameters): iterable
    {
        return [
            (object) ['choices' => [(object) ['delta' => (object) ['content' => 'Mock'], 'finish_reason' => null]]],
            (object) ['choices' => [(object) ['delta' => (object) ['content' => ' streamed'], 'finish_reason' => null]]],
            (object) ['choices' => [(object) ['delta' => (object) ['content' => ' response'], 'finish_reason' => 'stop']]]
        ]; // Mock implementation for testing
    }
}

class AudioResource
{
    public function transcribe(array $parameters): TranscriptionResponse
    {
        $response = new TranscriptionResponse();
        $response->text = 'Mock transcribed text';
        return $response;
    }

    public function translate(array $parameters): TranslationResponse
    {
        $response = new TranslationResponse();
        $response->text = 'Mock translated text';
        return $response;
    }
}
