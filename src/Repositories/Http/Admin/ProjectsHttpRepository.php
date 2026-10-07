<?php

declare(strict_types=1);

namespace CreativeCrafts\LaravelAiAssistant\Repositories\Http\Admin;

use CreativeCrafts\LaravelAiAssistant\Contracts\Admin\ProjectsRepositoryContract;

/**
 * @internal Use Ai::admin()->projects() instead.
 */
final readonly class ProjectsHttpRepository extends AbstractAdminHttpRepository implements ProjectsRepositoryContract
{
    public function create(array $payload): array
    {
        return $this->postJson('organization/projects', $payload);
    }

    public function retrieve(string $projectId): array
    {
        return $this->getJson($this->path('organization/projects/%s', $projectId));
    }

    public function update(string $projectId, array $payload): array
    {
        return $this->postJson($this->path('organization/projects/%s', $projectId), $payload);
    }

    public function list(array $params = []): array
    {
        return $this->getJson('organization/projects', $params);
    }

    public function archive(string $projectId): array
    {
        return $this->postJson($this->path('organization/projects/%s/archive', $projectId));
    }
}
