<?php

declare(strict_types=1);

namespace CreativeCrafts\LaravelAiAssistant\Repositories\Http;

use CreativeCrafts\LaravelAiAssistant\Contracts\BatchesRepositoryContract;
use CreativeCrafts\LaravelAiAssistant\Transport\OpenAITransport;

/**
 * @internal
 */
final readonly class BatchesHttpRepository implements BatchesRepositoryContract
{
    public function __construct(
        private OpenAITransport $transport,
        private string $basePath = '/v1'
    ) {
    }

    public function create(array $payload): array
    {
        return $this->transport->postJson($this->endpoint('batches'), $payload);
    }

    public function retrieve(string $batchId): array
    {
        return $this->transport->getJson($this->endpoint("batches/{$batchId}"));
    }

    public function cancel(string $batchId): array
    {
        return $this->transport->postJson($this->endpoint("batches/{$batchId}/cancel"), []);
    }

    public function list(array $params = []): array
    {
        $query = '';
        if ($params !== []) {
            $query = '?' . http_build_query($params);
        }
        return $this->transport->getJson($this->endpoint('batches') . $query);
    }

    private function endpoint(string $path): string
    {
        $prefix = rtrim($this->basePath, '/');
        $suffix = ltrim($path, '/');
        return $prefix . '/' . $suffix;
    }
}
