<?php

declare(strict_types=1);

use CreativeCrafts\LaravelAiAssistant\Compat\OpenAI\Responses\Assistants\AssistantResponse;
use CreativeCrafts\LaravelAiAssistant\Compat\OpenAI\Responses\Audio\TranscriptionResponse;
use CreativeCrafts\LaravelAiAssistant\Compat\OpenAI\Responses\Audio\TranslationResponse;
use CreativeCrafts\LaravelAiAssistant\Compat\OpenAI\Responses\Chat\CreateResponse as ChatResponse;
use CreativeCrafts\LaravelAiAssistant\Compat\OpenAI\Responses\Completions\CreateResponse as CompletionResponse;
use CreativeCrafts\LaravelAiAssistant\Compat\OpenAI\Responses\Threads\Messages\ThreadMessageResponse;
use CreativeCrafts\LaravelAiAssistant\Compat\OpenAI\Responses\Threads\Runs\ThreadRunResponse;
use CreativeCrafts\LaravelAiAssistant\Compat\OpenAI\Responses\Threads\ThreadResponse;
use CreativeCrafts\LaravelAiAssistant\Contracts\OpenAiRepositoryContract;
use CreativeCrafts\LaravelAiAssistant\Exceptions\ApiResponseValidationException;
use CreativeCrafts\LaravelAiAssistant\Exceptions\FileOperationException;
use CreativeCrafts\LaravelAiAssistant\Exceptions\MaxRetryAttemptsExceededException;
use CreativeCrafts\LaravelAiAssistant\Exceptions\ThreadExecutionTimeoutException;
use CreativeCrafts\LaravelAiAssistant\Services\AssistantService;
use CreativeCrafts\LaravelAiAssistant\Services\CacheService;
use CreativeCrafts\LaravelAiAssistant\Tests\DataFactories\AssistantFactory;

/**
 * Integration tests for API operations using mocked responses.
 * These tests verify the complete flow from service layer to repository
 * layer with realistic API response scenarios.
 */

beforeEach(function () {
    $this->repositoryMock = Mockery::mock(OpenAiRepositoryContract::class);
    $this->cacheServiceMock = Mockery::mock(CacheService::class);

    // Set up default cache behaviors
    $this->cacheServiceMock->shouldReceive('getCompletion')->andReturn(null)->byDefault();
    $this->cacheServiceMock->shouldReceive('cacheCompletion')->andReturn(true)->byDefault();
    $this->cacheServiceMock->shouldReceive('getResponse')->andReturn(null)->byDefault();
    $this->cacheServiceMock->shouldReceive('cacheResponse')->andReturn(true)->byDefault();

    $this->assistantService = new AssistantService($this->repositoryMock, $this->cacheServiceMock);
});

afterEach(function () {
    Mockery::close();
});

test('assistant creation flow', function () {
    // Arrange
    $config = AssistantFactory::assistantConfig();
    $expectedResponse = Mockery::mock(AssistantResponse::class);

    $this->repositoryMock
        ->shouldReceive('createAssistant')
        ->once()
        ->with($config)
        ->andReturn($expectedResponse);

    // Act
    $result = $this->assistantService->createAssistant($config);

    // Assert
    expect($result)->toBe($expectedResponse);
});

test('assistant creation with validation errors', function () {
    // Arrange - Invalid configuration (missing model)
    $invalidConfig = ['name' => 'Test Assistant'];

    // Act & Assert
    expect(fn () => $this->assistantService->createAssistant($invalidConfig))
        ->toThrow(InvalidArgumentException::class, 'Model parameter is required and must be a non-empty string.');
});

test('assistant retrieval flow', function () {
    // Arrange
    $assistantId = AssistantFactory::assistantId();
    $expectedResponse = Mockery::mock(AssistantResponse::class);

    $this->repositoryMock
        ->shouldReceive('retrieveAssistant')
        ->once()
        ->with($assistantId)
        ->andReturn($expectedResponse);

    // Act
    $result = $this->assistantService->getAssistantViaId($assistantId);

    // Assert
    expect($result)->toBe($expectedResponse);
});

test('assistant retrieval with invalid id', function () {
    // Arrange - Invalid assistant ID format
    $invalidId = 'invalid-assistant-id';

    // Act & Assert
    expect(fn () => $this->assistantService->getAssistantViaId($invalidId))
        ->toThrow(InvalidArgumentException::class, 'Assistant ID must follow the format: asst_[24 alphanumeric characters].');
});

