<?php

declare(strict_types=1);

use CreativeCrafts\LaravelAiAssistant\Facades\Ai;
use CreativeCrafts\LaravelAiAssistant\Contracts\ResponsesRepositoryContract;
use CreativeCrafts\LaravelAiAssistant\Contracts\ConversationsRepositoryContract;
use CreativeCrafts\LaravelAiAssistant\Contracts\FilesRepositoryContract;
use CreativeCrafts\LaravelAiAssistant\Tests\Fakes\FakeResponsesRepository;
use CreativeCrafts\LaravelAiAssistant\Tests\Fakes\FakeConversationsRepository;
use CreativeCrafts\LaravelAiAssistant\Tests\Fakes\FakeFilesRepository;

beforeEach(function () {
    config()->set('ai-assistant.api_key', 'test_key_123');

    $fakeResponses = new FakeResponsesRepository();
    $fakeConversations = new FakeConversationsRepository();
    $fakeFiles = new FakeFilesRepository();

    app()->instance(ResponsesRepositoryContract::class, $fakeResponses);
    app()->instance(ConversationsRepositoryContract::class, $fakeConversations);
    app()->instance(FilesRepositoryContract::class, $fakeFiles);
});

it('maps InputBuilder::message() to Response API input payload', function () {
    /** @var FakeResponsesRepository $responses */
    $responses = app(ResponsesRepositoryContract::class);

    // Trigger SSOT path using message()
    Ai::responses()
        ->instructions('sys')
        ->model('gpt-test')
        ->input()
        ->message('Hello world')
        ->send();

    $payload = $responses->lastPayload;

    expect($payload)->toHaveKey('input')
        ->and(is_array($payload['input']))->toBeTrue()
        ->and(count($payload['input']))->toBeGreaterThan(0)
        ->and($payload['input'][0]['role'] ?? null)->toBe('user')
        ->and($payload['input'][0]['content'][0]['type'] ?? null)->toBe('input_text')
        ->and($payload['input'][0]['content'][0]['text'] ?? null)->toBe('Hello world');
});

it('converts messages[] with string content to Response API input blocks', function () {
    /** @var FakeResponsesRepository $responses */
    $responses = app(ResponsesRepositoryContract::class);

    // Use withMessages() which should be converted by the ResponseApiAdapter
    Ai::responses()
        ->instructions('sys')
        ->model('gpt-test')
        ->withMessages([
            ['role' => 'user', 'content' => 'Hi there'],
        ])
        ->send();

    $payload = $responses->lastPayload;

    expect($payload)->toHaveKey('input')
        ->and($payload['input'][0]['role'] ?? null)->toBe('user')
        ->and($payload['input'][0]['content'][0]['type'] ?? null)->toBe('input_text')
        ->and($payload['input'][0]['content'][0]['text'] ?? null)->toBe('Hi there');
});
