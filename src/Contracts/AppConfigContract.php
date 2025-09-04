<?php

declare(strict_types=1);

namespace CreativeCrafts\LaravelAiAssistant\Contracts;

use CreativeCrafts\LaravelAiAssistant\Compat\OpenAI\Client;

interface AppConfigContract
{
    public static function openAiClient(Client $client = null): Client;
}
