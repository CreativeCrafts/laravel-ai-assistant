<?php

declare(strict_types=1);

use CreativeCrafts\LaravelAiAssistant\Compat\OpenAI\Responses\Audio\TranscriptionResponse;
use CreativeCrafts\LaravelAiAssistant\Compat\OpenAI\Responses\Audio\TranslationResponse;
use CreativeCrafts\LaravelAiAssistant\Compat\OpenAI\Responses\Chat\CreateResponse as ChatResponse;
use CreativeCrafts\LaravelAiAssistant\Compat\OpenAI\Responses\Completions\CreateResponse;
use CreativeCrafts\LaravelAiAssistant\Compat\OpenAI\Responses\Completions\StreamedCompletionResponse;
use CreativeCrafts\LaravelAiAssistant\Contracts\OpenAiRepositoryContract;
use CreativeCrafts\LaravelAiAssistant\Exceptions\FileOperationException;
use CreativeCrafts\LaravelAiAssistant\Services\AssistantService;
use CreativeCrafts\LaravelAiAssistant\Services\CacheService;

covers(AssistantService::class);

beforeEach(function () {
    $this->repositoryMock = Mockery::mock(OpenAiRepositoryContract::class);
    $this->cacheServiceMock = Mockery::mock(CacheService::class);

    // Default cache behaviors
    $this->cacheServiceMock->shouldReceive('getCompletion')->andReturn(null)->byDefault();
    $this->cacheServiceMock->shouldReceive('cacheCompletion')->andReturn(true)->byDefault();
    $this->cacheServiceMock->shouldReceive('getResponse')->andReturn(null)->byDefault();
    $this->cacheServiceMock->shouldReceive('cacheResponse')->andReturn(true)->byDefault();

    $this->service = new AssistantService($this->repositoryMock, $this->cacheServiceMock);
});

it('performs text completion and returns last choice text', function () {
    $payload = ['model' => 'gpt-3.5-turbo', 'prompt' => 'Hello'];

    $response = Mockery::mock(CreateResponse::class);
    $ref = new ReflectionClass(CreateResponse::class);
    $prop = $ref->getProperty('choices');
    $prop->setAccessible(true);
    $prop->setValue($response, [ (object)['text' => 'First'], (object)['text' => 'Second'] ]);

    $this->repositoryMock->shouldReceive('createCompletion')->once()->with($payload)->andReturn($response);

    expect($this->service->textCompletion($payload))->toBe('Second');
});

it('performs chat completion and returns message array', function () {
    $payload = ['model' => 'gpt-3.5-turbo', 'messages' => [['role' => 'user', 'content' => 'Hi']]];

    $chat = Mockery::mock(ChatResponse::class);
    $msg = new class () {
    public function toArray(): array
    {
    return ['content' => 'Reply'];
    }
    };
    $ref = new ReflectionClass(ChatResponse::class);
    $prop = $ref->getProperty('choices');
    $prop->setAccessible(true);
    $prop->setValue($chat, [ (object)['message' => $msg] ]);

    $this->repositoryMock->shouldReceive('createChatCompletion')->once()->with($payload)->andReturn($chat);

    expect($this->service->chatTextCompletion($payload))->toBe(['content' => 'Reply']);
});

it('performs streamed text completion and returns first chunk text if present', function () {
    $payload = ['model' => 'gpt-3.5-turbo', 'prompt' => 'stream me'];
    $chunk = Mockery::mock(StreamedCompletionResponse::class);
    $chunk->choices = [(object)['text' => 'chunk']];
    $generator = function () use ($chunk) { yield $chunk; };

    $this->repositoryMock->shouldReceive('createStreamedCompletion')->once()->with($payload)->andReturn($generator());

    expect($this->service->streamedCompletion($payload))->toBe('chunk');
});

it('returns empty string when streamed chunk has no text', function () {
    $payload = ['model' => 'gpt-3.5-turbo', 'prompt' => 'stream'];
    $chunk = Mockery::mock(StreamedCompletionResponse::class);
    $chunk->choices = [(object)['text' => null]];
    $generator = function () use ($chunk) { yield $chunk; };

    $this->repositoryMock->shouldReceive('createStreamedCompletion')->once()->with($payload)->andReturn($generator());

    expect($this->service->streamedCompletion($payload))->toBe('');
});

it('transcribes audio and returns text', function () {
    $file = fopen('data://text/plain;base64,' . base64_encode('audio'), 'r');
    $payload = ['file' => $file, 'model' => 'whisper-1'];

    $resp = Mockery::mock(TranscriptionResponse::class);
    $ref = new ReflectionClass(TranscriptionResponse::class);
    $prop = $ref->getProperty('text');
    $prop->setAccessible(true);
    $prop->setValue($resp, 'transcribed');

    $this->repositoryMock->shouldReceive('transcribeAudio')->once()->with($payload)->andReturn($resp);

    expect($this->service->transcribeTo($payload))->toBe('transcribed');
    fclose($file);
});

it('validates audio file resource for transcription', function () {
    expect(fn () => $this->service->transcribeTo(['file' => 'not-resource', 'model' => 'whisper-1']))
        ->toThrow(FileOperationException::class);
});

it('translates audio and returns text', function () {
    $file = fopen('data://text/plain;base64,' . base64_encode('audio'), 'r');
    $payload = ['file' => $file, 'model' => 'whisper-1'];

    $resp = Mockery::mock(TranslationResponse::class);
    $ref = new ReflectionClass(TranslationResponse::class);
    $prop = $ref->getProperty('text');
    $prop->setAccessible(true);
    $prop->setValue($resp, 'translated');

    $this->repositoryMock->shouldReceive('translateAudio')->once()->with($payload)->andReturn($resp);

    expect($this->service->translateTo($payload))->toBe('translated');
    fclose($file);
});
