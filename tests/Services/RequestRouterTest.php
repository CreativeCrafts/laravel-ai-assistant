<?php

declare(strict_types=1);

use CreativeCrafts\LaravelAiAssistant\Enums\OpenAiEndpoint;
use CreativeCrafts\LaravelAiAssistant\Services\RequestRouter;

beforeEach(function () {
    $this->router = new RequestRouter();
});

describe('Audio Transcription Routing', function () {
    it('routes audio transcription requests correctly', function () {
        $endpoint = $this->router->determineEndpoint([
            'audio' => [
                'file' => 'audio.mp3',
                'action' => 'transcribe',
            ],
        ]);

        expect($endpoint)->toBe(OpenAiEndpoint::AudioTranscription);
    });

    it('routes audio transcription with additional parameters', function () {
        $endpoint = $this->router->determineEndpoint([
            'audio' => [
                'file' => 'recording.wav',
                'action' => 'transcribe',
                'language' => 'en',
                'model' => 'whisper-1',
            ],
        ]);

        expect($endpoint)->toBe(OpenAiEndpoint::AudioTranscription);
    });

    it('does not route without audio file', function () {
        $endpoint = $this->router->determineEndpoint([
            'audio' => [
                'action' => 'transcribe',
            ],
        ]);

        expect($endpoint)->not->toBe(OpenAiEndpoint::AudioTranscription);
    });

    it('does not route without transcribe action', function () {
        $endpoint = $this->router->determineEndpoint([
            'audio' => [
                'file' => 'audio.mp3',
            ],
        ]);

        expect($endpoint)->not->toBe(OpenAiEndpoint::AudioTranscription);
    });
});

describe('Audio Translation Routing', function () {
    it('routes audio translation requests correctly', function () {
        $endpoint = $this->router->determineEndpoint([
            'audio' => [
                'file' => 'audio.mp3',
                'action' => 'translate',
            ],
        ]);

        expect($endpoint)->toBe(OpenAiEndpoint::AudioTranslation);
    });

    it('routes audio translation with additional parameters', function () {
        $endpoint = $this->router->determineEndpoint([
            'audio' => [
                'file' => 'german.mp3',
                'action' => 'translate',
                'model' => 'whisper-1',
            ],
        ]);

        expect($endpoint)->toBe(OpenAiEndpoint::AudioTranslation);
    });

    it('does not route without audio file', function () {
        $endpoint = $this->router->determineEndpoint([
            'audio' => [
                'action' => 'translate',
            ],
        ]);

        expect($endpoint)->not->toBe(OpenAiEndpoint::AudioTranslation);
    });
});

describe('Audio Speech Routing', function () {
    it('routes audio speech requests correctly', function () {
        $endpoint = $this->router->determineEndpoint([
            'audio' => [
                'text' => 'Hello, this is a test.',
                'action' => 'speech',
            ],
        ]);

        expect($endpoint)->toBe(OpenAiEndpoint::AudioSpeech);
    });

    it('routes audio speech with voice and format', function () {
        $endpoint = $this->router->determineEndpoint([
            'audio' => [
                'text' => 'Testing text-to-speech',
                'action' => 'speech',
                'voice' => 'nova',
                'format' => 'mp3',
            ],
        ]);

        expect($endpoint)->toBe(OpenAiEndpoint::AudioSpeech);
    });

    it('does not route without text', function () {
        $endpoint = $this->router->determineEndpoint([
            'audio' => [
                'action' => 'speech',
            ],
        ]);

        expect($endpoint)->not->toBe(OpenAiEndpoint::AudioSpeech);
    });

    it('does not route without speech action', function () {
        $endpoint = $this->router->determineEndpoint([
            'audio' => [
                'text' => 'Hello world',
            ],
        ]);

        expect($endpoint)->not->toBe(OpenAiEndpoint::AudioSpeech);
    });
});

