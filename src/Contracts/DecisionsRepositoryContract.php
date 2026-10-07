<?php

declare(strict_types=1);

namespace CreativeCrafts\LaravelAiAssistant\Contracts;

/**
 * Decisions API: typed answers to classification and scoring questions over text and images.
 * Resolve through Ai::decisions().
 */
interface DecisionsRepositoryContract
{
    /**
     * Evaluate ordered classification and scoring questions against shared input. Answers are returned in question order.
     *
     * POST /v1/decisions
     *
     * @param array<string, mixed> $payload
     */
    public function create(array $payload): array;
}
