<?php

declare(strict_types=1);

namespace CreativeCrafts\LaravelAiAssistant\Repositories\Http\Admin;

use CreativeCrafts\LaravelAiAssistant\Contracts\Admin\ProjectApiKeysRepositoryContract;

/**
 * @internal Use Ai::admin()->projectApiKeys() instead.
 */
final readonly class ProjectApiKeysHttpRepository extends AbstractAdminHttpRepository implements ProjectApiKeysRepositoryContract
{
    public function retrieve(string $projectId, string $apiKeyId): array
    {
        return $this->getJson($this->path('organization/projects/%s/api_keys/%s', $projectId, $apiKeyId));
    }

    public function list(string $projectId, array $params = []): array
    {
        return $this->getJson($this->path('organization/projects/%s/api_keys', $projectId), $params);
    }

    public function delete(string $projectId, string $apiKeyId): array
    {
        return $this->deleteJson($this->path('organization/projects/%s/api_keys/%s', $projectId, $apiKeyId));
    }
}
