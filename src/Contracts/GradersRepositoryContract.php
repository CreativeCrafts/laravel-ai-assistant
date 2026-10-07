<?php

declare(strict_types=1);

namespace CreativeCrafts\LaravelAiAssistant\Contracts;

/**
 * Graders API (alpha): run and validate graders used by reinforcement fine-tuning and evals.
 * Resolve through Ai::graders().
 */
interface GradersRepositoryContract
{
    /**
     * Run a grader.
     *
     * POST /v1/fine_tuning/alpha/graders/run
     *
     * @param array<string, mixed> $payload
     */
    public function run(array $payload): array;

    /**
     * Validate a grader.
     *
     * POST /v1/fine_tuning/alpha/graders/validate
     *
     * @param array<string, mixed> $payload
     */
    public function validate(array $payload): array;
}