test('thread creation flow', function () {
    // Arrange
    $threadParams = AssistantFactory::threadParams();
    $expectedResponse = Mockery::mock(ThreadResponse::class);

    $this->repositoryMock
        ->shouldReceive('createThread')
        ->once()
        ->with($threadParams)
        ->andReturn($expectedResponse);

    // Act
    $result = $this->assistantService->createThread($threadParams);

    // Assert
    expect($result)->toBe($expectedResponse);
});

test('message writing flow', function () {
    // Arrange
    $threadId = AssistantFactory::threadId();
    $messageData = AssistantFactory::messageData();
    $expectedResponse = Mockery::mock(ThreadMessageResponse::class);

    $this->repositoryMock
        ->shouldReceive('createThreadMessage')
        ->once()
        ->with($threadId, $messageData)
        ->andReturn($expectedResponse);

    // Act
    $result = $this->assistantService->writeMessage($threadId, $messageData);

    // Assert
    expect($result)->toBe($expectedResponse);
});

test('message writing with validation errors', function () {
    // Arrange
    $threadId = AssistantFactory::threadId();
    $invalidMessageData = ['content' => 'Missing role'];

    // Act & Assert
    expect(fn () => $this->assistantService->writeMessage($threadId, $invalidMessageData))
        ->toThrow(InvalidArgumentException::class, 'Message role is required and must be a non-empty string.');
});

test('thread execution successful flow', function () {
    // Arrange
    $threadId = AssistantFactory::threadId();
    $assistantId = AssistantFactory::assistantId();
    $runId = AssistantFactory::runId();
    $runParams = AssistantFactory::runParams($assistantId);

    $initialRun = Mockery::mock(ThreadRunResponse::class);
    $initialRun->threadId = $threadId;
    $initialRun->id = $runId;
    $initialRun->status = 'in_progress';

    $completedRun = Mockery::mock(ThreadRunResponse::class);
    $completedRun->threadId = $threadId;
    $completedRun->id = $runId;
    $completedRun->status = 'completed';

    $this->repositoryMock
        ->shouldReceive('createThreadRun')
        ->once()
        ->with($threadId, $runParams)
        ->andReturn($initialRun);

    $this->repositoryMock
        ->shouldReceive('retrieveThreadRun')
        ->once()
        ->with($threadId, $runId)
        ->andReturn($completedRun);

    // Act
    $result = $this->assistantService->runMessageThread($threadId, $runParams, 30, 5, 0.1);

    // Assert
    expect($result)->toBeTrue();
});

test('thread execution timeout', function () {
    // Arrange
    $threadId = AssistantFactory::threadId();
    $assistantId = AssistantFactory::assistantId();
    $runId = AssistantFactory::runId();
    $runParams = AssistantFactory::runParams($assistantId);

    $runInProgress = Mockery::mock(ThreadRunResponse::class);
    $runInProgress->threadId = $threadId;
    $runInProgress->id = $runId;
    $runInProgress->status = 'in_progress';

    $this->repositoryMock
        ->shouldReceive('createThreadRun')
        ->once()
        ->with($threadId, $runParams)
        ->andReturn($runInProgress);

    $this->repositoryMock
        ->shouldReceive('retrieveThreadRun')
        ->with($threadId, $runId)
        ->andReturn($runInProgress);

    // Act & Assert
    expect(fn () => $this->assistantService->runMessageThread($threadId, $runParams, 1, 10, 0.1))
        ->toThrow(ThreadExecutionTimeoutException::class, "Thread execution timed out after 1 seconds. Thread ID: {$threadId}, Run ID: {$runId}");
});

test('thread execution max retries', function () {
    // Arrange
    $threadId = AssistantFactory::threadId();
    $assistantId = AssistantFactory::assistantId();
    $runId = AssistantFactory::runId();
    $runParams = AssistantFactory::runParams($assistantId);

    $runInProgress = Mockery::mock(ThreadRunResponse::class);
    $runInProgress->threadId = $threadId;
    $runInProgress->id = $runId;
    $runInProgress->status = 'in_progress';

    $this->repositoryMock
        ->shouldReceive('createThreadRun')
        ->once()
        ->with($threadId, $runParams)
        ->andReturn($runInProgress);

    $this->repositoryMock
        ->shouldReceive('retrieveThreadRun')
        ->with($threadId, $runId)
        ->andReturn($runInProgress);

    // Act & Assert
    expect(fn () => $this->assistantService->runMessageThread($threadId, $runParams, 300, 2, 0.1))
        ->toThrow(MaxRetryAttemptsExceededException::class, "Maximum retry attempts (2) exceeded for thread execution. Thread ID: {$threadId}, Run ID: {$runId}");
});

