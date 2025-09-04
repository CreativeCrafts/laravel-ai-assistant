<?php

declare(strict_types=1);

use CreativeCrafts\LaravelAiAssistant\Services\AssistantService;
use CreativeCrafts\LaravelAiAssistant\Services\ToolRegistry;
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

it('handles missing tool by inserting tool_result with error and completing turn', function () {
    $assistant = app(AssistantService::class);
    /** @var FakeResponsesRepository $responses */
    $responses = app(ResponsesRepositoryContract::class);
    /** @var FakeConversationsRepository $convs */
    $convs = app(ConversationsRepositoryContract::class);

    $convId = $assistant->createConversation();

    $callId = ResponsesFactory::id('call_');
    $first = ResponsesFactory::withToolCalls($convId, [
        ['id' => $callId, 'name' => 'unknown_tool', 'arguments' => ['x' => 1]],
    ]);
    $second = ResponsesFactory::afterToolResultsFinal($convId, 'done');

    $responses->pushResponse($first);
    $responses->pushResponse($second);

    $result = $assistant->sendChatMessage($convId, 'hi');
    expect($result['messages'] ?? '')->toBe('done');

    $items = $convs->listItems($convId);
    $flat = $items['data'] ?? [];
    $found = false;
    foreach ($flat as $it) {
        if (($it['type'] ?? '') === 'tool_result' && ($it['tool_call_id'] ?? '') === $callId) {
            $found = true;
            $textBlock = $it['content'][0]['text'] ?? '';
            expect((string)$textBlock)->toContain('error');
        }
    }
    expect($found)->toBeTrue();
});

it('handles tool throwing exception by capturing error in tool_result', function () {
    $assistant = app(AssistantService::class);
    /** @var FakeResponsesRepository $responses */
    $responses = app(ResponsesRepositoryContract::class);
    /** @var FakeConversationsRepository $convs */
    $convs = app(ConversationsRepositoryContract::class);

    // register a tool that throws
    $tools = app(ToolRegistry::class);
    $tools->register('boom', function (array $args) {
        throw new RuntimeException('exploded');
    }, [
        'type' => 'function',
        'function' => ['name' => 'boom', 'parameters' => ['type' => 'object']]
    ]);

    $convId = $assistant->createConversation();

    $callId = ResponsesFactory::id('call_');
    $first = ResponsesFactory::withToolCalls($convId, [
        ['id' => $callId, 'name' => 'boom', 'arguments' => []],
    ]);
    $second = ResponsesFactory::afterToolResultsFinal($convId, 'ok');

    $responses->pushResponse($first);
    $responses->pushResponse($second);

    $assistant->sendChatMessage($convId, 'test');

    $items = $convs->listItems($convId);
    $flat = $items['data'] ?? [];
    $found = false;
    foreach ($flat as $it) {
        if (($it['type'] ?? '') === 'tool_result' && ($it['tool_call_id'] ?? '') === $callId) {
            $found = true;
            $txt = (string)($it['content'][0]['text'] ?? '');
            expect($txt)->toContain('error');
            expect($txt)->toContain('exploded');
        }
    }
    expect($found)->toBeTrue();
});

it('supports multiple tool calls combining results', function () {
    $assistant = app(AssistantService::class);
    /** @var FakeResponsesRepository $responses */
    $responses = app(ResponsesRepositoryContract::class);
    /** @var FakeConversationsRepository $convs */
    $convs = app(ConversationsRepositoryContract::class);

    $tools = app(ToolRegistry::class);
    $tools->register('sum', fn (array $a) => array_sum($a['numbers'] ?? []), [
        'type' => 'function',
        'function' => ['name' => 'sum', 'parameters' => ['type' => 'object']]
    ]);

    $convId = $assistant->createConversation();

    $call1 = ResponsesFactory::id('call_');
    $call2 = ResponsesFactory::id('call_');

    $first = ResponsesFactory::withToolCalls($convId, [
        ['id' => $call1, 'name' => 'sum', 'arguments' => ['numbers' => [1,2]]],
        ['id' => $call2, 'name' => 'missing_tool', 'arguments' => []],
    ]);
    $second = ResponsesFactory::afterToolResultsFinal($convId, 'final');

    $responses->pushResponse($first);
    $responses->pushResponse($second);

    $assistant->sendChatMessage($convId, 'go');

    $items = $convs->listItems($convId)['data'] ?? [];
    $ids = array_map(fn ($it) => $it['tool_call_id'] ?? null, array_filter($items, fn ($it) => ($it['type'] ?? '') === 'tool_result'));
    expect($ids)->toContain($call1)->toContain($call2);
});
