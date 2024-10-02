<?php

declare(strict_types=1);


use CreativeCrafts\LaravelAiAssistant\DataTransferObjects\AssistantMessageData;

covers(AssistantMessageData::class);

it('can be instantiated with a message and default role', function () {
    $message = 'Hello, world!';
    $data = new AssistantMessageData($message);

    expect($data)
        ->toBeInstanceOf(AssistantMessageData::class)
        ->and($data->toArray())
        ->toMatchArray([
            'role' => 'user',
            'content' => 'Hello, world!',
        ]);
});

it('can be instantiated with a message and custom role', function () {
    $message = 'Hello, world!';
    $role = 'assistant';
    $data = new AssistantMessageData($message, $role);

    expect($data)
        ->toBeInstanceOf(AssistantMessageData::class)
        ->and($data->toArray())
        ->toMatchArray([
            'role' => 'assistant',
            'content' => 'Hello, world!',
        ]);
});

it('returns an array with role and content', function () {
    $message = 'Testing message';
    $data = new AssistantMessageData($message);

    $array = $data->toArray();

    expect($array)
        ->toBeArray()
        ->and($array['role'])
        ->toBe('user')
        ->and($array['content'])
        ->toBe('Testing message');
});
