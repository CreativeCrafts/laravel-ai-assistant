<?php

declare(strict_types=1);

namespace CreativeCrafts\LaravelAiAssistant\Compat\OpenAI;

use CreativeCrafts\LaravelAiAssistant\Transport\GuzzleOpenAITransport;
use CreativeCrafts\LaravelAiAssistant\Transport\OpenAITransport;
use GuzzleHttp\Client as GuzzleClient;
use Symfony\Contracts\HttpClient\HttpClientInterface;

// kept for BC but not used now

// kept for BC constructor signature

class Client
{
    private OpenAITransport $transport;

    public function __construct(
        ?HttpClientInterface $http = null, // ignored, kept for BC
        private ?string $apiKey = null,
        private ?string $organization = null,
        private string $baseUri = 'https://api.openai.com',
        private float $timeout = 60.0,
    ) {
        if ($http !== null) {
            // BC: Constructor keeps the legacy HttpClientInterface parameter; no-op usage to satisfy static analyzers
        }
        $headers = [];
        if (is_string($this->apiKey) && $this->apiKey !== '') {
            $headers['Authorization'] = 'Bearer ' . $this->apiKey;
        }
        $headers['Accept'] = 'application/json';
        if (is_string($this->organization) && $this->organization !== '' && $this->organization !== 'YOUR_OPENAI_ORGANIZATION' && $this->organization !== 'your-organization-id-here') {
            $headers['OpenAI-Organization'] = $this->organization;
        }
        $guzzle = new GuzzleClient([
            'base_uri' => $this->baseUri,
            'headers' => $headers,
            'http_errors' => false,
            'timeout' => $this->timeout,
            'connect_timeout' => 10,
        ]);
        $this->transport = new GuzzleOpenAITransport($guzzle);
    }


    public function completions(): CompletionsResource
    {
        return new CompletionsResource();
    }

    public function chat(): ChatResource
    {
        return new ChatResource($this->transport);
    }

    public function audio(): AudioResource
    {
        return new AudioResource($this->transport);
    }
}