describe('Image Generation Routing', function () {
    it('routes image generation requests correctly', function () {
        $endpoint = $this->router->determineEndpoint([
            'image' => [
                'prompt' => 'A beautiful sunset over mountains',
            ],
        ]);

        expect($endpoint)->toBe(OpenAiEndpoint::ImageGeneration);
    });

    it('routes image generation with size and quality', function () {
        $endpoint = $this->router->determineEndpoint([
            'image' => [
                'prompt' => 'A futuristic city',
                'size' => '1024x1024',
                'quality' => 'hd',
            ],
        ]);

        expect($endpoint)->toBe(OpenAiEndpoint::ImageGeneration);
    });

    it('does not route when image file is present', function () {
        $endpoint = $this->router->determineEndpoint([
            'image' => [
                'prompt' => 'A sunset',
                'image' => 'existing.png',
            ],
        ]);

        expect($endpoint)->not->toBe(OpenAiEndpoint::ImageGeneration);
    });

    it('does not route without prompt', function () {
        $endpoint = $this->router->determineEndpoint([
            'image' => [
                'size' => '1024x1024',
            ],
        ]);

        expect($endpoint)->not->toBe(OpenAiEndpoint::ImageGeneration);
    });
});

describe('Image Edit Routing', function () {
    it('routes image edit requests correctly', function () {
        $endpoint = $this->router->determineEndpoint([
            'image' => [
                'image' => 'photo.png',
                'prompt' => 'Add a red car',
            ],
        ]);

        expect($endpoint)->toBe(OpenAiEndpoint::ImageEdit);
    });

    it('routes image edit with mask', function () {
        $endpoint = $this->router->determineEndpoint([
            'image' => [
                'image' => 'photo.png',
                'prompt' => 'Change the sky to sunset',
                'mask' => 'mask.png',
            ],
        ]);

        expect($endpoint)->toBe(OpenAiEndpoint::ImageEdit);
    });

    it('does not route without image file', function () {
        $endpoint = $this->router->determineEndpoint([
            'image' => [
                'prompt' => 'Add a car',
            ],
        ]);

        expect($endpoint)->not->toBe(OpenAiEndpoint::ImageEdit);
    });

    it('does not route without prompt', function () {
        $endpoint = $this->router->determineEndpoint([
            'image' => [
                'image' => 'photo.png',
            ],
        ]);

        expect($endpoint)->not->toBe(OpenAiEndpoint::ImageEdit);
    });
});

describe('Image Variation Routing', function () {
    it('routes image variation requests correctly', function () {
        $endpoint = $this->router->determineEndpoint([
            'image' => [
                'image' => 'original.png',
            ],
        ]);

        expect($endpoint)->toBe(OpenAiEndpoint::ImageVariation);
    });

    it('routes image variation with size parameter', function () {
        $endpoint = $this->router->determineEndpoint([
            'image' => [
                'image' => 'original.png',
                'size' => '512x512',
            ],
        ]);

        expect($endpoint)->toBe(OpenAiEndpoint::ImageVariation);
    });

    it('does not route when prompt is present', function () {
        $endpoint = $this->router->determineEndpoint([
            'image' => [
                'image' => 'original.png',
                'prompt' => 'Make it blue',
            ],
        ]);

        expect($endpoint)->not->toBe(OpenAiEndpoint::ImageVariation);
    });

    it('does not route without image file', function () {
        $endpoint = $this->router->determineEndpoint([
            'image' => [
                'size' => '512x512',
            ],
        ]);

        expect($endpoint)->not->toBe(OpenAiEndpoint::ImageVariation);
    });
});

