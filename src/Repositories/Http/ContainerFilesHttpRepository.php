<?php

declare(strict_types=1);

namespace CreativeCrafts\LaravelAiAssistant\Repositories\Http;

use CreativeCrafts\LaravelAiAssistant\Contracts\ContainerFilesRepositoryContract;

/**
 * @internal Use Ai::containerFiles() instead.
 */
final readonly class ContainerFilesHttpRepository extends AbstractHttpRepository implements ContainerFilesRepositoryContract
{
    public function create(string $containerId, array $payload): array
    {
        return $this->postForm($this->path('containers/%s/files', $containerId), $payload, ['file']);
    }

    public function retrieve(string $containerId, string $fileId): array
    {
        return $this->getJson($this->path('containers/%s/files/%s', $containerId, $fileId));
    }

    public function list(string $containerId, array $params = []): array
    {
        return $this->getJson($this->path('containers/%s/files', $containerId), $params);
    }

    public function delete(string $containerId, string $fileId): array
    {
        return $this->deleteJson($this->path('containers/%s/files/%s', $containerId, $fileId));
    }

    public function content(string $containerId, string $fileId): array
    {
        return $this->download($this->path('containers/%s/files/%s/content', $containerId, $fileId));
    }
}
