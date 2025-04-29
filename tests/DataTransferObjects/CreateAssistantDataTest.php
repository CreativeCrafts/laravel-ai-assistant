<?php

declare(strict_types=1);

use CreativeCrafts\LaravelAiAssistant\DataTransferObjects\CreateAssistantData;

covers(CreateAssistantData::class);

describe('CreateAssistantData', function () {
    it('converts to array correctly when metadata is provided', function () {
        $data = new CreateAssistantData(
            model: 'gpt-3.5-turbo',
            topP: 0.8,
            temperature: 0.7,
            assistantDescription: 'Test Description',
            assistantName: 'Test Assistant',
            instructions: 'Follow these instructions',
            reasoningEffort: 'high',
            tools: ['tool1', 'tool2'],
            toolResources: ['resource1'],
            metadata: ['key' => 'value'],
            responseFormat: 'json'
        );

        $expected = [
            'model'             => 'gpt-3.5-turbo',
            'top_p'             => 0.8,
            'temperature'       => 0.7,
            'description'       => 'Test Description',
            'name'              => 'Test Assistant',
            'instructions'      => 'Follow these instructions',
            'reasoning_effort'  => 'high',
            'tools'             => ['tool1', 'tool2'],
            'tool_resources'    => ['resource1'],
            'response_format'   => 'json',
            'metadata'          => ['key' => 'value'],
        ];

        expect($data->toArray())->toEqual($expected);
    });

    it('converts to array correctly when metadata is not provided', function () {
        $data = new CreateAssistantData(
            model: 'gpt-3.5-turbo',
            topP: null,
            temperature: null,
            assistantDescription: null,
            assistantName: null,
            instructions: null,
            reasoningEffort: null,
            tools: null,
            toolResources: null,
            metadata: null,
            responseFormat: 'auto'
        );

        $expected = [
            'model'             => 'gpt-3.5-turbo',
            'top_p'             => null,
            'temperature'       => null,
            'description'       => null,
            'name'              => null,
            'instructions'      => null,
            'tools'             => null,
            'tool_resources'    => null,
            'response_format'   => 'auto',
        ];

        expect($data->toArray())->toEqual($expected);
    });
});
