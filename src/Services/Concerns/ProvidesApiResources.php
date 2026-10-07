<?php

declare(strict_types=1);

namespace CreativeCrafts\LaravelAiAssistant\Services\Concerns;

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
use CreativeCrafts\LaravelAiAssistant\Services\AdminManager;

/**
 * Accessors for the low-level OpenAI API resources exposed on the Ai facade.
 */
trait ProvidesApiResources
{
    /**
     * Chat Completions API, including stored completions.
     */
    public function chatCompletions(): ChatCompletionsRepositoryContract
    {
        return app(ChatCompletionsRepositoryContract::class);
    }

    /**
     * Legacy Completions API.
     */
    public function completions(): CompletionsRepositoryContract
    {
        return app(CompletionsRepositoryContract::class);
    }

    /**
     * Embeddings API: vector representations of text.
     */
    public function embeddings(): EmbeddingsRepositoryContract
    {
        return app(EmbeddingsRepositoryContract::class);
    }

    /**
     * Images API: generation, editing and variations (gpt-image and DALL·E models).
     */
    public function images(): ImagesRepositoryContract
    {
        return app(ImagesRepositoryContract::class);
    }

    /**
     * Videos API (Sora): generate, remix, edit and extend videos.
     */
    public function videos(): VideosRepositoryContract
    {
        return app(VideosRepositoryContract::class);
    }

    /**
     * Models API.
     */
    public function models(): ModelsRepositoryContract
    {
        return app(ModelsRepositoryContract::class);
    }

    /**
     * Uploads API: upload large files in parts (up to 8 GB).
     */
    public function uploads(): UploadsRepositoryContract
    {
        return app(UploadsRepositoryContract::class);
    }

    /**
     * Containers API: sandboxed environments used by the Code Interpreter and shell tools.
     */
    public function containers(): ContainersRepositoryContract
    {
        return app(ContainersRepositoryContract::class);
    }

    /**
     * Container Files API.
     */
    public function containerFiles(): ContainerFilesRepositoryContract
    {
        return app(ContainerFilesRepositoryContract::class);
    }

    /**
     * Fine-tuning jobs API.
     */
    public function fineTuningJobs(): FineTuningJobsRepositoryContract
    {
        return app(FineTuningJobsRepositoryContract::class);
    }

    /**
     * Fine-tuning checkpoint permissions API: share fine-tuned model checkpoints with other projects. Requires an Admin API key.
     */
    public function fineTuningCheckpointPermissions(): FineTuningCheckpointPermissionsRepositoryContract
    {
        return app(FineTuningCheckpointPermissionsRepositoryContract::class);
    }

    /**
     * Graders API (alpha): run and validate graders used by reinforcement fine-tuning and evals.
     */
    public function graders(): GradersRepositoryContract
    {
        return app(GradersRepositoryContract::class);
    }

    /**
     * Evals API.
     */
    public function evals(): EvalsRepositoryContract
    {
        return app(EvalsRepositoryContract::class);
    }

    /**
     * Eval runs API, including run output items.
     */
    public function evalRuns(): EvalRunsRepositoryContract
    {
        return app(EvalRunsRepositoryContract::class);
    }

    /**
     * Realtime API: ephemeral client secrets, WebRTC/SIP calls and translation sessions.
     */
    public function realtime(): RealtimeRepositoryContract
    {
        return app(RealtimeRepositoryContract::class);
    }

    /**
     * Live API: realtime WebRTC sessions, SIP call control and recordings.
     */
    public function live(): LiveRepositoryContract
    {
        return app(LiveRepositoryContract::class);
    }

    /**
     * Webhook endpoints API: manage where OpenAI delivers webhook events.
     */
    public function webhookEndpoints(): WebhookEndpointsRepositoryContract
    {
        return app(WebhookEndpointsRepositoryContract::class);
    }

    /**
     * Skills API: reusable bundles of instructions and files for the shell tool.
     */
    public function skills(): SkillsRepositoryContract
    {
        return app(SkillsRepositoryContract::class);
    }

    /**
     * Decisions API: typed answers to classification and scoring questions over text and images.
     */
    public function decisions(): DecisionsRepositoryContract
    {
        return app(DecisionsRepositoryContract::class);
    }

    /**
     * Content provenance checks API.
     */
    public function contentProvenanceChecks(): ContentProvenanceChecksRepositoryContract
    {
        return app(ContentProvenanceChecksRepositoryContract::class);
    }

    /**
     * Safety API: alerts and cases raised for your organization.
     */
    public function safety(): SafetyRepositoryContract
    {
        return app(SafetyRepositoryContract::class);
    }

    /**
     * ChatKit API (beta): sessions and threads for embedded chat experiences.
     */
    public function chatKit(): ChatKitRepositoryContract
    {
        return app(ChatKitRepositoryContract::class);
    }

    /**
     * Agents API (beta): reusable agent definitions.
     */
    public function agents(): AgentsRepositoryContract
    {
        return app(AgentsRepositoryContract::class);
    }

    /**
     * Agents API (beta): execution environments, environment files and reusable templates.
     */
    public function agentEnvironments(): AgentEnvironmentsRepositoryContract
    {
        return app(AgentEnvironmentsRepositoryContract::class);
    }

    /**
     * Agents API (beta): managed agent sessions, events, turns, subagents, traces and artifacts.
     */
    public function agentSessions(): AgentSessionsRepositoryContract
    {
        return app(AgentSessionsRepositoryContract::class);
    }

    /**
     * Agents API (beta): vaults and the credentials agents use to reach external services.
     */
    public function vaults(): VaultsRepositoryContract
    {
        return app(VaultsRepositoryContract::class);
    }

    /**
     * Administration API (organization, projects, users, usage, ...). Requires an Admin API key.
     */
    public function admin(): AdminManager
    {
        return app(AdminManager::class);
    }
}
