<?php

declare(strict_types=1);

use CreativeCrafts\LaravelAiAssistant\Compat\OpenAI\Client;
use CreativeCrafts\LaravelAiAssistant\Compat\OpenAI\AssistantsResource;
use CreativeCrafts\LaravelAiAssistant\Compat\OpenAI\AudioResource;
use CreativeCrafts\LaravelAiAssistant\Compat\OpenAI\ChatResource;
use CreativeCrafts\LaravelAiAssistant\Compat\OpenAI\CompletionsResource;
use CreativeCrafts\LaravelAiAssistant\Compat\OpenAI\ThreadMessagesResource;
use CreativeCrafts\LaravelAiAssistant\Compat\OpenAI\ThreadRunsResource;
use CreativeCrafts\LaravelAiAssistant\Compat\OpenAI\ThreadsResource;
use CreativeCrafts\LaravelAiAssistant\Compat\OpenAI\Responses\Assistants\AssistantResponse;
use CreativeCrafts\LaravelAiAssistant\Compat\OpenAI\Responses\Audio\TranscriptionResponse;
use CreativeCrafts\LaravelAiAssistant\Compat\OpenAI\Responses\Audio\TranslationResponse;
use CreativeCrafts\LaravelAiAssistant\Compat\OpenAI\Responses\Chat\CreateResponse as ChatResponse;
use CreativeCrafts\LaravelAiAssistant\Compat\OpenAI\Responses\Completions\CreateResponse as CompletionResponse;
use CreativeCrafts\LaravelAiAssistant\Compat\OpenAI\Responses\StreamResponse;
use CreativeCrafts\LaravelAiAssistant\Compat\OpenAI\Responses\Threads\Messages\ThreadMessageListResponse;
use CreativeCrafts\LaravelAiAssistant\Compat\OpenAI\Responses\Threads\Messages\ThreadMessageResponse;
use CreativeCrafts\LaravelAiAssistant\Compat\OpenAI\Responses\Threads\Runs\ThreadRunResponse;
use CreativeCrafts\LaravelAiAssistant\Compat\OpenAI\Responses\Threads\ThreadResponse;
use CreativeCrafts\LaravelAiAssistant\Contracts\OpenAiRepositoryContract;
use CreativeCrafts\LaravelAiAssistant\Repositories\OpenAiRepository;

covers(OpenAiRepository::class);

beforeEach(function () {
    $this->clientMock = Mockery::mock(Client::class);
    $this->repository = new OpenAiRepository($this->clientMock);
});

it('implements OpenAiRepositoryContract', function () {
    expect($this->repository)->toBeInstanceOf(OpenAiRepositoryContract::class);
});

it('creates an assistant through the client', function () {
    $parameters = ['model' => 'gpt-4', 'name' => 'Test Assistant'];
    $responseMock = Mockery::mock(AssistantResponse::class);

    $assistantsMock = Mockery::mock(AssistantsResource::class);
    $assistantsMock->shouldReceive('create')
        ->with($parameters)
        ->andReturn($responseMock);

    $this->clientMock->shouldReceive('assistants')
        ->andReturn($assistantsMock);

    $result = $this->repository->createAssistant($parameters);

    expect($result)->toBe($responseMock);
});

it('retrieves an assistant through the client', function () {
    $assistantId = 'asst_1234567890abcdef12345678';
    $responseMock = Mockery::mock(AssistantResponse::class);

    $assistantsMock = Mockery::mock(AssistantsResource::class);
    $assistantsMock->shouldReceive('retrieve')
        ->with($assistantId)
        ->andReturn($responseMock);

    $this->clientMock->shouldReceive('assistants')
        ->andReturn($assistantsMock);

    $result = $this->repository->retrieveAssistant($assistantId);

    expect($result)->toBe($responseMock);
});

