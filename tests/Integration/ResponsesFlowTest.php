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
    // Ensure API key so Client can resolve if needed
    config()->set('ai-assistant.api_key', 'test_key_123');

    // Create shared fake instances
    $fakeResponses = new FakeResponsesRepository();
    $fakeConversations = new FakeConversationsRepository();
    $fakeFiles = new FakeFilesRepository();

    // Register as singletons/instances so all resolutions share same objects
    app()->instance(ResponsesRepositoryContract::class, $fakeResponses);
    app()->instance(ConversationsRepositoryContract::class, $fakeConversations);
    app()->instance(FilesRepositoryContract::class, $fakeFiles);

    // Recreate facade singleton to point to fakes
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

it('handles one-turn sync text', function () {
    $assistant = app(AssistantService::class);
    $responses = app(ResponsesRepositoryContract::class);
    $convs = app(ConversationsRepositoryContract::class);

    // Create conversation and queue a sync response
    $convId = $assistant->createConversation();
    $respPayload = ResponsesFactory::syncTextResponse($convId, 'Hello there');
    expect($convs->getConversation($convId)['id'] ?? null)->toBe($convId);

    expect($responses)->toBeInstanceOf(FakeResponsesRepository::class);
    $responses->pushResponse($respPayload);

    $result = $assistant->sendChatMessage($convId, 'Hi');

    expect($result['conversationId'] ?? null)->toBe($convId);
    expect($result['messages'] ?? '')->toBe('Hello there');
    expect($result['toolCalls'] ?? [])->toBeArray()->toBeEmpty();
});

it('supports multi-turn tool call followed by tool_result continuation', function () {
    $assistant = app(AssistantService::class);
    $responses = app(ResponsesRepositoryContract::class);
    $convs = app(ConversationsRepositoryContract::class);

    // Register a tool in the registry
    $tools = app(ToolRegistry::class);
    $tools->register('sum', function (array $args) {
        $nums = $args['numbers'] ?? [];
        return array_sum($nums);
    }, [
        'type' => 'function',
        'function' => [
            'name' => 'sum',
            'parameters' => [
                'type' => 'object',
                'properties' => [ 'numbers' => ['type' => 'array', 'items' => ['type' => 'number']] ],
                'required' => ['numbers'],
            ],
        ],
    ]);

    $convId = $assistant->createConversation();

    // Initial response asks for tool call
    $toolCallId = ResponsesFactory::id('call_');
    $first = ResponsesFactory::withToolCalls($convId, [
        ['id' => $toolCallId, 'name' => 'sum', 'arguments' => ['numbers' => [1,2,3]]],
    ]);

    // After tool results, final text
    $second = ResponsesFactory::afterToolResultsFinal($convId, 'The sum is 6');

    $responses->pushResponse($first);
    $responses->pushResponse($second);

    $result = $assistant->sendChatMessage($convId, 'Please sum numbers');

    expect($result['messages'] ?? '')->toBe('The sum is 6');

    // Conversation should contain tool_result item
    $items = $convs->listItems($convId);
    $flat = $items['data'] ?? [];
    $hasToolResult = false;
    foreach ($flat as $it) {
        if (($it['type'] ?? '') === 'tool_result' && ($it['tool_call_id'] ?? '') === $toolCallId) {
            $hasToolResult = true;
            break;
        }
    }
    expect($hasToolResult)->toBeTrue();
});

it('streams responses and yields accumulated deltas', function () {
    $assistant = app(AssistantService::class);
    /** @var FakeResponsesRepository $responses */
    $responses = app(ResponsesRepositoryContract::class);

    $convId = $assistant->createConversation();

    // Configure streaming SSE
    $responses->setStream(ResponsesFactory::streamingTextSse(['Hel', 'lo']));

    $events = iterator_to_array($assistant->streamTurn(
        $convId,
        instructions: null,
        model: null,
        tools: [],
        inputItems: [[
                'role' => 'user',
                'content' => [['type' => 'input_text', 'text' => 'hi']],
            ]]
    ));

    // Find last delta and final event
    $deltaEvents = array_values(array_filter($events, fn ($e) => ($e['type'] ?? '') === 'response.output_text.delta'));
    $lastDelta = end($deltaEvents);
    expect($lastDelta['data']['accumulated'] ?? '')->toBe('Hello');

    $final = end($events);
    expect($final['type'] ?? '')->toBe('response.completed');
});

it('auto-enables file_search when file_ids are provided', function () {
    $assistant = app(AssistantService::class);
    /** @var FakeResponsesRepository $responses */
    $responses = app(ResponsesRepositoryContract::class);

    $convId = $assistant->createConversation();

    // Queue a trivial final response
    $responses->pushResponse(ResponsesFactory::syncTextResponse($convId, 'ok'));

    $assistant->sendChatMessage($convId, 'read attached', [
        'file_ids' => ['file_123'],
        'tools' => [],
    ]);

    $payload = $responses->lastPayload;
    $tools = $payload['tools'] ?? [];
    $hasFileSearch = false;
    foreach ($tools as $t) {
        if (($t['type'] ?? null) === 'file_search') {
        $hasFileSearch = true;
        break;
        }
    }
    expect($hasFileSearch)->toBeTrue();
});
