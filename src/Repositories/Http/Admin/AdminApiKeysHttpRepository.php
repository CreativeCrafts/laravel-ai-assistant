<?php

declare(strict_types=1);

namespace CreativeCrafts\LaravelAiAssistant\Repositories\Http\Admin;

use CreativeCrafts\LaravelAiAssistant\Contracts\Admin\AdminApiKeysRepositoryContract;

/**
 * @internal Use Ai::admin()->apiKeys() instead.
 */
final readonly class AdminApiKeysHttpRepository extends AbstractAdminHttpRepository implements AdminApiKeysRepositoryContract
{
    public function create(array $payload): array
    {
        return $this->postJson('organization/admin_api_keys', $payload);
    }

    public function retrieve(string $keyId): array
    {
        return $this->getJson($this->path('organization/admin_api_keys/%s', $keyId));
    }

    public function list(array $params = []): array
    {
        return $this->getJson('organization/admin_api_keys', $params);
    }

    public function delete(string $keyId): array
    {
        return $this->deleteJson($this->path('organization/admin_api_keys/%s', $keyId));
    }
}
