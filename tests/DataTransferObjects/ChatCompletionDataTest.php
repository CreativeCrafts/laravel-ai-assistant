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
            reasoningEffort: 'high',
            metadata: ['key' => 'value'],
            maxCompletionTokens: 150,
            numberOfCompletionChoices: 2,
            outputTypes: ['text', 'audio'],
            audio: ['format' => 'mp3'],
            responseFormat: 'json',
            stopSequences: ['STOP'],
            stream: true,
            streamOptions: ['option' => 'value'],
            topP: 0.9
        );

        $expected = array_merge(
            [
                'model' => 'gpt-3.5-turbo',
                'message' => ['Hello', 'World'],
                'temperature' => 0.8,
                'store' => true,
                'reasoning_effort' => 'high',
                'n' => 2,
                'response_formats' => 'json',
                'stop' => ['STOP'],
                'stream' => true,
                'stream_options' => ['option' => 'value'],
                'top_p' => 0.9,
            ],
            ['metadata' => ['key' => 'value']],
            ['max_completion_tokens' => 150],
            ['modalities' => ['text', 'audio']],
            ['audio' => ['format' => 'mp3']]
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
            responseFormat: 'auto',
            stopSequences: null,
            stream: false,
            streamOptions: null,
            topP: null
        );

        $expected = array_merge(
            [
                'model' => 'gpt-3.5-turbo',
                'message' => ['Hi'],
                'temperature' => null,
                'store' => null,
                'reasoning_effort' => null,
                'n' => null,
                'response_formats' => 'auto',
                'stop' => null,
                'stream' => false,
                'stream_options' => null,
                'top_p' => null,
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
            responseFormat: 'auto',
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
            responseFormat: 'auto',
            stopSequences: null,
            stream: false,
            streamOptions: null,
            topP: null
        );

        expect($chatDataStreaming->shouldStream())->toBeTrue()
            ->and($chatDataNonStreaming->shouldStream())->toBeFalse();
    });
});
