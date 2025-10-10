<?php

declare(strict_types=1);

use CreativeCrafts\LaravelAiAssistant\Adapters\AdapterFactory;
use CreativeCrafts\LaravelAiAssistant\Adapters\AudioSpeechAdapter;
use CreativeCrafts\LaravelAiAssistant\Adapters\AudioTranscriptionAdapter;
use CreativeCrafts\LaravelAiAssistant\Adapters\AudioTranslationAdapter;
use CreativeCrafts\LaravelAiAssistant\DataTransferObjects\ResponseDto;
use CreativeCrafts\LaravelAiAssistant\Enums\OpenAiEndpoint;

/**
 * Integration tests for Audio Adapters with the new adapter architecture.
 *
 * These tests verify that the adapters correctly transform requests to OpenAI API format
 * and transform responses back to unified ResponseDto format.
 *
 * @group integration
 */
beforeEach(function () {
    $this->factory = new AdapterFactory();
});

afterEach(function () {
    // Clean up any temporary files
    if (isset($this->tempFile) && file_exists($this->tempFile)) {
        unlink($this->tempFile);
    }
});

describe('AudioTranscriptionAdapter Integration', function () {
    it('transforms request to correct OpenAI Whisper transcription format', function () {
        // Arrange: Create realistic audio file and request
        $this->tempFile = tempnam(sys_get_temp_dir(), 'integration_audio_') . '.mp3';
        file_put_contents($this->tempFile, 'mock audio content');

        $adapter = $this->factory->make(OpenAiEndpoint::AudioTranscription);

        $unifiedRequest = [
            'audio' => [
                'file' => $this->tempFile,
                'model' => 'whisper-1',
                'language' => 'en',
                'prompt' => 'Meeting notes',
                'response_format' => 'verbose_json',
                'temperature' => 0.3,
            ],
        ];

        // Act: Transform to OpenAI format
        $openAiRequest = $adapter->transformRequest($unifiedRequest);

        // Assert: Verify OpenAI API format
        expect($openAiRequest)->toMatchArray([
            'file' => $this->tempFile,
            'model' => 'whisper-1',
            'language' => 'en',
            'prompt' => 'Meeting notes',
            'response_format' => 'verbose_json',
            'temperature' => 0.3,
        ]);
    });

    it('transforms OpenAI Whisper transcription response to unified ResponseDto', function () {
        // Arrange: Realistic OpenAI API response
        $adapter = $this->factory->make(OpenAiEndpoint::AudioTranscription);

        $openAiResponse = [
            'text' => 'This is a transcription of the audio file with multiple sentences. It includes various details about the meeting.',
            'task' => 'transcribe',
            'language' => 'english',
            'duration' => 127.5,
            'segments' => [
                ['id' => 0, 'start' => 0.0, 'end' => 5.2, 'text' => 'This is a transcription'],
            ],
        ];

        // Act: Transform to unified format
        $responseDto = $adapter->transformResponse($openAiResponse);

        // Assert: Verify unified ResponseDto structure
        expect($responseDto)->toBeInstanceOf(ResponseDto::class)
            ->and($responseDto->type)->toBe('audio_transcription')
            ->and($responseDto->text)->toContain('transcription')
            ->and($responseDto->status)->toBe('completed')
            ->and($responseDto->audioContent)->toBeNull()
            ->and($responseDto->images)->toBeNull()
            ->and($responseDto->metadata['duration'])->toBe(127.5)
            ->and($responseDto->metadata['language'])->toBe('english')
            ->and($responseDto->raw)->toHaveKey('segments');
    });
});

