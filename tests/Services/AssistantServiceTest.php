<?php

declare(strict_types=1);

use CreativeCrafts\LaravelAiAssistant\Compat\OpenAI\Responses\Assistants\AssistantResponse;
use CreativeCrafts\LaravelAiAssistant\Compat\OpenAI\Responses\Audio\TranscriptionResponse;
use CreativeCrafts\LaravelAiAssistant\Compat\OpenAI\Responses\Audio\TranslationResponse;
use CreativeCrafts\LaravelAiAssistant\Compat\OpenAI\Responses\Completions\CreateResponse;
use CreativeCrafts\LaravelAiAssistant\Compat\OpenAI\Responses\Completions\StreamedCompletionResponse;
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

covers(AssistantService::class);

beforeEach(function () {
    $this->repositoryMock = Mockery::mock(OpenAiRepositoryContract::class);
    $this->cacheServiceMock = Mockery::mock(CacheService::class);

    // Set up default cache mock behaviors to allow caching methods to be called
    $this->cacheServiceMock->shouldReceive('getCompletion')->andReturn(null)->byDefault();
    $this->cacheServiceMock->shouldReceive('cacheCompletion')->andReturn(true)->byDefault();
    $this->cacheServiceMock->shouldReceive('getResponse')->andReturn(null)->byDefault();
    $this->cacheServiceMock->shouldReceive('cacheResponse')->andReturn(true)->byDefault();

    $this->assistantService = new AssistantService($this->repositoryMock, $this->cacheServiceMock);

    $this->createGenerator = function (array $responses) {
        foreach ($responses as $response) {
            yield $response;
        }
    };
});

it('creates an assistant', function () {
    $parameters = ['model' => 'gpt-4', 'name' => 'Test Assistant'];
    $responseMock = Mockery::mock(AssistantResponse::class);

    $this->repositoryMock->shouldReceive('createAssistant')
        ->with($parameters)
        ->andReturn($responseMock);

    $response = $this->assistantService->createAssistant($parameters);

    expect($response)->toBe($responseMock);
});

it('validates assistant parameters before creation', function () {
    expect(fn () => $this->assistantService->createAssistant([]))
        ->toThrow(InvalidArgumentException::class, 'Assistant parameters cannot be empty.');
});

it('validates model parameter is required for assistant creation', function () {
    $parameters = ['name' => 'Test Assistant']; // Missing required model parameter

    expect(fn () => $this->assistantService->createAssistant($parameters))
        ->toThrow(InvalidArgumentException::class, 'Model parameter is required and must be a non-empty string.');
});

it('retrieves an assistant via ID', function () {
    $assistantId = 'asst_1234567890abcdef12345678';
    $responseMock = Mockery::mock(AssistantResponse::class);

    $this->repositoryMock->shouldReceive('retrieveAssistant')
        ->with($assistantId)
        ->andReturn($responseMock);

    $response = $this->assistantService->getAssistantViaId($assistantId);

    expect($response)->toBe($responseMock);
});

it('validates assistant ID format', function () {
    expect(fn () => $this->assistantService->getAssistantViaId(''))
        ->toThrow(InvalidArgumentException::class, 'Assistant ID cannot be empty.');

    expect(fn () => $this->assistantService->getAssistantViaId('invalid-format'))
        ->toThrow(InvalidArgumentException::class, 'Assistant ID must follow the format: asst_[24 alphanumeric characters].');
});

it('creates a thread', function () {
    $parameters = ['title' => 'Test Thread'];
    $responseMock = Mockery::mock(ThreadResponse::class);

    $this->repositoryMock->shouldReceive('createThread')
        ->with($parameters)
        ->andReturn($responseMock);

    $response = $this->assistantService->createThread($parameters);

    expect($response)->toBe($responseMock);
});

