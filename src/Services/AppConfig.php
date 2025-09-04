<?php

declare(strict_types=1);

namespace CreativeCrafts\LaravelAiAssistant\Services;

// SDK factory removed; using internal stub OpenAI\\Client only
use CreativeCrafts\LaravelAiAssistant\Contracts\AppConfigContract;
use CreativeCrafts\LaravelAiAssistant\Exceptions\InvalidApiKeyException;
use CreativeCrafts\LaravelAiAssistant\Compat\OpenAI\Client;

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

        // Create an optimized HTTP client with connection pooling if enabled
        $connectionPoolConfig = config('ai-assistant.connection_pool', []);

        // Ensure connectionPoolConfig is an array
        if (!is_array($connectionPoolConfig)) {
            $connectionPoolConfig = [];
        }

        // SDK factory removed. We simply return a compat OpenAI Client stub instance.
        // Keep validation above; connection pooling and HTTP client wiring are handled in HTTP repositories.
        return new Client();
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
        $temperature = config('ai-assistant.temperature');
        $topP = config('ai-assistant.top_p');
        $stop = config('ai-assistant.stop');

        return [
            'model' => config('ai-assistant.model'),
            // Map standardised config key to API parameter name
            'max_tokens' => config('ai-assistant.max_completion_tokens'),
            'temperature' => is_numeric($temperature) ? (float) $temperature : null,
            'stream' => (bool) config('ai-assistant.stream'),
            'echo' => (bool) config('ai-assistant.echo'),
            'n' => config('ai-assistant.n'),
            'suffix' => config('ai-assistant.suffix'),
            'top_p' => is_numeric($topP) ? (float) $topP : null,
            'presence_penalty' => config('ai-assistant.presence_penalty'),
            'frequency_penalty' => config('ai-assistant.frequency_penalty'),
            'best_of' => config('ai-assistant.best_of'),
            'stop' => self::normalizeStop($stop),
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
        $temperature = config('ai-assistant.temperature');
        $topP = config('ai-assistant.top_p');
        $stop = config('ai-assistant.stop');

        return [
            'model' => config('ai-assistant.chat_model'),
            'max_tokens' => config('ai-assistant.max_completion_tokens'),
            'temperature' => is_numeric($temperature) ? (float) $temperature : null,
            'stream' => (bool) config('ai-assistant.stream'),
            'n' => config('ai-assistant.n'),
            'top_p' => is_numeric($topP) ? (float) $topP : null,
            'presence_penalty' => config('ai-assistant.presence_penalty'),
            'frequency_penalty' => config('ai-assistant.frequency_penalty'),
            'stop' => self::normalizeStop($stop),
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
        $temperature = config('ai-assistant.temperature');
        $topP = config('ai-assistant.top_p');
        return [
            'model' => config('ai-assistant.edit_model'),
            'temperature' => is_numeric($temperature) ? (float) $temperature : null,
            'top_p' => is_numeric($topP) ? (float) $topP : null,
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
        $temperature = config('ai-assistant.temperature');
        return [
            'model' => config('ai-assistant.audio_model'),
            'temperature' => is_numeric($temperature) ? (float) $temperature : null,
            'response_format' => config('ai-assistant.response_format'),
        ];
    }


    /**
     * Normalize the 'stop' parameter to null|string|array of strings as expected by the API.
     * Accepts string, array, or null; trims strings and filters out empty values.
     */
    private static function normalizeStop(mixed $stop): array|string|null
    {
        if ($stop === null || $stop === '') {
            return null;
        }
        if (is_string($stop)) {
            $trimmed = trim($stop);
            return $trimmed === '' ? null : $trimmed;
        }
        if (is_array($stop)) {
            $normalized = [];
            foreach ($stop as $item) {
                if (is_string($item)) {
                    $t = trim($item);
                    if ($t !== '') {
                        $normalized[] = $t;
                    }
                }
            }
            return $normalized === [] ? null : $normalized;
        }
        return null;
    }
}
