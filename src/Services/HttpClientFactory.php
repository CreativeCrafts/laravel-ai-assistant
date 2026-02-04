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
        private float $connectTimeout = 10.0,
        private array $connectionPool = []
    ) {
    }

    public function make(array $headers = [], ?float $timeout = null): GuzzleClient
    {
        $options = [
            'base_uri' => $this->baseUri,
            'headers' => array_merge($this->defaultHeaders, $headers),
            'http_errors' => false,
            'timeout' => $timeout ?? $this->timeout,
            'connect_timeout' => $this->connectTimeout,
        ];

        if (($this->connectionPool['enabled'] ?? false) === true) {
            $maxConnections = (int)($this->connectionPool['max_connections'] ?? 100);
            $maxConnectionsPerHost = (int)($this->connectionPool['max_connections_per_host'] ?? 10);
            $curl = [CURLOPT_MAXCONNECTS => $maxConnections];
            if (defined('CURLOPT_MAX_HOST_CONNECTIONS')) {
                $curl[CURLOPT_MAX_HOST_CONNECTIONS] = $maxConnectionsPerHost;
            }
            if (defined('CURLOPT_MAX_TOTAL_CONNECTIONS')) {
                $curl[CURLOPT_MAX_TOTAL_CONNECTIONS] = $maxConnections;
            }
            $options['curl'] = $curl;
        }

        return new GuzzleClient($options);
    }
}