describe('AudioTranslationAdapter Integration', function () {
    it('transforms request to correct OpenAI Whisper translation format', function () {
        // Arrange: Create realistic audio file and request
        $this->tempFile = tempnam(sys_get_temp_dir(), 'integration_audio_') . '.mp3';
        file_put_contents($this->tempFile, 'mock audio content in foreign language');

        $adapter = $this->factory->make(OpenAiEndpoint::AudioTranslation);

        $unifiedRequest = [
            'audio' => [
                'file' => $this->tempFile,
                'model' => 'whisper-1',
                'prompt' => 'Spanish business conversation',
                'response_format' => 'json',
                'temperature' => 0.0,
            ],
        ];

        // Act: Transform to OpenAI format
        $openAiRequest = $adapter->transformRequest($unifiedRequest);

        // Assert: Verify OpenAI API format (no language parameter for translation)
        expect($openAiRequest)->toMatchArray([
            'file' => $this->tempFile,
            'model' => 'whisper-1',
            'prompt' => 'Spanish business conversation',
            'response_format' => 'json',
            'temperature' => 0.0,
        ])
        ->and($openAiRequest)->not->toHaveKey('language');
    });

    it('transforms OpenAI Whisper translation response to unified ResponseDto', function () {
        // Arrange: Realistic OpenAI API response for translation
        $adapter = $this->factory->make(OpenAiEndpoint::AudioTranslation);

        $openAiResponse = [
            'text' => 'This is the translated text in English from a Spanish audio recording.',
            'task' => 'translate',
            'language' => 'spanish',
            'duration' => 85.3,
        ];

        // Act: Transform to unified format
        $responseDto = $adapter->transformResponse($openAiResponse);

        // Assert: Verify unified ResponseDto structure
        expect($responseDto)->toBeInstanceOf(ResponseDto::class)
            ->and($responseDto->type)->toBe('audio_translation')
            ->and($responseDto->text)->toContain('translated text in English')
            ->and($responseDto->status)->toBe('completed')
            ->and($responseDto->metadata['source_language'])->toBe('spanish')
            ->and($responseDto->metadata['target_language'])->toBe('en')
            ->and($responseDto->metadata['duration'])->toBe(85.3);
    });

    it('validates file before making API request', function () {
        // Arrange: Invalid file path
        $adapter = $this->factory->make(OpenAiEndpoint::AudioTranslation);

        $unifiedRequest = [
            'audio' => [
                'file' => '/non/existent/file.mp3',
            ],
        ];

        // Act & Assert: Should throw validation exception before API call
        expect(fn () => $adapter->transformRequest($unifiedRequest))
            ->toThrow(InvalidArgumentException::class, 'Audio file does not exist');
    });
});

describe('AudioSpeechAdapter Integration', function () {
    it('transforms request to correct OpenAI TTS format', function () {
        // Arrange: Realistic TTS request
        $adapter = $this->factory->make(OpenAiEndpoint::AudioSpeech);

        $unifiedRequest = [
            'audio' => [
                'text' => 'Welcome to our application. This is a demo of text-to-speech functionality.',
                'model' => 'tts-1-hd',
                'voice' => 'nova',
                'response_format' => 'opus',
                'speed' => 1.1,
            ],
        ];

        // Act: Transform to OpenAI format
        $openAiRequest = $adapter->transformRequest($unifiedRequest);

        // Assert: Verify OpenAI API format (input instead of text)
        expect($openAiRequest)->toMatchArray([
            'model' => 'tts-1-hd',
            'input' => 'Welcome to our application. This is a demo of text-to-speech functionality.',
            'voice' => 'nova',
            'response_format' => 'opus',
            'speed' => 1.1,
        ]);
    });

    it('transforms OpenAI TTS response to unified ResponseDto with audio content', function () {
        // Arrange: Realistic OpenAI TTS response (binary audio data)
        $adapter = $this->factory->make(OpenAiEndpoint::AudioSpeech);

        // Simulate binary audio content
        $binaryAudioContent = base64_encode(random_bytes(1024));

        $openAiResponse = [
            'id' => 'tts_abc123def456',
            'content' => $binaryAudioContent,
            'format' => 'opus',
            'voice' => 'nova',
            'model' => 'tts-1-hd',
            'speed' => 1.1,
        ];

        // Act: Transform to unified format
        $responseDto = $adapter->transformResponse($openAiResponse);

        // Assert: Verify unified ResponseDto structure
        expect($responseDto)->toBeInstanceOf(ResponseDto::class)
            ->and($responseDto->type)->toBe('audio_speech')
            ->and($responseDto->audioContent)->toBe($binaryAudioContent)
            ->and($responseDto->text)->toBeNull()
            ->and($responseDto->status)->toBe('completed')
            ->and($responseDto->metadata)->toMatchArray([
                'format' => 'opus',
                'voice' => 'nova',
                'model' => 'tts-1-hd',
                'speed' => 1.1,
            ])
            ->and($responseDto->isAudio())->toBeTrue();
    });

    it('handles very long text input for TTS', function () {
        // Arrange: Long text input (common use case for TTS)
        $adapter = $this->factory->make(OpenAiEndpoint::AudioSpeech);

        $longText = implode(' ', array_fill(0, 500, 'This is a sentence for text-to-speech.'));

        $unifiedRequest = [
            'audio' => [
                'text' => $longText,
                'model' => 'tts-1',
                'voice' => 'alloy',
            ],
        ];

        // Act: Transform to OpenAI format
        $openAiRequest = $adapter->transformRequest($unifiedRequest);

        // Assert: Should handle long text without truncation
        expect($openAiRequest['input'])->toBe($longText)
            ->and(strlen($openAiRequest['input']))->toBeGreaterThan(10000);
    });
});

