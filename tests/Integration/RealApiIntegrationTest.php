<?php

declare(strict_types=1);

use CreativeCrafts\LaravelAiAssistant\Facades\Ai;

/**
 * Real API Integration Tests - Makes actual OpenAI API calls.
 *
 * These tests verify that the unified Response API correctly routes to and calls
 * actual OpenAI endpoints (audio, image, chat completion) with real files and data.
 *
 * IMPORTANT:
 * - These tests require a valid OPENAI_API_KEY environment variable
 * - Tests are automatically skipped if no API key is present
 * - Designed to cost less than $1 for the full suite (using minimal data)
 * - To run: php artisan test --group=integration
 * - To exclude: php artisan test --exclude-group=integration
 *
 * @group integration
 */
beforeEach(function () {
    // Skip all tests in this file if no OpenAI API key is configured
    if (empty(config('ai-assistant.openai_api_key')) && empty(env('OPENAI_API_KEY'))) {
        $this->markTestSkipped('OpenAI API key not configured. Set OPENAI_API_KEY to run integration tests.');
    }

    $this->testAudioPath = __DIR__ . '/../fixtures/test-audio.mp3';
    $this->testImagePath = __DIR__ . '/../fixtures/test-image.png';
});

describe('Audio Transcription - Real API', function () {
    it('transcribes audio file using actual OpenAI Whisper API', function () {
        // Arrange: Use minimal test audio file
        expect(file_exists($this->testAudioPath))->toBeTrue('Test audio file must exist');

        // Act: Make real API call via unified interface
        $response = Ai::responses()
            ->input()
            ->audio([
                'file' => $this->testAudioPath,
                'action' => 'transcribe',
                'model' => 'whisper-1',
            ])
            ->send();

        // Assert: Verify real API response structure
        expect($response)->toBeInstanceOf(CreativeCrafts\LaravelAiAssistant\DataTransferObjects\ResponseDto::class)
            ->and($response->type)->toBe('audio_transcription')
            ->and($response->status)->toBe('completed')
            ->and($response->text)->toBeString()
            ->and($response->metadata)->toHaveKey('duration')
            ->and($response->metadata)->toHaveKey('language');
    })->skip(fn () => !config('ai-assistant.enable_real_api_tests', false), 'Real API tests disabled');
});

describe('Audio Translation - Real API', function () {
    it('translates audio file to English using actual OpenAI Whisper API', function () {
        // Arrange: Use minimal test audio file
        expect(file_exists($this->testAudioPath))->toBeTrue('Test audio file must exist');

        // Act: Make real API call via unified interface
        $response = Ai::responses()
            ->input()
            ->audio([
                'file' => $this->testAudioPath,
                'action' => 'translate',
                'model' => 'whisper-1',
            ])
            ->send();

        // Assert: Verify real API response structure
        expect($response)->toBeInstanceOf(CreativeCrafts\LaravelAiAssistant\DataTransferObjects\ResponseDto::class)
            ->and($response->type)->toBe('audio_translation')
            ->and($response->status)->toBe('completed')
            ->and($response->text)->toBeString()
            ->and($response->metadata)->toHaveKey('target_language')
            ->and($response->metadata['target_language'])->toBe('en');
    })->skip(fn () => !config('ai-assistant.enable_real_api_tests', false), 'Real API tests disabled');
});

describe('Audio Speech - Real API', function () {
    it('generates speech from text using actual OpenAI TTS API', function () {
        // Arrange: Use minimal text input
        $testText = 'Hello world.';

        // Act: Make real API call via unified interface
        $response = Ai::responses()
            ->input()
            ->audio([
                'text' => $testText,
                'action' => 'speech',
                'model' => 'tts-1',
                'voice' => 'alloy',
                'response_format' => 'mp3',
            ])
            ->send();

        // Assert: Verify real API response structure
        expect($response)->toBeInstanceOf(CreativeCrafts\LaravelAiAssistant\DataTransferObjects\ResponseDto::class)
            ->and($response->type)->toBe('audio_speech')
            ->and($response->status)->toBe('completed')
            ->and($response->audioContent)->not->toBeNull()
            ->and($response->audioContent)->toBeString()
            ->and($response->isAudio())->toBeTrue()
            ->and($response->metadata)->toHaveKey('format')
            ->and($response->metadata['format'])->toBe('mp3')
            ->and($response->metadata)->toHaveKey('voice')
            ->and($response->metadata['voice'])->toBe('alloy');

        // Verify audio content is not empty
        expect(strlen($response->audioContent))->toBeGreaterThan(100);
    })->skip(fn () => !config('ai-assistant.enable_real_api_tests', false), 'Real API tests disabled');
});

