<?php

declare(strict_types=1);

namespace CreativeCrafts\LaravelAiAssistant\Support;

use InvalidArgumentException;

/**
 * Builder for unified input requests that can be routed to different OpenAI endpoints.
 * Supports audio, image, and text inputs with validation.
 */
final class InputBuilder
{
    /**
     * @param array<string,mixed> $data
     */
    private function __construct(
        private array $data = []
    ) {
    }

    public static function make(): self
    {
        return new self();
    }

    /**
     * Add a text message to the unified request.
     */
    public function message(string $text): self
    {
        $clone = clone $this;
        $clone->data['message'] = $text;
        return $clone;
    }

    /**
     * Add audio configuration to the unified request.
     * Supports transcription, translation, and speech generation.
     *
     * @param array<string,mixed> $config Audio configuration with keys:
     *   - file: string (path to audio file for transcription/translation)
     *   - action: string (transcribe|translate|speech)
     *   - text: string (text for speech generation)
     *   - model: string (optional, e.g., whisper-1, tts-1)
     *   - voice: string (optional for speech: alloy|echo|fable|onyx|nova|shimmer)
     *   - language: string (optional for transcription)
     *   - prompt: string (optional for transcription)
     *   - response_format: string (optional: json|text|srt|verbose_json|vtt)
     *   - temperature: float (optional, 0-1)
     *   - speed: float (optional for speech, 0.25-4.0)
     *   - format: string (optional for speech: mp3|opus|aac|flac|wav|pcm)
     */
    public function audio(array $config): self
    {
        $this->validateAudioConfig($config);

        $clone = clone $this;
        $clone->data['audio'] = $config;
        return $clone;
    }

    /**
     * Add audio input in chat message context.
     * This is used when audio is part of a chat conversation.
     *
     * @param array<string,mixed> $config Audio input configuration with keys:
     *   - file: string (path to audio file)
     *   - format: string (optional)
     */
    public function audioInput(array $config): self
    {
        if (!isset($config['file'])) {
            throw new InvalidArgumentException('Audio input requires a "file" parameter.');
        }

        $clone = clone $this;
        $clone->data['audio_input'] = $config;
        return $clone;
    }

    /**
     * Add image configuration to the unified request.
     * Supports generation, editing, and variations.
     *
     * @param array<string,mixed> $config Image configuration with keys:
     *   - prompt: string (text description for generation/editing)
     *   - image: string (path to image file for editing/variation)
     *   - mask: string (optional, path to mask image for editing)
     *   - model: string (optional, e.g., dall-e-3, dall-e-2)
     *   - n: int (optional, number of images to generate, 1-10)
     *   - size: string (optional: 256x256|512x512|1024x1024|1792x1024|1024x1792)
     *   - quality: string (optional: standard|hd)
     *   - style: string (optional: vivid|natural)
     *   - response_format: string (optional: url|b64_json)
     */
    public function image(array $config): self
    {
        $this->validateImageConfig($config);

        $clone = clone $this;
        $clone->data['image'] = $config;
        return $clone;
    }

    /**
     * Get the unified request data as an array.
     *
     * @return array<string,mixed>
     */
    public function toArray(): array
    {
        return $this->data;
    }

    /**
     * Validate audio configuration based on action type.
     *
     * @param array<string,mixed> $config
     */
    private function validateAudioConfig(array $config): void
    {
        $action = $config['action'] ?? null;

        if ($action === 'transcribe' || $action === 'translate') {
            if (!isset($config['file'])) {
                throw new InvalidArgumentException(
                    "Audio {$action} action requires a \"file\" parameter."
                );
            }
        } elseif ($action === 'speech') {
            if (!isset($config['text'])) {
                throw new InvalidArgumentException(
                    'Audio speech action requires a "text" parameter.'
                );
            }

            if (isset($config['voice'])) {
                $validVoices = ['alloy', 'echo', 'fable', 'onyx', 'nova', 'shimmer'];
                if (!in_array($config['voice'], $validVoices, true)) {
                    throw new InvalidArgumentException(
                        'Invalid voice. Must be one of: ' . implode(', ', $validVoices)
                    );
                }
            }

            if (isset($config['speed'])) {
                if ($config['speed'] < 0.25 || $config['speed'] > 4.0) {
                    throw new InvalidArgumentException(
                        'Speed must be between 0.25 and 4.0'
                    );
                }
            }
        } elseif ($action !== null) {
            throw new InvalidArgumentException(
                'Invalid audio action. Must be one of: transcribe, translate, speech'
            );
        }

        if (isset($config['temperature'])) {
            if ($config['temperature'] < 0 || $config['temperature'] > 1) {
                throw new InvalidArgumentException(
                    'Temperature must be between 0 and 1'
                );
            }
        }
    }

    /**
     * Validate image configuration.
     *
     * @param array<string,mixed> $config
     */
    private function validateImageConfig(array $config): void
    {
        $hasPrompt = isset($config['prompt']);
        $hasImage = isset($config['image']);

        if (!$hasPrompt && !$hasImage) {
            throw new InvalidArgumentException(
                'Image configuration requires at least a "prompt" or "image" parameter.'
            );
        }

        if (isset($config['n'])) {
            if (!is_int($config['n']) || $config['n'] < 1 || $config['n'] > 10) {
                throw new InvalidArgumentException(
                    'Number of images (n) must be an integer between 1 and 10'
                );
            }
        }

        if (isset($config['quality'])) {
            if (!in_array($config['quality'], ['standard', 'hd'], true)) {
                throw new InvalidArgumentException(
                    'Quality must be either "standard" or "hd"'
                );
            }
        }

        if (isset($config['style'])) {
            if (!in_array($config['style'], ['vivid', 'natural'], true)) {
                throw new InvalidArgumentException(
                    'Style must be either "vivid" or "natural"'
                );
            }
        }

        if (isset($config['size'])) {
            $validSizes = ['256x256', '512x512', '1024x1024', '1792x1024', '1024x1792'];
            if (!in_array($config['size'], $validSizes, true)) {
                throw new InvalidArgumentException(
                    'Invalid size. Must be one of: ' . implode(', ', $validSizes)
                );
            }
        }
    }
}
