<?php

declare(strict_types=1);

namespace CreativeCrafts\LaravelAiAssistant\Repositories\Http;

use CreativeCrafts\LaravelAiAssistant\Contracts\VectorStoresRepositoryContract;
use CreativeCrafts\LaravelAiAssistant\Transport\OpenAITransport;

/**
 * @internal
 */
final readonly class VectorStoresHttpRepository implements VectorStoresRepositoryContract
{
    private const BETA_HEADER = ['OpenAI-Beta' => 'assistants=v2'];

    public function __construct(
        private OpenAITransport $transport,
        private string $basePath = '/v1'
    ) {
    }

    public function create(array $payload): array
    {
        return $this->transport->postJson($this->endpoint('vector_stores'), $payload, self::BETA_HEADER);
    }

    public function retrieve(string $vectorStoreId): array
    {
        return $this->transport->getJson($this->endpoint("vector_stores/{$vectorStoreId}"), self::BETA_HEADER);
    }

    public function update(string $vectorStoreId, array $payload): array
    {
        return $this->transport->postJson($this->endpoint("vector_stores/{$vectorStoreId}"), $payload, self::BETA_HEADER);
    }

    public function delete(string $vectorStoreId): bool
    {
        return $this->transport->delete($this->endpoint("vector_stores/{$vectorStoreId}"), self::BETA_HEADER);
    }

    public function list(array $params = []): array
    {
        $query = '';
        if ($params !== []) {
            $query = '?' . http_build_query($params);
        }
        return $this->transport->getJson($this->endpoint('vector_stores') . $query, self::BETA_HEADER);
    }

    private function endpoint(string $path): string
    {
        $prefix = rtrim($this->basePath, '/');
        $suffix = ltrim($path, '/');
        return $prefix . '/' . $suffix;
    }
}
