<?php

declare(strict_types=1);

namespace CreativeCrafts\LaravelAiAssistant\Contracts\Admin;

/**
 * Organization and project certificates for mutual TLS.
 * Requires an Admin API key (ai-assistant.admin_api_key / OPENAI_ADMIN_KEY).
 * Resolve through Ai::admin()->certificates().
 */
interface CertificatesRepositoryContract
{
    /**
     * Upload a certificate to the organization. This does not automatically activate the certificate.
     *
     * POST /v1/organization/certificates
     *
     * @param array<string, mixed> $payload
     */
    public function create(array $payload): array;

    /**
     * Get a certificate that has been uploaded to the organization.
     *
     * GET /v1/organization/certificates/{certificateId}
     */
    public function retrieve(string $certificateId): array;

    /**
     * Modify a certificate. Note that only the name can be modified.
     *
     * POST /v1/organization/certificates/{certificateId}
     *
     * @param array<string, mixed> $payload
     */
    public function update(string $certificateId, array $payload): array;

    /**
     * List uploaded certificates for this organization.
     *
     * GET /v1/organization/certificates
     *
     * @param array<string, mixed> $params Query parameters
     */
    public function list(array $params = []): array;

    /**
     * Delete a certificate from the organization.
     *
     * DELETE /v1/organization/certificates/{certificateId}
     */
    public function delete(string $certificateId): array;

    /**
     * Activate certificates at the organization level.
     *
     * POST /v1/organization/certificates/activate
     *
     * @param array<string, mixed> $payload
     */
    public function activate(array $payload): array;

    /**
     * Deactivate certificates at the organization level.
     *
     * POST /v1/organization/certificates/deactivate
     *
     * @param array<string, mixed> $payload
     */
    public function deactivate(array $payload): array;

    /**
     * List certificates for this project.
     *
     * GET /v1/organization/projects/{projectId}/certificates
     *
     * @param array<string, mixed> $params Query parameters
     */
    public function listForProject(string $projectId, array $params = []): array;

    /**
     * Activate certificates at the project level.
     *
     * POST /v1/organization/projects/{projectId}/certificates/activate
     *
     * @param array<string, mixed> $payload
     */
    public function activateForProject(string $projectId, array $payload): array;

    /**
     * Deactivate certificates at the project level. You can atomically and idempotently deactivate up to 10 certificates at a time.
     *
     * POST /v1/organization/projects/{projectId}/certificates/deactivate
     *
     * @param array<string, mixed> $payload
     */
    public function deactivateForProject(string $projectId, array $payload): array;
}
