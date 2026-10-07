<?php

declare(strict_types=1);

namespace CreativeCrafts\LaravelAiAssistant\Providers;

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
use CreativeCrafts\LaravelAiAssistant\Contracts\Agents\AgentEnvironmentsRepositoryContract;
use CreativeCrafts\LaravelAiAssistant\Contracts\Agents\AgentSessionsRepositoryContract;
use CreativeCrafts\LaravelAiAssistant\Contracts\Agents\AgentsRepositoryContract;
use CreativeCrafts\LaravelAiAssistant\Contracts\Agents\VaultsRepositoryContract;
use CreativeCrafts\LaravelAiAssistant\Contracts\ChatCompletionsRepositoryContract;
use CreativeCrafts\LaravelAiAssistant\Contracts\ChatKitRepositoryContract;
use CreativeCrafts\LaravelAiAssistant\Contracts\CompletionsRepositoryContract;
use CreativeCrafts\LaravelAiAssistant\Contracts\ContainerFilesRepositoryContract;
use CreativeCrafts\LaravelAiAssistant\Contracts\ContainersRepositoryContract;
use CreativeCrafts\LaravelAiAssistant\Contracts\ContentProvenanceChecksRepositoryContract;
use CreativeCrafts\LaravelAiAssistant\Contracts\DecisionsRepositoryContract;
use CreativeCrafts\LaravelAiAssistant\Contracts\EmbeddingsRepositoryContract;
use CreativeCrafts\LaravelAiAssistant\Contracts\EvalRunsRepositoryContract;
use CreativeCrafts\LaravelAiAssistant\Contracts\EvalsRepositoryContract;
use CreativeCrafts\LaravelAiAssistant\Contracts\FineTuningCheckpointPermissionsRepositoryContract;
use CreativeCrafts\LaravelAiAssistant\Contracts\FineTuningJobsRepositoryContract;
use CreativeCrafts\LaravelAiAssistant\Contracts\GradersRepositoryContract;
use CreativeCrafts\LaravelAiAssistant\Contracts\ImagesRepositoryContract;
use CreativeCrafts\LaravelAiAssistant\Contracts\LiveRepositoryContract;
use CreativeCrafts\LaravelAiAssistant\Contracts\ModelsRepositoryContract;
use CreativeCrafts\LaravelAiAssistant\Contracts\RealtimeRepositoryContract;
use CreativeCrafts\LaravelAiAssistant\Contracts\SafetyRepositoryContract;
use CreativeCrafts\LaravelAiAssistant\Contracts\SkillsRepositoryContract;
use CreativeCrafts\LaravelAiAssistant\Contracts\UploadsRepositoryContract;
use CreativeCrafts\LaravelAiAssistant\Contracts\VideosRepositoryContract;
use CreativeCrafts\LaravelAiAssistant\Contracts\WebhookEndpointsRepositoryContract;
use CreativeCrafts\LaravelAiAssistant\Repositories\Http\Admin\AdminApiKeysHttpRepository;
use CreativeCrafts\LaravelAiAssistant\Repositories\Http\Admin\AuditLogsHttpRepository;
use CreativeCrafts\LaravelAiAssistant\Repositories\Http\Admin\CertificatesHttpRepository;
use CreativeCrafts\LaravelAiAssistant\Repositories\Http\Admin\DataRetentionHttpRepository;
use CreativeCrafts\LaravelAiAssistant\Repositories\Http\Admin\ExternalStorageHttpRepository;
use CreativeCrafts\LaravelAiAssistant\Repositories\Http\Admin\GroupsHttpRepository;
use CreativeCrafts\LaravelAiAssistant\Repositories\Http\Admin\InvitesHttpRepository;
use CreativeCrafts\LaravelAiAssistant\Repositories\Http\Admin\ProjectApiKeysHttpRepository;
use CreativeCrafts\LaravelAiAssistant\Repositories\Http\Admin\ProjectGroupsHttpRepository;
use CreativeCrafts\LaravelAiAssistant\Repositories\Http\Admin\ProjectPermissionsHttpRepository;
use CreativeCrafts\LaravelAiAssistant\Repositories\Http\Admin\ProjectRateLimitsHttpRepository;
use CreativeCrafts\LaravelAiAssistant\Repositories\Http\Admin\ProjectServiceAccountsHttpRepository;
use CreativeCrafts\LaravelAiAssistant\Repositories\Http\Admin\ProjectUsersHttpRepository;
use CreativeCrafts\LaravelAiAssistant\Repositories\Http\Admin\ProjectsHttpRepository;
use CreativeCrafts\LaravelAiAssistant\Repositories\Http\Admin\RolesHttpRepository;
use CreativeCrafts\LaravelAiAssistant\Repositories\Http\Admin\SpendAlertsHttpRepository;
use CreativeCrafts\LaravelAiAssistant\Repositories\Http\Admin\SpendLimitHttpRepository;
use CreativeCrafts\LaravelAiAssistant\Repositories\Http\Admin\UsageHttpRepository;
use CreativeCrafts\LaravelAiAssistant\Repositories\Http\Admin\UsersHttpRepository;
use CreativeCrafts\LaravelAiAssistant\Repositories\Http\Agents\AgentEnvironmentsHttpRepository;
use CreativeCrafts\LaravelAiAssistant\Repositories\Http\Agents\AgentSessionsHttpRepository;
use CreativeCrafts\LaravelAiAssistant\Repositories\Http\Agents\AgentsHttpRepository;
use CreativeCrafts\LaravelAiAssistant\Repositories\Http\Agents\VaultsHttpRepository;
use CreativeCrafts\LaravelAiAssistant\Repositories\Http\ChatCompletionsHttpRepository;
use CreativeCrafts\LaravelAiAssistant\Repositories\Http\ChatKitHttpRepository;
use CreativeCrafts\LaravelAiAssistant\Repositories\Http\CompletionsHttpRepository;
use CreativeCrafts\LaravelAiAssistant\Repositories\Http\ContainerFilesHttpRepository;
use CreativeCrafts\LaravelAiAssistant\Repositories\Http\ContainersHttpRepository;
use CreativeCrafts\LaravelAiAssistant\Repositories\Http\ContentProvenanceChecksHttpRepository;
use CreativeCrafts\LaravelAiAssistant\Repositories\Http\DecisionsHttpRepository;
use CreativeCrafts\LaravelAiAssistant\Repositories\Http\EmbeddingsHttpRepository;
use CreativeCrafts\LaravelAiAssistant\Repositories\Http\EvalRunsHttpRepository;
use CreativeCrafts\LaravelAiAssistant\Repositories\Http\EvalsHttpRepository;
use CreativeCrafts\LaravelAiAssistant\Repositories\Http\FineTuningCheckpointPermissionsHttpRepository;
use CreativeCrafts\LaravelAiAssistant\Repositories\Http\FineTuningJobsHttpRepository;
use CreativeCrafts\LaravelAiAssistant\Repositories\Http\GradersHttpRepository;
use CreativeCrafts\LaravelAiAssistant\Repositories\Http\ImagesHttpRepository;
use CreativeCrafts\LaravelAiAssistant\Repositories\Http\LiveHttpRepository;
use CreativeCrafts\LaravelAiAssistant\Repositories\Http\ModelsHttpRepository;
use CreativeCrafts\LaravelAiAssistant\Repositories\Http\RealtimeHttpRepository;
use CreativeCrafts\LaravelAiAssistant\Repositories\Http\SafetyHttpRepository;
use CreativeCrafts\LaravelAiAssistant\Repositories\Http\SkillsHttpRepository;
use CreativeCrafts\LaravelAiAssistant\Repositories\Http\UploadsHttpRepository;
use CreativeCrafts\LaravelAiAssistant\Repositories\Http\VideosHttpRepository;
use CreativeCrafts\LaravelAiAssistant\Repositories\Http\WebhookEndpointsHttpRepository;
use CreativeCrafts\LaravelAiAssistant\Transport\OpenAITransport;
use Illuminate\Support\ServiceProvider;

