<?php

declare(strict_types=1);

namespace CreativeCrafts\LaravelAiAssistant\Contracts\Admin;

/**
 * Audit logs.
 * Requires an Admin API key (ai-assistant.admin_api_key / OPENAI_ADMIN_KEY).
 * Resolve through Ai::admin()->auditLogs().
 */
interface AuditLogsRepositoryContract
{
    /**
     * List user actions and configuration changes within this organization.
     *
     * GET /v1/organization/audit_logs
     *
     * @param array<string, mixed> $params Query parameters
     */
    public function list(array $params = []): array;
}
