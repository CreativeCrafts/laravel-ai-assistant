<?php

declare(strict_types=1);

use CreativeCrafts\LaravelAiAssistant\Adapters\AudioTranscriptionAdapter;
use CreativeCrafts\LaravelAiAssistant\DataTransferObjects\ResponseDto;
use CreativeCrafts\LaravelAiAssistant\Exceptions\AudioTranscriptionException;
use CreativeCrafts\LaravelAiAssistant\Exceptions\FileValidationException;

beforeEach(function () {
    $this->adapter = new AudioTranscriptionAdapter();
});

afterEach(function () {
    // Clean up any temporary files created during tests
    if (isset($this->tempFile) && file_exists($this->tempFile)) {
        unlink($this->tempFile);
    }
});

describe('End-to-end audio transcription flow', function () {
    it('processes complete transcription request with all parameters', function () {
        // Arrange: Create a temporary audio file
        $this->tempFile = tempnam(sys_get_temp_dir(), 'feature_audio_') . '.mp3';
        file_put_contents($this->tempFile, 'mock audio content for transcription');

        $unifiedRequest = [
            'audio' => [
                'file' => $this->tempFile,
                'model' => 'whisper-1',
                'language' => 'en',
                'prompt' => 'This is a meeting recording',
                'response_format' => 'verbose_json',
                'temperature' => 0.2,
            ],
        ];

        // Act: Transform request
        $transformedRequest = $this->adapter->transformRequest($unifiedRequest);

        // Assert: Verify request transformation
        expect($transformedRequest)->toBeArray()
            ->and($transformedRequest['file'])->toBe($this->tempFile)
            ->and($transformedRequest['model'])->toBe('whisper-1')
            ->and($transformedRequest['language'])->toBe('en')
            ->and($transformedRequest['prompt'])->toBe('This is a meeting recording')
            ->and($transformedRequest['response_format'])->toBe('verbose_json')
            ->and($transformedRequest['temperature'])->toBe(0.2);

        // Simulate API response
        $apiResponse = [
            'id' => 'transcription_123',
            'text' => 'This is the transcribed text from the audio file.',
            'duration' => 45.3,
            'language' => 'en',
        ];

        // Act: Transform response
        $responseDto = $this->adapter->transformResponse($apiResponse);

        // Assert: Verify response transformation
        expect($responseDto)->toBeInstanceOf(ResponseDto::class)
            ->and($responseDto->id)->toBe('transcription_123')
            ->and($responseDto->status)->toBe('completed')
            ->and($responseDto->text)->toBe('This is the transcribed text from the audio file.')
            ->and($responseDto->type)->toBe('audio_transcription')
            ->and($responseDto->audioContent)->toBeNull()
            ->and($responseDto->images)->toBeNull()
            ->and($responseDto->metadata)->toBe([
                'duration' => 45.3,
                'language' => 'en',
            ])
            ->and($responseDto->isText())->toBeTrue()
            ->and($responseDto->isAudio())->toBeFalse()
            ->and($responseDto->isImage())->toBeFalse();
    });

    it('handles minimal transcription request with defaults', function () {
        // Arrange: Create a temporary audio file with minimal config
        $this->tempFile = tempnam(sys_get_temp_dir(), 'feature_audio_') . '.wav';
        file_put_contents($this->tempFile, 'minimal audio content');

        $unifiedRequest = [
            'audio' => [
                'file' => $this->tempFile,
            ],
        ];

        // Act: Transform request
        $transformedRequest = $this->adapter->transformRequest($unifiedRequest);

        // Assert: Verify defaults are applied
        expect($transformedRequest['file'])->toBe($this->tempFile)
            ->and($transformedRequest['model'])->toBe('gpt-4o-mini-transcribe')
            ->and($transformedRequest['language'])->toBeNull()
            ->and($transformedRequest['prompt'])->toBeNull()
            ->and($transformedRequest['response_format'])->toBe('json')
            ->and($transformedRequest['temperature'])->toBe(0);
    });

    it('validates audio file format in end-to-end flow', function () {
        // Arrange: Create an invalid file format
        $this->tempFile = tempnam(sys_get_temp_dir(), 'feature_audio_') . '.pdf';
        file_put_contents($this->tempFile, 'not audio content');

        $unifiedRequest = [
            'audio' => [
                'file' => $this->tempFile,
            ],
        ];

        // Act & Assert: Should throw exception for unsupported format
        expect(fn () => $this->adapter->transformRequest($unifiedRequest))
            ->toThrow(AudioTranscriptionException::class, 'Unsupported audio format');
    });

    it('validates file existence in end-to-end flow', function () {
        // Arrange: Use a non-existent file path
        $unifiedRequest = [
            'audio' => [
                'file' => '/tmp/non_existent_audio_file_12345.mp3',
            ],
        ];

        // Act & Assert: Should throw exception for non-existent file
        expect(fn () => $this->adapter->transformRequest($unifiedRequest))
            ->toThrow(FileValidationException::class, 'File not found');
    });

    it('processes multiple supported audio formats', function () {
        $supportedFormats = ['mp3', 'wav', 'webm', 'm4a'];

        foreach ($supportedFormats as $format) {
            // Arrange: Create file with specific format
            $tempFile = tempnam(sys_get_temp_dir(), 'feature_audio_') . '.' . $format;
            file_put_contents($tempFile, 'audio content');

            $unifiedRequest = [
                'audio' => [
                    'file' => $tempFile,
                    'language' => 'en',
                ],
            ];

            // Act: Transform request
            $transformedRequest = $this->adapter->transformRequest($unifiedRequest);

            // Assert: Should successfully process the file
            expect($transformedRequest['file'])->toBe($tempFile)
                ->and($transformedRequest['language'])->toBe('en');

            // Cleanup
            unlink($tempFile);
        }
    });

    it('handles API response without optional fields', function () {
        // Arrange: Minimal API response
        $apiResponse = [
            'text' => 'Transcribed content',
        ];

        // Act: Transform response
        $responseDto = $this->adapter->transformResponse($apiResponse);

        // Assert: Should handle missing optional fields gracefully
        expect($responseDto)->toBeInstanceOf(ResponseDto::class)
            ->and($responseDto->text)->toBe('Transcribed content')
            ->and($responseDto->type)->toBe('audio_transcription')
            ->and($responseDto->status)->toBe('completed')
            ->and($responseDto->metadata['duration'])->toBeNull()
            ->and($responseDto->metadata['language'])->toBeNull();
    });

    it('preserves raw API response in ResponseDto', function () {
        // Arrange: Complete API response
        $apiResponse = [
            'id' => 'trans_456',
            'text' => 'Full transcription',
            'duration' => 120.5,
            'language' => 'es',
            'task' => 'transcribe',
        ];

        // Act: Transform response
        $responseDto = $this->adapter->transformResponse($apiResponse);

        // Assert: Raw response should be preserved
        expect($responseDto->raw)->toBe($apiResponse)
            ->and($responseDto->raw['task'])->toBe('transcribe');
    });
});
