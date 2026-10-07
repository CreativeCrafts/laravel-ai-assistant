<?php

declare(strict_types=1);

namespace CreativeCrafts\LaravelAiAssistant\Repositories\Http;

use CreativeCrafts\LaravelAiAssistant\Contracts\EvalsRepositoryContract;

/**
 * @internal Use Ai::evals() instead.
 */
final readonly class EvalsHttpRepository extends AbstractHttpRepository implements EvalsRepositoryContract
{
    public function create(array $payload): array
    {
        return $this->postJson('evals', $payload);
    }

    public function retrieve(string $evalId): array
    {
        return $this->getJson($this->path('evals/%s', $evalId));
    }

    public function update(string $evalId, array $payload): array
    {
        return $this->postJson($this->path('evals/%s', $evalId), $payload);
    }

    public function list(array $params = []): array
    {
        return $this->getJson('evals', $params);
    }

    public function delete(string $evalId): array
    {
        return $this->deleteJson($this->path('evals/%s', $evalId));
    }
}
