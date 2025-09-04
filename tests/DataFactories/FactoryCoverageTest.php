<?php

declare(strict_types=1);

use CreativeCrafts\LaravelAiAssistant\Tests\DataFactories\ConversationItemFactory;
use CreativeCrafts\LaravelAiAssistant\Tests\DataFactories\ToolInvocationFactory;

it('can create conversation item data using ConversationItemFactory', function () {
    $item = ConversationItemFactory::create();

    expect($item)->toBeArray()
        ->and($item)->toHaveKeys(['id', 'conversation_id', 'role', 'content', 'attachments'])
        ->and($item['role'])->toBeIn(['user', 'assistant', 'system'])
        ->and($item['content'])->toBeArray();
});

it('can create user message using ConversationItemFactory', function () {
    $item = ConversationItemFactory::userMessage('Test message');

    expect($item)->toBeArray()
        ->and($item['role'])->toBe('user')
        ->and($item['content']['text'])->toBe('Test message')
        ->and($item['attachments'])->toBeNull();
});

it('can create assistant message using ConversationItemFactory', function () {
    $item = ConversationItemFactory::assistantMessage('Assistant response');

    expect($item)->toBeArray()
        ->and($item['role'])->toBe('assistant')
        ->and($item['content']['text'])->toBe('Assistant response');
});

it('can create multiple conversation items', function () {
    $items = ConversationItemFactory::createMultiple(3);

    expect($items)->toHaveCount(3)
        ->and($items)->each->toBeArray();
});

it('can create tool invocation data using ToolInvocationFactory', function () {
    $invocation = ToolInvocationFactory::create();

    expect($invocation)->toBeArray()
        ->and($invocation)->toHaveKeys(['id', 'response_id', 'name', 'arguments', 'state', 'result_summary'])
        ->and($invocation['state'])->toBeIn(['pending', 'running', 'completed', 'failed', 'cancelled'])
        ->and($invocation['arguments'])->toBeArray();
});

it('can create pending tool invocation', function () {
    $invocation = ToolInvocationFactory::pending('test_tool', ['param' => 'value']);

    expect($invocation)->toBeArray()
        ->and($invocation['name'])->toBe('test_tool')
        ->and($invocation['state'])->toBe('pending')
        ->and($invocation['arguments'])->toEqual(['param' => 'value'])
        ->and($invocation['result_summary'])->toBeNull();
});

it('can create completed tool invocation', function () {
    $invocation = ToolInvocationFactory::completed('test_tool', ['success' => true]);

    expect($invocation)->toBeArray()
        ->and($invocation['name'])->toBe('test_tool')
        ->and($invocation['state'])->toBe('completed')
        ->and($invocation['result_summary'])->toBeArray();
});

it('can create failed tool invocation', function () {
    $invocation = ToolInvocationFactory::failed('test_tool', 'Custom error message');

    expect($invocation)->toBeArray()
        ->and($invocation['name'])->toBe('test_tool')
        ->and($invocation['state'])->toBe('failed')
        ->and($invocation['result_summary']['success'])->toBeFalse()
        ->and($invocation['result_summary']['error'])->toBe('Custom error message');
});

it('can create web search tool invocation', function () {
    $invocation = ToolInvocationFactory::webSearch('Laravel AI Assistant');

    expect($invocation)->toBeArray()
        ->and($invocation['name'])->toBe('web_search')
        ->and($invocation['arguments']['query'])->toBe('Laravel AI Assistant')
        ->and($invocation['state'])->toBe('completed');
});

it('can create code execution tool invocation', function () {
    $invocation = ToolInvocationFactory::codeExecution('print("hello")', 'python');

    expect($invocation)->toBeArray()
        ->and($invocation['name'])->toBe('code_interpreter')
        ->and($invocation['arguments']['code'])->toBe('print("hello")')
        ->and($invocation['arguments']['language'])->toBe('python')
        ->and($invocation['state'])->toBe('completed');
});

it('can create multiple tool invocations', function () {
    $invocations = ToolInvocationFactory::createMultiple(3);

    expect($invocations)->toHaveCount(3)
        ->and($invocations)->each->toBeArray();
});
