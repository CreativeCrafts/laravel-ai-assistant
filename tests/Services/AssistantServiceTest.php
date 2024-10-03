<?php

declare(strict_types=1);

use CreativeCrafts\LaravelAiAssistant\Services\AssistantService;
use OpenAI\Client;
use OpenAI\Contracts\Resources\ThreadsContract;
use OpenAI\Contracts\Resources\ThreadsRunsContract;
use OpenAI\Responses\Assistants\AssistantResponse;
use OpenAI\Responses\Audio\TranscriptionResponse;
use OpenAI\Responses\Audio\TranslationResponse;
use OpenAI\Responses\Completions\CreateResponse;
use OpenAI\Responses\Threads\Messages\ThreadMessageListResponse;
use OpenAI\Responses\Threads\Messages\ThreadMessageResponse;
use OpenAI\Responses\Threads\Runs\ThreadRunResponse;
use OpenAI\Responses\Threads\ThreadResponse;

covers(AssistantService::class);

beforeEach(function () {
    $this->clientMock = Mockery::mock(Client::class);
    $this->assistantService = new AssistantService($this->clientMock);

    $this->createGenerator = function (array $responses) {
        foreach ($responses as $response) {
            yield $response;
        }
    };
});

it('creates an assistant', function () {
    $parameters = ['name' => 'Test Assistant'];
    $responseMock = Mockery::mock(AssistantResponse::class);

    $this->clientMock->shouldReceive('assistants->create')
        ->with($parameters)
        ->andReturn($responseMock);

    $response = $this->assistantService->createAssistant($parameters);

    expect($response)->toBe($responseMock);
});

it('retrieves an assistant via ID', function () {
    $assistantId = 'assistant-123';
    $responseMock = Mockery::mock(AssistantResponse::class);

    $this->clientMock->shouldReceive('assistants->retrieve')
        ->with($assistantId)
        ->andReturn($responseMock);

    $response = $this->assistantService->getAssistantViaId($assistantId);

    expect($response)->toBe($responseMock);
});

it('creates a thread', function () {
    $parameters = ['title' => 'Test Thread'];
    $responseMock = Mockery::mock(ThreadResponse::class);

    $this->clientMock->shouldReceive('threads->create')
        ->with($parameters)
        ->andReturn($responseMock);

    $response = $this->assistantService->createThread($parameters);

    expect($response)->toBe($responseMock);
});

it('writes a message to a thread', function () {
    $threadId = 'thread-123';
    $messageData = ['content' => 'Test Message'];
    $responseMock = Mockery::mock(ThreadMessageResponse::class);

    $this->clientMock->shouldReceive('threads->messages->create')
        ->with($threadId, $messageData)
        ->andReturn($responseMock);

    $response = $this->assistantService->writeMessage($threadId, $messageData);

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

    $response = $this->assistantService->runMessageThread($threadId, $messageData);

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

    $message = $this->assistantService->listMessages($threadId);

    expect($message)->toBe('Test Message');
});

it('returns the text from the last choice when choices are present', function () {
    $mockResponse = Mockery::mock(CreateResponse::class);

    $reflection = new ReflectionClass(CreateResponse::class);
    $property = $reflection->getProperty('choices');
    $property->setAccessible(true);
    $property->setValue($mockResponse, [
        (object) ['text' => 'First choice'],
        (object) ['text' => 'Second choice'],
    ]);

    $this->clientMock->shouldReceive('completions->create')
        ->once()
        ->andReturn($mockResponse);

    $payload = ['prompt' => 'Sample prompt'];
    $result = $this->assistantService->textCompletion($payload);

    expect($result)->toBe('Second choice');
});


it('returns an empty string when there are no choices', function () {
    $mockResponse = Mockery::mock(CreateResponse::class);

    $reflection = new ReflectionClass(CreateResponse::class);
    $property = $reflection->getProperty('choices');
    $property->setAccessible(true);
    $property->setValue($mockResponse, []);

    $this->clientMock->shouldReceive('completions->create')
        ->once()
        ->andReturn($mockResponse);

    $payload = ['prompt' => 'Sample prompt'];
    $result = $this->assistantService->textCompletion($payload);

    expect($result)->toBe('');
});

it('returns the transcription text when the transcribe method is called', function () {
    $mockResponse = Mockery::mock(TranscriptionResponse::class);

    $reflection = new ReflectionClass(TranscriptionResponse::class);
    $property = $reflection->getProperty('text');
    $property->setAccessible(true);
    $property->setValue($mockResponse, 'Transcribed text');

    $this->clientMock->shouldReceive('audio->transcribe')
        ->once()
        ->andReturn($mockResponse);

    $payload = ['audio' => 'Sample audio file'];
    $result = $this->assistantService->transcribeTo($payload);

    expect($result)->toBe('Transcribed text');
});

it('throws an error when transcription fails', function () {
    $this->clientMock->shouldReceive('audio->transcribe')
        ->once()
        ->andThrow(new Exception('Transcription failed'));

    $payload = ['audio' => 'Sample audio file'];

    expect(fn () => $this->assistantService->transcribeTo($payload))
        ->toThrow(Exception::class, 'Transcription failed');
});

it('returns the translation text when the translate method is called', function () {
    $mockResponse = Mockery::mock(TranslationResponse::class);

    $reflection = new ReflectionClass(TranslationResponse::class);
    $property = $reflection->getProperty('text');
    $property->setAccessible(true);
    $property->setValue($mockResponse, 'Translated text');

    $this->clientMock->shouldReceive('audio->translate')
        ->once()
        ->andReturn($mockResponse);

    $payload = ['audio' => 'Sample audio file'];
    $result = $this->assistantService->translateTo($payload);

    expect($result)->toBe('Translated text');
});

it('throws an error when translation fails', function () {
    $this->clientMock->shouldReceive('audio->translate')
        ->once()
        ->andThrow(new Exception('Translation failed'));

    $payload = ['audio' => 'Sample audio file'];

    expect(fn () => $this->assistantService->translateTo($payload))
        ->toThrow(Exception::class, 'Translation failed');
});
