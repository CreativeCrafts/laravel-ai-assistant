<?php

declare(strict_types=1);

namespace CreativeCrafts\LaravelAiAssistant\Contracts\Agents;

/**
 * Agents API (beta): reusable agent definitions.
 * Requests carry the OpenAI-Beta: agents=v1 header.
 * Resolve through Ai::agents().
 */
interface AgentsRepositoryContract
{
    /**
     * Creates a reusable agent without storing credentials. See agent configuration.
     *
     * POST /v1/agents
     *
     * @param array<string, mixed> $payload
     */
    public function create(array $payload): array;

    /**
     * Retrieves a reusable agent by ID. See agent configuration.
     *
     * GET /v1/agents/{agentId}
     */
    public function retrieve(string $agentId): array;

    /**
     * Updates a reusable agent. See agent configuration.
     *
     * POST /v1/agents/{agentId}
     *
     * @param array<string, mixed> $payload
     */
    public function update(string $agentId, array $payload): array;

    /**
     * Lists reusable agents in the current project. See agent configuration.
     *
     * GET /v1/agents
     *
     * @param array<string, mixed> $params Query parameters
     */
    public function list(array $params = []): array;

    /**
     * Deletes a reusable agent. See agent configuration.
     *
     * DELETE /v1/agents/{agentId}
     */
    public function delete(string $agentId): array;
}
