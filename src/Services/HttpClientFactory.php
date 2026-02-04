<?php

declare(strict_types=1);

namespace CreativeCrafts\LaravelAiAssistant\Services;

use GuzzleHttp\Client as GuzzleClient;

final readonly class HttpClientFactory
{
    public function __construct(
        private string $baseUri = 'https://api.openai.com',
        private array $defaultHeaders = [],
        private float $timeout = 120.0,
        private float $connectTimeout = 10.0
    ) {
    }

    public function make(array $headers = [], ?float $timeout = null): GuzzleClient
    {
        return new GuzzleClient([
            'base_uri' => $this->baseUri,
            'headers' => array_merge($this->defaultHeaders, $headers),
            'http_errors' => false,
            'timeout' => $timeout ?? $this->timeout,
            'connect_timeout' => $this->connectTimeout,
        ]);
    }
}
