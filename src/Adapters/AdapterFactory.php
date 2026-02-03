<?php

declare(strict_types=1);

namespace CreativeCrafts\LaravelAiAssistant\Adapters;

use CreativeCrafts\LaravelAiAssistant\Contracts\Adapters\EndpointAdapter;
use CreativeCrafts\LaravelAiAssistant\Enums\OpenAiEndpoint;
use InvalidArgumentException;

/**
 * Factory for creating endpoint-specific adapters.
 *
 * This factory is responsible for instantiating the appropriate EndpointAdapter
 * implementation based on the target OpenAI endpoint. It uses a match expression
 * to map OpenAiEndpoint enum cases to their corresponding adapter instances.
 *
 * The factory creates adapters implementing specialized interfaces that follow
 * the Interface Segregation Principle:
 * - TextEndpointAdapter for text-based endpoints (ResponseApi, ChatCompletion)
 * - AudioEndpointAdapter for audio endpoints (AudioTranscription, AudioTranslation, AudioSpeech)
 * - ImageEndpointAdapter for image endpoints (ImageGeneration, ImageEdit, ImageVariation)
 *
 * All specialized interfaces extend the base EndpointAdapter interface, ensuring
 * backward compatibility while providing domain-specific type segregation.
 *
 * Adapters are cached and reused within the same request lifecycle for optimal
 * performance, as they are stateless and can be safely shared.
 *
 * @internal Used internally by ResponsesBuilder to transform requests for specific endpoints.
 * Do not use directly.
 */
final class AdapterFactory
{
    /**
     * Cache of adapter instances keyed by endpoint value.
     *
     * @var array<string, EndpointAdapter>
     */
    private array $adapterCache = [];

    /**
     * Create or retrieve a cached adapter instance for the specified OpenAI endpoint.
     *
     * This method instantiates and returns the appropriate EndpointAdapter implementation
     * based on the provided endpoint type. Adapter instances are cached and reused within
     * the same request to improve performance, as adapters are stateless.
     *
     * @param OpenAiEndpoint $endpoint The target OpenAI endpoint
     * @return EndpointAdapter The adapter instance for the specified endpoint
     * @throws InvalidArgumentException If the endpoint type is not supported
     */
    public function make(OpenAiEndpoint $endpoint): EndpointAdapter
    {
        $cacheKey = $endpoint->value;

        if (isset($this->adapterCache[$cacheKey])) {
            return $this->adapterCache[$cacheKey];
        }

        $adapter = match ($endpoint) {
            OpenAiEndpoint::AudioTranscription => new AudioTranscriptionAdapter(),
            OpenAiEndpoint::AudioTranslation => new AudioTranslationAdapter(),
            OpenAiEndpoint::AudioSpeech => new AudioSpeechAdapter(),
            OpenAiEndpoint::ImageGeneration => new ImageGenerationAdapter(),
            OpenAiEndpoint::ImageEdit => new ImageEditAdapter(),
            OpenAiEndpoint::ImageVariation => new ImageVariationAdapter(),
            OpenAiEndpoint::ChatCompletion => new ChatCompletionAdapter(),
            OpenAiEndpoint::ResponseApi => new ResponseApiAdapter(),
        };

        $this->adapterCache[$cacheKey] = $adapter;

        return $adapter;
    }
}
