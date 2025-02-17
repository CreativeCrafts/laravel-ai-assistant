<?php

declare(strict_types=1);

use CreativeCrafts\LaravelAiAssistant\DataTransferObjects\TranscribeToData;

covers(TranscribeToData::class);

describe('TranscribeToData', function () {
    it('converts to array without prompt when prompt is null', function () {
        $transcribeData = new TranscribeToData(
            'gpt-3.5-turbo',
            0.7,
            'json',
            '/path/to/audio.mp3',
            'en'
        );

        $expected = [
            'model' => 'gpt-3.5-turbo',
            'temperature' => 0.7,
            'response_format' => 'json',
            'file' => '/path/to/audio.mp3',
            'language' => 'en',
        ];

        expect($transcribeData->toArray())->toEqual($expected);
    });

    it('converts to array including prompt when prompt is provided', function () {
        $transcribeData = new TranscribeToData(
            'gpt-3.5-turbo',
            0.7,
            'json',
            '/path/to/audio.mp3',
            'en',
            'Please transcribe this audio'
        );

        $expected = [
            'model' => 'gpt-3.5-turbo',
            'temperature' => 0.7,
            'response_format' => 'json',
            'file' => '/path/to/audio.mp3',
            'language' => 'en',
            'prompt' => 'Please transcribe this audio',
        ];

        expect($transcribeData->toArray())->toEqual($expected);
    });
});
