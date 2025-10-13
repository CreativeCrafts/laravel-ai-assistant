<?php

declare(strict_types=1);

namespace CreativeCrafts\LaravelAiAssistant\Services;

use CreativeCrafts\LaravelAiAssistant\DataTransferObjects\ModelOptions;
use CreativeCrafts\LaravelAiAssistant\Enums\Modality;
use CreativeCrafts\LaravelAiAssistant\Factories\ModelConfigFactory;

/**
 * Legacy configuration class for OpenAI API settings.
 *
 * @deprecated Since v3.0. Use ModelConfigFactory::for(Modality, ModelOptions) instead.
 *             This class will be removed in v4.0.
 *
 * Migration Guide:
 * - Replace AppConfig::textGeneratorConfig() → ModelConfigFactory::for(Modality::Text, ModelOptions::fromConfig())
 * - Replace AppConfig::chatTextGeneratorConfig() → ModelConfigFactory::for(Modality::Chat, ModelOptions::fromConfig())
 * - Replace AppConfig::editTextGeneratorConfig() → ModelConfigFactory::for(Modality::Edit, ModelOptions::fromConfig())
 * - Replace AppConfig::audioToTextGeneratorConfig() → ModelConfigFactory::for(Modality::AudioToText, ModelOptions::fromConfig())
 *
 * @see ModelConfigFactory
 * @see ModelOptions
 * @see Modality
 */
final class AppConfig
{
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
