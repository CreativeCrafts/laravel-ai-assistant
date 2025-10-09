<?php

declare(strict_types=1);

use CreativeCrafts\LaravelAiAssistant\Compat\OpenAI\Responses\Audio\TranscriptionResponse;
use CreativeCrafts\LaravelAiAssistant\Compat\OpenAI\Responses\Audio\TranslationResponse;
use CreativeCrafts\LaravelAiAssistant\Compat\OpenAI\Responses\Chat\CreateResponse as ChatResponse;
use CreativeCrafts\LaravelAiAssistant\Compat\OpenAI\Responses\Completions\CreateResponse as CompletionResponse;
use CreativeCrafts\LaravelAiAssistant\Contracts\OpenAiRepositoryContract;
use CreativeCrafts\LaravelAiAssistant\Exceptions\FileOperationException;
use CreativeCrafts\LaravelAiAssistant\Services\AssistantService;
use CreativeCrafts\LaravelAiAssistant\Services\CacheService;
use CreativeCrafts\LaravelAiAssistant\Services\AiManager;
use CreativeCrafts\LaravelAiAssistant\Enums\Mode;
use CreativeCrafts\LaravelAiAssistant\Enums\Transport;
use CreativeCrafts\LaravelAiAssistant\DataTransferObjects\CompletionRequest;
use CreativeCrafts\LaravelAiAssistant\Tests\DataFactories\ApiPayloadFactory;

/**
 * Integration tests for modern API operations using mocked responses.
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
        $this->aiManager = new AiManager($this->assistantService);
});

afterEach(function () {
    Mockery::close();
});

test('text completion flow', function () {
    $payload = ApiPayloadFactory::completionPayload();
    $expectedText = 'Generated completion text';
    $mockResponse = Mockery::mock(CompletionResponse::class);

    $reflection = new ReflectionClass(CompletionResponse::class);
    $property = $reflection->getProperty('choices');
    $property->setAccessible(true);
    $property->setValue($mockResponse, [
        (object)['text' => $expectedText]
    ]);

    $this->repositoryMock->shouldReceive('createCompletion')->once()->with($payload)->andReturn($mockResponse);

    $result = (string) $this->aiManager->complete(Mode::TEXT, Transport::SYNC, CompletionRequest::fromArray($payload));

    expect($result)->toBe($expectedText);
});

test('text completion with caching', function () {
    $payload = ApiPayloadFactory::completionPayload(['temperature' => 0.0]);
    $cachedResult = 'Cached completion text';

    $this->cacheServiceMock->shouldReceive('getCompletion')->once()->andReturn($cachedResult);

    $result = (string) $this->aiManager->complete(Mode::TEXT, Transport::SYNC, CompletionRequest::fromArray($payload));

    expect($result)->toBe($cachedResult);
});

test('chat completion flow', function () {
    $payload = ApiPayloadFactory::chatCompletionPayload();
    $expectedContent = 'Chat response content';
    $mockResponse = Mockery::mock(ChatResponse::class);

    $mockMessage = new class () {
        private $content = 'Chat response content';
        public function toArray(): array
        {
        return ['content' => $this->content];
        }
    };

    $reflection = new ReflectionClass(ChatResponse::class);
    $property = $reflection->getProperty('choices');
    $property->setAccessible(true);
    $property->setValue($mockResponse, [(object)['message' => $mockMessage]]);

    $this->repositoryMock->shouldReceive('createChatCompletion')->once()->with($payload)->andReturn($mockResponse);

    $result = $this->aiManager->complete(Mode::CHAT, Transport::SYNC, CompletionRequest::fromArray($payload))->toArray();

    expect($result)->toBe(['content' => $expectedContent]);
});

test('audio transcription flow', function () {
    $file = ApiPayloadFactory::createTestAudioFile();
    $payload = ApiPayloadFactory::audioPayload($file);
    $expectedText = 'Transcribed audio content';
    $mockResponse = Mockery::mock(TranscriptionResponse::class);

    $reflection = new ReflectionClass(TranscriptionResponse::class);
    $property = $reflection->getProperty('text');
    $property->setAccessible(true);
    $property->setValue($mockResponse, $expectedText);

    $this->repositoryMock->shouldReceive('transcribeAudio')->once()->with($payload)->andReturn($mockResponse);

    $result = $this->assistantService->transcribeTo($payload);

    expect($result)->toBe($expectedText);

    fclose($file);
});

test('audio translation flow', function () {
    $file = ApiPayloadFactory::createTestAudioFile();
    $payload = ApiPayloadFactory::audioPayload($file);
    $expectedText = 'Translated audio content';
    $mockResponse = Mockery::mock(TranslationResponse::class);

    $reflection = new ReflectionClass(TranslationResponse::class);
    $property = $reflection->getProperty('text');
    $property->setAccessible(true);
    $property->setValue($mockResponse, $expectedText);

    $this->repositoryMock->shouldReceive('translateAudio')->once()->with($payload)->andReturn($mockResponse);

    $result = $this->assistantService->translateTo($payload);

    expect($result)->toBe($expectedText);

    fclose($file);
});

test('audio processing with invalid file', function () {
    $invalidPayload = ['file' => 'not-a-resource', 'model' => 'whisper-1'];

    expect(fn () => $this->assistantService->transcribeTo($invalidPayload))
        ->toThrow(FileOperationException::class, 'File parameter must be a valid file resource.');
});

test('validation error scenarios', function () {
    expect(fn () => $this->aiManager->complete(Mode::TEXT, Transport::SYNC, CompletionRequest::fromArray([])))
        ->toThrow(InvalidArgumentException::class, 'Text completion payload cannot be empty.');
});
