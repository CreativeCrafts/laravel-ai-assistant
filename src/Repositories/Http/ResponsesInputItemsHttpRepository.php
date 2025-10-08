<?php

declare(strict_types=1);

namespace CreativeCrafts\LaravelAiAssistant\Repositories\Http;

use CreativeCrafts\LaravelAiAssistant\Contracts\ResponsesInputItemsRepositoryContract;
use CreativeCrafts\LaravelAiAssistant\Transport\GuzzleOpenAITransport;
use CreativeCrafts\LaravelAiAssistant\Transport\OpenAITransport;
use GuzzleHttp\Client as GuzzleClient;

final readonly class ResponsesInputItemsHttpRepository implements ResponsesInputItemsRepositoryContract
{
    private OpenAITransport $transport;

    public function __construct(
        private GuzzleClient $http,
        private string $basePath = '/v1'
    ) {
        $this->transport = new GuzzleOpenAITransport($this->http, $this->basePath);
    }

    public function append(string $responseId, array $items): array
    {
        $payload = ['items' => $items];
        return $this->transport->postJson($this->endpoint("responses/{$responseId}/input/items"), $payload, idempotent: true);
    }

    public function list(string $responseId, array $params = []): array
    {
        $query = '';
        if (!empty($params)) {
            $query = '?' . http_build_query($params);
        }
        return $this->transport->getJson($this->endpoint("responses/{$responseId}/input/items") . $query);
    }

    private function endpoint(string $path): string
    {
        $prefix = rtrim($this->basePath, '/');
        $suffix = ltrim($path, '/');
        return $prefix . '/' . $suffix;
    }
}