it('writes a message to a thread', function () {
    $threadId = 'thread_1234567890abcdef12345678';
    $messageData = ['role' => 'user', 'content' => 'Test Message'];
    $responseMock = Mockery::mock(ThreadMessageResponse::class);

    $this->repositoryMock->shouldReceive('createThreadMessage')
        ->with($threadId, $messageData)
        ->andReturn($responseMock);

    $response = $this->assistantService->writeMessage($threadId, $messageData);

    expect($response)->toBe($responseMock);
});

it('validates thread ID format for write message', function () {
    $messageData = ['role' => 'user', 'content' => 'Test Message'];

    expect(fn () => $this->assistantService->writeMessage('', $messageData))
        ->toThrow(InvalidArgumentException::class, 'Thread ID cannot be empty.');

    expect(fn () => $this->assistantService->writeMessage('invalid-format', $messageData))
        ->toThrow(InvalidArgumentException::class, 'Thread ID must follow the format: thread_[24 alphanumeric characters].');
});

it('validates message data for write message', function () {
    $threadId = 'thread_1234567890abcdef12345678';

    expect(fn () => $this->assistantService->writeMessage($threadId, []))
        ->toThrow(InvalidArgumentException::class, 'Message data cannot be empty.');

    expect(fn () => $this->assistantService->writeMessage($threadId, ['content' => 'Test']))
        ->toThrow(InvalidArgumentException::class, 'Message role is required and must be a non-empty string.');

    expect(fn () => $this->assistantService->writeMessage($threadId, ['role' => 'invalid', 'content' => 'Test']))
        ->toThrow(InvalidArgumentException::class, 'Message role must be one of: user, assistant, system');
});

it('runs a message thread and waits for completion', function () {
    $threadId = 'thread_1234567890abcdef12345678';
    $runId = 'run_1234567890abcdef12345678';
    $messageData = ['assistant_id' => 'asst_1234567890abcdef12345678'];

    $initialRunMock = Mockery::mock(ThreadRunResponse::class);
    $completedRunMock = Mockery::mock(ThreadRunResponse::class);

    // Set up initial run response
    $initialRunMock->threadId = $threadId;
    $initialRunMock->id = $runId;
    $initialRunMock->status = 'in_progress';

    // Set up completed run response
    $completedRunMock->threadId = $threadId;
    $completedRunMock->id = $runId;
    $completedRunMock->status = 'completed';

    $this->repositoryMock->shouldReceive('createThreadRun')
        ->with($threadId, $messageData)
        ->andReturn($initialRunMock);

    $this->repositoryMock->shouldReceive('retrieveThreadRun')
        ->with($threadId, $runId)
        ->andReturn($completedRunMock);

    $response = $this->assistantService->runMessageThread($threadId, $messageData, 30, 5, 0.1);

    expect($response)->toBeTrue();
});

it('throws timeout exception when thread execution exceeds timeout', function () {
    $threadId = 'thread_1234567890abcdef12345678';
    $runId = 'run_1234567890abcdef12345678';
    $messageData = ['assistant_id' => 'asst_1234567890abcdef12345678'];

    $runMock = Mockery::mock(ThreadRunResponse::class);
    $runMock->threadId = $threadId;
    $runMock->id = $runId;
    $runMock->status = 'in_progress';

    $this->repositoryMock->shouldReceive('createThreadRun')
        ->with($threadId, $messageData)
        ->andReturn($runMock);

    $this->repositoryMock->shouldReceive('retrieveThreadRun')
        ->with($threadId, $runId)
        ->andReturn($runMock);

    expect(fn () => $this->assistantService->runMessageThread($threadId, $messageData, 1, 10, 0.1))
        ->toThrow(ThreadExecutionTimeoutException::class, "Thread execution timed out after 1 seconds. Thread ID: {$threadId}, Run ID: {$runId}");
});

