<?php

declare(strict_types=1);

namespace CreativeCrafts\LaravelAiAssistant\Repositories\Http;

use CreativeCrafts\LaravelAiAssistant\Contracts\FineTuningJobsRepositoryContract;

/**
 * @internal Use Ai::fineTuningJobs() instead.
 */
final readonly class FineTuningJobsHttpRepository extends AbstractHttpRepository implements FineTuningJobsRepositoryContract
{
    public function create(array $payload): array
    {
        return $this->postJson('fine_tuning/jobs', $payload);
    }

    public function retrieve(string $fineTuningJobId): array
    {
        return $this->getJson($this->path('fine_tuning/jobs/%s', $fineTuningJobId));
    }

    public function list(array $params = []): array
    {
        return $this->getJson('fine_tuning/jobs', $params);
    }

    public function cancel(string $fineTuningJobId): array
    {
        return $this->postJson($this->path('fine_tuning/jobs/%s/cancel', $fineTuningJobId));
    }

    public function pause(string $fineTuningJobId): array
    {
        return $this->postJson($this->path('fine_tuning/jobs/%s/pause', $fineTuningJobId));
    }

    public function resume(string $fineTuningJobId): array
    {
        return $this->postJson($this->path('fine_tuning/jobs/%s/resume', $fineTuningJobId));
    }

    public function listEvents(string $fineTuningJobId, array $params = []): array
    {
        return $this->getJson($this->path('fine_tuning/jobs/%s/events', $fineTuningJobId), $params);
    }

    public function listCheckpoints(string $fineTuningJobId, array $params = []): array
    {
        return $this->getJson($this->path('fine_tuning/jobs/%s/checkpoints', $fineTuningJobId), $params);
    }
}
