<?php

declare(strict_types=1);

namespace CreativeCrafts\LaravelAiAssistant\Repositories\Http\Admin;

use CreativeCrafts\LaravelAiAssistant\Contracts\Admin\ProjectRateLimitsRepositoryContract;

/**
 * @internal Use Ai::admin()->projectRateLimits() instead.
 */
final readonly class ProjectRateLimitsHttpRepository extends AbstractAdminHttpRepository implements ProjectRateLimitsRepositoryContract
{
    public function list(string $projectId, array $params = []): array
    {
        return $this->getJson($this->path('organization/projects/%s/rate_limits', $projectId), $params);
    }

    public function update(string $projectId, string $rateLimitId, array $payload): array
    {
        return $this->postJson($this->path('organization/projects/%s/rate_limits/%s', $projectId, $rateLimitId), $payload);
    }
}
