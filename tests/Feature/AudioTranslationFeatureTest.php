<?php

declare(strict_types=1);

use CreativeCrafts\LaravelAiAssistant\Adapters\AudioTranslationAdapter;
use CreativeCrafts\LaravelAiAssistant\DataTransferObjects\ResponseDto;
use CreativeCrafts\LaravelAiAssistant\Exceptions\AudioTranslationException;
use CreativeCrafts\LaravelAiAssistant\Exceptions\FileValidationException;

beforeEach(function () {
    $this->adapter = new AudioTranslationAdapter();
});

afterEach(function () {
    // Clean up any temporary files created during tests
    if (isset($this->tempFile) && file_exists($this->tempFile)) {
        unlink($this->tempFile);
    }
});

describe('End-to-end audio translation flow', function () {
    it('processes complete translation request with all parameters', function () {
        // Arrange: Create a temporary audio file
        $this->tempFile = tempnam(sys_get_temp_dir(), 'feature_audio_') . '.mp3';
        file_put_contents($this->tempFile, 'mock audio content for translation');

        $unifiedRequest = [
            'audio' => [
                'file' => $this->tempFile,
                'model' => 'whisper-1',
                'prompt' => 'Context for translation',
                'response_format' => 'text',
                'temperature' => 0.1,
            ],
        ];

        // Act: Transform request
        $transformedRequest = $this->adapter->transformRequest($unifiedRequest);

        // Assert: Verify request transformation (no language parameter for translation)
        expect($transformedRequest)->toBeArray()
            ->and($transformedRequest['file'])->toBe($this->tempFile)
            ->and($transformedRequest['model'])->toBe('whisper-1')
            ->and($transformedRequest['prompt'])->toBe('Context for translation')
            ->and($transformedRequest['response_format'])->toBe('text')
            ->and($transformedRequest['temperature'])->toBe(0.1)
            ->and(array_key_exists('language', $transformedRequest))->toBeFalse();

        // Simulate API response
        $apiResponse = [
            'id' => 'translation_789',
            'text' => 'This is the translated text in English from the source audio.',
            'duration' => 32.7,
            'language' => 'es',
        ];

        // Act: Transform response
        $responseDto = $this->adapter->transformResponse($apiResponse);

        // Assert: Verify response transformation
        expect($responseDto)->toBeInstanceOf(ResponseDto::class)
            ->and($responseDto->id)->toBe('translation_789')
            ->and($responseDto->status)->toBe('completed')
            ->and($responseDto->text)->toBe('This is the translated text in English from the source audio.')
            ->and($responseDto->type)->toBe('audio_translation')
            ->and($responseDto->audioContent)->toBeNull()
            ->and($responseDto->images)->toBeNull()
            ->and($responseDto->metadata)->toBe([
                'duration' => 32.7,
                'source_language' => 'es',
                'target_language' => 'en',
            ])
            ->and($responseDto->isText())->toBeTrue()
            ->and($responseDto->isAudio())->toBeFalse()
            ->and($responseDto->isImage())->toBeFalse();
    });

    it('handles minimal translation request with defaults', function () {
        // Arrange: Create a temporary audio file with minimal config
        $this->tempFile = tempnam(sys_get_temp_dir(), 'feature_audio_') . '.m4a';
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
            ->and($transformedRequest['model'])->toBe('whisper-1')
            ->and($transformedRequest['prompt'])->toBeNull()
            ->and($transformedRequest['response_format'])->toBe('json')
            ->and($transformedRequest['temperature'])->toBe(0)
            ->and(array_key_exists('language', $transformedRequest))->toBeFalse();
    });

    it('ignores language parameter in translation requests', function () {
        // Arrange: Request with language parameter (should be ignored)
        $this->tempFile = tempnam(sys_get_temp_dir(), 'feature_audio_') . '.mp3';
        file_put_contents($this->tempFile, 'audio content');

        $unifiedRequest = [
            'audio' => [
                'file' => $this->tempFile,
                'language' => 'fr', // This should be ignored for translation
            ],
        ];

        // Act: Transform request
        $transformedRequest = $this->adapter->transformRequest($unifiedRequest);

        // Assert: Language parameter should not be included
        expect($transformedRequest)->not->toHaveKey('language')
            ->and(array_keys($transformedRequest))->toBe(['file', 'model', 'prompt', 'response_format', 'temperature']);
    });

    it('validates audio file format in end-to-end flow', function () {
        // Arrange: Create an invalid file format
        $this->tempFile = tempnam(sys_get_temp_dir(), 'feature_audio_') . '.json';
        file_put_contents($this->tempFile, '{"not": "audio"}');

        $unifiedRequest = [
            'audio' => [
                'file' => $this->tempFile,
            ],
        ];

        // Act & Assert: Should throw exception for unsupported format
        expect(fn () => $this->adapter->transformRequest($unifiedRequest))
            ->toThrow(AudioTranslationException::class, 'Unsupported audio format');
    });

    it('validates file existence in end-to-end flow', function () {
        // Arrange: Use a non-existent file path
        $unifiedRequest = [
            'audio' => [
                'file' => '/tmp/non_existent_translation_audio.mp3',
            ],
        ];

        // Act & Assert: Should throw exception for non-existent file
        expect(fn () => $this->adapter->transformRequest($unifiedRequest))
            ->toThrow(FileValidationException::class, 'File not found');
    });

    it('validates file readability in end-to-end flow', function () {
        // Arrange: Create a file and make it unreadable
        $this->tempFile = tempnam(sys_get_temp_dir(), 'feature_audio_') . '.mp3';
        file_put_contents($this->tempFile, 'test');
        chmod($this->tempFile, 0000);

        $unifiedRequest = [
            'audio' => [
                'file' => $this->tempFile,
            ],
        ];

        try {
            // Act & Assert: Should throw exception for unreadable file
            expect(fn () => $this->adapter->transformRequest($unifiedRequest))
                ->toThrow(FileValidationException::class, 'not readable');
        } finally {
            // Cleanup: Restore permissions before deletion
            chmod($this->tempFile, 0644);
        }
    });

    it('processes multiple supported audio formats', function () {
        $supportedFormats = ['mp3', 'mp4', 'mpeg', 'mpga', 'wav'];

        foreach ($supportedFormats as $format) {
            // Arrange: Create file with specific format
            $tempFile = tempnam(sys_get_temp_dir(), 'feature_audio_') . '.' . $format;
            file_put_contents($tempFile, 'audio content');

            $unifiedRequest = [
                'audio' => [
                    'file' => $tempFile,
                    'prompt' => 'Translation context',
                ],
            ];

            // Act: Transform request
            $transformedRequest = $this->adapter->transformRequest($unifiedRequest);

            // Assert: Should successfully process the file
            expect($transformedRequest['file'])->toBe($tempFile)
                ->and($transformedRequest['prompt'])->toBe('Translation context');

            // Cleanup
            unlink($tempFile);
        }
    });

    it('handles API response without optional fields', function () {
        // Arrange: Minimal API response
        $apiResponse = [
            'text' => 'Translated English text',
        ];

        // Act: Transform response
        $responseDto = $this->adapter->transformResponse($apiResponse);

        // Assert: Should handle missing optional fields gracefully
        expect($responseDto)->toBeInstanceOf(ResponseDto::class)
            ->and($responseDto->text)->toBe('Translated English text')
            ->and($responseDto->type)->toBe('audio_translation')
            ->and($responseDto->status)->toBe('completed')
            ->and($responseDto->metadata['duration'])->toBeNull()
            ->and($responseDto->metadata['source_language'])->toBeNull()
            ->and($responseDto->metadata['target_language'])->toBe('en');
    });

    it('preserves raw API response in ResponseDto', function () {
        // Arrange: Complete API response
        $apiResponse = [
            'id' => 'trans_999',
            'text' => 'Full translation to English',
            'duration' => 67.2,
            'language' => 'de',
            'task' => 'translate',
        ];

        // Act: Transform response
        $responseDto = $this->adapter->transformResponse($apiResponse);

        // Assert: Raw response should be preserved
        expect($responseDto->raw)->toBe($apiResponse)
            ->and($responseDto->raw['task'])->toBe('translate')
            ->and($responseDto->metadata['source_language'])->toBe('de')
            ->and($responseDto->metadata['target_language'])->toBe('en');
    });

    it('generates UUID when API response lacks id', function () {
        // Arrange: API response without id
        $apiResponse = [
            'text' => 'Translation without ID',
        ];

        // Act: Transform response
        $responseDto = $this->adapter->transformResponse($apiResponse);

        // Assert: Should generate UUID with prefix
        expect($responseDto->id)->toStartWith('audio_translation_')
            ->and(strlen($responseDto->id))->toBeGreaterThan(20);
    });
});
