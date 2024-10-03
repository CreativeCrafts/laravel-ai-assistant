<?php

declare(strict_types=1);

namespace CreativeCrafts\LaravelAiAssistant\Services;

use CreativeCrafts\LaravelAiAssistant\Contracts\AppConfigContract;
use CreativeCrafts\LaravelAiAssistant\Exceptions\InvalidApiKeyException;
use OpenAI;
use OpenAI\Client;

final class AppConfig implements AppConfigContract
{
    /**
     * Creates and returns an instance of the OpenAI client.
     * @throws InvalidApiKeyException If the API key or organization is not set in the configuration.
     */
    public static function openAiClient(Client $client = null): Client
    {
        /** @var string $apiKey */
        $apiKey = config('ai-assistant.api_key');
        /** @var string $organisation */
        $organisation = config('ai-assistant.organization');

        if (
            config('ai-assistant.api_key') === null ||
            config('ai-assistant.api_key') === '' ||
            config('ai-assistant.api_key') === 'YOUR_OPENAI_API_KEY' ||
            config('ai-assistant.organization') === null ||
            config('ai-assistant.organization') === '' ||
            config('ai-assistant.organization') === 'YOUR_OPENAI_ORGANIZATION'
        ) {
            throw new InvalidApiKeyException();
        }

        return $client instanceof Client ? $client : OpenAI::client($apiKey, $organisation);
    }

    /**
     * Returns an array of configuration settings for the text generator.
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
        return [
            'model' => config('ai-assistant.model'),
            'max_tokens' => config('ai-assistant.max_tokens'),
            'temperature' => config('ai-assistant.temperature'),
            'stream' => config('ai-assistant.stream'),
            'echo' => config('ai-assistant.echo'),
            'n' => config('ai-assistant.n'),
            'suffix' => config('ai-assistant.suffix'),
            'top_p' => config('ai-assistant.top_p'),
            'presence_penalty' => config('ai-assistant.presence_penalty'),
            'frequency_penalty' => config('ai-assistant.frequency_penalty'),
            'best_of' => config('ai-assistant.best_of'),
            'stop' => config('ai-assistant.stop'),
        ];
    }

    /**
     * Returns an array of configuration settings for the chat text generator.
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
        return [
            'model' => config('ai-assistant.chat_model'),
            'max_tokens' => config('ai-assistant.max_tokens'),
            'temperature' => config('ai-assistant.temperature'),
            'stream' => config('ai-assistant.stream'),
            'n' => config('ai-assistant.n'),
            'top_p' => config('ai-assistant.top_p'),
            'presence_penalty' => config('ai-assistant.presence_penalty'),
            'frequency_penalty' => config('ai-assistant.frequency_penalty'),
            'stop' => config('ai-assistant.stop'),
        ];
    }

    /**
     * Returns an array of configuration settings for the text editing model.
     *
     * @return array An associative array containing the following keys:
     *  - model: The model to use for text editing.
     *  - temperature: The randomness of the generated text.
     *  - top_p: The cumulative probability threshold for nucleus sampling.
     */
    public static function editTextGeneratorConfig(): array
    {
        return [
            'model' => config('ai-assistant.edit_model'),
            'temperature' => config('ai-assistant.temperature'),
            'top_p' => config('ai-assistant.top_p'),
        ];
    }

    /**
     * Returns an array of configuration settings for the audio to text generator.
     *
     * @return array An associative array containing the following keys:
     *  - model: The model to use for audio to text conversion.
     *  - temperature: The randomness of the generated text.
     *  - response_format: The format of the response (e.g., 'text', 'json').
     */
    public static function audioToTextGeneratorConfig(): array
    {
        return [
            'model' => config('ai-assistant.audio_model'),
            'temperature' => config('ai-assistant.temperature'),
            'response_format' => config('ai-assistant.response_format'),
        ];
    }
}