test('message listing flow', function () {
    // Arrange
    $threadId = AssistantFactory::threadId();
    $messagesResponse = AssistantFactory::messagesListResponse();

    $this->repositoryMock
        ->shouldReceive('listThreadMessages')
        ->once()
        ->with($threadId)
        ->andReturn($messagesResponse);

    // Act
    $result = $this->assistantService->listMessages($threadId);

    // Assert
    expect($result)->toBe('Hello! How can I help you today?');
});

test('message listing with empty response', function () {
    // Arrange
    $threadId = AssistantFactory::threadId();
    $emptyResponse = ['data' => []];

    $this->repositoryMock
        ->shouldReceive('listThreadMessages')
        ->once()
        ->with($threadId)
        ->andReturn($emptyResponse);

    // Act
    $result = $this->assistantService->listMessages($threadId);

    // Assert
    expect($result)->toBe('');
});

test('message listing with invalid response', function () {
    // Arrange
    $threadId = AssistantFactory::threadId();
    $invalidResponse = ['invalid' => 'structure'];

    $this->repositoryMock
        ->shouldReceive('listThreadMessages')
        ->once()
        ->with($threadId)
        ->andReturn($invalidResponse);

    // Act & Assert
    expect(fn () => $this->assistantService->listMessages($threadId))
        ->toThrow(ApiResponseValidationException::class, 'Invalid API response structure: missing or invalid data array.');
});

test('text completion flow', function () {
    // Arrange
    $payload = AssistantFactory::completionPayload();
    $expectedText = 'Generated completion text';
    $mockResponse = Mockery::mock(CompletionResponse::class);

    // Set up the choices property using reflection
    $reflection = new ReflectionClass(CompletionResponse::class);
    $property = $reflection->getProperty('choices');
    $property->setAccessible(true);
    $property->setValue($mockResponse, [
        (object)['text' => $expectedText]
    ]);

    $this->repositoryMock
        ->shouldReceive('createCompletion')
        ->once()
        ->with($payload)
        ->andReturn($mockResponse);

    // Act
    $result = $this->assistantService->textCompletion($payload);

    // Assert
    expect($result)->toBe($expectedText);
});

test('text completion with caching', function () {
    // Arrange
    $payload = AssistantFactory::completionPayload(['temperature' => 0.0]); // Deterministic
    $cachedResult = 'Cached completion text';

    $this->cacheServiceMock
        ->shouldReceive('getCompletion')
        ->once()
        ->andReturn($cachedResult);

    // Act
    $result = $this->assistantService->textCompletion($payload);

    // Assert
    expect($result)->toBe($cachedResult);
});

test('chat completion flow', function () {
    // Arrange
    $payload = AssistantFactory::chatCompletionPayload();
    $expectedContent = 'Chat response content';
    $mockResponse = Mockery::mock(ChatResponse::class);

    // Create a mock message with explicit toArray method
    $mockMessage = new class () {
        private $content;
        public function __construct()
        {
            $this->content = 'Chat response content';
        }
        public function toArray(): array
        {
            return ['content' => $this->content];
        }
    };

    // Set up the choice property
    $reflection = new ReflectionClass(ChatResponse::class);
    $property = $reflection->getProperty('choices');
    $property->setAccessible(true);
    $property->setValue($mockResponse, [
        (object)['message' => $mockMessage]
    ]);

    $this->repositoryMock
        ->shouldReceive('createChatCompletion')
        ->once()
        ->with($payload)
        ->andReturn($mockResponse);

    // Act
    $result = $this->assistantService->chatTextCompletion($payload);

    // Assert
    expect($result)->toBe(['content' => $expectedContent]);
});