describe('Audio Input in Chat Context Routing', function () {
    it('routes audio input in chat context to Chat Completion', function () {
        $endpoint = $this->router->determineEndpoint([
            'audio_input' => [
                'file' => 'question.mp3',
            ],
            'message' => 'What do you hear in this audio?',
        ]);

        expect($endpoint)->toBe(OpenAiEndpoint::ChatCompletion);
    });

    it('routes audio input without message to Chat Completion', function () {
        $endpoint = $this->router->determineEndpoint([
            'audio_input' => [
                'file' => 'speech.mp3',
            ],
        ]);

        expect($endpoint)->toBe(OpenAiEndpoint::ChatCompletion);
    });

    it('prioritizes specific audio actions over audio_input', function () {
        $router = new RequestRouter(
            validateConflicts: false
        );

        $endpoint = $router->determineEndpoint([
            'audio' => [
                'file' => 'audio.mp3',
                'action' => 'transcribe',
            ],
            'audio_input' => [
                'file' => 'other.mp3',
            ],
        ]);

        expect($endpoint)->toBe(OpenAiEndpoint::AudioTranscription);
    });
});

describe('Default Response API Routing', function () {
    it('routes standard text messages to Response API', function () {
        $endpoint = $this->router->determineEndpoint([
            'message' => 'Hello, how are you?',
        ]);

        expect($endpoint)->toBe(OpenAiEndpoint::ResponseApi);
    });

    it('routes empty request to Response API', function () {
        $endpoint = $this->router->determineEndpoint([]);

        expect($endpoint)->toBe(OpenAiEndpoint::ResponseApi);
    });

    it('routes requests with only model parameter to Response API', function () {
        $endpoint = $this->router->determineEndpoint([
            'model' => 'gpt-4o',
            'temperature' => 0.7,
        ]);

        expect($endpoint)->toBe(OpenAiEndpoint::ResponseApi);
    });
});

describe('Priority and Edge Cases', function () {
    it('prioritizes audio transcription over other actions', function () {
        $endpoint = $this->router->determineEndpoint([
            'audio' => [
                'file' => 'audio.mp3',
                'action' => 'transcribe',
            ],
            'image' => [
                'prompt' => 'A cat',
            ],
        ]);

        expect($endpoint)->toBe(OpenAiEndpoint::AudioTranscription);
    });

    it('prioritizes image generation over audio input', function () {
        $endpoint = $this->router->determineEndpoint([
            'image' => [
                'prompt' => 'A landscape',
            ],
            'audio_input' => [
                'file' => 'audio.mp3',
            ],
        ]);

        expect($endpoint)->toBe(OpenAiEndpoint::ImageGeneration);
    });

    it('handles case-sensitive action values correctly', function () {
        $endpoint = $this->router->determineEndpoint([
            'audio' => [
                'file' => 'audio.mp3',
                'action' => 'TRANSCRIBE',
            ],
        ]);

        expect($endpoint)->not->toBe(OpenAiEndpoint::AudioTranscription);
        expect($endpoint)->toBe(OpenAiEndpoint::ResponseApi);
    });

    it('handles null audio action', function () {
        $endpoint = $this->router->determineEndpoint([
            'audio' => [
                'file' => 'audio.mp3',
                'action' => null,
            ],
        ]);

        expect($endpoint)->toBe(OpenAiEndpoint::ResponseApi);
    });

    it('handles empty audio array', function () {
        $endpoint = $this->router->determineEndpoint([
            'audio' => [],
        ]);

        expect($endpoint)->toBe(OpenAiEndpoint::ResponseApi);
    });

    it('handles empty image array', function () {
        $endpoint = $this->router->determineEndpoint([
            'image' => [],
        ]);

        expect($endpoint)->toBe(OpenAiEndpoint::ResponseApi);
    });

    it('correctly distinguishes between image edit and variation', function () {
        $editEndpoint = $this->router->determineEndpoint([
            'image' => [
                'image' => 'photo.png',
                'prompt' => 'Edit this',
            ],
        ]);

        $variationEndpoint = $this->router->determineEndpoint([
            'image' => [
                'image' => 'photo.png',
            ],
        ]);

        expect($editEndpoint)->toBe(OpenAiEndpoint::ImageEdit);
        expect($variationEndpoint)->toBe(OpenAiEndpoint::ImageVariation);
        expect($editEndpoint)->not->toBe($variationEndpoint);
    });
});
