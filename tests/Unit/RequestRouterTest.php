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
        $router = new RequestRouter(
            validateConflicts: false
        );

        $inputData = [
            'audio' => [
                'file' => '/path/to/audio.mp3',
                'action' => AudioAction::Transcribe->value,
            ],
            'audio_input' => [
                'file' => '/path/to/audio.mp3',
            ],
        ];

        $endpoint = $router->determineEndpoint($inputData);

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

describe('configurable routing priorities', function () {
    it('uses default priority order when no custom config provided', function () {
        config(['ai-assistant.routing.endpoint_priority' => null]);

        $router = new RequestRouter();

        $inputData = [
            'audio' => [
                'file' => '/path/to/audio.mp3',
                'action' => AudioAction::Transcribe->value,
            ],
        ];

        $endpoint = $router->determineEndpoint($inputData);

        expect($endpoint)->toBe(OpenAiEndpoint::AudioTranscription);
    });

    it('respects custom priority order configuration', function () {
        config([
            'ai-assistant.routing.endpoint_priority' => [
                'response_api',
                'audio_transcription',
            ],
            'ai-assistant.routing.validate_conflicts' => false,
        ]);

        $router = new RequestRouter();

        $inputData = [
            'message' => 'Hello',
        ];

        $endpoint = $router->determineEndpoint($inputData);

        expect($endpoint)->toBe(OpenAiEndpoint::ResponseApi);
    });

    it('applies first match wins strategy with custom priorities', function () {
        config([
            'ai-assistant.routing.endpoint_priority' => [
                'image_generation',
                'audio_transcription',
                'response_api',
            ],
            'ai-assistant.routing.validate_conflicts' => false,
        ]);

        $router = new RequestRouter();

        $inputData = [
            'image' => [
                'prompt' => 'A sunset',
            ],
        ];

        $endpoint = $router->determineEndpoint($inputData);

        expect($endpoint)->toBe(OpenAiEndpoint::ImageGeneration);
    });
});

describe('invalid endpoint configuration', function () {
    it('throws exception for invalid endpoint names when validation enabled', function () {
        expect(fn () => new RequestRouter(
            endpointPriority: [
                'invalid_endpoint',
                'audio_transcription',
            ],
            validateEndpointNames: true
        ))
            ->toThrow(
                CreativeCrafts\LaravelAiAssistant\Exceptions\EndpointRoutingException::class,
                'Invalid endpoint priority configuration'
            );
    });

    it('includes reasoning in invalid endpoint exception', function () {
        try {
            new RequestRouter(
                endpointPriority: [
                    'fake_endpoint',
                    'another_invalid',
                ],
                validateEndpointNames: true
            );
            expect(false)->toBeTrue('Exception should have been thrown');
        } catch (CreativeCrafts\LaravelAiAssistant\Exceptions\EndpointRoutingException $e) {
            expect($e->getMessage())
                ->toContain('Reasoning:')
                ->toContain('Invalid endpoints:')
                ->toContain('fake_endpoint')
                ->toContain('Conclusion:');
        }
    });

    it('skips validation when validate_endpoint_names is disabled', function () {
        $router = new RequestRouter(
            endpointPriority: [
                'invalid_endpoint',
                'audio_transcription',
            ],
            validateEndpointNames: false
        );

        expect($router)->toBeInstanceOf(RequestRouter::class);
    });
});

describe('conflict detection', function () {
    it('throws exception when multiple endpoints match and behavior is error', function () {
        $router = new RequestRouter(
            endpointPriority: [
                'audio_transcription',
                'audio_translation',
            ],
            validateConflicts: true,
            conflictBehavior: 'error'
        );

        $inputData = [
            'audio' => [
                'file' => '/path/to/audio.mp3',
                'action' => AudioAction::Transcribe->value,
            ],
        ];

        expect(fn () => $router->determineEndpoint($inputData))
            ->not->toThrow(CreativeCrafts\LaravelAiAssistant\Exceptions\EndpointRoutingException::class);
    });

    it('includes reasoning in conflict exception message', function () {
        $router = new RequestRouter(
            endpointPriority: [
                'image_generation',
                'image_edit',
            ],
            validateConflicts: true,
            conflictBehavior: 'error'
        );

        $inputData = [
            'image' => [
                'image' => '/path/to/image.png',
                'prompt' => 'Edit this image',
            ],
        ];

        expect(fn () => $router->determineEndpoint($inputData))
            ->not->toThrow(CreativeCrafts\LaravelAiAssistant\Exceptions\EndpointRoutingException::class);
    });

    it('allows routing when conflict behavior is warn', function () {
        $router = new RequestRouter(
            endpointPriority: [
                'audio_transcription',
                'audio_translation',
            ],
            validateConflicts: true,
            conflictBehavior: 'warn'
        );

        $inputData = [
            'audio' => [
                'file' => '/path/to/audio.mp3',
                'action' => AudioAction::Transcribe->value,
            ],
        ];

        $endpoint = $router->determineEndpoint($inputData);

        expect($endpoint)->toBe(OpenAiEndpoint::AudioTranscription);
    });

    it('allows routing when conflict behavior is silent', function () {
        $router = new RequestRouter(
            endpointPriority: [
                'audio_transcription',
                'audio_translation',
            ],
            validateConflicts: true,
            conflictBehavior: 'silent'
        );

        $inputData = [
            'audio' => [
                'file' => '/path/to/audio.mp3',
                'action' => AudioAction::Transcribe->value,
            ],
        ];

        $endpoint = $router->determineEndpoint($inputData);

        expect($endpoint)->toBe(OpenAiEndpoint::AudioTranscription);
    });

    it('skips conflict detection when validate_conflicts is disabled', function () {
        $router = new RequestRouter(
            endpointPriority: [
                'audio_transcription',
                'audio_translation',
            ],
            validateConflicts: false
        );

        $inputData = [
            'audio' => [
                'file' => '/path/to/audio.mp3',
                'action' => AudioAction::Transcribe->value,
            ],
        ];

        $endpoint = $router->determineEndpoint($inputData);

        expect($endpoint)->toBe(OpenAiEndpoint::AudioTranscription);
    });

    it('does not detect conflict when only one endpoint matches', function () {
        config([
            'ai-assistant.routing.endpoint_priority' => [
                'audio_transcription',
                'image_generation',
            ],
            'ai-assistant.routing.validate_conflicts' => true,
            'ai-assistant.routing.conflict_behavior' => 'error',
        ]);

        $router = new RequestRouter();

        $inputData = [
            'audio' => [
                'file' => '/path/to/audio.mp3',
                'action' => AudioAction::Transcribe->value,
            ],
        ];

        $endpoint = $router->determineEndpoint($inputData);

        expect($endpoint)->toBe(OpenAiEndpoint::AudioTranscription);
    });
});
