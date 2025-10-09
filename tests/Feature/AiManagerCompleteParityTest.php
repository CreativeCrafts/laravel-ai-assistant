<?php

declare(strict_types=1);

use CreativeCrafts\LaravelAiAssistant\Compat\OpenAI\Responses\Chat\CreateResponse as ChatResponse;
use CreativeCrafts\LaravelAiAssistant\Compat\OpenAI\Responses\Completions\CreateResponse as CompletionResponse;
use CreativeCrafts\LaravelAiAssistant\Contracts\OpenAiRepositoryContract;
use CreativeCrafts\LaravelAiAssistant\DataTransferObjects\CompletionRequest;
use CreativeCrafts\LaravelAiAssistant\Enums\Mode;
use CreativeCrafts\LaravelAiAssistant\Enums\Transport;
use CreativeCrafts\LaravelAiAssistant\Services\AiManager;
use CreativeCrafts\LaravelAiAssistant\Services\AssistantService;
use CreativeCrafts\LaravelAiAssistant\Services\CacheService;

beforeEach(function () {
    $this->repositoryMock = Mockery::mock(OpenAiRepositoryContract::class);
    $this->cacheServiceMock = Mockery::mock(CacheService::class);

    $this->cacheServiceMock->shouldReceive('getCompletion')->andReturn(null)->byDefault();
    $this->cacheServiceMock->shouldReceive('cacheCompletion')->andReturn(true)->byDefault();
    $this->cacheServiceMock->shouldReceive('getResponse')->andReturn(null)->byDefault();
    $this->cacheServiceMock->shouldReceive('cacheResponse')->andReturn(true)->byDefault();

    $this->service = new AssistantService($this->repositoryMock, $this->cacheServiceMock);
    $this->ai = new AiManager($this->service);
});

test('parity: text sync equals legacy textCompletion', function () {
    $payload = ['model' => 'gpt-4o-mini', 'prompt' => 'Hi'];

    $resp = new CompletionResponse();
    $resp->choices = [(object)['text' => 'Answer']];

    $this->repositoryMock->shouldReceive('createCompletion')->times(2)->with($payload)->andReturn($resp);

    $legacy = $this->service->textCompletion($payload);
    $unified = (string) $this->ai->complete(Mode::TEXT, Transport::SYNC, CompletionRequest::fromArray($payload));

    expect($unified)->toBe($legacy);
});

test('parity: chat sync equals legacy chatTextCompletion', function () {
    $payload = ['model' => 'gpt-4o-mini', 'messages' => [ ['role' => 'user', 'content' => 'Hi'] ]];

    $chat = new ChatResponse();
    $msg = new class () {
        public function toArray(): array
        {
        return ['content' => 'Reply'];
        }
    };
    $chat->choices = [(object)['message' => $msg]];

    $this->repositoryMock->shouldReceive('createChatCompletion')->times(2)->with($payload)->andReturn($chat);

    $legacy = $this->service->chatTextCompletion($payload);
    $unified = $this->ai->complete(Mode::CHAT, Transport::SYNC, CompletionRequest::fromArray($payload))->toArray();

    expect($unified)->toBe($legacy);
});

test('parity: text stream equals legacy streamedCompletion', function () {
    $payload = ['model' => 'gpt-4o-mini', 'prompt' => 'Stream me'];

    // Provide SSE stream via ResponsesRepositoryContract so both paths use StreamingService
    $fakeResponses = new CreativeCrafts\LaravelAiAssistant\Tests\Fakes\FakeResponsesRepository();
    app()->instance(CreativeCrafts\LaravelAiAssistant\Contracts\ResponsesRepositoryContract::class, $fakeResponses);

    $fakeResponses->setStream(CreativeCrafts\LaravelAiAssistant\Tests\DataFactories\ResponsesFactory::streamingTextSse(['Hello ', 'world']));

    $legacy = $this->service->streamedCompletion($payload);
    $unified = (string) $this->ai->complete(Mode::TEXT, Transport::STREAM, CompletionRequest::fromArray($payload));

    expect($unified)->toBe($legacy);
});

test('parity: chat stream equals legacy streamedChat', function () {
    $payload = ['model' => 'gpt-4o-mini', 'messages' => [ ['role' => 'user', 'content' => 'Hi'] ]];

    // Provide SSE stream via ResponsesRepositoryContract so both paths use StreamingService
    $fakeResponses = new CreativeCrafts\LaravelAiAssistant\Tests\Fakes\FakeResponsesRepository();
    app()->instance(CreativeCrafts\LaravelAiAssistant\Contracts\ResponsesRepositoryContract::class, $fakeResponses);

    $fakeResponses->setStream(CreativeCrafts\LaravelAiAssistant\Tests\DataFactories\ResponsesFactory::streamingTextSse(['Hello ', 'world']));

    $legacy = $this->service->streamedChat($payload);
    $unified = $this->ai->complete(Mode::CHAT, Transport::STREAM, CompletionRequest::fromArray($payload))->toArray();

    expect($unified)->toBe($legacy);
});
