<?php

declare(strict_types=1);

use CreativeCrafts\LaravelAiAssistant\Adapters\AudioSpeechAdapter;
use CreativeCrafts\LaravelAiAssistant\DataTransferObjects\ResponseDto;

beforeEach(function () {
    $this->adapter = new AudioSpeechAdapter();
});

describe('transformRequest', function () {
    it('transforms unified request with all parameters', function () {
        $unifiedRequest = [
            'audio' => [
                'text' => 'Hello, this is a test.',
                'model' => 'tts-1-hd',
                'voice' => 'nova',
                'response_format' => 'opus',
                'speed' => 1.25,
            ],
        ];

        $result = $this->adapter->transformRequest($unifiedRequest);

        expect($result)->toBe([
            'model' => 'tts-1-hd',
            'input' => 'Hello, this is a test.',
            'voice' => 'nova',
            'response_format' => 'opus',
            'speed' => 1.25,
        ]);
    });

    it('uses format as fallback for response_format', function () {
        $unifiedRequest = [
            'audio' => [
                'text' => 'Test',
                'format' => 'wav',
            ],
        ];

        $result = $this->adapter->transformRequest($unifiedRequest);

        expect($result['response_format'])->toBe('wav');
    });

    it('applies default values', function () {
        $unifiedRequest = [
            'audio' => [
                'text' => 'Test speech',
            ],
        ];

        $result = $this->adapter->transformRequest($unifiedRequest);

        expect($result)->toBe([
            'model' => 'tts-1',
            'input' => 'Test speech',
            'voice' => 'alloy',
            'response_format' => 'mp3',
            'speed' => 1.0,
        ]);
    });
});

describe('transformResponse', function () {
    it('transforms response with audio content', function () {
        $apiResponse = [
            'id' => 'speech_789',
            'content' => 'binary_audio_data_here',
            'format' => 'mp3',
            'voice' => 'nova',
            'model' => 'tts-1',
            'speed' => 1.0,
        ];

        $result = $this->adapter->transformResponse($apiResponse);

        expect($result)->toBeInstanceOf(ResponseDto::class);
        expect($result->audioContent)->toBe('binary_audio_data_here');
        expect($result->type)->toBe('audio_speech');
        expect($result->text)->toBeNull();
        expect($result->metadata)->toBe([
            'format' => 'mp3',
            'voice' => 'nova',
            'model' => 'tts-1',
            'speed' => 1.0,
        ]);
    });

    it('returns ResponseDto with correct helper method results', function () {
        $apiResponse = ['content' => 'audio_data'];

        $result = $this->adapter->transformResponse($apiResponse);

        expect($result->isAudio())->toBeTrue();
        expect($result->isText())->toBeFalse();
        expect($result->isImage())->toBeFalse();
    });
});