it('creates a thread through the client', function () {
    $parameters = ['title' => 'Test Thread'];
    $responseMock = Mockery::mock(ThreadResponse::class);

    $threadsMock = Mockery::mock(ThreadsResource::class);
    $threadsMock->shouldReceive('create')
        ->with($parameters)
        ->andReturn($responseMock);

    $this->clientMock->shouldReceive('threads')
        ->andReturn($threadsMock);

    $result = $this->repository->createThread($parameters);

    expect($result)->toBe($responseMock);
});

it('creates a thread message through the client', function () {
    $threadId = 'thread_1234567890abcdef12345678';
    $messageData = ['role' => 'user', 'content' => 'Test message'];
    $responseMock = Mockery::mock(ThreadMessageResponse::class);

    $messagesMock = Mockery::mock(ThreadMessagesResource::class);
    $messagesMock->shouldReceive('create')
        ->with($threadId, $messageData)
        ->andReturn($responseMock);

    $threadsMock = Mockery::mock(ThreadsResource::class);
    $threadsMock->shouldReceive('messages')
        ->andReturn($messagesMock);

    $this->clientMock->shouldReceive('threads')
        ->andReturn($threadsMock);

    $result = $this->repository->createThreadMessage($threadId, $messageData);

    expect($result)->toBe($responseMock);
});

it('creates a thread run through the client', function () {
    $threadId = 'thread_1234567890abcdef12345678';
    $parameters = ['assistant_id' => 'asst_1234567890abcdef12345678'];
    $responseMock = Mockery::mock(ThreadRunResponse::class);

    $runsMock = Mockery::mock(ThreadRunsResource::class);
    $runsMock->shouldReceive('create')
        ->with($threadId, $parameters)
        ->andReturn($responseMock);

    $threadsMock = Mockery::mock(ThreadsResource::class);
    $threadsMock->shouldReceive('runs')
        ->andReturn($runsMock);

    $this->clientMock->shouldReceive('threads')
        ->andReturn($threadsMock);

    $result = $this->repository->createThreadRun($threadId, $parameters);

    expect($result)->toBe($responseMock);
});

it('retrieves a thread run through the client', function () {
    $threadId = 'thread_1234567890abcdef12345678';
    $runId = 'run_1234567890abcdef12345678';
    $responseMock = Mockery::mock(ThreadRunResponse::class);

    $runsMock = Mockery::mock(ThreadRunsResource::class);
    $runsMock->shouldReceive('retrieve')
        ->with($threadId, $runId)
        ->andReturn($responseMock);

    $threadsMock = Mockery::mock(ThreadsResource::class);
    $threadsMock->shouldReceive('runs')
        ->andReturn($runsMock);

    $this->clientMock->shouldReceive('threads')
        ->andReturn($threadsMock);

    $result = $this->repository->retrieveThreadRun($threadId, $runId);

    expect($result)->toBe($responseMock);
});

it('lists thread messages through the client', function () {
    $threadId = 'thread_1234567890abcdef12345678';
    $expectedArray = [
        'data' => [
            ['content' => [['text' => ['value' => 'Test message']]]]
        ]
    ];

    $messageListMock = Mockery::mock(ThreadMessageListResponse::class);
    $messageListMock->shouldReceive('toArray')
        ->andReturn($expectedArray);

    $messagesMock = Mockery::mock(ThreadMessagesResource::class);
    $messagesMock->shouldReceive('list')
        ->with($threadId)
        ->andReturn($messageListMock);

    $threadsMock = Mockery::mock(ThreadsResource::class);
    $threadsMock->shouldReceive('messages')
        ->andReturn($messagesMock);

    $this->clientMock->shouldReceive('threads')
        ->andReturn($threadsMock);

    $result = $this->repository->listThreadMessages($threadId);

    expect($result)->toBe($expectedArray);
});

