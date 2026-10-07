<?php

declare(strict_types=1);

namespace CreativeCrafts\LaravelAiAssistant\Services;

use CreativeCrafts\LaravelAiAssistant\Contracts\Admin\AdminApiKeysRepositoryContract;
use CreativeCrafts\LaravelAiAssistant\Contracts\Admin\AuditLogsRepositoryContract;
use CreativeCrafts\LaravelAiAssistant\Contracts\Admin\CertificatesRepositoryContract;
use CreativeCrafts\LaravelAiAssistant\Contracts\Admin\DataRetentionRepositoryContract;
use CreativeCrafts\LaravelAiAssistant\Contracts\Admin\ExternalStorageRepositoryContract;
use CreativeCrafts\LaravelAiAssistant\Contracts\Admin\GroupsRepositoryContract;
use CreativeCrafts\LaravelAiAssistant\Contracts\Admin\InvitesRepositoryContract;
use CreativeCrafts\LaravelAiAssistant\Contracts\Admin\ProjectApiKeysRepositoryContract;
use CreativeCrafts\LaravelAiAssistant\Contracts\Admin\ProjectGroupsRepositoryContract;
use CreativeCrafts\LaravelAiAssistant\Contracts\Admin\ProjectPermissionsRepositoryContract;
use CreativeCrafts\LaravelAiAssistant\Contracts\Admin\ProjectRateLimitsRepositoryContract;
use CreativeCrafts\LaravelAiAssistant\Contracts\Admin\ProjectServiceAccountsRepositoryContract;
use CreativeCrafts\LaravelAiAssistant\Contracts\Admin\ProjectUsersRepositoryContract;
use CreativeCrafts\LaravelAiAssistant\Contracts\Admin\ProjectsRepositoryContract;
use CreativeCrafts\LaravelAiAssistant\Contracts\Admin\RolesRepositoryContract;
use CreativeCrafts\LaravelAiAssistant\Contracts\Admin\SpendAlertsRepositoryContract;
use CreativeCrafts\LaravelAiAssistant\Contracts\Admin\SpendLimitRepositoryContract;
use CreativeCrafts\LaravelAiAssistant\Contracts\Admin\UsageRepositoryContract;
use CreativeCrafts\LaravelAiAssistant\Contracts\Admin\UsersRepositoryContract;

/**
 * Entry point for the OpenAI Administration API (Ai::admin()).
 *
 * Requests are authenticated with ai-assistant.admin_api_key (OPENAI_ADMIN_KEY) when configured.
 */
final class AdminManager
{
    /**
     * Admin API keys.
     */
    public function apiKeys(): AdminApiKeysRepositoryContract
    {
        return app(AdminApiKeysRepositoryContract::class);
    }

    /**
     * Audit logs.
     */
    public function auditLogs(): AuditLogsRepositoryContract
    {
        return app(AuditLogsRepositoryContract::class);
    }

    /**
     * Organization and project certificates for mutual TLS.
     */
    public function certificates(): CertificatesRepositoryContract
    {
        return app(CertificatesRepositoryContract::class);
    }

    /**
     * Organization and project data retention settings.
     */
    public function dataRetention(): DataRetentionRepositoryContract
    {
        return app(DataRetentionRepositoryContract::class);
    }

    /**
     * External storage connections (customer-managed buckets).
     */
    public function externalStorage(): ExternalStorageRepositoryContract
    {
        return app(ExternalStorageRepositoryContract::class);
    }

    /**
     * Organization invites.
     */
    public function invites(): InvitesRepositoryContract
    {
        return app(InvitesRepositoryContract::class);
    }

    /**
     * Organization and project custom roles.
     */
    public function roles(): RolesRepositoryContract
    {
        return app(RolesRepositoryContract::class);
    }

    /**
     * Organization and project spend alerts.
     */
    public function spendAlerts(): SpendAlertsRepositoryContract
    {
        return app(SpendAlertsRepositoryContract::class);
    }

    /**
     * Organization and project spend limits.
     */
    public function spendLimit(): SpendLimitRepositoryContract
    {
        return app(SpendLimitRepositoryContract::class);
    }

    /**
     * Usage and costs reporting.
     */
    public function usage(): UsageRepositoryContract
    {
        return app(UsageRepositoryContract::class);
    }

    /**
     * Organization groups, their members and role assignments.
     */
    public function groups(): GroupsRepositoryContract
    {
        return app(GroupsRepositoryContract::class);
    }

    /**
     * Organization users and their role assignments.
     */
    public function users(): UsersRepositoryContract
    {
        return app(UsersRepositoryContract::class);
    }

    /**
     * Organization projects.
     */
    public function projects(): ProjectsRepositoryContract
    {
        return app(ProjectsRepositoryContract::class);
    }

    /**
     * Project members and their project role assignments.
     */
    public function projectUsers(): ProjectUsersRepositoryContract
    {
        return app(ProjectUsersRepositoryContract::class);
    }

    /**
     * Groups with access to a project and their project role assignments.
     */
    public function projectGroups(): ProjectGroupsRepositoryContract
    {
        return app(ProjectGroupsRepositoryContract::class);
    }

    /**
     * Project service accounts and their API keys.
     */
    public function projectServiceAccounts(): ProjectServiceAccountsRepositoryContract
    {
        return app(ProjectServiceAccountsRepositoryContract::class);
    }

    /**
     * Project API keys.
     */
    public function projectApiKeys(): ProjectApiKeysRepositoryContract
    {
        return app(ProjectApiKeysRepositoryContract::class);
    }

    /**
     * Per-model project rate limits.
     */
    public function projectRateLimits(): ProjectRateLimitsRepositoryContract
    {
        return app(ProjectRateLimitsRepositoryContract::class);
    }

    /**
     * Project model and hosted tool permissions.
     */
    public function projectPermissions(): ProjectPermissionsRepositoryContract
    {
        return app(ProjectPermissionsRepositoryContract::class);
    }
}
