<?php

declare(strict_types=1);

namespace CreativeCrafts\LaravelAiAssistant\Contract;

use OpenAI\Client;

interface AppConfigContract
{
    public static function openAiClient(Client $client = null): Client;
}
