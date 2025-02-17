<?php

declare(strict_types=1);

use CreativeCrafts\LaravelAiAssistant\DataTransferObjects\ChatAssistantMessageData;

covers(ChatAssistantMessageData::class);

describe('ChatAssistantMessageData', function () {
    it('converts to array with only required keys when optional values are null', function () {
        $data = new ChatAssistantMessageData('assistant');
        $array = $data->toArray();

        expect($array)->toEqual([
            'role'    => 'assistant',
            'refusal' => null,
            'audio'   => null,
        ]);
    });

    it('includes content in the array when provided', function () {
        $content = 'Hello, world!';
        $data = new ChatAssistantMessageData('assistant', $content);
        $array = $data->toArray();

        expect($array)->toEqual([
            'role'    => 'assistant',
            'refusal' => null,
            'audio'   => null,
            'content' => $content,
        ]);
    });

    it('includes name in the array when provided', function () {
        $name = 'ChatGPT';
        $data = new ChatAssistantMessageData('assistant', null, null, $name);
        $array = $data->toArray();

        expect($array)->toEqual([
            'role'    => 'assistant',
            'refusal' => null,
            'audio'   => null,
            'name'    => $name,
        ]);
    });

    it('includes toolCalls in the array when provided', function () {
        $toolCalls = [
            ['id' => 'tool1', 'action' => 'doSomething']
        ];
        $data = new ChatAssistantMessageData('assistant', null, null, null, null, $toolCalls);
        $array = $data->toArray();

        expect($array)->toEqual([
            'role'      => 'assistant',
            'refusal'   => null,
            'audio'     => null,
            'toolCalls' => $toolCalls,
        ]);
    });

    it('includes all provided values in the array', function () {
        $content = ['Part one', 'Part two'];
        $refusal = 'No refusal';
        $name = 'AssistantName';
        $audio = ['format' => 'mp3', 'duration' => 120];
        $toolCalls = [
            ['id' => 'tool1', 'action' => 'action1'],
            ['id' => 'tool2', 'action' => 'action2']
        ];
        $data = new ChatAssistantMessageData('assistant', $content, $refusal, $name, $audio, $toolCalls);
        $array = $data->toArray();

        expect($array)->toEqual([
            'role'      => 'assistant',
            'refusal'   => $refusal,
            'audio'     => $audio,
            'content'   => $content,
            'name'      => $name,
            'toolCalls' => $toolCalls,
        ]);
    });
});
