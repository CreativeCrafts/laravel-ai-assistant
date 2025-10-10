<?php

declare(strict_types=1);

use CreativeCrafts\LaravelAiAssistant\Enums\AudioAction;
use CreativeCrafts\LaravelAiAssistant\Enums\OpenAiEndpoint;
use CreativeCrafts\LaravelAiAssistant\Services\RequestRouter;

beforeEach(function () {
    $this->router = new RequestRouter();
});

describe('determineEndpoint', function () {
    it('routes to audio transcription endpoint', function () {
        $inputData = [
            'audio' => [
                'file' => '/path/to/audio.mp3',
                'action' => AudioAction::Transcribe->value,
            ],
        ];

        $endpoint = $this->router->determineEndpoint($inputData);

        expect($endpoint)->toBe(OpenAiEndpoint::AudioTranscription);
    });

    it('routes to audio translation endpoint', function () {
        $inputData = [
            'audio' => [
                'file' => '/path/to/audio.mp3',
                'action' => AudioAction::Translate->value,
            ],
        ];

        $endpoint = $this->router->determineEndpoint($inputData);

        expect($endpoint)->toBe(OpenAiEndpoint::AudioTranslation);
    });

    it('routes to audio speech endpoint', function () {
        $inputData = [
            'audio' => [
                'text' => 'Hello, world!',
                'action' => AudioAction::Speech->value,
            ],
        ];

        $endpoint = $this->router->determineEndpoint($inputData);

        expect($endpoint)->toBe(OpenAiEndpoint::AudioSpeech);
    });

    it('routes to image generation endpoint', function () {
        $inputData = [
            'image' => [
                'prompt' => 'A beautiful sunset',
            ],
        ];

        $endpoint = $this->router->determineEndpoint($inputData);

        expect($endpoint)->toBe(OpenAiEndpoint::ImageGeneration);
    });

    it('routes to image edit endpoint', function () {
        $inputData = [
            'image' => [
                'image' => '/path/to/image.png',
                'prompt' => 'Add a cat to this image',
            ],
        ];

        $endpoint = $this->router->determineEndpoint($inputData);

        expect($endpoint)->toBe(OpenAiEndpoint::ImageEdit);
    });

    it('routes to image variation endpoint', function () {
        $inputData = [
            'image' => [
                'image' => '/path/to/image.png',
            ],
        ];

        $endpoint = $this->router->determineEndpoint($inputData);

        expect($endpoint)->toBe(OpenAiEndpoint::ImageVariation);
    });

    it('routes to chat completion endpoint for audio input in chat context', function () {
        $inputData = [
            'audio_input' => [
                'file' => '/path/to/audio.mp3',
            ],
            'message' => 'What do you hear in this audio?',
        ];

        $endpoint = $this->router->determineEndpoint($inputData);

        expect($endpoint)->toBe(OpenAiEndpoint::ChatCompletion);
    });

    it('defaults to response API for standard text requests', function () {
        $inputData = [
            'message' => 'Hello, how are you?',
        ];

        $endpoint = $this->router->determineEndpoint($inputData);

        expect($endpoint)->toBe(OpenAiEndpoint::ResponseApi);
    });

    it('defaults to response API for empty input', function () {
        $inputData = [];

        $endpoint = $this->router->determineEndpoint($inputData);

        expect($endpoint)->toBe(OpenAiEndpoint::ResponseApi);
    });
});

describe('routing priority', function () {
    it('prioritizes audio transcription over chat completion', function () {
        $inputData = [
            'audio' => [
                'file' => '/path/to/audio.mp3',
                'action' => AudioAction::Transcribe->value,
            ],
            'audio_input' => [
                'file' => '/path/to/audio.mp3',
            ],
        ];

        $endpoint = $this->router->determineEndpoint($inputData);

        expect($endpoint)->toBe(OpenAiEndpoint::AudioTranscription);
    });

    it('prioritizes audio translation over audio speech', function () {
        $inputData = [
            'audio' => [
                'file' => '/path/to/audio.mp3',
                'text' => 'Some text',
                'action' => AudioAction::Translate->value,
            ],
        ];

        $endpoint = $this->router->determineEndpoint($inputData);

        expect($endpoint)->toBe(OpenAiEndpoint::AudioTranslation);
    });

    it('prioritizes image generation over audio input', function () {
        $inputData = [
            'image' => [
                'prompt' => 'A sunset',
            ],
            'audio_input' => [
                'file' => '/path/to/audio.mp3',
            ],
        ];

        $endpoint = $this->router->determineEndpoint($inputData);

        expect($endpoint)->toBe(OpenAiEndpoint::ImageGeneration);
    });

    it('prioritizes image edit over image variation', function () {
        $inputData = [
            'image' => [
                'image' => '/path/to/image.png',
                'prompt' => 'Edit this image',
            ],
        ];

        $endpoint = $this->router->determineEndpoint($inputData);

        expect($endpoint)->toBe(OpenAiEndpoint::ImageEdit);
    });
});

describe('edge cases', function () {
    it('handles audio file without action as default response API', function () {
        $inputData = [
            'audio' => [
                'file' => '/path/to/audio.mp3',
            ],
        ];

        $endpoint = $this->router->determineEndpoint($inputData);

        expect($endpoint)->toBe(OpenAiEndpoint::ResponseApi);
    });

    it('handles audio text without action as default response API', function () {
        $inputData = [
            'audio' => [
                'text' => 'Some text',
            ],
        ];

        $endpoint = $this->router->determineEndpoint($inputData);

        expect($endpoint)->toBe(OpenAiEndpoint::ResponseApi);
    });

    it('handles image prompt with image file (should be edit, not generation)', function () {
        $inputData = [
            'image' => [
                'image' => '/path/to/image.png',
                'prompt' => 'A sunset',
            ],
        ];

        $endpoint = $this->router->determineEndpoint($inputData);

        expect($endpoint)->toBe(OpenAiEndpoint::ImageEdit);
    });

    it('handles invalid action value as default response API', function () {
        $inputData = [
            'audio' => [
                'file' => '/path/to/audio.mp3',
                'action' => 'invalid_action',
            ],
        ];

        $endpoint = $this->router->determineEndpoint($inputData);

        expect($endpoint)->toBe(OpenAiEndpoint::ResponseApi);
    });

    it('handles null values in audio data', function () {
        $inputData = [
            'audio' => [
                'file' => null,
                'action' => AudioAction::Transcribe->value,
            ],
        ];

        $endpoint = $this->router->determineEndpoint($inputData);

        expect($endpoint)->toBe(OpenAiEndpoint::ResponseApi);
    });

    it('handles null values in image data', function () {
        $inputData = [
            'image' => [
                'prompt' => null,
            ],
        ];

        $endpoint = $this->router->determineEndpoint($inputData);

        expect($endpoint)->toBe(OpenAiEndpoint::ResponseApi);
    });

    it('handles non-array audio value', function () {
        $inputData = [
            'audio' => 'not an array',
        ];

        $endpoint = $this->router->determineEndpoint($inputData);

        expect($endpoint)->toBe(OpenAiEndpoint::ResponseApi);
    });

    it('handles non-array image value', function () {
        $inputData = [
            'image' => 'not an array',
        ];

        $endpoint = $this->router->determineEndpoint($inputData);

        expect($endpoint)->toBe(OpenAiEndpoint::ResponseApi);
    });
});
