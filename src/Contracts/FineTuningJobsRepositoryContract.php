<?php

declare(strict_types=1);

namespace CreativeCrafts\LaravelAiAssistant\Contracts;

/**
 * Fine-tuning jobs API.
 * Resolve through Ai::fineTuningJobs().
 */
interface FineTuningJobsRepositoryContract
{
    /**
     * Creates a fine-tuning job which begins the process of creating a new model from a given dataset.
     *
     * POST /v1/fine_tuning/jobs
     *
     * @param array<string, mixed> $payload
     */
    public function create(array $payload): array;

    /**
     * Get info about a fine-tuning job.
     *
     * GET /v1/fine_tuning/jobs/{fineTuningJobId}
     */
    public function retrieve(string $fineTuningJobId): array;

    /**
     * List your organization's fine-tuning jobs.
     *
     * GET /v1/fine_tuning/jobs
     *
     * @param array<string, mixed> $params Query parameters
     */
    public function list(array $params = []): array;

    /**
     * Immediately cancel a fine-tune job.
     *
     * POST /v1/fine_tuning/jobs/{fineTuningJobId}/cancel
     */
    public function cancel(string $fineTuningJobId): array;

    /**
     * Pause a fine-tune job.
     *
     * POST /v1/fine_tuning/jobs/{fineTuningJobId}/pause
     */
    public function pause(string $fineTuningJobId): array;

    /**
     * Resume a fine-tune job.
     *
     * POST /v1/fine_tuning/jobs/{fineTuningJobId}/resume
     */
    public function resume(string $fineTuningJobId): array;

    /**
     * Get status updates for a fine-tuning job.
     *
     * GET /v1/fine_tuning/jobs/{fineTuningJobId}/events
     *
     * @param array<string, mixed> $params Query parameters
     */
    public function listEvents(string $fineTuningJobId, array $params = []): array;

    /**
     * List checkpoints for a fine-tuning job.
     *
     * GET /v1/fine_tuning/jobs/{fineTuningJobId}/checkpoints
     *
     * @param array<string, mixed> $params Query parameters
     */
    public function listCheckpoints(string $fineTuningJobId, array $params = []): array;
}
