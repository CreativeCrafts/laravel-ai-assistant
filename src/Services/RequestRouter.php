<?php

declare(strict_types=1);

namespace CreativeCrafts\LaravelAiAssistant\Services;

use CreativeCrafts\LaravelAiAssistant\Enums\AudioAction;
use CreativeCrafts\LaravelAiAssistant\Enums\OpenAiEndpoint;

/**
 * Routes unified API requests to the appropriate OpenAI endpoint.
 *
 * This service analyzes input data and determines which OpenAI endpoint
 * (audio, image, chat completion, or response API) should handle the request.
 *
 * Routing Priority:
 * 1. Audio transcription (has audio file + transcribe action)
 * 2. Audio translation (has audio file + translate action)
 * 3. Audio speech (has audio text + speech action)
 * 4. Image generation (has image prompt, no existing image)
 * 5. Image edit (has image file + image prompt)
 * 6. Image variation (has image file, no prompt)
 * 7. Audio input in chat context (has audio input without specific action)
 * 8. Default: Response API (standard text/chat requests)
 *
 * Example usage:
 * ```php
 * $router = new RequestRouter();
 * $endpoint = $router->determineEndpoint([
 *     'audio' => ['file' => 'audio.mp3', 'action' => 'transcribe']
 * ]);
 * // Returns: OpenAiEndpoint::AudioTranscription
 * ```
 */
final class RequestRouter
{
    /**
     * Determine which OpenAI endpoint should handle the request.
     *
     * @param array<string, mixed> $inputData The unified request data
     * @return OpenAiEndpoint The appropriate endpoint for this request
     */
    public function determineEndpoint(array $inputData): OpenAiEndpoint
    {
        return match (true) {
            $this->hasAudioTranscription($inputData) => OpenAiEndpoint::AudioTranscription,
            $this->hasAudioTranslation($inputData) => OpenAiEndpoint::AudioTranslation,
            $this->hasAudioSpeech($inputData) => OpenAiEndpoint::AudioSpeech,
            $this->hasImageGeneration($inputData) => OpenAiEndpoint::ImageGeneration,
            $this->hasImageEdit($inputData) => OpenAiEndpoint::ImageEdit,
            $this->hasImageVariation($inputData) => OpenAiEndpoint::ImageVariation,
            $this->hasAudioInput($inputData) => OpenAiEndpoint::ChatCompletion,
            default => OpenAiEndpoint::ResponseApi,
        };
    }

    /**
     * Check if request is for audio transcription.
     *
     * Requires: audio file + transcribe action
     */
    private function hasAudioTranscription(array $data): bool
    {
        return isset($data['audio']['file'])
            && ($data['audio']['action'] ?? null) === AudioAction::Transcribe->value;
    }

    /**
     * Check if request is for audio translation.
     *
     * Requires: audio file + translate action
     */
    private function hasAudioTranslation(array $data): bool
    {
        return isset($data['audio']['file'])
            && ($data['audio']['action'] ?? null) === AudioAction::Translate->value;
    }

    /**
     * Check if request is for audio speech generation.
     *
     * Requires: audio text + speech action
     */
    private function hasAudioSpeech(array $data): bool
    {
        return isset($data['audio']['text'])
            && ($data['audio']['action'] ?? null) === AudioAction::Speech->value;
    }

    /**
     * Check if request is for image generation.
     *
     * Requires: image prompt without an existing image file
     */
    private function hasImageGeneration(array $data): bool
    {
        return isset($data['image']['prompt'])
            && !isset($data['image']['image']);
    }

    /**
     * Check if request is for image editing.
     *
     * Requires: image file + image prompt
     */
    private function hasImageEdit(array $data): bool
    {
        return isset($data['image']['image'], $data['image']['prompt']);
    }

    /**
     * Check if request is for image variation.
     *
     * Requires: image file without a prompt
     */
    private function hasImageVariation(array $data): bool
    {
        return isset($data['image']['image'])
            && !isset($data['image']['prompt']);
    }

    /**
     * Check if request has audio input in chat context.
     *
     * This handles audio files that should be processed as part of
     * a chat conversation rather than standalone transcription/translation.
     *
     * Requires: audio_input field (indicating audio in chat context)
     */
    private function hasAudioInput(array $data): bool
    {
        return isset($data['audio_input']);
    }
}
