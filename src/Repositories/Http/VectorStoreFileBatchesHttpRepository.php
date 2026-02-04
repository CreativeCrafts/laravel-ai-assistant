<?php

declare(strict_types=1);

namespace CreativeCrafts\LaravelAiAssistant\Repositories\Http;

use CreativeCrafts\LaravelAiAssistant\Contracts\VectorStoreFileBatchesRepositoryContract;
use CreativeCrafts\LaravelAiAssistant\Transport\OpenAITransport;

/**
 * @internal
 */
final readonly class VectorStoreFileBatchesHttpRepository implements VectorStoreFileBatchesRepositoryContract
{
    private const BETA_HEADER = ['OpenAI-Beta' => 'assistants=v2'];

    public function __construct(
        private OpenAITransport $transport,
        private string $basePath = '/v1'
    ) {
    }

    public function create(string $vectorStoreId, array $payload): array
    {
        return $this->transport->postJson($this->endpoint("vector_stores/{$vectorStoreId}/file_batches"), $payload, self::BETA_HEADER);
    }

    public function retrieve(string $vectorStoreId, string $batchId): array
    {
        return $this->transport->getJson($this->endpoint("vector_stores/{$vectorStoreId}/file_batches/{$batchId}"), self::BETA_HEADER);
    }

    public function cancel(string $vectorStoreId, string $batchId): array
    {
        return $this->transport->postJson($this->endpoint("vector_stores/{$vectorStoreId}/file_batches/{$batchId}/cancel"), [], self::BETA_HEADER);
    }

    public function listFiles(string $vectorStoreId, string $batchId, array $params = []): array
    {
        $query = '';
        if ($params !== []) {
            $query = '?' . http_build_query($params);
        }

        return $this->transport->getJson(
            $this->endpoint("vector_stores/{$vectorStoreId}/file_batches/{$batchId}/files") . $query,
            self::BETA_HEADER
        );
    }

    private function endpoint(string $path): string
    {
        $prefix = rtrim($this->basePath, '/');
        $suffix = ltrim($path, '/');
        return $prefix . '/' . $suffix;
    }
}