test('audio transcription flow', function () {
    // Arrange
    $file = AssistantFactory::createTestAudioFile();
    $payload = AssistantFactory::audioPayload($file);
    $expectedText = 'Transcribed audio content';
    $mockResponse = Mockery::mock(TranscriptionResponse::class);

    // Set up the text property
    $reflection = new ReflectionClass(TranscriptionResponse::class);
    $property = $reflection->getProperty('text');
    $property->setAccessible(true);
    $property->setValue($mockResponse, $expectedText);

    $this->repositoryMock
        ->shouldReceive('transcribeAudio')
        ->once()
        ->with($payload)
        ->andReturn($mockResponse);

    // Act
    $result = $this->assistantService->transcribeTo($payload);

    // Assert
    expect($result)->toBe($expectedText);

    // Cleanup
    fclose($file);
});

test('audio translation flow', function () {
    // Arrange
    $file = AssistantFactory::createTestAudioFile();
    $payload = AssistantFactory::audioPayload($file);
    $expectedText = 'Translated audio content';
    $mockResponse = Mockery::mock(TranslationResponse::class);

    // Set up the text property
    $reflection = new ReflectionClass(TranslationResponse::class);
    $property = $reflection->getProperty('text');
    $property->setAccessible(true);
    $property->setValue($mockResponse, $expectedText);

    $this->repositoryMock
        ->shouldReceive('translateAudio')
        ->once()
        ->with($payload)
        ->andReturn($mockResponse);

    // Act
    $result = $this->assistantService->translateTo($payload);

    // Assert
    expect($result)->toBe($expectedText);

    // Cleanup
    fclose($file);
});

test('audio processing with invalid file', function () {
    // Arrange
    $invalidPayload = ['file' => 'not-a-resource', 'model' => 'whisper-1'];

    // Act & Assert
    expect(fn () => $this->assistantService->transcribeTo($invalidPayload))
        ->toThrow(FileOperationException::class, 'File parameter must be a valid file resource.');
});

test('validation error scenarios', function () {
    // Test empty payload
    expect(fn () => $this->assistantService->textCompletion([]))
        ->toThrow(InvalidArgumentException::class, 'Text completion payload cannot be empty.');
});

test('end to end assistant workflow', function () {
    // Arrange - Complete workflow from assistant creation to message processing
    $assistantConfig = AssistantFactory::assistantConfig();
    $threadParams = AssistantFactory::threadParams();
    $messageData = AssistantFactory::messageData();

    $assistantId = AssistantFactory::assistantId();
    $threadId = AssistantFactory::threadId();
    $runId = AssistantFactory::runId();

    // Mock responses
    $assistantResponse = Mockery::mock(AssistantResponse::class);
    $threadResponse = Mockery::mock(ThreadResponse::class);
    $threadResponse->id = $threadId;
    $messageResponse = Mockery::mock(ThreadMessageResponse::class);

    $initialRun = Mockery::mock(ThreadRunResponse::class);
    $initialRun->threadId = $threadId;
    $initialRun->id = $runId;
    $initialRun->status = 'in_progress';

    $completedRun = Mockery::mock(ThreadRunResponse::class);
    $completedRun->threadId = $threadId;
    $completedRun->id = $runId;
    $completedRun->status = 'completed';

    $messagesResponse = AssistantFactory::messagesListResponse();

    // Set up expectations
    $this->repositoryMock->shouldReceive('createAssistant')->with($assistantConfig)->andReturn($assistantResponse);
    $this->repositoryMock->shouldReceive('createThread')->with($threadParams)->andReturn($threadResponse);
    $this->repositoryMock->shouldReceive('createThreadMessage')->with($threadId, $messageData)->andReturn($messageResponse);
    $this->repositoryMock->shouldReceive('createThreadRun')->andReturn($initialRun);
    $this->repositoryMock->shouldReceive('retrieveThreadRun')->andReturn($completedRun);
    $this->repositoryMock->shouldReceive('listThreadMessages')->with($threadId)->andReturn($messagesResponse);

    // Act - Execute complete workflow
    $assistant = $this->assistantService->createAssistant($assistantConfig);
    $thread = $this->assistantService->createThread($threadParams);
    $message = $this->assistantService->writeMessage($threadId, $messageData);
    $runResult = $this->assistantService->runMessageThread($threadId, ['assistant_id' => $assistantId], 30, 5, 0.1);
    $response = $this->assistantService->listMessages($threadId);

    // Assert
    expect($assistant)->toBe($assistantResponse);
    expect($thread)->toBe($threadResponse);
    expect($message)->toBe($messageResponse);
    expect($runResult)->toBeTrue();
    expect($response)->toBe('Hello! How can I help you today?');
});
