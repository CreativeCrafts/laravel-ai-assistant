<?php

declare(strict_types=1);

namespace CreativeCrafts\LaravelAiAssistant\Repositories\Http;

use CreativeCrafts\LaravelAiAssistant\Contracts\ModelsRepositoryContract;

/**
 * @internal Use Ai::models() instead.
 */
final readonly class ModelsHttpRepository extends AbstractHttpRepository implements ModelsRepositoryContract
{
    public function list(): array
    {
        return $this->getJson('models');
    }

    public function retrieve(string $model): array
    {
        return $this->getJson($this->path('models/%s', $model));
    }

    public function delete(string $model): array
    {
        return $this->deleteJson($this->path('models/%s', $model));
    }
}
