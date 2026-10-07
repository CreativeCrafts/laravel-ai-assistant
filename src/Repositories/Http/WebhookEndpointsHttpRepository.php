<?php

declare(strict_types=1);

namespace CreativeCrafts\LaravelAiAssistant\Repositories\Http;

use CreativeCrafts\LaravelAiAssistant\Contracts\WebhookEndpointsRepositoryContract;

/**
 * @internal Use Ai::webhookEndpoints() instead.
 */
final readonly class WebhookEndpointsHttpRepository extends AbstractHttpRepository implements WebhookEndpointsRepositoryContract
{
    public function create(array $payload): array
    {
        return $this->postJson('webhook_endpoints', $payload);
    }

    public function retrieve(string $webhookEndpointId): array
    {
        return $this->getJson($this->path('webhook_endpoints/%s', $webhookEndpointId));
    }

    public function update(string $webhookEndpointId, array $payload): array
    {
        return $this->postJson($this->path('webhook_endpoints/%s', $webhookEndpointId), $payload);
    }

    public function list(array $params = []): array
    {
        return $this->getJson('webhook_endpoints', $params);
    }

    public function delete(string $webhookEndpointId): array
    {
        return $this->deleteJson($this->path('webhook_endpoints/%s', $webhookEndpointId));
    }

    public function rotateSecret(string $webhookEndpointId): array
    {
        return $this->postJson($this->path('webhook_endpoints/%s/rotate_secret', $webhookEndpointId));
    }

    public function test(string $webhookEndpointId, array $payload = []): array
    {
        return $this->postJson($this->path('webhook_endpoints/%s/test', $webhookEndpointId), $payload);
    }

    public function listEventTypes(array $params = []): array
    {
        return $this->getJson('webhook_event_types', $params);
    }
}
