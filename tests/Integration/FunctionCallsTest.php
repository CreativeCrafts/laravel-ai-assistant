<?php

declare(strict_types=1);

use CreativeCrafts\LaravelAiAssistant\OpenAIClientFacade;
use CreativeCrafts\LaravelAiAssistant\Services\AssistantService;
use CreativeCrafts\LaravelAiAssistant\Services\ToolRegistry;
use CreativeCrafts\LaravelAiAssistant\Contracts\ResponsesRepositoryContract;
use CreativeCrafts\LaravelAiAssistant\Contracts\ConversationsRepositoryContract;
use CreativeCrafts\LaravelAiAssistant\Contracts\FilesRepositoryContract;
use CreativeCrafts\LaravelAiAssistant\Tests\Fakes\FakeResponsesRepository;
use CreativeCrafts\LaravelAiAssistant\Tests\Fakes\FakeConversationsRepository;
use CreativeCrafts\LaravelAiAssistant\Tests\Fakes\FakeFilesRepository;
use CreativeCrafts\LaravelAiAssistant\Tests\DataFactories\ResponsesFactory;

beforeEach(function () {
    // Ensure API key present
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
            $app->make(CreativeCrafts\LaravelAiAssistant\Contracts\ResponsesInputItemsRepositoryContract::class),
        );
    });
});

it('executes multiple tool calls in a single turn and continues to final text', function () {
    $assistant = app(AssistantService::class);
    $responses = app(ResponsesRepositoryContract::class);
    $convs = app(ConversationsRepositoryContract::class);

    // Register two tools
    $registry = app(ToolRegistry::class);
    $registry->register('sum', fn (array $args) => array_sum($args['numbers'] ?? []));
    $registry->register('multiply', function (array $args) {
        $nums = $args['numbers'] ?? [];
        return array_reduce($nums, fn ($c, $n) => $c * $n, 1);
    });

    $convId = $assistant->createConversation();

    $callId1 = ResponsesFactory::id('call_');
    $callId2 = ResponsesFactory::id('call_');

    $first = ResponsesFactory::withToolCalls($convId, [
        ['id' => $callId1, 'name' => 'sum', 'arguments' => ['numbers' => [2,3]]],
        ['id' => $callId2, 'name' => 'multiply', 'arguments' => ['numbers' => [2,3]]],
    ]);

    $second = ResponsesFactory::afterToolResultsFinal($convId, 'sum=5, product=6');

    $responses->pushResponse($first);
    $responses->pushResponse($second);

    $res = $assistant->sendChatMessage($convId, 'compute');

    expect($res['messages'] ?? '')->toBe('sum=5, product=6');

    // Verify both tool_result items saved into conversation
    $list = $convs->listItems($convId);
    $ids = array_map(fn ($it) => $it['tool_call_id'] ?? null, $list['data'] ?? []);
    expect($ids)->toContain($callId1, $callId2);
});
