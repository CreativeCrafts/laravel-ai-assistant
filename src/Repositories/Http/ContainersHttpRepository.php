<?php

declare(strict_types=1);

namespace CreativeCrafts\LaravelAiAssistant\Repositories\Http;

use CreativeCrafts\LaravelAiAssistant\Contracts\ContainersRepositoryContract;

/**
 * @internal Use Ai::containers() instead.
 */
final readonly class ContainersHttpRepository extends AbstractHttpRepository implements ContainersRepositoryContract
{
    public function create(array $payload): array
    {
        return $this->postJson('containers', $payload);
    }

    public function retrieve(string $containerId): array
    {
        return $this->getJson($this->path('containers/%s', $containerId));
    }

    public function list(array $params = []): array
    {
        return $this->getJson('containers', $params);
    }

    public function delete(string $containerId): array
    {
        return $this->deleteJson($this->path('containers/%s', $containerId));
    }
}
