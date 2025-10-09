<?php

declare(strict_types=1);

use CreativeCrafts\LaravelAiAssistant\OpenAIClientFacade;
use CreativeCrafts\LaravelAiAssistant\Services\AssistantService;
use CreativeCrafts\LaravelAiAssistant\Contracts\ConversationsRepositoryContract;
use CreativeCrafts\LaravelAiAssistant\Contracts\ResponsesRepositoryContract;
use CreativeCrafts\LaravelAiAssistant\Contracts\FilesRepositoryContract;
use CreativeCrafts\LaravelAiAssistant\Tests\Fakes\FakeConversationsRepository;
use CreativeCrafts\LaravelAiAssistant\Tests\Fakes\FakeResponsesRepository;
use CreativeCrafts\LaravelAiAssistant\Tests\Fakes\FakeFilesRepository;

beforeEach(function () {
    config()->set('ai-assistant.api_key', 'test_key_123');

    $fakeConversations = new FakeConversationsRepository();
    $fakeResponses = new FakeResponsesRepository();
    $fakeFiles = new FakeFilesRepository();

    app()->instance(ConversationsRepositoryContract::class, $fakeConversations);
    app()->instance(ResponsesRepositoryContract::class, $fakeResponses);
    app()->instance(FilesRepositoryContract::class, $fakeFiles);

    app()->forgetInstance(OpenAIClientFacade::class);
    app()->singleton(OpenAIClientFacade::class, function ($app) use ($fakeResponses, $fakeConversations, $fakeFiles) {
        return new OpenAIClientFacade(
            $fakeResponses,
            $fakeConversations,
            $fakeFiles,
            $app->make(CreativeCrafts\LaravelAiAssistant\Contracts\OpenAiRepositoryContract::class),
            $app->make(CreativeCrafts\LaravelAiAssistant\Contracts\ResponsesInputItemsRepositoryContract::class),
        );
    });
});

it('supports Conversations CRUD and items lifecycle', function () {
    $assistant = app(AssistantService::class);
    /** @var FakeConversationsRepository $convs */
    $convs = app(ConversationsRepositoryContract::class);

    // Create
    $convId = $assistant->createConversation();
    expect($convs->getConversation($convId)['id'] ?? null)->toBe($convId);

    // Update
    $updated = $convs->updateConversation($convId, ['title' => 'Hello', 'status' => 'active']);
    expect($updated['title'] ?? null)->toBe('Hello');
    expect($updated['status'] ?? null)->toBe('active');

    // Create items
    $item1 = ['id' => 'itm_1', 'type' => 'message', 'role' => 'user', 'content' => [['text' => 'Hi']]];
    $item2 = ['id' => 'itm_2', 'type' => 'message', 'role' => 'assistant', 'content' => [['text' => 'Hello']]];
    $createRes = $convs->createItems($convId, [$item1, $item2]);
    expect($createRes['object'] ?? '')->toBe('list');
    $list = $convs->listItems($convId);
    expect(count($list['data'] ?? []))->toBeGreaterThanOrEqual(2);

    // Delete a single item
    $ok = $convs->deleteItem($convId, 'itm_1');
    expect($ok)->toBeTrue();
    $ids = array_map(fn ($it) => $it['id'] ?? null, $convs->listItems($convId)['data'] ?? []);
    expect($ids)->not->toContain('itm_1');

    // Delete conversation
    expect($convs->deleteConversation($convId))->toBeTrue();
});