it('throws max retry attempts exception when maximum retries exceeded', function () {
    $threadId = 'thread_1234567890abcdef12345678';
    $runId = 'run_1234567890abcdef12345678';
    $messageData = ['assistant_id' => 'asst_1234567890abcdef12345678'];

    $runMock = Mockery::mock(ThreadRunResponse::class);
    $runMock->threadId = $threadId;
    $runMock->id = $runId;
    $runMock->status = 'in_progress';

    $this->repositoryMock->shouldReceive('createThreadRun')
        ->with($threadId, $messageData)
        ->andReturn($runMock);

    $this->repositoryMock->shouldReceive('retrieveThreadRun')
        ->with($threadId, $runId)
        ->andReturn($runMock);

    expect(fn () => $this->assistantService->runMessageThread($threadId, $messageData, 300, 2, 0.1))
        ->toThrow(MaxRetryAttemptsExceededException::class, "Maximum retry attempts (2) exceeded for thread execution. Thread ID: {$threadId}, Run ID: {$runId}");
});

it('throws exception when thread execution fails', function () {
    $threadId = 'thread_1234567890abcdef12345678';
    $runId = 'run_1234567890abcdef12345678';
    $messageData = ['assistant_id' => 'asst_1234567890abcdef12345678'];

    $initialRunMock = Mockery::mock(ThreadRunResponse::class);
    $failedRunMock = Mockery::mock(ThreadRunResponse::class);

    $initialRunMock->threadId = $threadId;
    $initialRunMock->id = $runId;
    $initialRunMock->status = 'in_progress';

    $failedRunMock->threadId = $threadId;
    $failedRunMock->id = $runId;
    $failedRunMock->status = 'failed';

    $this->repositoryMock->shouldReceive('createThreadRun')
        ->with($threadId, $messageData)
        ->andReturn($initialRunMock);

    $this->repositoryMock->shouldReceive('retrieveThreadRun')
        ->with($threadId, $runId)
        ->andReturn($failedRunMock);

    expect(fn () => $this->assistantService->runMessageThread($threadId, $messageData, 30, 5, 0.1))
        ->toThrow(ThreadExecutionTimeoutException::class, "Thread execution failed with status 'failed'. Thread ID: {$threadId}, Run ID: {$runId}");
});

it('lists messages from a thread', function () {
    $threadId = 'thread_1234567890abcdef12345678';

    $messagesData = [
        'data' => [
            [
                'content' => [
                    ['text' => ['value' => 'Test Message']],
                ],
            ],
        ],
    ];

    $this->repositoryMock->shouldReceive('listThreadMessages')
        ->with($threadId)
        ->andReturn($messagesData);

    $message = $this->assistantService->listMessages($threadId);

    expect($message)->toBe('Test Message');
});

it('validates thread ID for list messages', function () {
    expect(fn () => $this->assistantService->listMessages(''))
        ->toThrow(InvalidArgumentException::class, 'Thread ID cannot be empty.');

    expect(fn () => $this->assistantService->listMessages('invalid-format'))
        ->toThrow(InvalidArgumentException::class, 'Thread ID must follow the format: thread_[24 alphanumeric characters].');
});

it('returns empty string when no messages exist', function () {
    $threadId = 'thread_1234567890abcdef12345678';
    $messagesData = ['data' => []];

    $this->repositoryMock->shouldReceive('listThreadMessages')
        ->with($threadId)
        ->andReturn($messagesData);

    $message = $this->assistantService->listMessages($threadId);

    expect($message)->toBe('');
});

it('throws exception for invalid API response structure', function () {
    $threadId = 'thread_1234567890abcdef12345678';

    // Test missing data array
    $this->repositoryMock->shouldReceive('listThreadMessages')
        ->with($threadId)
        ->andReturn(['invalid' => 'structure']);

    expect(fn () => $this->assistantService->listMessages($threadId))
        ->toThrow(ApiResponseValidationException::class, 'Invalid API response structure: missing or invalid data array.');
});

it('throws exception for invalid message structure', function () {
    $threadId = 'thread_1234567890abcdef12345678';

    // Test missing content array
    $this->repositoryMock->shouldReceive('listThreadMessages')
        ->with($threadId)
        ->andReturn(['data' => [['invalid' => 'message']]]);

    expect(fn () => $this->assistantService->listMessages($threadId))
        ->toThrow(ApiResponseValidationException::class, 'Invalid message structure: missing or invalid content array.');
});