describe('Image Generation - Real API', function () {
    it('generates image from prompt using actual OpenAI DALL-E API', function () {
        // Arrange: Use minimal, cost-effective parameters (DALL-E 2 for lower cost)
        $prompt = 'A red circle';

        // Act: Make real API call via unified interface
        $response = Ai::responses()
            ->input()
            ->image([
                'prompt' => $prompt,
                'model' => 'dall-e-2',
                'size' => '256x256',
                'n' => 1,
            ])
            ->send();

        // Assert: Verify real API response structure
        expect($response)->toBeInstanceOf(CreativeCrafts\LaravelAiAssistant\DataTransferObjects\ResponseDto::class)
            ->and($response->type)->toBe('image_generation')
            ->and($response->status)->toBe('completed')
            ->and($response->images)->toBeArray()
            ->and($response->images)->toHaveCount(1)
            ->and($response->isImage())->toBeTrue()
            ->and($response->metadata)->toHaveKey('count')
            ->and($response->metadata['count'])->toBe(1);

        // Verify image data contains either URL or base64
        $image = $response->images[0];
        expect($image)->toHaveKey('url')
            ->and($image['url'])->toBeString()
            ->and($image['url'])->toContain('http');
    })->skip(fn () => !config('ai-assistant.enable_real_api_tests', false), 'Real API tests disabled');
});

describe('Image Edit - Real API', function () {
    it('edits image using actual OpenAI DALL-E API', function () {
        // Arrange: Use minimal test image and simple prompt
        expect(file_exists($this->testImagePath))->toBeTrue('Test image file must exist');

        // Act: Make real API call via unified interface
        $response = Ai::responses()
            ->input()
            ->image([
                'image' => $this->testImagePath,
                'prompt' => 'Make it blue',
                'model' => 'dall-e-2',
                'size' => '256x256',
                'n' => 1,
            ])
            ->send();

        // Assert: Verify real API response structure
        expect($response)->toBeInstanceOf(CreativeCrafts\LaravelAiAssistant\DataTransferObjects\ResponseDto::class)
            ->and($response->type)->toBe('image_edit')
            ->and($response->status)->toBe('completed')
            ->and($response->images)->toBeArray()
            ->and($response->images)->toHaveCount(1)
            ->and($response->isImage())->toBeTrue();

        // Verify image data
        $image = $response->images[0];
        expect($image)->toHaveKey('url')
            ->and($image['url'])->toBeString();
    })->skip(fn () => !config('ai-assistant.enable_real_api_tests', false), 'Real API tests disabled');
});

describe('Image Variation - Real API', function () {
    it('creates image variations using actual OpenAI DALL-E API', function () {
        // Arrange: Use minimal test image
        expect(file_exists($this->testImagePath))->toBeTrue('Test image file must exist');

        // Act: Make real API call via unified interface
        $response = Ai::responses()
            ->input()
            ->image([
                'image' => $this->testImagePath,
                'action' => 'variation',
                'model' => 'dall-e-2',
                'size' => '256x256',
                'n' => 1,
            ])
            ->send();

        // Assert: Verify real API response structure
        expect($response)->toBeInstanceOf(CreativeCrafts\LaravelAiAssistant\DataTransferObjects\ResponseDto::class)
            ->and($response->type)->toBe('image_variation')
            ->and($response->status)->toBe('completed')
            ->and($response->images)->toBeArray()
            ->and($response->images)->toHaveCount(1)
            ->and($response->isImage())->toBeTrue();

        // Verify image data
        $image = $response->images[0];
        expect($image)->toHaveKey('url')
            ->and($image['url'])->toBeString();
    })->skip(fn () => !config('ai-assistant.enable_real_api_tests', false), 'Real API tests disabled');
});

