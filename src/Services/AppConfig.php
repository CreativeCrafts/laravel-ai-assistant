<?php

declare(strict_types=1);

namespace CreativeCrafts\LaravelAiAssistant\Services;

// SDK factory removed; using internal stub OpenAI\\Client only
use CreativeCrafts\LaravelAiAssistant\Contracts\AppConfigContract;
use CreativeCrafts\LaravelAiAssistant\DataTransferObjects\ModelOptions;
use CreativeCrafts\LaravelAiAssistant\Enums\Modality;
use CreativeCrafts\LaravelAiAssistant\Exceptions\InvalidApiKeyException;
use CreativeCrafts\LaravelAiAssistant\Compat\OpenAI\Client;
use CreativeCrafts\LaravelAiAssistant\Factories\ModelConfigFactory;

final class AppConfig implements AppConfigContract
{
    /**
     * Creates and returns an instance of the OpenAI client with optimized HTTP configuration.
     * Organization is optional; only the API key is required.
     * @throws InvalidApiKeyException If the API key is not set or appears to be a placeholder.
     */
    public static function openAiClient(Client $client = null): Client
    {
        /** @var string|null $apiKey */
        $apiKey = config('ai-assistant.api_key');
        /** @var string|null $organisation */
        $organisation = config('ai-assistant.organization');

        if ($apiKey === null || $apiKey === '' || $apiKey === 'YOUR_OPENAI_API_KEY' || $apiKey === 'your-api-key-here') {
            throw new InvalidApiKeyException();
        }

        if ($client instanceof Client) {
            return $client;
        }

        // Timeouts shared with HTTP repositories
        $timeout = config('ai-assistant.responses.timeout', 120);
        if (!is_numeric($timeout)) {
            $timeout = 120;
        }

        // Instantiate our internal OpenAI client with real HTTP wiring for chat.completions
        return new Client(
            http: null,
            apiKey: (string)$apiKey,
            organization: is_string($organisation) ? $organisation : null,
            baseUri: 'https://api.openai.com',
            timeout: (float)$timeout,
        );
    }

    /**
     * Returns an array of configuration settings for the text generator.
     *
     * @deprecated Use ModelConfigFactory::for(Modality::Text, ModelOptions) instead
     *
     * @return array An associative array containing the following keys:
     *  - model: The model to use for text generation.
     *  - max_tokens: The maximum number of tokens to generate.
     *  - temperature: The randomness of the generated text.
     *  - stream: Whether to stream the generated text.
     *  - echo: Whether to echo the input prompt in the generated text.
     *  - n: The number of output sequences to generate.
     *  - suffix: The suffix to append to the generated text.
     *  - top_p: The cumulative probability threshold for nucleus sampling.
     *  - presence_penalty: The penalty for repeating the same prompt.
     *  - frequency_penalty: The penalty for repeating the same phrases.
     *  - best_of: The number of candidate outputs to generate and select the best one.
     *  - stop: The sequence to stop generating text at.
     */
    public static function textGeneratorConfig(): array
    {
        trigger_error(
            'AppConfig::textGeneratorConfig() is deprecated. Use ModelConfigFactory::for(Modality::Text, ModelOptions::fromConfig()) instead.',
            E_USER_DEPRECATED
        );

        $config = ModelConfigFactory::for(
            Modality::Text,
            ModelOptions::fromConfig()
        );

        return $config->toArray();
    }

    /**
     * Returns an array of configuration settings for the chat text generator.
     *
     * @deprecated Use ModelConfigFactory::for(Modality::Chat, ModelOptions) instead
     *
     * @return array An associative array containing the following keys:
     *  - model: The model to use for chat text generation.
     *  - max_tokens: The maximum number of tokens to generate.
     *  - temperature: The randomness of the generated text.
     *  - stream: Whether to stream the generated text.
     *  - n: The number of output sequences to generate.
     *  - top_p: The cumulative probability threshold for nucleus sampling.
     *  - presence_penalty: The penalty for repeating the same prompt.
     *  - frequency_penalty: The penalty for repeating the same phrases.
     *  - stop: The sequence to stop generating text at.
     */
    public static function chatTextGeneratorConfig(): array
    {
        trigger_error(
            'AppConfig::chatTextGeneratorConfig() is deprecated. Use ModelConfigFactory::for(Modality::Chat, ModelOptions::fromConfig()) instead.',
            E_USER_DEPRECATED
        );

        $config = ModelConfigFactory::for(
            Modality::Chat,
            ModelOptions::fromConfig()
        );

        return $config->toArray();
    }

    /**
     * Returns an array of configuration settings for the text editing model.
     *
     * @deprecated Use ModelConfigFactory::for(Modality::Edit, ModelOptions) instead
     *
     * @return array An associative array containing the following keys:
     *  - model: The model to use for text editing.
     *  - temperature: The randomness of the generated text.
     *  - top_p: The cumulative probability threshold for nucleus sampling.
     */
    public static function editTextGeneratorConfig(): array
    {
        trigger_error(
            'AppConfig::editTextGeneratorConfig() is deprecated. Use ModelConfigFactory::for(Modality::Edit, ModelOptions::fromConfig()) instead.',
            E_USER_DEPRECATED
        );

        $config = ModelConfigFactory::for(
            Modality::Edit,
            ModelOptions::fromConfig()
        );

        return $config->toArray();
    }

    /**
     * Returns an array of configuration settings for the audio to text generator.
     *
     * @deprecated Use ModelConfigFactory::for(Modality::AudioToText, ModelOptions) instead
     *
     * @return array An associative array containing the following keys:
     *  - model: The model to use for audio to text conversion.
     *  - temperature: The randomness of the generated text.
     *  - response_format: The format of the response (e.g., 'text', 'json').
     */
    public static function audioToTextGeneratorConfig(): array
    {
        trigger_error(
            'AppConfig::audioToTextGeneratorConfig() is deprecated. Use ModelConfigFactory::for(Modality::AudioToText, ModelOptions::fromConfig()) instead.',
            E_USER_DEPRECATED
        );

        $config = ModelConfigFactory::for(
            Modality::AudioToText,
            ModelOptions::fromConfig()
        );

        return $config->toArray();
    }
}