describe('AdapterFactory Integration', function () {
    it('creates correct adapter for each audio endpoint', function () {
        $testCases = [
            [OpenAiEndpoint::AudioTranscription, AudioTranscriptionAdapter::class],
            [OpenAiEndpoint::AudioTranslation, AudioTranslationAdapter::class],
            [OpenAiEndpoint::AudioSpeech, AudioSpeechAdapter::class],
        ];

        foreach ($testCases as [$endpoint, $expectedClass]) {
            // Act: Create adapter
            $adapter = $this->factory->make($endpoint);

            // Assert: Correct adapter type
            expect($adapter)->toBeInstanceOf($expectedClass);
        }
    });

    it('all audio adapters implement EndpointAdapter interface', function () {
        $audioEndpoints = [
            OpenAiEndpoint::AudioTranscription,
            OpenAiEndpoint::AudioTranslation,
            OpenAiEndpoint::AudioSpeech,
        ];

        foreach ($audioEndpoints as $endpoint) {
            // Act: Create adapter
            $adapter = $this->factory->make($endpoint);

            // Assert: Has required methods
            expect(method_exists($adapter, 'transformRequest'))->toBeTrue()
                ->and(method_exists($adapter, 'transformResponse'))->toBeTrue();
        }
    });
});

describe('Round-trip transformation', function () {
    it('maintains data integrity through complete transcription flow', function () {
        // Arrange: Complete flow simulation
        $this->tempFile = tempnam(sys_get_temp_dir(), 'integration_audio_') . '.wav';
        file_put_contents($this->tempFile, 'audio content');

        $adapter = new AudioTranscriptionAdapter();

        $originalData = [
            'audio' => [
                'file' => $this->tempFile,
                'model' => 'whisper-1',
                'language' => 'fr',
                'prompt' => 'French lesson',
                'temperature' => 0.5,
            ],
        ];

        // Act: Transform request -> simulate API -> transform response
        $apiRequest = $adapter->transformRequest($originalData);

        $simulatedApiResponse = [
            'id' => 'trans_integration_test',
            'text' => 'Bonjour, ceci est une leçon de français.',
            'duration' => 45.0,
            'language' => 'fr',
        ];

        $responseDto = $adapter->transformResponse($simulatedApiResponse);

        // Assert: Data integrity maintained
        expect($responseDto->text)->toBe('Bonjour, ceci est une leçon de français.')
            ->and($responseDto->metadata['language'])->toBe('fr')
            ->and($responseDto->metadata['duration'])->toBe(45.0)
            ->and($responseDto->type)->toBe('audio_transcription');
    });

    it('maintains data integrity through complete translation flow', function () {
        // Arrange: Complete flow simulation
        $this->tempFile = tempnam(sys_get_temp_dir(), 'integration_audio_') . '.mp3';
        file_put_contents($this->tempFile, 'audio content');

        $adapter = new AudioTranslationAdapter();

        $originalData = [
            'audio' => [
                'file' => $this->tempFile,
                'model' => 'whisper-1',
                'prompt' => 'German business meeting',
            ],
        ];

        // Act: Transform request -> simulate API -> transform response
        $apiRequest = $adapter->transformRequest($originalData);

        $simulatedApiResponse = [
            'id' => 'trans_integration_test_2',
            'text' => 'Good morning everyone. Let us begin the meeting.',
            'duration' => 32.5,
            'language' => 'de',
        ];

        $responseDto = $adapter->transformResponse($simulatedApiResponse);

        // Assert: Data integrity maintained
        expect($responseDto->text)->toContain('Good morning')
            ->and($responseDto->metadata['source_language'])->toBe('de')
            ->and($responseDto->metadata['target_language'])->toBe('en')
            ->and($responseDto->type)->toBe('audio_translation');
    });

    it('maintains data integrity through complete TTS flow', function () {
        // Arrange: Complete flow simulation
        $adapter = new AudioSpeechAdapter();

        $originalData = [
            'audio' => [
                'text' => 'Integration test for text-to-speech adapter.',
                'model' => 'tts-1-hd',
                'voice' => 'shimmer',
                'response_format' => 'flac',
                'speed' => 0.9,
            ],
        ];

        // Act: Transform request -> simulate API -> transform response
        $apiRequest = $adapter->transformRequest($originalData);

        expect($apiRequest['input'])->toBe('Integration test for text-to-speech adapter.');

        $simulatedApiResponse = [
            'id' => 'tts_integration_test',
            'content' => base64_encode('binary_audio_flac_data'),
            'format' => 'flac',
            'voice' => 'shimmer',
            'model' => 'tts-1-hd',
            'speed' => 0.9,
        ];

        $responseDto = $adapter->transformResponse($simulatedApiResponse);

        // Assert: Data integrity maintained
        expect($responseDto->audioContent)->not->toBeNull()
            ->and($responseDto->metadata['format'])->toBe('flac')
            ->and($responseDto->metadata['voice'])->toBe('shimmer')
            ->and($responseDto->metadata['speed'])->toBe(0.9)
            ->and($responseDto->type)->toBe('audio_speech');
    });
});
