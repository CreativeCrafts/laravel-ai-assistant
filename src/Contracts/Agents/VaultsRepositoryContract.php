<?php

declare(strict_types=1);

namespace CreativeCrafts\LaravelAiAssistant\Contracts\Agents;

/**
 * Agents API (beta): vaults and the credentials agents use to reach external services.
 * Requests carry the OpenAI-Beta: agents=v1 header.
 * Resolve through Ai::vaults().
 */
interface VaultsRepositoryContract
{
    /**
     * Creates a vault for the current project. See vaults.
     *
     * POST /v1/vaults
     *
     * @param array<string, mixed> $payload
     */
    public function create(array $payload): array;

    /**
     * Retrieves a vault by its ID. See vaults.
     *
     * GET /v1/vaults/{vaultId}
     */
    public function retrieve(string $vaultId): array;

    /**
     * Updates a vault's name or metadata. See vaults.
     *
     * POST /v1/vaults/{vaultId}
     *
     * @param array<string, mixed> $payload e.g. ['name' => 'Production', 'metadata' => ['team' => 'ops']]
     */
    public function update(string $vaultId, array $payload): array;

    /**
     * Lists vaults using ID-based pagination. See vaults.
     *
     * GET /v1/vaults
     *
     * @param array<string, mixed> $params Query parameters
     */
    public function list(array $params = []): array;

    /**
     * Deletes a vault and all its credentials. See vaults.
     *
     * DELETE /v1/vaults/{vaultId}
     */
    public function delete(string $vaultId): array;

    /**
     * Creates a vault credential. Secret values are write-only and are never returned. See vaults.
     *
     * POST /v1/vaults/{vaultId}/credentials
     *
     * @param array<string, mixed> $payload
     */
    public function createCredential(string $vaultId, array $payload): array;

    /**
     * Retrieves vault credential metadata without returning secret values. See vaults.
     *
     * GET /v1/vaults/{vaultId}/credentials/{credentialId}
     */
    public function retrieveCredential(string $vaultId, string $credentialId): array;

    /**
     * Updates credential metadata or rotates its write-only secret. See vaults.
     *
     * POST /v1/vaults/{vaultId}/credentials/{credentialId}
     *
     * @param array<string, mixed> $payload
     */
    public function updateCredential(string $vaultId, string $credentialId, array $payload): array;

    /**
     * Lists a vault's credentials using ID-based pagination without returning secret values. See vaults.
     *
     * GET /v1/vaults/{vaultId}/credentials
     *
     * @param array<string, mixed> $params Query parameters
     */
    public function listCredentials(string $vaultId, array $params = []): array;

    /**
     * Deletes a vault credential. See vaults.
     *
     * DELETE /v1/vaults/{vaultId}/credentials/{credentialId}
     */
    public function deleteCredential(string $vaultId, string $credentialId): array;
}
