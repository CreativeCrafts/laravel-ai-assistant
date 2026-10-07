<?php

declare(strict_types=1);

namespace CreativeCrafts\LaravelAiAssistant\Repositories\Http\Admin;

use CreativeCrafts\LaravelAiAssistant\Contracts\Admin\CertificatesRepositoryContract;

/**
 * @internal Use Ai::admin()->certificates() instead.
 */
final readonly class CertificatesHttpRepository extends AbstractAdminHttpRepository implements CertificatesRepositoryContract
{
    public function create(array $payload): array
    {
        return $this->postJson('organization/certificates', $payload);
    }

    public function retrieve(string $certificateId): array
    {
        return $this->getJson($this->path('organization/certificates/%s', $certificateId));
    }

    public function update(string $certificateId, array $payload): array
    {
        return $this->postJson($this->path('organization/certificates/%s', $certificateId), $payload);
    }

    public function list(array $params = []): array
    {
        return $this->getJson('organization/certificates', $params);
    }

    public function delete(string $certificateId): array
    {
        return $this->deleteJson($this->path('organization/certificates/%s', $certificateId));
    }

    public function activate(array $payload): array
    {
        return $this->postJson('organization/certificates/activate', $payload);
    }

    public function deactivate(array $payload): array
    {
        return $this->postJson('organization/certificates/deactivate', $payload);
    }

    public function listForProject(string $projectId, array $params = []): array
    {
        return $this->getJson($this->path('organization/projects/%s/certificates', $projectId), $params);
    }

    public function activateForProject(string $projectId, array $payload): array
    {
        return $this->postJson($this->path('organization/projects/%s/certificates/activate', $projectId), $payload);
    }

    public function deactivateForProject(string $projectId, array $payload): array
    {
        return $this->postJson($this->path('organization/projects/%s/certificates/deactivate', $projectId), $payload);
    }
}
