<?php

declare(strict_types=1);

namespace CreativeCrafts\LaravelAiAssistant\Contracts;

/**
 * Evals API.
 * Resolve through Ai::evals().
 */
interface EvalsRepositoryContract
{
    /**
     * Create the structure of an evaluation that can be used to test a model's performance.
     *
     * POST /v1/evals
     *
     * @param array<string, mixed> $payload
     */
    public function create(array $payload): array;

    /**
     * Get an evaluation by ID.
     *
     * GET /v1/evals/{evalId}
     */
    public function retrieve(string $evalId): array;

    /**
     * Update certain properties of an evaluation.
     *
     * POST /v1/evals/{evalId}
     *
     * @param array<string, mixed> $payload
     */
    public function update(string $evalId, array $payload): array;

    /**
     * List evaluations for a project.
     *
     * GET /v1/evals
     *
     * @param array<string, mixed> $params Query parameters
     */
    public function list(array $params = []): array;

    /**
     * Delete an evaluation.
     *
     * DELETE /v1/evals/{evalId}
     */
    public function delete(string $evalId): array;
}
