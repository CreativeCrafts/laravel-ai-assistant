<?php

declare(strict_types=1);

namespace CreativeCrafts\LaravelAiAssistant\Contracts\Admin;

/**
 * Organization invites.
 * Requires an Admin API key (ai-assistant.admin_api_key / OPENAI_ADMIN_KEY).
 * Resolve through Ai::admin()->invites().
 */
interface InvitesRepositoryContract
{
    /**
     * Create an invite for a user to the organization. The invite must be accepted by the user before they have access to the organization.
     *
     * POST /v1/organization/invites
     *
     * @param array<string, mixed> $payload
     */
    public function create(array $payload): array;

    /**
     * Retrieves an invite.
     *
     * GET /v1/organization/invites/{inviteId}
     */
    public function retrieve(string $inviteId): array;

    /**
     * Returns a list of invites in the organization.
     *
     * GET /v1/organization/invites
     *
     * @param array<string, mixed> $params Query parameters
     */
    public function list(array $params = []): array;

    /**
     * Delete an invite. If the invite has already been accepted, it cannot be deleted.
     *
     * DELETE /v1/organization/invites/{inviteId}
     */
    public function delete(string $inviteId): array;
}
