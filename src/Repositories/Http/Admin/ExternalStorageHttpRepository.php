<?php

declare(strict_types=1);

namespace CreativeCrafts\LaravelAiAssistant\Repositories\Http\Admin;

use CreativeCrafts\LaravelAiAssistant\Contracts\Admin\ExternalStorageRepositoryContract;

/**
 * @internal Use Ai::admin()->externalStorage() instead.
 */
final readonly class ExternalStorageHttpRepository extends AbstractAdminHttpRepository implements ExternalStorageRepositoryContract
{
    public function create(array $payload): array
    {
        return $this->postJson('organization/external_storage', $payload);
    }

    public function retrieve(string $externalStorageId): array
    {
        return $this->getJson($this->path('organization/external_storage/%s', $externalStorageId));
    }

    public function list(array $params = []): array
    {
        return $this->getJson('organization/external_storage', $params);
    }

    public function delete(string $externalStorageId): array
    {
        return $this->deleteJson($this->path('organization/external_storage/%s', $externalStorageId));
    }

    public function validate(string $externalStorageId): array
    {
        return $this->postJson($this->path('organization/external_storage/%s/validate', $externalStorageId));
    }
}
