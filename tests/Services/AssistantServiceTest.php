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
use CreativeCrafts\LaravelAiAssistant\Services\AiManager;
use CreativeCrafts\LaravelAiAssistant\Enums\Mode;
use CreativeCrafts\LaravelAiAssistant\Enums\Transport;
use CreativeCrafts\LaravelAiAssistant\DataTransferObjects\CompletionRequest;

covers(AssistantService::class);

beforeEach(function () {
    $this->repositoryMock = Mockery::mock(OpenAiRepositoryContract::class);
    $this->cacheServiceMock = Mockery::mock(CacheService::class);

    // Default cache behaviors
    $this->cacheServiceMock->shouldReceive('getCompletion')->andReturn(null)->byDefault();
    $this->cacheServiceMock->shouldReceive('cacheCompletion')->andReturn(true)->byDefault();
    $this->cacheServiceMock->shouldReceive('getResponse')->andReturn(null)->byDefault();
    $this->cacheServiceMock->shouldReceive('cacheResponse')->andReturn(true)->byDefault();

    // Bind the mock repository to the container so StreamingService uses it
    app()->instance(OpenAiRepositoryContract::class, $this->repositoryMock);

    $this->service = new AssistantService($this->repositoryMock, $this->cacheServiceMock);
    $this->aiManager = new AiManager($this->service);
});

it('performs text completion and returns last choice text', function () {
    $payload = ['model' => 'gpt-3.5-turbo', 'prompt' => 'Hello'];

    $response = Mockery::mock(CreateResponse::class);
    $ref = new ReflectionClass(CreateResponse::class);
    $prop = $ref->getProperty('choices');
    $prop->setAccessible(true);
    $prop->setValue($response, [ (object)['text' => 'First'], (object)['text' => 'Second'] ]);

    $this->repositoryMock->shouldReceive('createCompletion')->once()->with($payload)->andReturn($response);

    expect((string) $this->aiManager->complete(Mode::TEXT, Transport::SYNC, CompletionRequest::fromArray($payload)))->toBe('Second');
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

    expect($this->aiManager->complete(Mode::CHAT, Transport::SYNC, CompletionRequest::fromArray($payload))->toArray())->toBe(['content' => 'Reply']);
});

it('performs streamed text completion and returns first chunk text if present', function () {
    $payload = ['model' => 'gpt-3.5-turbo', 'prompt' => 'stream me'];
    $chunk = Mockery::mock(StreamedCompletionResponse::class);
    $chunk->choices = [(object)['text' => 'chunk']];
    $generator = function () use ($chunk) { yield $chunk; };

    $this->repositoryMock->shouldReceive('createStreamedCompletion')->once()->with($payload)->andReturn($generator());

    expect((string) $this->aiManager->complete(Mode::TEXT, Transport::STREAM, CompletionRequest::fromArray($payload)))->toBe('chunk');
});

it('returns empty string when streamed chunk has no text', function () {
    $payload = ['model' => 'gpt-3.5-turbo', 'prompt' => 'stream'];
    $chunk = Mockery::mock(StreamedCompletionResponse::class);
    $chunk->choices = [(object)['text' => null]];
    $generator = function () use ($chunk) { yield $chunk; };

    $this->repositoryMock->shouldReceive('createStreamedCompletion')->once()->with($payload)->andReturn($generator());

    expect((string) $this->aiManager->complete(Mode::TEXT, Transport::STREAM, CompletionRequest::fromArray($payload)))->toBe('');
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

it('emits deprecation once for legacy textCompletion', function () {
    $payload = ['model' => 'gpt-3.5-turbo', 'prompt' => 'Hello'];

    $response = Mockery::mock(CreateResponse::class);
    $ref = new ReflectionClass(CreateResponse::class);
    $prop = $ref->getProperty('choices');
    $prop->setAccessible(true);
    $prop->setValue($response, [ (object)['text' => 'Only'] ]);

    $this->repositoryMock->shouldReceive('createCompletion')->twice()->with($payload)->andReturn($response);

    // Reset deprecation flag
    $classRef = new ReflectionClass(AssistantService::class);
    $depProp = $classRef->getProperty('deprecationOnce');
    $depProp->setAccessible(true);
    $depProp->setValue(null, []);

    $count = 0;
    $prev = set_error_handler(function (int $errno, string $errstr) use (&$count): bool {
        if ($errno === E_USER_DEPRECATED && str_contains($errstr, 'textCompletion')) {
            $count++;
        }
        // prevent default handling
        return true;
    });

    try {
        // Call legacy twice, should emit once
        $this->service->textCompletion($payload);
        $this->service->textCompletion($payload);
    } finally {
        set_error_handler($prev);
    }

    expect($count)->toBe(1);
});
