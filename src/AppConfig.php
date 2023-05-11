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
}
