<?php

declare(strict_types=1);

namespace CreativeCrafts\LaravelAiAssistant\Repositories\Http\Agents;

use CreativeCrafts\LaravelAiAssistant\Contracts\Agents\VaultsRepositoryContract;
use CreativeCrafts\LaravelAiAssistant\Repositories\Http\AbstractHttpRepository;

/**
 * @internal Use Ai::vaults() instead.
 */
final readonly class VaultsHttpRepository extends AbstractHttpRepository implements VaultsRepositoryContract
{
    public function create(array $payload): array
    {
        return $this->postJson('vaults', $payload);
    }

    public function retrieve(string $vaultId): array
    {
        return $this->getJson($this->path('vaults/%s', $vaultId));
    }

    public function update(string $vaultId, array $payload): array
    {
        return $this->postJson($this->path('vaults/%s', $vaultId), $payload);
    }

    public function list(array $params = []): array
    {
        return $this->getJson('vaults', $params);
    }

    public function delete(string $vaultId): array
    {
        return $this->deleteJson($this->path('vaults/%s', $vaultId));
    }

    public function createCredential(string $vaultId, array $payload): array
    {
        return $this->postJson($this->path('vaults/%s/credentials', $vaultId), $payload);
    }

    public function retrieveCredential(string $vaultId, string $credentialId): array
    {
        return $this->getJson($this->path('vaults/%s/credentials/%s', $vaultId, $credentialId));
    }

    public function updateCredential(string $vaultId, string $credentialId, array $payload): array
    {
        return $this->postJson($this->path('vaults/%s/credentials/%s', $vaultId, $credentialId), $payload);
    }

    public function listCredentials(string $vaultId, array $params = []): array
    {
        return $this->getJson($this->path('vaults/%s/credentials', $vaultId), $params);
    }

    public function deleteCredential(string $vaultId, string $credentialId): array
    {
        return $this->deleteJson($this->path('vaults/%s/credentials/%s', $vaultId, $credentialId));
    }

    protected function headers(): array
    {
        return ['OpenAI-Beta' => 'agents=v1'];
    }
}
