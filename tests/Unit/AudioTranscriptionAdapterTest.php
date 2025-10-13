<?php

declare(strict_types=1);

use CreativeCrafts\LaravelAiAssistant\Adapters\AudioTranscriptionAdapter;
use CreativeCrafts\LaravelAiAssistant\DataTransferObjects\ResponseDto;
use CreativeCrafts\LaravelAiAssistant\Exceptions\AudioTranscriptionException;
use CreativeCrafts\LaravelAiAssistant\Exceptions\FileValidationException;

beforeEach(function () {
    $this->adapter = new AudioTranscriptionAdapter();
});

describe('transformRequest', function () {
    it('transforms unified request with all parameters', function () {
        $tempFile = tempnam(sys_get_temp_dir(), 'test_audio_') . '.mp3';
        file_put_contents($tempFile, 'test audio content');

        $unifiedRequest = [
            'audio' => [
                'file' => $tempFile,
                'model' => 'whisper-1',
                'language' => 'en',
                'prompt' => 'This is a test prompt',
                'response_format' => 'json',
                'temperature' => 0.5,
            ],
        ];

        $result = $this->adapter->transformRequest($unifiedRequest);

        expect($result)->toBe([
            'file' => $tempFile,
            'model' => 'whisper-1',
            'language' => 'en',
            'prompt' => 'This is a test prompt',
            'response_format' => 'json',
            'temperature' => 0.5,
        ]);

        unlink($tempFile);
    });

    it('applies default values for missing parameters', function () {
        $tempFile = tempnam(sys_get_temp_dir(), 'test_audio_') . '.mp3';
        file_put_contents($tempFile, 'test audio content');

        $unifiedRequest = [
            'audio' => [
                'file' => $tempFile,
            ],
        ];

        $result = $this->adapter->transformRequest($unifiedRequest);

        expect($result)->toBe([
            'file' => $tempFile,
            'model' => 'whisper-1',
            'language' => null,
            'prompt' => null,
            'response_format' => 'json',
            'temperature' => 0,
        ]);

        unlink($tempFile);
    });

    it('throws exception when audio array is empty', function () {
        $unifiedRequest = ['audio' => []];

        expect(fn () => $this->adapter->transformRequest($unifiedRequest))
            ->toThrow(AudioTranscriptionException::class, 'Audio file is required for transcription');
    });

    it('throws exception when audio key is missing', function () {
        $unifiedRequest = [];

        expect(fn () => $this->adapter->transformRequest($unifiedRequest))
            ->toThrow(AudioTranscriptionException::class, 'Audio file is required for transcription');
    });

    it('throws exception when file path is not a string', function () {
        $unifiedRequest = [
            'audio' => [
                'file' => 123,
            ],
        ];

        expect(fn () => $this->adapter->transformRequest($unifiedRequest))
            ->toThrow(FileValidationException::class, 'File path must be a string');
    });

    it('throws exception when file does not exist', function () {
        $unifiedRequest = [
            'audio' => [
                'file' => '/non/existent/path/audio.mp3',
            ],
        ];

        expect(fn () => $this->adapter->transformRequest($unifiedRequest))
            ->toThrow(FileValidationException::class, 'File not found');
    });

    it('throws exception when file is not readable', function () {
        $tempFile = tempnam(sys_get_temp_dir(), 'test_audio_') . '.mp3';
        file_put_contents($tempFile, 'test');
        chmod($tempFile, 0000);

        $unifiedRequest = [
            'audio' => [
                'file' => $tempFile,
            ],
        ];

        try {
            expect(fn () => $this->adapter->transformRequest($unifiedRequest))
                ->toThrow(FileValidationException::class, 'not readable');
        } finally {
            chmod($tempFile, 0644);
            unlink($tempFile);
        }
    });

    it('throws exception for unsupported file format', function () {
        $tempFile = tempnam(sys_get_temp_dir(), 'test_audio_') . '.txt';
        file_put_contents($tempFile, 'test');

        $unifiedRequest = [
            'audio' => [
                'file' => $tempFile,
            ],
        ];

        try {
            expect(fn () => $this->adapter->transformRequest($unifiedRequest))
                ->toThrow(AudioTranscriptionException::class, 'Unsupported audio format');
        } finally {
            unlink($tempFile);
        }
    });

    it('accepts all supported audio formats', function () {
        $supportedFormats = ['mp3', 'mp4', 'mpeg', 'mpga', 'm4a', 'wav', 'webm'];

        foreach ($supportedFormats as $format) {
            $tempFile = tempnam(sys_get_temp_dir(), 'test_audio_') . '.' . $format;
            file_put_contents($tempFile, 'test');

            $unifiedRequest = [
                'audio' => [
                    'file' => $tempFile,
                ],
            ];

            $result = $this->adapter->transformRequest($unifiedRequest);
            expect($result['file'])->toBe($tempFile);

            unlink($tempFile);
        }
    });
});

describe('transformResponse', function () {
    it('transforms OpenAI API response with all fields', function () {
        $apiResponse = [
            'id' => 'transcription_123',
            'text' => 'This is the transcribed text.',
            'duration' => 45.5,
            'language' => 'en',
        ];

        $result = $this->adapter->transformResponse($apiResponse);

        expect($result)->toBeInstanceOf(ResponseDto::class);
        expect($result->id)->toBe('transcription_123');
        expect($result->status)->toBe('completed');
        expect($result->text)->toBe('This is the transcribed text.');
        expect($result->type)->toBe('audio_transcription');
        expect($result->metadata)->toBe([
            'duration' => 45.5,
            'language' => 'en',
        ]);
        expect($result->raw)->toBe($apiResponse);
        expect($result->conversationId)->toBeNull();
        expect($result->audioContent)->toBeNull();
        expect($result->images)->toBeNull();
    });

    it('transforms response with minimal fields', function () {
        $apiResponse = [
            'text' => 'Minimal transcription.',
        ];

        $result = $this->adapter->transformResponse($apiResponse);

        expect($result)->toBeInstanceOf(ResponseDto::class);
        expect($result->text)->toBe('Minimal transcription.');
        expect($result->status)->toBe('completed');
        expect($result->type)->toBe('audio_transcription');
        expect($result->metadata)->toBe([
            'duration' => null,
            'language' => null,
        ]);
    });

    it('generates unique id when not provided', function () {
        $apiResponse = ['text' => 'Test'];

        $result1 = $this->adapter->transformResponse($apiResponse);
        $result2 = $this->adapter->transformResponse($apiResponse);

        expect($result1->id)->not->toBe($result2->id);
        expect($result1->id)->toContain('audio_transcription_');
        expect($result2->id)->toContain('audio_transcription_');
    });

    it('handles empty response', function () {
        $apiResponse = [];

        $result = $this->adapter->transformResponse($apiResponse);

        expect($result)->toBeInstanceOf(ResponseDto::class);
        expect($result->text)->toBeNull();
        expect($result->status)->toBe('completed');
        expect($result->type)->toBe('audio_transcription');
    });

    it('returns ResponseDto with correct helper method results', function () {
        $apiResponse = ['text' => 'Test transcription'];

        $result = $this->adapter->transformResponse($apiResponse);

        expect($result->isText())->toBeTrue();
        expect($result->isAudio())->toBeFalse();
        expect($result->isImage())->toBeFalse();
    });
});
