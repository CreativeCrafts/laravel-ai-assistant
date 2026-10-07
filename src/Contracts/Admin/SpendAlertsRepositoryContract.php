<?php

declare(strict_types=1);

namespace CreativeCrafts\LaravelAiAssistant\Contracts\Admin;

/**
 * Organization and project spend alerts.
 * Requires an Admin API key (ai-assistant.admin_api_key / OPENAI_ADMIN_KEY).
 * Resolve through Ai::admin()->spendAlerts().
 */
interface SpendAlertsRepositoryContract
{
    /**
     * Creates an organization spend alert.
     *
     * POST /v1/organization/spend_alerts
     *
     * @param array<string, mixed> $payload
     */
    public function create(array $payload): array;

    /**
     * Retrieves an organization spend alert.
     *
     * GET /v1/organization/spend_alerts/{alertId}
     */
    public function retrieve(string $alertId): array;

    /**
     * Updates an organization spend alert.
     *
     * POST /v1/organization/spend_alerts/{alertId}
     *
     * @param array<string, mixed> $payload
     */
    public function update(string $alertId, array $payload): array;

    /**
     * Lists organization spend alerts.
     *
     * GET /v1/organization/spend_alerts
     *
     * @param array<string, mixed> $params Query parameters
     */
    public function list(array $params = []): array;

    /**
     * Deletes an organization spend alert.
     *
     * DELETE /v1/organization/spend_alerts/{alertId}
     */
    public function delete(string $alertId): array;

    /**
     * Creates a project spend alert.
     *
     * POST /v1/organization/projects/{projectId}/spend_alerts
     *
     * @param array<string, mixed> $payload
     */
    public function createForProject(string $projectId, array $payload): array;

    /**
     * Retrieves a project spend alert.
     *
     * GET /v1/organization/projects/{projectId}/spend_alerts/{alertId}
     */
    public function retrieveForProject(string $projectId, string $alertId): array;

    /**
     * Updates a project spend alert.
     *
     * POST /v1/organization/projects/{projectId}/spend_alerts/{alertId}
     *
     * @param array<string, mixed> $payload
     */
    public function updateForProject(string $projectId, string $alertId, array $payload): array;

    /**
     * Lists project spend alerts.
     *
     * GET /v1/organization/projects/{projectId}/spend_alerts
     *
     * @param array<string, mixed> $params Query parameters
     */
    public function listForProject(string $projectId, array $params = []): array;

    /**
     * Deletes a project spend alert.
     *
     * DELETE /v1/organization/projects/{projectId}/spend_alerts/{alertId}
     */
    public function deleteForProject(string $projectId, string $alertId): array;
}
