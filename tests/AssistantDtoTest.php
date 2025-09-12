<?php

declare(strict_types=1);

use CreativeCrafts\LaravelAiAssistant\Assistant;
use CreativeCrafts\LaravelAiAssistant\DataTransferObjects\ChatResponseDto;
use CreativeCrafts\LaravelAiAssistant\DataTransferObjects\MessageData;
use CreativeCrafts\LaravelAiAssistant\Services\AssistantService;

it('returns ChatResponseDto from sendChatMessageDto when streaming', function () {
    $dummyResponse = [
        'id' => '',
        'status' => 'unknown',
        'content' => null,
        'raw' => [
            'id' => '',
            'status' => 'unknown',
            'content' => null,
            'result' => 'streamed response dto',
        ],
    ];

    $mockClient = Mockery::mock(AssistantService::class);
    $mockClient->shouldReceive('streamedChat')
        ->once()
        ->with(Mockery::type('array'))
        ->andReturn($dummyResponse);

    $assistant = Assistant::new()->client($mockClient);

    $ref = new ReflectionClass($assistant);
    $prop = $ref->getProperty('modelConfig');
    $prop->setAccessible(true);
    $config = $prop->getValue($assistant);
    $config['stream'] = true;
    $config['messages'] = (new MessageData(
        message: 'This is a test message',
        role: 'user',
    ))->toArray();
    $prop->setValue($assistant, $config);

    $result = $assistant->sendChatMessageDto();
    expect($result)->toBeInstanceOf(ChatResponseDto::class);
});

it('returns ChatResponseDto from sendChatMessageDto when not streaming', function () {
    $dummyResponse = ['result' => 'non-streamed response dto'];

    $mockClient = Mockery::mock(AssistantService::class);
    $mockClient->shouldReceive('chatTextCompletion')
        ->once()
        ->with(Mockery::type('array'))
        ->andReturn($dummyResponse);

    $assistant = Assistant::new()->client($mockClient);

    $ref = new ReflectionClass($assistant);
    $prop = $ref->getProperty('modelConfig');
    $prop->setAccessible(true);
    $config = $prop->getValue($assistant);
    $config['stream'] = false;
    $config['messages'] = (new MessageData(
        message: 'This is a test message',
        role: 'user',
    ))->toArray();
    $prop->setValue($assistant, $config);

    $result = $assistant->sendChatMessageDto();
    expect($result)->toBeInstanceOf(ChatResponseDto::class);
});
