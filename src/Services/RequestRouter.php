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
 * ## Architecture Decision: Response API as Default
 *
 * Per OpenAI's official recommendation, this router uses the Response API as the
 * default endpoint for all text-based chat and conversation operations. The Response
 * API is the recommended approach for new projects as it provides:
 * - Better conversation management with native conversation IDs
 * - Built-in support for multi-turn dialogues
 * - Improved streaming capabilities
 * - More consistent response format
 *
 * ## Audio Input Exception
 *
 * The Chat Completions API is used ONLY for audio input in chat context (priority #7)
 * because the Response API does not yet support audio input. This is a temporary
 * limitation until OpenAI adds audio support to the Response API. For dedicated audio
 * operations (transcription, translation, speech), we use the specialized Audio endpoints.
 *
 * ## Routing Priority:
 * 1. Audio transcription (has audio file + transcribe action) → Audio Transcription endpoint
 * 2. Audio translation (has audio file + translate action) → Audio Translation endpoint
 * 3. Audio speech (has audio text + speech action) → Audio Speech endpoint
 * 4. Image generation (has image prompt, no existing image) → Image Generation endpoint
 * 5. Image edit (has image file + image prompt) → Image Edit endpoint
 * 6. Image variation (has image file, no prompt) → Image Variation endpoint
 * 7. Audio input in chat context → Chat Completions API (Response API limitation)
 * 8. Default: Response API (recommended for all text/chat operations)
 *
 * Example usage:
 * ```php
 * $router = new RequestRouter();
 * $endpoint = $router->determineEndpoint([
 *     'audio' => ['file' => 'audio.mp3', 'action' => 'transcribe']
 * ]);
 * // Returns: OpenAiEndpoint::AudioTranscription
 * ```
 *
 * @internal Used internally by ResponsesBuilder to route requests to appropriate endpoints.
 * Do not use directly - use Ai::responses() instead.
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
            // Exception: Use Chat Completions API for audio input in chat context
            // because Response API does not yet support audio input (OpenAI limitation)
            $this->hasAudioInput($inputData) => OpenAiEndpoint::ChatCompletion,
            // Default: Use Response API for all standard text/chat operations
            // (recommended by OpenAI for all new projects)
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
     * ## Why This Routes to Chat Completions API
     *
     * When this returns true, the request is routed to Chat Completions API
     * instead of the default Response API. This is because:
     * - Response API does not yet support audio input (OpenAI limitation)
     * - Chat Completions API supports audio input for conversational context
     * - This is a temporary workaround until Response API adds audio support
     *
     * For standalone audio operations (transcribe, translate, speech), use
     * the dedicated Audio endpoints instead (AudioTranscription, AudioTranslation,
     * AudioSpeech) by setting the appropriate action.
     *
     * Requires: audio_input field (indicating audio in chat context)
     */
    private function hasAudioInput(array $data): bool
    {
        return isset($data['audio_input']);
    }
}
