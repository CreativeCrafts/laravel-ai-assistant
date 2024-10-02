<?php

declare(strict_types=1);


use CreativeCrafts\LaravelAiAssistant\DataTransferObjects\NewAssistantResponseData;
use OpenAI\Responses\Assistants\AssistantResponse;

covers(NewAssistantResponseData::class);

it('can be instantiated with an AssistantResponse', function () {
    $assistantResponse = Mockery::mock(AssistantResponse::class)->makePartial();
    $assistantResponse->id = 'assistant_123';

    $newAssistantResponse = new NewAssistantResponseData($assistantResponse);

    expect($newAssistantResponse)
        ->toBeInstanceOf(NewAssistantResponseData::class)
        ->and($newAssistantResponse->assistantId())
        ->toBe('assistant_123')
        ->and($newAssistantResponse->assistant())
        ->toBe($assistantResponse);
});

it('returns the assistantId from the assistantResponse', function () {
    $assistantResponse = Mockery::mock(AssistantResponse::class)->makePartial();
    $assistantResponse->id = 'assistant_456';

    $newAssistantResponse = new NewAssistantResponseData($assistantResponse);

    expect($newAssistantResponse->assistantId())->toBe('assistant_456');
});

it('returns the AssistantResponse instance', function () {
    $assistantResponse = Mockery::mock(AssistantResponse::class);

    $newAssistantResponse = new NewAssistantResponseData($assistantResponse);

    expect($newAssistantResponse->assistant())->toBe($assistantResponse);
});