it('throws exception for invalid content structure', function () {
    $threadId = 'thread_1234567890abcdef12345678';

    // Test missing text structure
    $this->repositoryMock->shouldReceive('listThreadMessages')
        ->with($threadId)
        ->andReturn(['data' => [['content' => [['invalid' => 'content']]]]]);

    expect(fn () => $this->assistantService->listMessages($threadId))
        ->toThrow(ApiResponseValidationException::class, 'Invalid content structure: missing or invalid text array.');
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

    $payload = ['model' => 'gpt-3.5-turbo', 'prompt' => 'Sample prompt'];

    $this->repositoryMock->shouldReceive('createCompletion')
        ->with($payload)
        ->andReturn($mockResponse);

    $result = $this->assistantService->textCompletion($payload);

    expect($result)->toBe('Second choice');
});

it('returns an empty string when there are no choices', function () {
    $mockResponse = Mockery::mock(CreateResponse::class);

    $reflection = new ReflectionClass(CreateResponse::class);
    $property = $reflection->getProperty('choices');
    $property->setAccessible(true);
    $property->setValue($mockResponse, []);

    $payload = ['model' => 'gpt-3.5-turbo', 'prompt' => 'Sample prompt'];

    $this->repositoryMock->shouldReceive('createCompletion')
        ->with($payload)
        ->andReturn($mockResponse);

    $result = $this->assistantService->textCompletion($payload);

    expect($result)->toBe('');
});

it('validates text completion payload is not empty', function () {
    expect(fn () => $this->assistantService->textCompletion([]))
        ->toThrow(InvalidArgumentException::class, 'Text completion payload cannot be empty.');
});

it('validates model parameter is required for text completion', function () {
    $payload = ['prompt' => 'Test prompt']; // Missing required model parameter

    expect(fn () => $this->assistantService->textCompletion($payload))
        ->toThrow(InvalidArgumentException::class, 'Model parameter is required and must be a non-empty string.');
});

it('validates temperature parameter range for text completion', function () {
    $payload = ['model' => 'gpt-3.5-turbo', 'prompt' => 'Test', 'temperature' => 3.0]; // Invalid temperature

    expect(fn () => $this->assistantService->textCompletion($payload))
        ->toThrow(InvalidArgumentException::class, 'Temperature must be a number between 0 and 2.');
});

it('validates max_tokens parameter for text completion', function () {
    $payload = ['model' => 'gpt-3.5-turbo', 'prompt' => 'Test', 'max_tokens' => -1]; // Invalid max_tokens

    expect(fn () => $this->assistantService->textCompletion($payload))
        ->toThrow(InvalidArgumentException::class, 'Max tokens must be a positive integer.');
});

it('returns the transcription text when the transcribe method is called', function () {
    $mockResponse = Mockery::mock(TranscriptionResponse::class);

    $reflection = new ReflectionClass(TranscriptionResponse::class);
    $property = $reflection->getProperty('text');
    $property->setAccessible(true);
    $property->setValue($mockResponse, 'Transcribed text');

    $fileResource = fopen('data://text/plain;base64,' . base64_encode('test audio content'), 'r');
    $payload = ['file' => $fileResource, 'model' => 'whisper-1'];

    $this->repositoryMock->shouldReceive('transcribeAudio')
        ->with($payload)
        ->andReturn($mockResponse);

    $result = $this->assistantService->transcribeTo($payload);

    expect($result)->toBe('Transcribed text');

    fclose($fileResource);
});

it('validates audio payload is not empty for transcription', function () {
    expect(fn () => $this->assistantService->transcribeTo([]))
        ->toThrow(InvalidArgumentException::class, 'Audio payload cannot be empty.');
});

