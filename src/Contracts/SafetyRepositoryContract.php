<?php

declare(strict_types=1);

namespace CreativeCrafts\LaravelAiAssistant\Contracts;

/**
 * Safety API: alerts and cases raised for your organization.
 * Resolve through Ai::safety().
 */
interface SafetyRepositoryContract
{
    /**
     * Get a safety alert belonging to the authenticated API project.
     *
     * GET /v1/safety/alerts/{alertId}
     */
    public function retrieveAlert(string $alertId): array;

    /**
     * Get a safety case by ID.
     *
     * GET /v1/safety/cases/{caseId}
     */
    public function retrieveCase(string $caseId): array;
}
