<?php

declare(strict_types=1);

namespace CreativeCrafts\LaravelAiAssistant\Facades;

use CreativeCrafts\LaravelAiAssistant\Chat\ChatSession;
use CreativeCrafts\LaravelAiAssistant\Contracts\Agents\AgentEnvironmentsRepositoryContract;
use CreativeCrafts\LaravelAiAssistant\Contracts\Agents\AgentSessionsRepositoryContract;
use CreativeCrafts\LaravelAiAssistant\Contracts\Agents\AgentsRepositoryContract;
use CreativeCrafts\LaravelAiAssistant\Contracts\Agents\VaultsRepositoryContract;
use CreativeCrafts\LaravelAiAssistant\Contracts\AssistantsRepositoryContract;
use CreativeCrafts\LaravelAiAssistant\Contracts\AudioRepositoryContract;
use CreativeCrafts\LaravelAiAssistant\Contracts\BatchesRepositoryContract;
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
use CreativeCrafts\LaravelAiAssistant\Contracts\FilesRepositoryContract;
use CreativeCrafts\LaravelAiAssistant\Contracts\FineTuningCheckpointPermissionsRepositoryContract;
use CreativeCrafts\LaravelAiAssistant\Contracts\FineTuningJobsRepositoryContract;
use CreativeCrafts\LaravelAiAssistant\Contracts\GradersRepositoryContract;
use CreativeCrafts\LaravelAiAssistant\Contracts\ImagesRepositoryContract;
use CreativeCrafts\LaravelAiAssistant\Contracts\LiveRepositoryContract;
use CreativeCrafts\LaravelAiAssistant\Contracts\ModelsRepositoryContract;
use CreativeCrafts\LaravelAiAssistant\Contracts\ModerationsRepositoryContract;
use CreativeCrafts\LaravelAiAssistant\Contracts\RealtimeRepositoryContract;
use CreativeCrafts\LaravelAiAssistant\Contracts\RealtimeSessionsRepositoryContract;
use CreativeCrafts\LaravelAiAssistant\Contracts\SafetyRepositoryContract;
use CreativeCrafts\LaravelAiAssistant\Contracts\SkillsRepositoryContract;
use CreativeCrafts\LaravelAiAssistant\Contracts\UploadsRepositoryContract;
use CreativeCrafts\LaravelAiAssistant\Contracts\VectorStoreFileBatchesRepositoryContract;
use CreativeCrafts\LaravelAiAssistant\Contracts\VectorStoreFilesRepositoryContract;
use CreativeCrafts\LaravelAiAssistant\Contracts\VectorStoresRepositoryContract;
use CreativeCrafts\LaravelAiAssistant\Contracts\VideosRepositoryContract;
use CreativeCrafts\LaravelAiAssistant\Contracts\WebhookEndpointsRepositoryContract;
use CreativeCrafts\LaravelAiAssistant\DataTransferObjects\ChatResponseDto;
use CreativeCrafts\LaravelAiAssistant\DataTransferObjects\CompletionRequest;
use CreativeCrafts\LaravelAiAssistant\DataTransferObjects\CompletionResult;
use CreativeCrafts\LaravelAiAssistant\Enums\Mode;
use CreativeCrafts\LaravelAiAssistant\Enums\Transport;
use CreativeCrafts\LaravelAiAssistant\Services\AdminManager;
use CreativeCrafts\LaravelAiAssistant\Services\AiManager;
use CreativeCrafts\LaravelAiAssistant\Support\ConversationsBuilder;
use CreativeCrafts\LaravelAiAssistant\Support\DiarizationBuilder;
use CreativeCrafts\LaravelAiAssistant\Support\ResponsesBuilder;
use Generator;
use Illuminate\Support\Facades\Facade;

/**
 * @method static ChatSession chat(?string $prompt = '')
 * @method static ChatResponseDto quick(string $prompt)
 * @method static Generator stream(string $prompt, ?callable $onEvent = null, ?callable $shouldStop = null)
 * @method static ResponsesBuilder responses()
 * @method static ConversationsBuilder conversations()
 * @method static ModerationsRepositoryContract moderations()
 * @method static BatchesRepositoryContract batches()
 * @method static RealtimeSessionsRepositoryContract realtimeSessions()
 * @method static VectorStoresRepositoryContract vectorStores()
 * @method static VectorStoreFilesRepositoryContract vectorStoreFiles()
 * @method static VectorStoreFileBatchesRepositoryContract vectorStoreFileBatches()
 * @method static AssistantsRepositoryContract assistants()
 * @method static FilesRepositoryContract files()
 * @method static AudioRepositoryContract audio()
 * @method static DiarizationBuilder diarize(mixed $file = null, ?string $filename = null)
 * @method static CompletionResult complete(Mode $mode, Transport $transport, CompletionRequest $request)
 * @method static ChatCompletionsRepositoryContract chatCompletions()
 * @method static CompletionsRepositoryContract completions()
 * @method static EmbeddingsRepositoryContract embeddings()
 * @method static ImagesRepositoryContract images()
 * @method static VideosRepositoryContract videos()
 * @method static ModelsRepositoryContract models()
 * @method static UploadsRepositoryContract uploads()
 * @method static ContainersRepositoryContract containers()
 * @method static ContainerFilesRepositoryContract containerFiles()
 * @method static FineTuningJobsRepositoryContract fineTuningJobs()
 * @method static FineTuningCheckpointPermissionsRepositoryContract fineTuningCheckpointPermissions()
 * @method static GradersRepositoryContract graders()
 * @method static EvalsRepositoryContract evals()
 * @method static EvalRunsRepositoryContract evalRuns()
 * @method static RealtimeRepositoryContract realtime()
 * @method static LiveRepositoryContract live()
 * @method static WebhookEndpointsRepositoryContract webhookEndpoints()
 * @method static SkillsRepositoryContract skills()
 * @method static DecisionsRepositoryContract decisions()
 * @method static ContentProvenanceChecksRepositoryContract contentProvenanceChecks()
 * @method static SafetyRepositoryContract safety()
 * @method static ChatKitRepositoryContract chatKit()
 * @method static AgentsRepositoryContract agents()
 * @method static AgentEnvironmentsRepositoryContract agentEnvironments()
 * @method static AgentSessionsRepositoryContract agentSessions()
 * @method static VaultsRepositoryContract vaults()
 * @method static AdminManager admin()
 * @see AiManager
 */
class Ai extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return AiManager::class;
    }
}