it('validates file parameter is required for transcription', function () {
    $payload = ['model' => 'whisper-1']; // Missing required file parameter

    expect(fn () => $this->assistantService->transcribeTo($payload))
        ->toThrow(InvalidArgumentException::class, 'File parameter is required for audio processing.');
});

it('validates file parameter is a valid resource for transcription', function () {
    $payload = ['file' => 'not-a-resource', 'model' => 'whisper-1'];

    expect(fn () => $this->assistantService->transcribeTo($payload))
        ->toThrow(FileOperationException::class, 'File parameter must be a valid file resource.');
});

it('validates temperature parameter range for transcription', function () {
    $fileResource = fopen('data://text/plain;base64,' . base64_encode('test'), 'r');
    $payload = ['file' => $fileResource, 'model' => 'whisper-1', 'temperature' => 2.0]; // Invalid temperature for audio

    expect(fn () => $this->assistantService->transcribeTo($payload))
        ->toThrow(InvalidArgumentException::class, 'Temperature must be a number between 0 and 1 for audio processing.');

    fclose($fileResource);
});

it('validates language parameter format for transcription', function () {
    $fileResource = fopen('data://text/plain;base64,' . base64_encode('test'), 'r');
    $payload = ['file' => $fileResource, 'model' => 'whisper-1', 'language' => 'english']; // Invalid language format

    expect(fn () => $this->assistantService->transcribeTo($payload))
        ->toThrow(InvalidArgumentException::class, 'Language must be a 2-character ISO 639-1 language code.');

    fclose($fileResource);
});

it('returns the translation text when the translate method is called', function () {
    $mockResponse = Mockery::mock(TranslationResponse::class);

    $reflection = new ReflectionClass(TranslationResponse::class);
    $property = $reflection->getProperty('text');
    $property->setAccessible(true);
    $property->setValue($mockResponse, 'Translated text');

    $fileResource = fopen('data://text/plain;base64,' . base64_encode('test audio content'), 'r');
    $payload = ['file' => $fileResource, 'model' => 'whisper-1'];

    $this->repositoryMock->shouldReceive('translateAudio')
        ->with($payload)
        ->andReturn($mockResponse);

    $result = $this->assistantService->translateTo($payload);

    expect($result)->toBe('Translated text');

    fclose($fileResource);
});

it('validates audio payload is not empty for translation', function () {
    expect(fn () => $this->assistantService->translateTo([]))
        ->toThrow(InvalidArgumentException::class, 'Audio payload cannot be empty.');
});

it('validates file parameter is required for translation', function () {
    $payload = ['model' => 'whisper-1']; // Missing required file parameter

    expect(fn () => $this->assistantService->translateTo($payload))
        ->toThrow(InvalidArgumentException::class, 'File parameter is required for audio processing.');
});

it('returns the first text from the streamed response when present', function () {
    $completionResponseMock = Mockery::mock(StreamedCompletionResponse::class);
    $completionResponseMock->choices = [(object) ['text' => 'test completion text']];

    // Create a generator function to simulate streamed responses
    $generator = function () use ($completionResponseMock) {
        yield $completionResponseMock;
    };

    $payload = ['model' => 'gpt-3.5-turbo', 'prompt' => 'Test prompt'];

    $this->repositoryMock->shouldReceive('createStreamedCompletion')
        ->with($payload)
        ->andReturn($generator());

    $result = $this->assistantService->streamedCompletion($payload);

    expect($result)->toBe('test completion text');
});

it('returns empty string when no text is present in the streamed response', function () {
    $completionResponseMock = Mockery::mock(StreamedCompletionResponse::class);
    $completionResponseMock->choices = [(object) ['text' => null]];

    $generator = function () use ($completionResponseMock) {
        yield $completionResponseMock;
    };

    $payload = ['model' => 'gpt-3.5-turbo', 'prompt' => 'Test prompt'];

    $this->repositoryMock->shouldReceive('createStreamedCompletion')
        ->with($payload)
        ->andReturn($generator());

    $result = $this->assistantService->streamedCompletion($payload);

    expect($result)->toBe('');
});
