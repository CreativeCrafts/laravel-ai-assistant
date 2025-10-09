<?php

declare(strict_types=1);

use CreativeCrafts\LaravelAiAssistant\Compat\OpenAI\Client;
use CreativeCrafts\LaravelAiAssistant\Compat\OpenAI\AudioResource;
use CreativeCrafts\LaravelAiAssistant\Compat\OpenAI\ChatResource;
use CreativeCrafts\LaravelAiAssistant\Compat\OpenAI\CompletionsResource;
use CreativeCrafts\LaravelAiAssistant\Compat\OpenAI\Responses\Audio\TranscriptionResponse;
use CreativeCrafts\LaravelAiAssistant\Compat\OpenAI\Responses\Audio\TranslationResponse;
use CreativeCrafts\LaravelAiAssistant\Compat\OpenAI\Responses\Chat\CreateResponse as ChatResponse;
use CreativeCrafts\LaravelAiAssistant\Compat\OpenAI\Responses\Completions\CreateResponse as CompletionResponse;
use CreativeCrafts\LaravelAiAssistant\Compat\OpenAI\Responses\StreamResponse;
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
