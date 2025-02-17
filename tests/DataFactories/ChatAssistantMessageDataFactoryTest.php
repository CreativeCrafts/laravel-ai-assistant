<?php

declare(strict_types=1);

use CreativeCrafts\LaravelAiAssistant\Contracts\ChatAssistantMessageDataContract;
use CreativeCrafts\LaravelAiAssistant\DataFactories\ChatAssistantMessageDataFactory;

covers(ChatAssistantMessageDataFactory::class);

describe('ChatAssistantMessageDataFactory::buildChatAssistantMessageData', function () {
    it('builds a ChatAssistantMessageData object with valid parameters', function () {
        $content = 'Test content';
        $refusal = 'Optional refusal';
        $name = 'Assistant Name';
        $audio = ['id' => 'audio123'];
        $toolCalls = [
            'id' => 'toolCall1',
            'type' => 'type1',
            'function' => [
                'arguments' => 'some arguments',
                'name' => 'functionName',
            ],
        ];

        $result = ChatAssistantMessageDataFactory::buildChatAssistantMessageData($content, $refusal, $name, $audio, $toolCalls);

        expect($result)->toBeInstanceOf(ChatAssistantMessageDataContract::class)
            ->and($result->toArray()['role'])->toBe('assistant')
            ->and($result->toArray()['content'])->toBe($content)
            ->and($result->toArray()['refusal'])->toBe($refusal)
            ->and($result->toArray()['name'])->toBe($name)
            ->and($result->toArray()['audio'])->toBe($audio)
            ->and($result->toArray()['toolCalls'])->toBe($toolCalls);
    });

    it('throws an exception if audio array is provided without an "id" key', function () {
        $content = 'Test content';
        $refusal = 'Optional refusal';
        $name = 'Assistant Name';
        $audio = ['other_key' => 'value'];
        $toolCalls = null;

        expect(fn () => ChatAssistantMessageDataFactory::buildChatAssistantMessageData($content, $refusal, $name, $audio, $toolCalls))
            ->toThrow(InvalidArgumentException::class, 'Id for the previous audio response from the model is required');
    });

    it('throws an exception if toolCalls array is missing required fields', function () {
        $content = 'Test content';
        $refusal = null;
        $name = 'Assistant Name';
        $audio = null;
        $toolCalls = [
            'id'   => 'toolCall1',
            'type' => 'type1',
        ];

        expect(fn () => ChatAssistantMessageDataFactory::buildChatAssistantMessageData($content, $refusal, $name, $audio, $toolCalls))
            ->toThrow(InvalidArgumentException::class, 'Missing required fields for tool call');
    });

    it('throws an exception if toolCalls function is missing required fields', function () {
        $content = 'Test content';
        $refusal = null;
        $name = 'Assistant Name';
        $audio = null;
        $toolCalls = [
            'id'   => 'toolCall1',
            'type' => 'type1',
            'function' => [
                'arguments' => 'some arguments',
            ],
        ];

        expect(fn () => ChatAssistantMessageDataFactory::buildChatAssistantMessageData($content, $refusal, $name, $audio, $toolCalls))
            ->toThrow(InvalidArgumentException::class, 'Missing required fields for tool call function');
    });
});
