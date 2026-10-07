<?php

declare(strict_types=1);

namespace CreativeCrafts\LaravelAiAssistant\Contracts\Agents;

/**
 * Agents API (beta): execution environments, environment files and reusable templates.
 * Requests carry the OpenAI-Beta: agents=v1 header.
 * Resolve through Ai::agentEnvironments().
 */
interface AgentEnvironmentsRepositoryContract
{
    /**
     * Retrieves an execution environment's connection status and safe installed metadata. See environment lifecycle.
     *
     * GET /v1/agents/environments/{environmentId}
     */
    public function retrieve(string $environmentId): array;

    /**
     * Copies inline bytes or a Files API file into a connected execution environment. See environment files.
     *
     * POST /v1/agents/environments/{environmentId}/files
     *
     * @param array<string, mixed> $payload
     */
    public function createFile(string $environmentId, array $payload): array;

    /**
     * Lists live files on a connected execution environment with optional directory filtering and opaque cursor pagination. See environment files.
     *
     * GET /v1/agents/environments/{environmentId}/files
     *
     * @param array<string, mixed> $params Query parameters
     */
    public function listFiles(string $environmentId, array $params = []): array;

    /**
     * Creates reusable environment configuration without returning confidential setup commands or environment values. See reusing a hosted setup.
     *
     * POST /v1/agents/environments/templates
     *
     * @param array<string, mixed> $payload
     */
    public function createTemplate(array $payload): array;

    /**
     * Retrieves reusable environment configuration without returning confidential values. See reusing a hosted setup.
     *
     * GET /v1/agents/environments/templates/{templateId}
     */
    public function retrieveTemplate(string $templateId): array;

    /**
     * Updates reusable environment configuration without returning confidential values. See reusing a hosted setup.
     *
     * POST /v1/agents/environments/templates/{templateId}
     *
     * @param array<string, mixed> $payload
     */
    public function updateTemplate(string $templateId, array $payload): array;

    /**
     * Lists reusable environment templates without returning confidential values. See reusing a hosted setup.
     *
     * GET /v1/agents/environments/templates
     *
     * @param array<string, mixed> $params Query parameters
     */
    public function listTemplates(array $params = []): array;

    /**
     * Deletes reusable environment configuration and all confidential template inputs. See reusing a hosted setup.
     *
     * DELETE /v1/agents/environments/templates/{templateId}
     */
    public function deleteTemplate(string $templateId): array;
}
