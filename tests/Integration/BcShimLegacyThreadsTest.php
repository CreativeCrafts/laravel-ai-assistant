<?php

declare(strict_types=1);

use CreativeCrafts\LaravelAiAssistant\Services\AssistantService;
use CreativeCrafts\LaravelAiAssistant\Services\ThreadsToConversationsMapper;
use CreativeCrafts\LaravelAiAssistant\Contracts\ResponsesRepositoryContract;
use CreativeCrafts\LaravelAiAssistant\Contracts\ConversationsRepositoryContract;
use CreativeCrafts\LaravelAiAssistant\Contracts\FilesRepositoryContract;
use CreativeCrafts\LaravelAiAssistant\Tests\Fakes\FakeResponsesRepository;
use CreativeCrafts\LaravelAiAssistant\Tests\Fakes\FakeConversationsRepository;
use CreativeCrafts\LaravelAiAssistant\Tests\Fakes\FakeFilesRepository;
use CreativeCrafts\LaravelAiAssistant\Tests\DataFactories\ResponsesFactory;
use CreativeCrafts\LaravelAiAssistant\OpenAIClientFacade;

beforeEach(function () {
    config()->set('ai-assistant.api_key', 'test_key_123');

    $fakeResponses = new FakeResponsesRepository();
    $fakeConversations = new FakeConversationsRepository();
    $fakeFiles = new FakeFilesRepository();

    app()->instance(ResponsesRepositoryContract::class, $fakeResponses);
    app()->instance(ConversationsRepositoryContract::class, $fakeConversations);
    app()->instance(FilesRepositoryContract::class, $fakeFiles);

    app()->forgetInstance(OpenAIClientFacade::class);
    app()->singleton(OpenAIClientFacade::class, function ($app) use ($fakeResponses, $fakeConversations, $fakeFiles) {
        return new OpenAIClientFacade(
            $fakeResponses,
            $fakeConversations,
            $fakeFiles,
            $app->make(CreativeCrafts\LaravelAiAssistant\Contracts\OpenAiRepositoryContract::class),
        );
    });
});

it('maps createThread to a conversation and persists mapping', function () {
    $assistant = app(AssistantService::class);
    $mapper = app(ThreadsToConversationsMapper::class);

    $thread = $assistant->createThread(['metadata' => ['a' => 1]]);
    $threadId = $thread->id;

    $convId = $mapper->get($threadId);
    expect($convId)->not->toBeNull()->toBeString();
});

it('writeMessage and runMessageThread use the mapped conversation id', function () {
    $assistant = app(AssistantService::class);
    /** @var FakeResponsesRepository $responses */
    $responses = app(ResponsesRepositoryContract::class);
    $mapper = app(ThreadsToConversationsMapper::class);

    // Create mapping
    $thread = $assistant->createThread(['metadata' => []]);
    $threadId = $thread->id;
    $convId = $mapper->get($threadId);

    // Queue responses for subsequent createResponse calls
    $responses->pushResponse(ResponsesFactory::syncTextResponse($convId, 'ok1'));
    $responses->pushResponse(ResponsesFactory::syncTextResponse($convId, 'ok2'));

    // writeMessage should route to mapped conversation
    $assistant->writeMessage($threadId, ['role' => 'user', 'content' => 'hello']);
    expect($responses->lastPayload['conversation'] ?? null)->toBe($convId);

    // runMessageThread should also route to mapped conversation
    $assistant->runMessageThread($threadId, ['model' => 'gpt-test']);
    expect($responses->lastPayload['conversation'] ?? null)->toBe($convId);
});

it('listMessages reads from conversation items via mapping', function () {
    $assistant = app(AssistantService::class);
    /** @var FakeConversationsRepository $convs */
    $convs = app(ConversationsRepositoryContract::class);
    $mapper = app(ThreadsToConversationsMapper::class);

    $thread = $assistant->createThread(['metadata' => []]);
    $threadId = $thread->id;
    $convId = $mapper->get($threadId);

    // Preload conversation with a message item
    $convs->createItems($convId, [[
        'type' => 'message',
        'role' => 'assistant',
        'content' => [ ['type' => 'text', 'text' => 'Legacy OK'] ],
    ]]);

    $text = $assistant->listMessages($threadId);
    expect($text)->toBe('Legacy OK');
});
