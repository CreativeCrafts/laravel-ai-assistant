<?php

declare(strict_types=1);

use CreativeCrafts\LaravelAiAssistant\DataTransferObjects\ChatCompletionData;

covers(ChatCompletionData::class);

describe('ChatCompletionData', function () {
    it('converts to array correctly when all properties are provided', function () {
        $chatData = new ChatCompletionData(
            model: 'gpt-3.5-turbo',
            message: ['Hello', 'World'],
            temperature: 0.8,
            store: true,
            reasoningEffort: 'medium',
            metadata: ['key' => 'value'],
            maxCompletionTokens: 150,
            numberOfCompletionChoices: 2,
            outputTypes: ['text', 'audio'],
            audio: ['format' => 'mp3'],
            responseFormat: ['type' => 'json_object'],
            stopSequences: ['STOP'],
            stream: true,
            streamOptions: ['option' => 'value'],
            topP: 0.9
        );

        $expected = array_merge(
            [
                'model' => 'gpt-3.5-turbo',
                'messages' => ['Hello', 'World'],
                'temperature' => 0.8,
                'store' => true,
                'n' => 2,
                'stream' => true,
                'metadata' => ['key' => 'value'],
                'max_completion_tokens' => 150,
                'modalities' => ['text', 'audio'],
                'audio' => ['format' => 'mp3'],
                'stop' => ['STOP'],
                'stream_options' => ['option' => 'value'],
                'reasoning_effort' => 'medium',
                'response_format' => ['type' => 'json_object'],
            ],
        );
        expect($chatData->toArray())->toEqual($expected);
    });

    it('converts to array correctly when optional properties are null', function () {
        $chatData = new ChatCompletionData(
            model: 'gpt-3.5-turbo',
            message: ['Hi'],
            temperature: null,
            store: null,
            reasoningEffort: null,
            metadata: null,
            maxCompletionTokens: null,
            numberOfCompletionChoices: null,
            outputTypes: null,
            audio: null,
            stopSequences: null,
            stream: false,
            streamOptions: null,
            topP: 1
        );

        $expected = array_merge(
            [
                'model' => 'gpt-3.5-turbo',
                'messages' => ['Hi'],
                'temperature' => null,
                'store' => null,
                'n' => null,
                'stream' => false,
                'top_p' => 1,
            ]
        );

        expect($chatData->toArray())->toEqual($expected);
    });

    it('returns the correct stream value using shouldStream()', function () {
        $chatDataStreaming = new ChatCompletionData(
            model: 'gpt-3.5-turbo',
            message: ['Streaming test'],
            temperature: 1.0,
            store: false,
            reasoningEffort: 'medium',
            metadata: null,
            maxCompletionTokens: null,
            numberOfCompletionChoices: null,
            outputTypes: null,
            audio: null,
            stopSequences: null,
            stream: true,
            streamOptions: null,
            topP: null
        );

        $chatDataNonStreaming = new ChatCompletionData(
            model: 'gpt-3.5-turbo',
            message: ['Streaming test'],
            temperature: 1.0,
            store: false,
            reasoningEffort: 'medium',
            metadata: null,
            maxCompletionTokens: null,
            numberOfCompletionChoices: null,
            outputTypes: null,
            audio: null,
            stopSequences: null,
            stream: false,
            streamOptions: null,
        );

        expect($chatDataStreaming->shouldStream())->toBeTrue()
            ->and($chatDataNonStreaming->shouldStream())->toBeFalse();
    });
});
