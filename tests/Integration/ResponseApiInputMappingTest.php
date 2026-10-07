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

it('streams the text given with input()->message()', function () {
    /** @var FakeResponsesRepository $responses */
    $responses = app(ResponsesRepositoryContract::class);

    $builder = Ai::responses()->model('gpt-test');
    $builder->input()->message('Hello world');
    iterator_to_array($builder->stream(), false);

    expect($responses->lastPayload['input'] ?? null)->toBe([[
        'role' => 'user',
        'content' => [['type' => 'input_text', 'text' => 'Hello world']],
    ]]);
});

it('streams messages[] the same way send() maps them', function () {
    /** @var FakeResponsesRepository $responses */
    $responses = app(ResponsesRepositoryContract::class);

    $builder = Ai::responses()->instructions('sys')->model('gpt-test')->withMessages([
        ['role' => 'user', 'content' => 'Hi there'],
    ]);
    $builder->send();
    $sent = $responses->lastPayload['input'] ?? null;

    iterator_to_array($builder->stream(), false);

    expect($sent)->not->toBeNull()
        ->and($responses->lastPayload['input'] ?? null)->toBe($sent);
});

it('streams with the builder temperature and output token limit', function () {
    /** @var FakeResponsesRepository $responses */
    $responses = app(ResponsesRepositoryContract::class);

    $builder = Ai::responses()->model('gpt-test')->temperature(0.3)->maxCompletionTokens(120);
    $builder->input()->message('Hello');
    iterator_to_array($builder->stream(), false);

    expect($responses->lastPayload['temperature'] ?? null)->toBe(0.3)
        ->and($responses->lastPayload['max_output_tokens'] ?? null)->toBe(120);
});
