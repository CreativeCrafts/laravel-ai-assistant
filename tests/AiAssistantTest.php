<?php

declare(strict_types=1);

use CreativeCrafts\LaravelAiAssistant\AiAssistant;
use CreativeCrafts\LaravelAiAssistant\Services\AssistantService;
use CreativeCrafts\LaravelAiAssistant\DataTransferObjects\ChatResponseDto;

covers(AiAssistant::class);

beforeEach(function () {
    $this->clientMock = Mockery::mock(AssistantService::class);
    $this->prompt = 'Test prompt';
    $this->aiAssistant = new AiAssistant($this->prompt);
});

it('can accept a prompt', function () {
    $aiAssistant = AiAssistant::acceptPrompt('New prompt');

    $reflection = new ReflectionClass($aiAssistant);
    $property = $reflection->getProperty('prompt');
    $property->setAccessible(true);

    expect($aiAssistant)->toBeInstanceOf(AiAssistant::class)
        ->and($property->getValue($aiAssistant))->toBe('New prompt');
});

it('can set client service', function () {
    $this->aiAssistant->client($this->clientMock);

    $reflection = new ReflectionClass($this->aiAssistant);
    $property = $reflection->getProperty('client');
    $property->setAccessible(true);

    expect($property->getValue($this->aiAssistant))->toBe($this->clientMock);
});

it('sendChatMessageDto starts a conversation and returns dto', function () {
    $this->aiAssistant->client($this->clientMock);

    $this->clientMock->shouldReceive('createConversation')
        ->once()
        ->andReturn('conv_123');

    $expected = [
        'id' => 'resp_1',
        'status' => 'completed',
        'content' => 'Hello',
        'conversationId' => 'conv_123',
    ];

    $this->clientMock->shouldReceive('sendChatMessage')
        ->once()
        ->with('conv_123', 'Test prompt', Mockery::type('array'))
        ->andReturn($expected);

    $dto = $this->aiAssistant->sendChatMessageDto();

    expect($dto)->toBeInstanceOf(ChatResponseDto::class)
        ->and($dto->toArray()['content'])->toBe('Hello')
        ->and($dto->toArray()['conversation_id'])->toBe('conv_123');
});

it('sendChatMessageDto uses setUserMessage()', function () {
    $this->aiAssistant->client($this->clientMock);

    $this->clientMock->shouldReceive('createConversation')
        ->once()
        ->andReturn('conv_456');

    $expected = [
        'id' => 'r2',
        'status' => 'completed',
        'content' => 'World',
        'conversationId' => 'conv_456',
    ];

    $this->clientMock->shouldReceive('sendChatMessage')
        ->once()
        ->with('conv_456', 'User says', Mockery::type('array'))
        ->andReturn($expected);

    $dto = $this->aiAssistant->setUserMessage('User says')->sendChatMessageDto();

    expect($dto->toArray()['content'])->toBe('World');
});

it('throws when message empty', function () {
    $assistant = new AiAssistant('');
    $assistant->client($this->clientMock);

    $this->clientMock->shouldReceive('createConversation')
        ->once()
        ->andReturn('conv_empty');

    $assistant->sendChatMessageDto();
})->throws(InvalidArgumentException::class);

it('streams chat text', function () {
    $this->aiAssistant->client($this->clientMock);

    $this->clientMock->shouldReceive('createConversation')
        ->once()
        ->andReturn('conv_stream');

    $generator = (function () {
        yield ['type' => 'output_text.delta', 'delta' => 'Hello'];
        yield ['type' => 'output_text.delta', 'delta' => ' World'];
        yield ['type' => 'response.completed'];
    })();

    $this->clientMock->shouldReceive('getStreamingResponse')
        ->once()
        ->with('conv_stream', 'Test prompt', Mockery::type('array'), Mockery::on(fn ($c) => $c === null || is_callable($c)), Mockery::on(fn ($c) => $c === null || is_callable($c)))
        ->andReturn($generator);

    $chunks = iterator_to_array($this->aiAssistant->streamChatText(), false);

    expect(implode('', $chunks))->toBe('Hello World');
});
