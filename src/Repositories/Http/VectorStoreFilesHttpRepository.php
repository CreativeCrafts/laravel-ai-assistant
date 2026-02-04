<?php

declare(strict_types=1);

namespace CreativeCrafts\LaravelAiAssistant\Repositories\Http;

use CreativeCrafts\LaravelAiAssistant\Contracts\VectorStoreFilesRepositoryContract;
use CreativeCrafts\LaravelAiAssistant\Transport\OpenAITransport;

/**
 * @internal
 */
final readonly class VectorStoreFilesHttpRepository implements VectorStoreFilesRepositoryContract
{
    private const BETA_HEADER = ['OpenAI-Beta' => 'assistants=v2'];

    public function __construct(
        private OpenAITransport $transport,
        private string $basePath = '/v1'
    ) {
    }

    public function create(string $vectorStoreId, array $payload): array
    {
        return $this->transport->postJson($this->endpoint("vector_stores/{$vectorStoreId}/files"), $payload, self::BETA_HEADER);
    }

    public function retrieve(string $vectorStoreId, string $fileId): array
    {
        return $this->transport->getJson($this->endpoint("vector_stores/{$vectorStoreId}/files/{$fileId}"), self::BETA_HEADER);
    }

    public function update(string $vectorStoreId, string $fileId, array $payload): array
    {
        return $this->transport->postJson($this->endpoint("vector_stores/{$vectorStoreId}/files/{$fileId}"), $payload, self::BETA_HEADER);
    }

    public function delete(string $vectorStoreId, string $fileId): bool
    {
        return $this->transport->delete($this->endpoint("vector_stores/{$vectorStoreId}/files/{$fileId}"), self::BETA_HEADER);
    }

    public function list(string $vectorStoreId, array $params = []): array
    {
        $query = '';
        if ($params !== []) {
            $query = '?' . http_build_query($params);
        }
        return $this->transport->getJson($this->endpoint("vector_stores/{$vectorStoreId}/files") . $query, self::BETA_HEADER);
    }

    public function content(string $vectorStoreId, string $fileId): array
    {
        return $this->transport->getContent(
            $this->endpoint("vector_stores/{$vectorStoreId}/files/{$fileId}/content"),
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
