<?php

declare(strict_types=1);

use CreativeCrafts\LaravelAiAssistant\DataTransferObjects\ResponseDto;
use CreativeCrafts\LaravelAiAssistant\Facades\Ai;
use CreativeCrafts\LaravelAiAssistant\Tests\Fakes\FakeOpenAITransport;
use CreativeCrafts\LaravelAiAssistant\Transport\OpenAITransport;

/**
 * Comprehensive Integration Tests for Task 3: Add Comprehensive Integration Tests
 *
 * These tests verify the complete chain for all 8 endpoint types through the unified API:
 * 1. Audio Transcription
 * 2. Audio Translation
 * 3. Audio Speech (TTS)
 * 4. Image Generation
 * 5. Image Edit
 * 6. Image Variation
 * 7. Audio Input in Chat Context
 * 8. Text Message (Response API default)
 *
 * Acceptance Criteria:
 * - Full integration tests for all 8 endpoint types
 * - Tests validate actual API calls (with mocking)
 * - Tests validate error handling
 * - Tests run in CI/CD pipeline
 * - Code coverage for integration flows > 90%
 */
describe('Unified API Integration Tests', function () {
    beforeEach(function () {
        $this->fakeTransport = new FakeOpenAITransport();
        $this->app->singleton(OpenAITransport::class, fn () => $this->fakeTransport);
        $this->tempFiles = [];
    });

    afterEach(function () {
        foreach ($this->tempFiles as $file) {
            if (file_exists($file)) {
                unlink($file);
            }
        }
    });

    describe('Endpoint Type 1: Audio Transcription', function () {
        it('sends audio transcription via unified API', function () {
            $this->tempFiles[] = $tempFile = tempnam(sys_get_temp_dir(), 'test_audio_') . '.mp3';
            file_put_contents($tempFile, 'mock audio content');

            $this->fakeTransport->responses['/v1/audio/transcriptions'] = [
                'id' => 'transcription_123',
                'text' => 'This is a test transcription',
                'duration' => 45.3,
                'language' => 'en',
            ];

            $response = Ai::responses()
                ->input()
                ->audio([
                    'file' => $tempFile,
                    'action' => 'transcribe',
                ])
                ->send();

            expect($response)->toBeInstanceOf(ResponseDto::class)
                ->and($response->text)->toBe('This is a test transcription')
                ->and($response->type)->toBe('audio_transcription')
                ->and($response->status)->toBe('completed')
                ->and($response->isText())->toBeTrue();
        });

        it('sends audio transcription with additional parameters', function () {
            $this->tempFiles[] = $tempFile = tempnam(sys_get_temp_dir(), 'test_audio_') . '.mp3';
            file_put_contents($tempFile, 'mock audio content');

            $this->fakeTransport->responses['/v1/audio/transcriptions'] = [
                'id' => 'transcription_456',
                'text' => 'Detailed transcription with metadata',
                'duration' => 120.5,
                'language' => 'es',
            ];

            $response = Ai::responses()
                ->input()
                ->audio([
                    'file' => $tempFile,
                    'action' => 'transcribe',
                    'model' => 'whisper-1',
                    'language' => 'es',
                    'temperature' => 0.2,
                ])
                ->send();

            expect($response)->toBeInstanceOf(ResponseDto::class)
                ->and($response->text)->toBe('Detailed transcription with metadata')
                ->and($response->metadata['language'])->toBe('es');
        });
    });

    describe('Endpoint Type 2: Audio Translation', function () {
        it('sends audio translation via unified API', function () {
            $this->tempFiles[] = $tempFile = tempnam(sys_get_temp_dir(), 'test_audio_') . '.mp3';
            file_put_contents($tempFile, 'mock audio content');

            $this->fakeTransport->responses['/v1/audio/translations'] = [
                'id' => 'translation_789',
                'text' => 'This is a test translation to English',
                'duration' => 60.0,
                'language' => 'en',
            ];

            $response = Ai::responses()
                ->input()
                ->audio([
                    'file' => $tempFile,
                    'action' => 'translate',
                ])
                ->send();

            expect($response)->toBeInstanceOf(ResponseDto::class)
                ->and($response->text)->toBe('This is a test translation to English')
                ->and($response->type)->toBe('audio_translation')
                ->and($response->status)->toBe('completed')
                ->and($response->isText())->toBeTrue();
        });
    });

    describe('Endpoint Type 3: Audio Speech (TTS)', function () {
        it('sends audio speech generation via unified API', function () {
            $this->fakeTransport->responses['/v1/audio/speech'] = [
                'id' => 'speech_101',
                'content' => base64_encode('mock audio binary data'),
                'format' => 'mp3',
            ];

            $response = Ai::responses()
                ->input()
                ->audio([
                    'text' => 'Hello, this is a test speech',
                    'action' => 'speech',
                ])
                ->send();

            expect($response)->toBeInstanceOf(ResponseDto::class)
                ->and($response->type)->toBe('audio_speech')
                ->and($response->status)->toBe('completed')
                ->and($response->audioContent)->not->toBeNull()
                ->and($response->isAudio())->toBeTrue();
        });

        it('sends audio speech with custom voice and model', function () {
            $this->fakeTransport->responses['/v1/audio/speech'] = [
                'id' => 'speech_202',
                'content' => base64_encode('custom voice audio'),
                'format' => 'mp3',
            ];

            $response = Ai::responses()
                ->input()
                ->audio([
                    'text' => 'Custom voice test',
                    'action' => 'speech',
                    'voice' => 'alloy',
                    'model' => 'tts-1-hd',
                ])
                ->send();

            expect($response)->toBeInstanceOf(ResponseDto::class)
                ->and($response->audioContent)->not->toBeNull()
                ->and($response->isAudio())->toBeTrue();
        });
    });

    describe('Endpoint Type 4: Image Generation', function () {
        it('sends image generation via unified API', function () {
            $this->fakeTransport->responses['/v1/images/generations'] = [
                'created' => time(),
                'data' => [
                    ['url' => 'https://example.com/generated-image.png'],
                ],
            ];

            $response = Ai::responses()
                ->input()
                ->image([
                    'prompt' => 'A beautiful sunset over mountains',
                ])
                ->send();

            expect($response)->toBeInstanceOf(ResponseDto::class)
                ->and($response->type)->toBe('image_generation')
                ->and($response->status)->toBe('completed')
                ->and($response->images)->toBeArray()
                ->and($response->images)->toHaveCount(1)
                ->and($response->isImage())->toBeTrue();
        });

        it('sends image generation with multiple images', function () {
            $this->fakeTransport->responses['/v1/images/generations'] = [
                'created' => time(),
                'data' => [
                    ['url' => 'https://example.com/image1.png'],
                    ['url' => 'https://example.com/image2.png'],
                    ['url' => 'https://example.com/image3.png'],
                ],
            ];

            $response = Ai::responses()
                ->input()
                ->image([
                    'prompt' => 'Three variations of a cat',
                    'n' => 3,
                ])
                ->send();

            expect($response)->toBeInstanceOf(ResponseDto::class)
                ->and($response->images)->toHaveCount(3)
                ->and($response->isImage())->toBeTrue();
        });
    });

    describe('Endpoint Type 5: Image Edit', function () {
        it('sends image edit via unified API', function () {
            $this->tempFiles[] = $tempFile = tempnam(sys_get_temp_dir(), 'test_image_') . '.png';
            file_put_contents($tempFile, 'mock image content');

            $this->fakeTransport->responses['/v1/images/edits'] = [
                'created' => time(),
                'data' => [
                    ['url' => 'https://example.com/edited-image.png'],
                ],
            ];

            $response = Ai::responses()
                ->input()
                ->image([
                    'image' => $tempFile,
                    'prompt' => 'Add a rainbow to the sky',
                ])
                ->send();

            expect($response)->toBeInstanceOf(ResponseDto::class)
                ->and($response->type)->toBe('image_edit')
                ->and($response->status)->toBe('completed')
                ->and($response->images)->toBeArray()
                ->and($response->images)->toHaveCount(1)
                ->and($response->isImage())->toBeTrue();
        });

        it('sends image edit with mask', function () {
            $this->tempFiles[] = $imageFile = tempnam(sys_get_temp_dir(), 'test_image_') . '.png';
            $this->tempFiles[] = $maskFile = tempnam(sys_get_temp_dir(), 'test_mask_') . '.png';
            file_put_contents($imageFile, 'mock image content');
            file_put_contents($maskFile, 'mock mask content');

            $this->fakeTransport->responses['/v1/images/edits'] = [
                'created' => time(),
                'data' => [
                    ['url' => 'https://example.com/masked-edit.png'],
                ],
            ];

            $response = Ai::responses()
                ->input()
                ->image([
                    'image' => $imageFile,
                    'mask' => $maskFile,
                    'prompt' => 'Replace the masked area with a tree',
                ])
                ->send();

            expect($response)->toBeInstanceOf(ResponseDto::class)
                ->and($response->images)->toHaveCount(1)
                ->and($response->isImage())->toBeTrue();
        });
    });

    describe('Endpoint Type 6: Image Variation', function () {
        it('sends image variation via unified API', function () {
            $this->tempFiles[] = $tempFile = tempnam(sys_get_temp_dir(), 'test_image_') . '.png';
            file_put_contents($tempFile, 'mock image content');

            $this->fakeTransport->responses['/v1/images/variations'] = [
                'created' => time(),
                'data' => [
                    ['url' => 'https://example.com/variation1.png'],
                ],
            ];

            $response = Ai::responses()
                ->input()
                ->image([
                    'image' => $tempFile,
                ])
                ->send();

            expect($response)->toBeInstanceOf(ResponseDto::class)
                ->and($response->type)->toBe('image_variation')
                ->and($response->status)->toBe('completed')
                ->and($response->images)->toBeArray()
                ->and($response->images)->toHaveCount(1)
                ->and($response->isImage())->toBeTrue();
        });

        it('sends multiple image variations', function () {
            $this->tempFiles[] = $tempFile = tempnam(sys_get_temp_dir(), 'test_image_') . '.png';
            file_put_contents($tempFile, 'mock image content');

            $this->fakeTransport->responses['/v1/images/variations'] = [
                'created' => time(),
                'data' => [
                    ['url' => 'https://example.com/variation1.png'],
                    ['url' => 'https://example.com/variation2.png'],
                ],
            ];

            $response = Ai::responses()
                ->input()
                ->image([
                    'image' => $tempFile,
                    'n' => 2,
                ])
                ->send();

            expect($response)->toBeInstanceOf(ResponseDto::class)
                ->and($response->images)->toHaveCount(2)
                ->and($response->isImage())->toBeTrue();
        });
    });

    describe('Endpoint Type 7: Audio Input in Chat Context', function () {
        it('sends audio input in chat context via unified API', function () {
            $this->tempFiles[] = $tempFile = tempnam(sys_get_temp_dir(), 'test_audio_') . '.mp3';
            file_put_contents($tempFile, 'mock audio content');

            $this->fakeTransport->responses['/v1/chat/completions'] = [
                'id' => 'chatcmpl_audio_123',
                'object' => 'chat.completion',
                'created' => time(),
                'model' => 'gpt-4o-mini',
                'choices' => [
                    [
                        'index' => 0,
                        'message' => [
                            'role' => 'assistant',
                            'content' => 'I heard your audio message and here is my response.',
                        ],
                        'finish_reason' => 'stop',
                    ],
                ],
            ];

            $response = Ai::responses()
                ->input()
                ->audioInput([
                    'file' => $tempFile,
                ])
                ->send();

            expect($response)->toBeInstanceOf(ResponseDto::class)
                ->and($response->text)->toBe('I heard your audio message and here is my response.')
                ->and($response->type)->toBe('chat_completion')
                ->and($response->status)->toBe('completed');
        });
    });

    describe('Endpoint Type 8: Text Message (Response API Default)', function () {
        it('routes text messages to Response API endpoint', function () {
            $inputBuilder = Ai::responses()->input()->message('Hello');
            $inputData = $inputBuilder->toArray();

            $router = new CreativeCrafts\LaravelAiAssistant\Services\RequestRouter();
            $endpoint = $router->determineEndpoint($inputData);

            expect($endpoint)->toBe(CreativeCrafts\LaravelAiAssistant\Enums\OpenAiEndpoint::ResponseApi);
        });

        it('verifies Response API adapter is used for text messages', function () {
            $factory = new CreativeCrafts\LaravelAiAssistant\Adapters\AdapterFactory();
            $adapter = $factory->make(CreativeCrafts\LaravelAiAssistant\Enums\OpenAiEndpoint::ResponseApi);

            expect($adapter)->toBeInstanceOf(CreativeCrafts\LaravelAiAssistant\Adapters\ResponseApiAdapter::class);
        });
    });

    describe('Error Handling: Missing Required Parameters', function () {
        it('throws exception when missing required audio file for transcription', function () {
            expect(
                fn () => Ai::responses()
                ->input()
                ->audio([
                    'action' => 'transcribe',
                ])
                ->send()
            )->toThrow(InvalidArgumentException::class);
        });

        it('throws exception when missing required audio file for translation', function () {
            expect(
                fn () => Ai::responses()
                ->input()
                ->audio([
                    'action' => 'translate',
                ])
                ->send()
            )->toThrow(InvalidArgumentException::class);
        });

        it('throws exception when missing required text for speech generation', function () {
            expect(
                fn () => Ai::responses()
                ->input()
                ->audio([
                    'action' => 'speech',
                ])
                ->send()
            )->toThrow(InvalidArgumentException::class);
        });

        it('throws exception when missing required prompt for image generation', function () {
            expect(
                fn () => Ai::responses()
                ->input()
                ->image([])
                ->send()
            )->toThrow(InvalidArgumentException::class);
        });

        it('throws exception when missing required image file for image variation', function () {
            expect(
                fn () => Ai::responses()
                ->input()
                ->image([
                    'n' => 2,
                ])
                ->send()
            )->toThrow(InvalidArgumentException::class);
        });

        it('throws exception when no input provided at all', function () {
            expect(
                fn () => Ai::responses()->send()
            )->toThrow(InvalidArgumentException::class, 'No input provided');
        });

        it('validates error message includes usage patterns when no input provided', function () {
            try {
                Ai::responses()->send();
                expect(false)->toBeTrue('Expected exception was not thrown');
            } catch (InvalidArgumentException $e) {
                expect($e->getMessage())
                    ->toContain('No input provided')
                    ->toContain('->input()->message($text)')
                    ->toContain('->input()->audio($config)')
                    ->toContain('->input()->image($config)')
                    ->toContain('->inputItems()->appendUserText($text)')
                    ->toContain('Current builder state:');
            }
        });
    });

    describe('Error Handling: Invalid Configurations', function () {
        it('throws exception for invalid audio file format', function () {
            $this->tempFiles[] = $tempFile = tempnam(sys_get_temp_dir(), 'test_file_') . '.txt';
            file_put_contents($tempFile, 'not an audio file');

            expect(
                fn () => Ai::responses()
                ->input()
                ->audio([
                    'file' => $tempFile,
                    'action' => 'transcribe',
                ])
                ->send()
            )->toThrow(Exception::class);
        });

        it('throws exception for non-existent audio file', function () {
            expect(
                fn () => Ai::responses()
                ->input()
                ->audio([
                    'file' => '/tmp/non_existent_audio_file_xyz.mp3',
                    'action' => 'transcribe',
                ])
                ->send()
            )->toThrow(Exception::class);
        });

        it('throws exception for invalid image file format', function () {
            $this->tempFiles[] = $tempFile = tempnam(sys_get_temp_dir(), 'test_file_') . '.txt';
            file_put_contents($tempFile, 'not an image file');

            expect(
                fn () => Ai::responses()
                ->input()
                ->image([
                    'image' => $tempFile,
                ])
                ->send()
            )->toThrow(Exception::class);
        });

        it('throws exception for non-existent image file', function () {
            expect(
                fn () => Ai::responses()
                ->input()
                ->image([
                    'image' => '/tmp/non_existent_image_xyz.png',
                ])
                ->send()
            )->toThrow(Exception::class);
        });

        it('throws exception for invalid audio action', function () {
            $this->tempFiles[] = $tempFile = tempnam(sys_get_temp_dir(), 'test_audio_') . '.mp3';
            file_put_contents($tempFile, 'mock audio content');

            expect(
                fn () => Ai::responses()
                ->input()
                ->audio([
                    'file' => $tempFile,
                    'action' => 'invalid_action',
                ])
                ->send()
            )->toThrow(Exception::class);
        });
    });

    describe('Integration: Chaining Multiple Operations', function () {
        it('allows resetting input between requests', function () {
            $this->tempFiles[] = $audioFile = tempnam(sys_get_temp_dir(), 'test_audio_') . '.mp3';
            file_put_contents($audioFile, 'mock audio content');

            $this->fakeTransport->responses['/v1/images/generations'] = [
                'created' => time(),
                'data' => [
                    ['url' => 'https://example.com/image1.png'],
                ],
            ];

            $this->fakeTransport->responses['/v1/audio/transcriptions'] = [
                'id' => 'transcription_chain_123',
                'text' => 'Transcribed audio from second request',
                'duration' => 30.0,
                'language' => 'en',
            ];

            $imageResponse = Ai::responses()
                ->input()
                ->image(['prompt' => 'A sunset'])
                ->send();

            expect($imageResponse)->toBeInstanceOf(ResponseDto::class)
                ->and($imageResponse->type)->toBe('image_generation')
                ->and($imageResponse->isImage())->toBeTrue();

            $audioResponse = Ai::responses()
                ->input()
                ->audio([
                    'file' => $audioFile,
                    'action' => 'transcribe',
                ])
                ->send();

            expect($audioResponse)->toBeInstanceOf(ResponseDto::class)
                ->and($audioResponse->type)->toBe('audio_transcription')
                ->and($audioResponse->text)->toBe('Transcribed audio from second request');
        });
    });
});
