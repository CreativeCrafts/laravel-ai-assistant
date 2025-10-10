<?php

declare(strict_types=1);

use CreativeCrafts\LaravelAiAssistant\Adapters\AudioSpeechAdapter;
use CreativeCrafts\LaravelAiAssistant\DataTransferObjects\ResponseDto;

beforeEach(function () {
    $this->adapter = new AudioSpeechAdapter();
});

describe('End-to-end audio speech (TTS) flow', function () {
    it('processes complete text-to-speech request with all parameters', function () {
        // Arrange: Create a complete TTS request
        $unifiedRequest = [
            'audio' => [
                'text' => 'Hello, this is a text-to-speech test with all parameters configured.',
                'model' => 'tts-1-hd',
                'voice' => 'nova',
                'response_format' => 'opus',
                'speed' => 1.25,
            ],
        ];

        // Act: Transform request
        $transformedRequest = $this->adapter->transformRequest($unifiedRequest);

        // Assert: Verify request transformation
        expect($transformedRequest)->toBeArray()
            ->and($transformedRequest['model'])->toBe('tts-1-hd')
            ->and($transformedRequest['input'])->toBe('Hello, this is a text-to-speech test with all parameters configured.')
            ->and($transformedRequest['voice'])->toBe('nova')
            ->and($transformedRequest['response_format'])->toBe('opus')
            ->and($transformedRequest['speed'])->toBe(1.25);

        // Simulate API response with binary audio content
        $binaryAudioData = base64_encode('mock_binary_audio_content_opus_format');
        $apiResponse = [
            'id' => 'speech_12345',
            'content' => $binaryAudioData,
            'format' => 'opus',
            'voice' => 'nova',
            'model' => 'tts-1-hd',
            'speed' => 1.25,
        ];

        // Act: Transform response
        $responseDto = $this->adapter->transformResponse($apiResponse);

        // Assert: Verify response transformation
        expect($responseDto)->toBeInstanceOf(ResponseDto::class)
            ->and($responseDto->id)->toBe('speech_12345')
            ->and($responseDto->status)->toBe('completed')
            ->and($responseDto->text)->toBeNull()
            ->and($responseDto->audioContent)->toBe($binaryAudioData)
            ->and($responseDto->type)->toBe('audio_speech')
            ->and($responseDto->images)->toBeNull()
            ->and($responseDto->metadata)->toBe([
                'format' => 'opus',
                'voice' => 'nova',
                'model' => 'tts-1-hd',
                'speed' => 1.25,
            ])
            ->and($responseDto->isAudio())->toBeTrue()
            ->and($responseDto->isText())->toBeFalse()
            ->and($responseDto->isImage())->toBeFalse();
    });

    it('handles minimal text-to-speech request with defaults', function () {
        // Arrange: Minimal TTS request
        $unifiedRequest = [
            'audio' => [
                'text' => 'Simple TTS test.',
            ],
        ];

        // Act: Transform request
        $transformedRequest = $this->adapter->transformRequest($unifiedRequest);

        // Assert: Verify defaults are applied
        expect($transformedRequest)->toBe([
            'model' => 'tts-1',
            'input' => 'Simple TTS test.',
            'voice' => 'alloy',
            'response_format' => 'mp3',
            'speed' => 1.0,
        ]);
    });

    it('uses format as fallback for response_format', function () {
        // Arrange: Request using 'format' key instead of 'response_format'
        $unifiedRequest = [
            'audio' => [
                'text' => 'Format fallback test',
                'format' => 'aac',
            ],
        ];

        // Act: Transform request
        $transformedRequest = $this->adapter->transformRequest($unifiedRequest);

        // Assert: Format should be used as response_format
        expect($transformedRequest['response_format'])->toBe('aac');
    });

    it('prioritizes response_format over format', function () {
        // Arrange: Request with both response_format and format
        $unifiedRequest = [
            'audio' => [
                'text' => 'Priority test',
                'response_format' => 'flac',
                'format' => 'mp3',
            ],
        ];

        // Act: Transform request
        $transformedRequest = $this->adapter->transformRequest($unifiedRequest);

        // Assert: response_format should take priority
        expect($transformedRequest['response_format'])->toBe('flac');
    });

    it('supports all available voices', function () {
        $availableVoices = ['alloy', 'echo', 'fable', 'onyx', 'nova', 'shimmer'];

        foreach ($availableVoices as $voice) {
            // Arrange: Request with specific voice
            $unifiedRequest = [
                'audio' => [
                    'text' => "Testing {$voice} voice",
                    'voice' => $voice,
                ],
            ];

            // Act: Transform request
            $transformedRequest = $this->adapter->transformRequest($unifiedRequest);

            // Assert: Voice should be correctly set
            expect($transformedRequest['voice'])->toBe($voice)
                ->and($transformedRequest['input'])->toBe("Testing {$voice} voice");
        }
    });

    it('supports various audio output formats', function () {
        $outputFormats = ['mp3', 'opus', 'aac', 'flac', 'wav', 'pcm'];

        foreach ($outputFormats as $format) {
            // Arrange: Request with specific format
            $unifiedRequest = [
                'audio' => [
                    'text' => 'Format test',
                    'response_format' => $format,
                ],
            ];

            // Act: Transform request
            $transformedRequest = $this->adapter->transformRequest($unifiedRequest);

            // Assert: Format should be correctly set
            expect($transformedRequest['response_format'])->toBe($format);
        }
    });

    it('supports speed variations', function () {
        $speedValues = [0.25, 0.5, 1.0, 1.5, 2.0, 4.0];

        foreach ($speedValues as $speed) {
            // Arrange: Request with specific speed
            $unifiedRequest = [
                'audio' => [
                    'text' => 'Speed test',
                    'speed' => $speed,
                ],
            ];

            // Act: Transform request
            $transformedRequest = $this->adapter->transformRequest($unifiedRequest);

            // Assert: Speed should be correctly set
            expect($transformedRequest['speed'])->toBe($speed);
        }
    });

    it('handles empty text input', function () {
        // Arrange: Request with empty text
        $unifiedRequest = [
            'audio' => [
                'text' => '',
            ],
        ];

        // Act: Transform request
        $transformedRequest = $this->adapter->transformRequest($unifiedRequest);

        // Assert: Empty string should be preserved
        expect($transformedRequest['input'])->toBe('');
    });

    it('handles API response without optional fields', function () {
        // Arrange: Minimal API response
        $apiResponse = [
            'content' => 'binary_audio_data',
        ];

        // Act: Transform response
        $responseDto = $this->adapter->transformResponse($apiResponse);

        // Assert: Should handle missing optional fields with defaults
        expect($responseDto)->toBeInstanceOf(ResponseDto::class)
            ->and($responseDto->audioContent)->toBe('binary_audio_data')
            ->and($responseDto->type)->toBe('audio_speech')
            ->and($responseDto->status)->toBe('completed')
            ->and($responseDto->metadata['format'])->toBe('mp3')
            ->and($responseDto->metadata['voice'])->toBeNull()
            ->and($responseDto->metadata['model'])->toBeNull()
            ->and($responseDto->metadata['speed'])->toBe(1.0);
    });

    it('preserves raw API response in ResponseDto', function () {
        // Arrange: Complete API response
        $apiResponse = [
            'id' => 'speech_999',
            'content' => 'full_binary_audio_data',
            'format' => 'mp3',
            'voice' => 'shimmer',
            'model' => 'tts-1',
            'speed' => 1.0,
            'duration' => 5.2,
        ];

        // Act: Transform response
        $responseDto = $this->adapter->transformResponse($apiResponse);

        // Assert: Raw response should be preserved
        expect($responseDto->raw)->toBe($apiResponse)
            ->and($responseDto->raw['duration'])->toBe(5.2);
    });

    it('generates UUID when API response lacks id', function () {
        // Arrange: API response without id
        $apiResponse = [
            'content' => 'audio_without_id',
        ];

        // Act: Transform response
        $responseDto = $this->adapter->transformResponse($apiResponse);

        // Assert: Should generate UUID with prefix
        expect($responseDto->id)->toStartWith('audio_speech_')
            ->and(strlen($responseDto->id))->toBeGreaterThan(20);
    });

    it('handles empty audio array gracefully', function () {
        // Arrange: Request with empty audio array
        $unifiedRequest = [
            'audio' => [],
        ];

        // Act: Transform request
        $transformedRequest = $this->adapter->transformRequest($unifiedRequest);

        // Assert: Should apply all defaults including empty input
        expect($transformedRequest)->toBe([
            'model' => 'tts-1',
            'input' => '',
            'voice' => 'alloy',
            'response_format' => 'mp3',
            'speed' => 1.0,
        ]);
    });

    it('handles missing audio key gracefully', function () {
        // Arrange: Request without audio key
        $unifiedRequest = [];

        // Act: Transform request
        $transformedRequest = $this->adapter->transformRequest($unifiedRequest);

        // Assert: Should apply all defaults
        expect($transformedRequest)->toBe([
            'model' => 'tts-1',
            'input' => '',
            'voice' => 'alloy',
            'response_format' => 'mp3',
            'speed' => 1.0,
        ]);
    });

    it('handles long text input', function () {
        // Arrange: Request with long text (simulating real-world usage)
        $longText = str_repeat('This is a long sentence for text-to-speech conversion. ', 50);
        $unifiedRequest = [
            'audio' => [
                'text' => $longText,
                'model' => 'tts-1-hd',
            ],
        ];

        // Act: Transform request
        $transformedRequest = $this->adapter->transformRequest($unifiedRequest);

        // Assert: Long text should be preserved
        expect($transformedRequest['input'])->toBe($longText)
            ->and(strlen($transformedRequest['input']))->toBeGreaterThan(1000);
    });
});
