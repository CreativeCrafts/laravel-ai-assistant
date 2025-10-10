<?php

declare(strict_types=1);

namespace CreativeCrafts\LaravelAiAssistant\Adapters;

use CreativeCrafts\LaravelAiAssistant\Enums\OpenAiEndpoint;
use InvalidArgumentException;

/**
 * Factory for creating endpoint-specific adapters.
 *
 * This factory is responsible for instantiating the appropriate EndpointAdapter
 * implementation based on the target OpenAI endpoint. It uses a match expression
 * to map OpenAiEndpoint enum cases to their corresponding adapter instances.
 */
final class AdapterFactory
{
    /**
     * Create an adapter instance for the specified OpenAI endpoint.
     *
     * This method instantiates and returns the appropriate EndpointAdapter implementation
     * based on the provided endpoint type. Each adapter knows how to transform requests
     * and responses for its specific OpenAI endpoint.
     *
     * @param OpenAiEndpoint $endpoint The target OpenAI endpoint
     * @return EndpointAdapter The adapter instance for the specified endpoint
     * @throws InvalidArgumentException If the endpoint type is not supported
     */
    public function make(OpenAiEndpoint $endpoint): EndpointAdapter
    {
        return match ($endpoint) {
            OpenAiEndpoint::AudioTranscription => new AudioTranscriptionAdapter(),
            OpenAiEndpoint::AudioTranslation => new AudioTranslationAdapter(),
            OpenAiEndpoint::AudioSpeech => new AudioSpeechAdapter(),
            OpenAiEndpoint::ImageGeneration => new ImageGenerationAdapter(),
            OpenAiEndpoint::ImageEdit => new ImageEditAdapter(),
            OpenAiEndpoint::ImageVariation => new ImageVariationAdapter(),
            OpenAiEndpoint::ChatCompletion => new ChatCompletionAdapter(),
            OpenAiEndpoint::ResponseApi => new ResponseApiAdapter(),
        };
    }
}
