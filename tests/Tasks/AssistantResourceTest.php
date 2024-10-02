<?php

declare(strict_types=1);

use CreativeCrafts\LaravelAiAssistant\Tasks\AssistantResource;
use OpenAI\Client;
use OpenAI\Contracts\Resources\ThreadsContract;
use OpenAI\Contracts\Resources\ThreadsRunsContract;
use OpenAI\Responses\Assistants\AssistantResponse;
use OpenAI\Responses\Threads\Messages\ThreadMessageListResponse;
use OpenAI\Responses\Threads\Messages\ThreadMessageResponse;
use OpenAI\Responses\Threads\Runs\ThreadRunResponse;
use OpenAI\Responses\Threads\ThreadResponse;

covers(AssistantResource::class);

beforeEach(function () {
    $this->clientMock = Mockery::mock(Client::class);
    $this->assistantResource = new AssistantResource($this->clientMock);
});

it('creates an assistant', function () {
    $parameters = ['name' => 'Test Assistant'];
    $responseMock = Mockery::mock(AssistantResponse::class);

    $this->clientMock->shouldReceive('assistants->create')
        ->with($parameters)
        ->andReturn($responseMock);

    $response = $this->assistantResource->createAssistant($parameters);

    expect($response)->toBe($responseMock);
});

it('retrieves an assistant via ID', function () {
    $assistantId = 'assistant-123';
    $responseMock = Mockery::mock(AssistantResponse::class);

    $this->clientMock->shouldReceive('assistants->retrieve')
        ->with($assistantId)
        ->andReturn($responseMock);

    $response = $this->assistantResource->getAssistantViaId($assistantId);

    expect($response)->toBe($responseMock);
});

it('creates a thread', function () {
    $parameters = ['title' => 'Test Thread'];
    $responseMock = Mockery::mock(ThreadResponse::class);

    $this->clientMock->shouldReceive('threads->create')
        ->with($parameters)
        ->andReturn($responseMock);

    $response = $this->assistantResource->createThread($parameters);

    expect($response)->toBe($responseMock);
});

it('writes a message to a thread', function () {
    $threadId = 'thread-123';
    $messageData = ['content' => 'Test Message'];
    $responseMock = Mockery::mock(ThreadMessageResponse::class);

    $this->clientMock->shouldReceive('threads->messages->create')
        ->with($threadId, $messageData)
        ->andReturn($responseMock);

    $response = $this->assistantResource->writeMessage($threadId, $messageData);

    expect($response)->toBe($responseMock);
});

it('runs a message thread and waits for completion', function () {
    $threadId = 'thread-123';
    $runId = 'run-123';
    $messageData = ['content' => 'Test Message'];

    $runResponseMock = Mockery::mock(ThreadRunResponse::class);
    $reflectionClass = new ReflectionClass($runResponseMock);

    $threadIdProperty = $reflectionClass->getProperty('threadId');
    $threadIdProperty->setAccessible(true);
    $threadIdProperty->setValue($runResponseMock, $threadId);

    $idProperty = $reflectionClass->getProperty('id');
    $idProperty->setAccessible(true);
    $idProperty->setValue($runResponseMock, $runId);

    $statusProperty = $reflectionClass->getProperty('status');
    $statusProperty->setAccessible(true);
    $statusProperty->setValue($runResponseMock, 'pending');

    $threadsMock = Mockery::mock(ThreadsContract::class);
    $runsMock = Mockery::mock(ThreadsRunsContract::class);

    $runsMock->shouldReceive('create')
        ->with($threadId, $messageData)
        ->andReturn($runResponseMock);

    $statusProperty->setValue($runResponseMock, 'completed');

    $runsMock->shouldReceive('retrieve')
        ->with($threadId, $runId)
        ->andReturn($runResponseMock);

    $this->clientMock->shouldReceive('threads')->andReturn($threadsMock);
    $threadsMock->shouldReceive('runs')->andReturn($runsMock);

    $response = $this->assistantResource->runMessageThread($threadId, $messageData);

    expect($response)->toBeTrue();
});

it('lists messages from a thread', function () {
    $threadId = 'thread-123';

    $messageListMock = Mockery::mock(ThreadMessageListResponse::class);
    $messageListMock->shouldReceive('toArray')->andReturn([
        'data' => [
            [
                'content' => [
                    ['text' => ['value' => 'Test Message']],
                ],
            ],
        ],
    ]);

    $this->clientMock->shouldReceive('threads->messages->list')
        ->with($threadId)
        ->andReturn($messageListMock);

    $message = $this->assistantResource->listMessages($threadId);

    expect($message)->toBe('Test Message');
});
