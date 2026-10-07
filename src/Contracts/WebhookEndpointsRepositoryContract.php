<?php

declare(strict_types=1);

namespace CreativeCrafts\LaravelAiAssistant\Contracts;

/**
 * Webhook endpoints API: manage where OpenAI delivers webhook events.
 * Resolve through Ai::webhookEndpoints().
 */
interface WebhookEndpointsRepositoryContract
{
    /**
     * Creates a webhook endpoint for the authenticated project.
     *
     * POST /v1/webhook_endpoints
     *
     * @param array<string, mixed> $payload
     */
    public function create(array $payload): array;

    /**
     * Retrieves a webhook endpoint for the authenticated project.
     *
     * GET /v1/webhook_endpoints/{webhookEndpointId}
     */
    public function retrieve(string $webhookEndpointId): array;

    /**
     * Updates a webhook endpoint for the authenticated project.
     *
     * POST /v1/webhook_endpoints/{webhookEndpointId}
     *
     * @param array<string, mixed> $payload
     */
    public function update(string $webhookEndpointId, array $payload): array;

    /**
     * Returns webhook endpoints for the authenticated project in newest-first order.
     *
     * GET /v1/webhook_endpoints
     *
     * @param array<string, mixed> $params Query parameters
     */
    public function list(array $params = []): array;

    /**
     * Deletes a webhook endpoint for the authenticated project.
     *
     * DELETE /v1/webhook_endpoints/{webhookEndpointId}
     */
    public function delete(string $webhookEndpointId): array;

    /**
     * Rotates the signing secret for a webhook endpoint in the authenticated project.
     *
     * POST /v1/webhook_endpoints/{webhookEndpointId}/rotate_secret
     */
    public function rotateSecret(string $webhookEndpointId): array;

    /**
     * Sends a sample event to a webhook endpoint for the authenticated project.
     *
     * POST /v1/webhook_endpoints/{webhookEndpointId}/test
     *
     * @param array<string, mixed> $payload
     */
    public function test(string $webhookEndpointId, array $payload = []): array;

    /**
     * Returns webhook event types visible to the authenticated project.
     *
     * GET /v1/webhook_event_types
     *
     * @param array<string, mixed> $params Query parameters
     */
    public function listEventTypes(array $params = []): array;
}