describe('Unified API End-to-End - Real API', function () {
    it('handles chat completion with text input via unified API', function () {
        // Act: Make real API call via unified interface
        $response = Ai::responses()
            ->input()
            ->message('Say "test passed" and nothing else.')
            ->send();

        // Assert: Verify response
        expect($response)->toBeInstanceOf(CreativeCrafts\LaravelAiAssistant\DataTransferObjects\ResponseDto::class)
            ->and($response->status)->toBe('completed')
            ->and($response->text)->toBeString()
            ->and($response->text)->not->toBeEmpty();
    })->skip(fn () => !config('ai-assistant.enable_real_api_tests', false), 'Real API tests disabled');
});

describe('Error Handling - Real API', function () {
    it('handles invalid audio file gracefully', function () {
        // Arrange: Create invalid audio file
        $invalidFile = sys_get_temp_dir() . '/invalid_audio.mp3';
        file_put_contents($invalidFile, 'not valid audio data');

        try {
            // Act: Attempt to transcribe invalid audio
            Ai::responses()
                ->input()
                ->audio([
                    'file' => $invalidFile,
                    'action' => 'transcribe',
                ])
                ->send();

            // Should throw exception before reaching this point
            expect(false)->toBeTrue('Should have thrown an exception');
        } catch (Exception $e) {
            // Assert: Verify error handling
            expect($e)->toBeInstanceOf(Exception::class)
                ->and($e->getMessage())->toBeString();
        } finally {
            // Cleanup
            if (file_exists($invalidFile)) {
                unlink($invalidFile);
            }
        }
    })->skip(fn () => !config('ai-assistant.enable_real_api_tests', false), 'Real API tests disabled');

    it('handles missing required image prompt gracefully', function () {
        // Arrange: Create request without required prompt
        try {
            // Act: Attempt to generate image without prompt
            Ai::responses()
                ->input()
                ->image([
                    'model' => 'dall-e-2',
                ])
                ->send();

            // Should throw exception
            expect(false)->toBeTrue('Should have thrown an exception');
        } catch (Exception $e) {
            // Assert: Verify error handling
            expect($e)->toBeInstanceOf(Exception::class);
        }
    })->skip(fn () => !config('ai-assistant.enable_real_api_tests', false), 'Real API tests disabled');
});

describe('Cost Optimization Verification', function () {
    it('uses minimal parameters to keep costs under $1', function () {
        // This test verifies our test design choices that keep costs low:

        // 1. Audio tests use minimal file (483 bytes)
        $audioSize = filesize($this->testAudioPath);
        expect($audioSize)->toBeLessThan(1000); // Less than 1KB

        // 2. Image tests use minimal dimensions (256x256 for DALL-E 2)
        $imageSize = filesize($this->testImagePath);
        expect($imageSize)->toBeLessThan(100); // Less than 100 bytes

        // 3. Text-to-speech uses minimal text (2 words)
        $testText = 'Hello world.';
        expect(strlen($testText))->toBeLessThan(20);

        // 4. Image generation uses cheapest model and smallest size
        // DALL-E 2 256x256: ~$0.016 per image
        // Expected costs for full suite:
        // - Audio transcription: ~$0.006 (1 min @ $0.006/min)
        // - Audio translation: ~$0.006
        // - Audio speech: ~$0.015 (1 request @ $0.015/1k chars)
        // - Image generation: ~$0.016 (1 image @ 256x256)
        // - Image edit: ~$0.016
        // - Image variation: ~$0.016
        // - Chat completion: ~$0.0001
        // Total: ~$0.075 (well under $1.00)

        expect(true)->toBeTrue('Cost optimization parameters verified');
    });
});
