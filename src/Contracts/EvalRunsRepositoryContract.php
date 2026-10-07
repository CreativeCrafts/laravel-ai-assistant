<?php

declare(strict_types=1);

namespace CreativeCrafts\LaravelAiAssistant\Contracts;

/**
 * Eval runs API, including run output items.
 * Resolve through Ai::evalRuns().
 */
interface EvalRunsRepositoryContract
{
    /**
     * Kicks off a new run for a given evaluation, specifying the data source, and what model configuration to use to test. The datasource will be validated against the schema specified in the config of the evaluation.
     *
     * POST /v1/evals/{evalId}/runs
     *
     * @param array<string, mixed> $payload
     */
    public function create(string $evalId, array $payload): array;

    /**
     * Get an evaluation run by ID.
     *
     * GET /v1/evals/{evalId}/runs/{runId}
     */
    public function retrieve(string $evalId, string $runId): array;

    /**
     * Get a list of runs for an evaluation.
     *
     * GET /v1/evals/{evalId}/runs
     *
     * @param array<string, mixed> $params Query parameters
     */
    public function list(string $evalId, array $params = []): array;

    /**
     * Delete an eval run.
     *
     * DELETE /v1/evals/{evalId}/runs/{runId}
     */
    public function delete(string $evalId, string $runId): array;

    /**
     * Cancel an ongoing evaluation run.
     *
     * POST /v1/evals/{evalId}/runs/{runId}/cancel
     */
    public function cancel(string $evalId, string $runId): array;

    /**
     * Get a list of output items for an evaluation run.
     *
     * GET /v1/evals/{evalId}/runs/{runId}/output_items
     *
     * @param array<string, mixed> $params Query parameters
     */
    public function listOutputItems(string $evalId, string $runId, array $params = []): array;

    /**
     * Get an evaluation run output item by ID.
     *
     * GET /v1/evals/{evalId}/runs/{runId}/output_items/{outputItemId}
     */
    public function retrieveOutputItem(string $evalId, string $runId, string $outputItemId): array;
}