it('creates a completion through the client', function () {
    $parameters = ['model' => 'gpt-3.5-turbo', 'prompt' => 'Test prompt'];
    $responseMock = Mockery::mock(CompletionResponse::class);

    $completionsMock = Mockery::mock(CompletionsResource::class);
    $completionsMock->shouldReceive('create')
        ->with($parameters)
        ->andReturn($responseMock);

    $this->clientMock->shouldReceive('completions')
        ->andReturn($completionsMock);

    $result = $this->repository->createCompletion($parameters);

    expect($result)->toBe($responseMock);
});

it('creates a streamed completion through the client', function () {
    $parameters = ['model' => 'gpt-3.5-turbo', 'prompt' => 'Test prompt'];
    $mockStreamResponse = Mockery::mock(StreamResponse::class);

    $completionsMock = Mockery::mock(CompletionsResource::class);
    $completionsMock->shouldReceive('createStreamed')
        ->with($parameters)
        ->andReturn($mockStreamResponse);

    $this->clientMock->shouldReceive('completions')
        ->andReturn($completionsMock);

    $result = $this->repository->createStreamedCompletion($parameters);

    expect($result)->toBe($mockStreamResponse);
});

it('creates a chat completion through the client', function () {
    $parameters = ['model' => 'gpt-3.5-turbo', 'messages' => [['role' => 'user', 'content' => 'Test']]];
    $responseMock = Mockery::mock(ChatResponse::class);

    $chatMock = Mockery::mock(ChatResource::class);
    $chatMock->shouldReceive('create')
        ->with($parameters)
        ->andReturn($responseMock);

    $this->clientMock->shouldReceive('chat')
        ->andReturn($chatMock);

    $result = $this->repository->createChatCompletion($parameters);

    expect($result)->toBe($responseMock);
});

it('creates a streamed chat completion through the client', function () {
    $parameters = ['model' => 'gpt-3.5-turbo', 'messages' => [['role' => 'user', 'content' => 'Test']]];
    $mockStreamResponse = Mockery::mock(StreamResponse::class);

    $chatMock = Mockery::mock(ChatResource::class);
    $chatMock->shouldReceive('createStreamed')
        ->with($parameters)
        ->andReturn($mockStreamResponse);

    $this->clientMock->shouldReceive('chat')
        ->andReturn($chatMock);

    $result = $this->repository->createStreamedChatCompletion($parameters);

    expect($result)->toBe($mockStreamResponse);
});

it('transcribes audio through the client', function () {
    $fileResource = fopen('data://text/plain;base64,' . base64_encode('test audio'), 'r');
    $parameters = ['file' => $fileResource, 'model' => 'whisper-1'];
    $responseMock = Mockery::mock(TranscriptionResponse::class);

    $audioMock = Mockery::mock(AudioResource::class);
    $audioMock->shouldReceive('transcribe')
        ->with($parameters)
        ->andReturn($responseMock);

    $this->clientMock->shouldReceive('audio')
        ->andReturn($audioMock);

    $result = $this->repository->transcribeAudio($parameters);

    expect($result)->toBe($responseMock);

    fclose($fileResource);
});

it('translates audio through the client', function () {
    $fileResource = fopen('data://text/plain;base64,' . base64_encode('test audio'), 'r');
    $parameters = ['file' => $fileResource, 'model' => 'whisper-1'];
    $responseMock = Mockery::mock(TranslationResponse::class);

    $audioMock = Mockery::mock(AudioResource::class);
    $audioMock->shouldReceive('translate')
        ->with($parameters)
        ->andReturn($responseMock);

    $this->clientMock->shouldReceive('audio')
        ->andReturn($audioMock);

    $result = $this->repository->translateAudio($parameters);

    expect($result)->toBe($responseMock);

    fclose($fileResource);
});

it('properly handles client dependency injection', function () {
    $newClient = Mockery::mock(Client::class);
    $newRepository = new OpenAiRepository($newClient);

    // Use reflection to access the protected client property
    $reflection = new ReflectionClass($newRepository);
    $property = $reflection->getProperty('client');
    $property->setAccessible(true);

    expect($property->getValue($newRepository))->toBe($newClient);
});
