<?php

declare(strict_types=1);

namespace CreativeCrafts\LaravelAiAssistant\Tasks;

use CreativeCrafts\LaravelAiAssistant\AppConfig;
use CreativeCrafts\LaravelAiAssistant\Contract\AudioResourceContract;
use OpenAI\Client;

final class AudioResource implements AudioResourceContract
{
    protected Client $client;

    public function __construct(?Client $client = null)
    {
        $this->client = $client ?? AppConfig::openAiClient();
    }

    public function transcribeTo(array $payload): string
    {
        return $this->client->audio()->transcribe($payload)->text;
    }

    public function translateTo(array $payload): string
    {
        return $this->client->audio()->translate($payload)->text;
    }
}
