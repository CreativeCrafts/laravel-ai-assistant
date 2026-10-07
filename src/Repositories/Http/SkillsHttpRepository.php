<?php

declare(strict_types=1);

namespace CreativeCrafts\LaravelAiAssistant\Repositories\Http;

use CreativeCrafts\LaravelAiAssistant\Contracts\SkillsRepositoryContract;

/**
 * @internal Use Ai::skills() instead.
 */
final readonly class SkillsHttpRepository extends AbstractHttpRepository implements SkillsRepositoryContract
{
    public function create(array $payload): array
    {
        return $this->postForm('skills', $payload, ['files']);
    }

    public function retrieve(string $skillId): array
    {
        return $this->getJson($this->path('skills/%s', $skillId));
    }

    public function update(string $skillId, array $payload): array
    {
        return $this->postJson($this->path('skills/%s', $skillId), $payload);
    }

    public function list(array $params = []): array
    {
        return $this->getJson('skills', $params);
    }

    public function delete(string $skillId): array
    {
        return $this->deleteJson($this->path('skills/%s', $skillId));
    }

    public function content(string $skillId): array
    {
        return $this->download($this->path('skills/%s/content', $skillId));
    }

    public function createVersion(string $skillId, array $payload): array
    {
        return $this->postForm($this->path('skills/%s/versions', $skillId), $payload, ['files']);
    }

    public function retrieveVersion(string $skillId, string $version): array
    {
        return $this->getJson($this->path('skills/%s/versions/%s', $skillId, $version));
    }

    public function listVersions(string $skillId, array $params = []): array
    {
        return $this->getJson($this->path('skills/%s/versions', $skillId), $params);
    }

    public function deleteVersion(string $skillId, string $version): array
    {
        return $this->deleteJson($this->path('skills/%s/versions/%s', $skillId, $version));
    }

    public function versionContent(string $skillId, string $version): array
    {
        return $this->download($this->path('skills/%s/versions/%s/content', $skillId, $version));
    }
}
