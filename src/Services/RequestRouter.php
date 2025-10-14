<?php

declare(strict_types=1);

namespace CreativeCrafts\LaravelAiAssistant\Services;

use CreativeCrafts\LaravelAiAssistant\Enums\AudioAction;
use CreativeCrafts\LaravelAiAssistant\Enums\OpenAiEndpoint;
use CreativeCrafts\LaravelAiAssistant\Exceptions\EndpointRoutingException;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Log;

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
     * Endpoint priority order from configuration.
     *
     * @var array<int, string>
     */
    private array $endpointPriority;

    /**
     * Whether to validate for conflicts.
     */
    private bool $validateConflicts;

    /**
     * Conflict behavior: 'error', 'warn', or 'silent'.
     */
    private string $conflictBehavior;

    /**
     * Whether to validate endpoint names.
     */
    private bool $validateEndpointNames;

    /**
     * Mapping of endpoint names to checker methods.
     *
     * @var array<string, string>
     */
    private array $endpointCheckers = [
        'audio_transcription' => 'hasAudioTranscription',
        'audio_translation' => 'hasAudioTranslation',
        'audio_speech' => 'hasAudioSpeech',
        'image_generation' => 'hasImageGeneration',
        'image_edit' => 'hasImageEdit',
        'image_variation' => 'hasImageVariation',
        'chat_completion' => 'hasAudioInput',
        'response_api' => 'isDefaultEndpoint',
    ];

    /**
     * Mapping of endpoint names to OpenAiEndpoint enum cases.
     *
     * @var array<string, OpenAiEndpoint>
     */
    private array $endpointMapping = [
        'audio_transcription' => OpenAiEndpoint::AudioTranscription,
        'audio_translation' => OpenAiEndpoint::AudioTranslation,
        'audio_speech' => OpenAiEndpoint::AudioSpeech,
        'image_generation' => OpenAiEndpoint::ImageGeneration,
        'image_edit' => OpenAiEndpoint::ImageEdit,
        'image_variation' => OpenAiEndpoint::ImageVariation,
        'chat_completion' => OpenAiEndpoint::ChatCompletion,
        'response_api' => OpenAiEndpoint::ResponseApi,
    ];

    public function __construct()
    {
        $configuredPriority = config('ai-assistant.routing.endpoint_priority');

        $this->endpointPriority = is_array($configuredPriority) && count($configuredPriority) > 0
            ? $configuredPriority
            : [
                'audio_transcription',
                'audio_translation',
                'audio_speech',
                'image_generation',
                'image_edit',
                'image_variation',
                'chat_completion',
                'response_api',
            ];

        $this->validateConflicts = Config::boolean(key: 'ai-assistant.routing.validate_conflicts', default: true);
        $this->conflictBehavior = Config::string(key: 'ai-assistant.routing.conflict_behavior', default: 'error');
        $this->validateEndpointNames = Config::boolean(key: 'ai-assistant.routing.validate_endpoint_names', default: true);

        $this->validateConfiguration();
    }

    /**
     * Determine which OpenAI endpoint should handle the request.
     * Uses configured endpoint priorities to determine routing.
     * Implements reasoning-first approach: checks for conflicts,
     * then evaluates endpoints in priority order.
     *
     * @param array<string, mixed> $inputData The unified request data
     * @return OpenAiEndpoint The appropriate endpoint for this request
     * @throws EndpointRoutingException When conflicts are detected and conflict_behavior is 'error'
     */
    public function determineEndpoint(array $inputData): OpenAiEndpoint
    {
        if ($this->validateConflicts) {
            $this->detectAndHandleConflicts($inputData);
        }

        foreach ($this->endpointPriority as $endpointName) {
            if (!isset($this->endpointCheckers[$endpointName])) {
                continue;
            }

            $checkerMethod = $this->endpointCheckers[$endpointName];

            if ($this->{$checkerMethod}($inputData)) {
                return $this->endpointMapping[$endpointName];
            }
        }

        return OpenAiEndpoint::ResponseApi;
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

    /**
     * Check if request should use default endpoint (Response API).
     * This always returns true and serves as the fallback endpoint
     * when no other specific endpoint matches the request.
     */
    private function isDefaultEndpoint(array $data): bool
    {
        return true;
    }

    /**
     * Validate the routing configuration.
     * Implements reasoning-first approach: analyzes configuration,
     * identifies issues, explains reasoning, then throws exception if needed.
     *
     * @throws EndpointRoutingException When configuration is invalid
     */
    private function validateConfiguration(): void
    {
        if (!$this->validateEndpointNames) {
            return;
        }

        $invalidEndpoints = [];

        foreach ($this->endpointPriority as $endpointName) {
            if (!isset($this->endpointMapping[$endpointName])) {
                $invalidEndpoints[] = $endpointName;
            }
        }

        if (count($invalidEndpoints) === 0) {
            return;
        }

        $validEndpoints = implode(', ', array_keys($this->endpointMapping));
        $invalidList = implode(', ', $invalidEndpoints);

        $reasoning = "The routing configuration contains invalid endpoint names that are not recognized by the system.\n" .
            "- Invalid endpoints: {$invalidList}\n" .
            "- Valid endpoints: {$validEndpoints}\n" .
            "- These invalid endpoints will be skipped during routing, which may lead to unexpected behavior.\n" .
            "- The configuration should only reference supported endpoint types.";

        throw EndpointRoutingException::invalidPriorityConfiguration($reasoning);
    }

    /**
     * Detect and handle routing conflicts based on input data.
     * Implements reasoning-first approach: analyzes input, detects all
     * matching endpoints, reasons about conflicts, then handles based
     * on configured behavior.
     * A conflict exists when multiple endpoints within the SAME category
     * match the input data, indicating ambiguous intent. Cross-category
     * matches (e.g., audio + image) are NOT conflicts - they are handled
     * by the priority system.
     *
     * @param array<string, mixed> $inputData The request data to analyze
     * @throws EndpointRoutingException When conflicts detected and behavior is 'error'
     */
    private function detectAndHandleConflicts(array $inputData): void
    {
        $matchingEndpoints = [];

        foreach ($this->endpointPriority as $endpointName) {
            if (!isset($this->endpointCheckers[$endpointName])) {
                continue;
            }

            $checkerMethod = $this->endpointCheckers[$endpointName];

            if ($endpointName === 'response_api') {
                continue;
            }

            if ($this->{$checkerMethod}($inputData)) {
                $matchingEndpoints[] = $endpointName;
            }
        }

        if (count($matchingEndpoints) <= 1) {
            return;
        }

        $audioEndpoints = array_intersect($matchingEndpoints, ['audio_transcription', 'audio_translation', 'audio_speech', 'chat_completion']);
        $imageEndpoints = array_intersect($matchingEndpoints, ['image_generation', 'image_edit', 'image_variation']);

        $hasAudioConflict = count($audioEndpoints) > 1;
        $hasImageConflict = count($imageEndpoints) > 1;

        if (!$hasAudioConflict && !$hasImageConflict) {
            return;
        }

        $conflictingEndpoints = [];
        if ($hasAudioConflict) {
            $conflictingEndpoints = array_merge($conflictingEndpoints, array_values($audioEndpoints));
        }
        if ($hasImageConflict) {
            $conflictingEndpoints = array_merge($conflictingEndpoints, array_values($imageEndpoints));
        }

        $reasoning = $this->buildConflictReasoning($conflictingEndpoints, $inputData);

        if ($this->conflictBehavior === 'error') {
            throw EndpointRoutingException::conflictingEndpoints($conflictingEndpoints, $reasoning);
        }

        if ($this->conflictBehavior === 'warn') {
            Log::warning('Endpoint routing conflict detected', [
                'matching_endpoints' => $conflictingEndpoints,
                'reasoning' => $reasoning,
                'selected_endpoint' => $conflictingEndpoints[0],
            ]);
        }
    }

    /**
     * Build detailed reasoning for detected conflicts.
     *
     * @param array<int, string> $matchingEndpoints List of conflicting endpoints
     * @param array<string, mixed> $inputData The request data
     * @return string Detailed reasoning about the conflict
     */
    private function buildConflictReasoning(array $matchingEndpoints, array $inputData): string
    {
        $reasoning = "Multiple endpoints match the provided input data, creating an ambiguous routing situation.\n\n";

        $reasoning .= "Matching endpoints (in priority order):\n";
        foreach ($matchingEndpoints as $endpoint) {
            $reasoning .= "- {$endpoint}: " . $this->explainEndpointMatch($endpoint, $inputData) . "\n";
        }

        $reasoning .= "\nAnalysis:\n";

        $audioEndpoints = array_intersect($matchingEndpoints, ['audio_transcription', 'audio_translation', 'audio_speech', 'chat_completion']);
        $imageEndpoints = array_intersect($matchingEndpoints, ['image_generation', 'image_edit', 'image_variation']);

        if (count($audioEndpoints) > 1) {
            $reasoning .= "- Multiple audio endpoints are matching simultaneously, indicating ambiguous audio processing intent.\n";
        }

        if (count($imageEndpoints) > 1) {
            $reasoning .= "- Multiple image endpoints are matching simultaneously, indicating ambiguous image processing intent.\n";
        }

        if (count($audioEndpoints) > 0 && count($imageEndpoints) > 0) {
            $reasoning .= "- Both audio and image endpoints are matching, which suggests conflicting input data types.\n";
        }

        $reasoning .= "\nRecommendation:\n";
        $reasoning .= "- Review the input data to ensure it clearly specifies a single operation type.\n";
        $reasoning .= "- Adjust the routing priority configuration if this is an expected scenario.\n";
        $reasoning .= "- Consider setting conflict_behavior to 'warn' during development to allow first-match routing.";

        return $reasoning;
    }

    /**
     * Explain why a specific endpoint matches the input data.
     *
     * @param string $endpointName The endpoint name
     * @param array<string, mixed> $inputData The request data
     * @return string Explanation of the match
     */
    private function explainEndpointMatch(string $endpointName, array $inputData): string
    {
        return match ($endpointName) {
            'audio_transcription' => 'Audio file present with transcribe action',
            'audio_translation' => 'Audio file present with translate action',
            'audio_speech' => 'Text present with speech generation action',
            'image_generation' => 'Image prompt present without existing image file',
            'image_edit' => 'Image file and prompt present for editing',
            'image_variation' => 'Image file present without prompt for variations',
            'chat_completion' => 'Audio input present in chat context',
            default => 'Matches endpoint criteria',
        };
    }
}
