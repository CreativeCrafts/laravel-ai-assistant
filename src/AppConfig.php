<?php

namespace CreativeCrafts\LaravelAiAssistant;

use CreativeCrafts\LaravelAiAssistant\Exceptions\InvalidApiKeyException;
use OpenAI;
use OpenAI\Client;

final class AppConfig
{
    public static function openAiClient(): Client
    {
        $apiKey = config('ai-assistant.api_key');
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

        return OpenAI::client($apiKey, $organisation);
    }

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

    public static function editTextGeneratorConfig(): array
    {
        return [
            'model' => config('ai-assistant.edit_model'),
            'max_tokens' => config('ai-assistant.max_tokens'),
            'temperature' => config('ai-assistant.temperature'),
            'top_p' => config('ai-assistant.top_p'),
        ];
    }
}
