<?php

declare(strict_types=1);

use CreativeCrafts\LaravelAiAssistant\Facades\Ai;
use CreativeCrafts\LaravelAiAssistant\Tests\Fakes\FakeOpenAITransport;
use CreativeCrafts\LaravelAiAssistant\Transport\OpenAITransport;

/**
 * Feature test to verify Task 1: Fix the Builder Chain
 *
 * Tests that the InputBuilder mutable pattern correctly allows
 * ResponsesBuilder to receive updated data when send() is called.
 *
 * Acceptance Criteria:
 * - All InputBuilder methods return $this after modifying internal state
 * - ResponsesBuilder receives updated data when send() is called
 * - Pattern Ai::responses()->input()->audio([...])->send() works correctly
 */
describe('Unified API Builder Chain', function () {
    beforeEach(function () {
        // Create a fake transport with mock responses
        $fakeTransport = new FakeOpenAITransport();

        // Configure mock responses for different endpoints
        $fakeTransport->responses = [
            '/v1/audio/transcriptions' => [
                'text' => 'This is a test transcription',
            ],
            '/v1/audio/translations' => [
                'text' => 'This is a test translation',
            ],
            '/v1/images/generations' => [
                'data' => [
                    ['url' => 'https://example.com/image.png'],
                ],
            ],
        ];

        // Bind the fake transport to the container
        $this->app->singleton(OpenAITransport::class, fn () => $fakeTransport);
    });

    it('correctly chains input builder audio transcription with send', function () {
        // Create a temporary test file
        $tempFile = tempnam(sys_get_temp_dir(), 'test_audio_') . '.mp3';
        file_put_contents($tempFile, 'mock audio content');

        try {
            // Test the full chain pattern: Ai::responses()->input()->audio([...])->send()
            $response = Ai::responses()
                ->input()
                ->audio([
                    'file' => $tempFile,
                    'action' => 'transcribe',
                ])
                ->send();

            // Verify response was received (not null/empty)
            expect($response)->not->toBeNull()
                ->and($response->text)->toBe('This is a test transcription');
        } finally {
            // Clean up
            if (file_exists($tempFile)) {
                unlink($tempFile);
            }
        }
    });

    it('correctly chains input builder audio translation with send', function () {
        // Create a temporary test file
        $tempFile = tempnam(sys_get_temp_dir(), 'test_audio_') . '.mp3';
        file_put_contents($tempFile, 'mock audio content');

        try {
            $response = Ai::responses()
                ->input()
                ->audio([
                    'file' => $tempFile,
                    'action' => 'translate',
                ])
                ->send();

            expect($response)->not->toBeNull()
                ->and($response->text)->toBe('This is a test translation');
        } finally {
            if (file_exists($tempFile)) {
                unlink($tempFile);
            }
        }
    });

    it('correctly chains input builder image generation with send', function () {
        $response = Ai::responses()
            ->input()
            ->image([
                'prompt' => 'A beautiful sunset',
            ])
            ->send();

        expect($response)->not->toBeNull()
            ->and($response->images)->toBeArray()
            ->and($response->images)->toHaveCount(1);
    });

    it('verifies input builder is mutable and returns same instance', function () {
        $builder = Ai::responses();
        $inputBuilder1 = $builder->input();
        $inputBuilder2 = $inputBuilder1->message('Test');

        // With mutable pattern, inputBuilder2 should be the same instance as inputBuilder1
        expect($inputBuilder1)->toBe($inputBuilder2)
            ->and($inputBuilder1->toArray())->toHaveKey('message')
            ->and($inputBuilder1->toArray()['message'])->toBe('Test');
    });

    it('verifies ResponsesBuilder receives updated data from InputBuilder', function () {
        // Create a temporary test file
        $tempFile = tempnam(sys_get_temp_dir(), 'test_audio_') . '.mp3';
        file_put_contents($tempFile, 'mock audio content');

        try {
            $builder = Ai::responses();

            // Get input builder reference
            $inputBuilder = $builder->input();

            // Modify input builder
            $inputBuilder->audio([
                'file' => $tempFile,
                'action' => 'transcribe',
            ]);

            // Verify that the input builder has the audio data
            expect($inputBuilder->toArray())->toHaveKey('audio')
                ->and($inputBuilder->toArray()['audio']['file'])->toBe($tempFile);

            // Send through ResponsesBuilder - it should have access to the updated data
            $response = $builder->send();

            // If we get a response, it means ResponsesBuilder successfully accessed the data
            expect($response)->not->toBeNull();
        } finally {
            if (file_exists($tempFile)) {
                unlink($tempFile);
            }
        }
    });
});
