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

beforeEach(function () {
    config()->set('ai-assistant.api_key', 'test_key_123');

    $fakeResponses = new FakeResponsesRepository();
    $fakeConversations = new FakeConversationsRepository();
    $fakeFiles = new FakeFilesRepository();

    app()->instance(ResponsesRepositoryContract::class, $fakeResponses);
    app()->instance(ConversationsRepositoryContract::class, $fakeConversations);
    app()->instance(FilesRepositoryContract::class, $fakeFiles);
});

it('handles missing tool by sending a function_call_output with an error and completing turn', function () {
    $assistant = app(AssistantService::class);
    /** @var FakeResponsesRepository $responses */
    $responses = app(ResponsesRepositoryContract::class);

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

    $outputs = ResponsesFactory::functionCallOutputs($responses->lastPayload);
    expect($outputs)->toHaveKey($callId)
        ->and($outputs[$callId])->toContain('error')
        ->and($responses->lastPayload['conversation'] ?? null)->toBe($convId);
});

it('handles tool throwing exception by capturing error in the function_call_output', function () {
    $assistant = app(AssistantService::class);
    /** @var FakeResponsesRepository $responses */
    $responses = app(ResponsesRepositoryContract::class);

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

    $outputs = ResponsesFactory::functionCallOutputs($responses->lastPayload);
    expect($outputs)->toHaveKey($callId)
        ->and($outputs[$callId])->toContain('error')
        ->and($outputs[$callId])->toContain('exploded');
});

it('supports multiple tool calls combining results', function () {
    $assistant = app(AssistantService::class);
    /** @var FakeResponsesRepository $responses */
    $responses = app(ResponsesRepositoryContract::class);

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

    $outputs = ResponsesFactory::functionCallOutputs($responses->lastPayload);
    expect(array_keys($outputs))->toContain($call1)->toContain($call2)
        ->and($outputs[$call1])->toBe('3');
});