/**
 * Binds the low-level repositories for every OpenAI API resource.
 * Each binding can be swapped in your application container (e.g. with a fake in tests).
 */
class ApiResourcesServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(ChatCompletionsRepositoryContract::class, function ($app) {
            return new ChatCompletionsHttpRepository($app->make(OpenAITransport::class));
        });

        $this->app->bind(CompletionsRepositoryContract::class, function ($app) {
            return new CompletionsHttpRepository($app->make(OpenAITransport::class));
        });

        $this->app->bind(EmbeddingsRepositoryContract::class, function ($app) {
            return new EmbeddingsHttpRepository($app->make(OpenAITransport::class));
        });

        $this->app->bind(ImagesRepositoryContract::class, function ($app) {
            return new ImagesHttpRepository($app->make(OpenAITransport::class));
        });

        $this->app->bind(VideosRepositoryContract::class, function ($app) {
            return new VideosHttpRepository($app->make(OpenAITransport::class));
        });

        $this->app->bind(ModelsRepositoryContract::class, function ($app) {
            return new ModelsHttpRepository($app->make(OpenAITransport::class));
        });

        $this->app->bind(UploadsRepositoryContract::class, function ($app) {
            return new UploadsHttpRepository($app->make(OpenAITransport::class));
        });

        $this->app->bind(ContainersRepositoryContract::class, function ($app) {
            return new ContainersHttpRepository($app->make(OpenAITransport::class));
        });

        $this->app->bind(ContainerFilesRepositoryContract::class, function ($app) {
            return new ContainerFilesHttpRepository($app->make(OpenAITransport::class));
        });

        $this->app->bind(FineTuningJobsRepositoryContract::class, function ($app) {
            return new FineTuningJobsHttpRepository($app->make(OpenAITransport::class));
        });

        $this->app->bind(FineTuningCheckpointPermissionsRepositoryContract::class, function ($app) {
            return new FineTuningCheckpointPermissionsHttpRepository($app->make(OpenAITransport::class), $this->adminApiKey());
        });

        $this->app->bind(GradersRepositoryContract::class, function ($app) {
            return new GradersHttpRepository($app->make(OpenAITransport::class));
        });

        $this->app->bind(EvalsRepositoryContract::class, function ($app) {
            return new EvalsHttpRepository($app->make(OpenAITransport::class));
        });

        $this->app->bind(EvalRunsRepositoryContract::class, function ($app) {
            return new EvalRunsHttpRepository($app->make(OpenAITransport::class));
        });

        $this->app->bind(RealtimeRepositoryContract::class, function ($app) {
            return new RealtimeHttpRepository($app->make(OpenAITransport::class));
        });

        $this->app->bind(LiveRepositoryContract::class, function ($app) {
            return new LiveHttpRepository($app->make(OpenAITransport::class));
        });

        $this->app->bind(WebhookEndpointsRepositoryContract::class, function ($app) {
            return new WebhookEndpointsHttpRepository($app->make(OpenAITransport::class));
        });

        $this->app->bind(SkillsRepositoryContract::class, function ($app) {
            return new SkillsHttpRepository($app->make(OpenAITransport::class));
        });

        $this->app->bind(DecisionsRepositoryContract::class, function ($app) {
            return new DecisionsHttpRepository($app->make(OpenAITransport::class));
        });

        $this->app->bind(ContentProvenanceChecksRepositoryContract::class, function ($app) {
            return new ContentProvenanceChecksHttpRepository($app->make(OpenAITransport::class));
        });

        $this->app->bind(SafetyRepositoryContract::class, function ($app) {
            return new SafetyHttpRepository($app->make(OpenAITransport::class));
        });

        $this->app->bind(ChatKitRepositoryContract::class, function ($app) {
            return new ChatKitHttpRepository($app->make(OpenAITransport::class));
        });

        $this->app->bind(AgentsRepositoryContract::class, function ($app) {
            return new AgentsHttpRepository($app->make(OpenAITransport::class));
        });

        $this->app->bind(AgentEnvironmentsRepositoryContract::class, function ($app) {
            return new AgentEnvironmentsHttpRepository($app->make(OpenAITransport::class));
        });

        $this->app->bind(AgentSessionsRepositoryContract::class, function ($app) {
            return new AgentSessionsHttpRepository($app->make(OpenAITransport::class));
        });

        $this->app->bind(VaultsRepositoryContract::class, function ($app) {
            return new VaultsHttpRepository($app->make(OpenAITransport::class));
        });

        $this->app->bind(AdminApiKeysRepositoryContract::class, function ($app) {
            return new AdminApiKeysHttpRepository($app->make(OpenAITransport::class), $this->adminApiKey());
        });

        $this->app->bind(AuditLogsRepositoryContract::class, function ($app) {
            return new AuditLogsHttpRepository($app->make(OpenAITransport::class), $this->adminApiKey());
        });

        $this->app->bind(CertificatesRepositoryContract::class, function ($app) {
            return new CertificatesHttpRepository($app->make(OpenAITransport::class), $this->adminApiKey());
        });

        $this->app->bind(DataRetentionRepositoryContract::class, function ($app) {
            return new DataRetentionHttpRepository($app->make(OpenAITransport::class), $this->adminApiKey());
        });

        $this->app->bind(ExternalStorageRepositoryContract::class, function ($app) {
            return new ExternalStorageHttpRepository($app->make(OpenAITransport::class), $this->adminApiKey());
        });

        $this->app->bind(InvitesRepositoryContract::class, function ($app) {
            return new InvitesHttpRepository($app->make(OpenAITransport::class), $this->adminApiKey());
        });

        $this->app->bind(RolesRepositoryContract::class, function ($app) {
            return new RolesHttpRepository($app->make(OpenAITransport::class), $this->adminApiKey());
        });

        $this->app->bind(SpendAlertsRepositoryContract::class, function ($app) {
            return new SpendAlertsHttpRepository($app->make(OpenAITransport::class), $this->adminApiKey());
        });

        $this->app->bind(SpendLimitRepositoryContract::class, function ($app) {
            return new SpendLimitHttpRepository($app->make(OpenAITransport::class), $this->adminApiKey());
        });

        $this->app->bind(UsageRepositoryContract::class, function ($app) {
            return new UsageHttpRepository($app->make(OpenAITransport::class), $this->adminApiKey());
        });

        $this->app->bind(GroupsRepositoryContract::class, function ($app) {
            return new GroupsHttpRepository($app->make(OpenAITransport::class), $this->adminApiKey());
        });

        $this->app->bind(UsersRepositoryContract::class, function ($app) {
            return new UsersHttpRepository($app->make(OpenAITransport::class), $this->adminApiKey());
        });

        $this->app->bind(ProjectsRepositoryContract::class, function ($app) {
            return new ProjectsHttpRepository($app->make(OpenAITransport::class), $this->adminApiKey());
        });

        $this->app->bind(ProjectUsersRepositoryContract::class, function ($app) {
            return new ProjectUsersHttpRepository($app->make(OpenAITransport::class), $this->adminApiKey());
        });

        $this->app->bind(ProjectGroupsRepositoryContract::class, function ($app) {
            return new ProjectGroupsHttpRepository($app->make(OpenAITransport::class), $this->adminApiKey());
        });

        $this->app->bind(ProjectServiceAccountsRepositoryContract::class, function ($app) {
            return new ProjectServiceAccountsHttpRepository($app->make(OpenAITransport::class), $this->adminApiKey());
        });

        $this->app->bind(ProjectApiKeysRepositoryContract::class, function ($app) {
            return new ProjectApiKeysHttpRepository($app->make(OpenAITransport::class), $this->adminApiKey());
        });

        $this->app->bind(ProjectRateLimitsRepositoryContract::class, function ($app) {
            return new ProjectRateLimitsHttpRepository($app->make(OpenAITransport::class), $this->adminApiKey());
        });

        $this->app->bind(ProjectPermissionsRepositoryContract::class, function ($app) {
            return new ProjectPermissionsHttpRepository($app->make(OpenAITransport::class), $this->adminApiKey());
        });
    }

    private function adminApiKey(): ?string
    {
        $key = config('ai-assistant.admin_api_key');

        return is_string($key) && $key !== '' ? $key : null;
    }
}
