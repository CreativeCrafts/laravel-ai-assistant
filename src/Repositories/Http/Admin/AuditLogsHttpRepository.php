<?php

declare(strict_types=1);

namespace CreativeCrafts\LaravelAiAssistant\Repositories\Http\Admin;

use CreativeCrafts\LaravelAiAssistant\Contracts\Admin\AuditLogsRepositoryContract;

/**
 * @internal Use Ai::admin()->auditLogs() instead.
 */
final readonly class AuditLogsHttpRepository extends AbstractAdminHttpRepository implements AuditLogsRepositoryContract
{
    public function list(array $params = []): array
    {
        return $this->getJson('organization/audit_logs', $params);
    }
}
