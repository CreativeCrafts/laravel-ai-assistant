<?php

declare(strict_types=1);

namespace CreativeCrafts\LaravelAiAssistant\Providers;

use CreativeCrafts\LaravelAiAssistant\Adapters\AdapterFactory;
use CreativeCrafts\LaravelAiAssistant\Contracts\AssistantsRepositoryContract;
use CreativeCrafts\LaravelAiAssistant\Contracts\BatchesRepositoryContract;
use CreativeCrafts\LaravelAiAssistant\Contracts\ConversationsRepositoryContract;
use CreativeCrafts\LaravelAiAssistant\Contracts\FilesRepositoryContract;
use CreativeCrafts\LaravelAiAssistant\Contracts\ModerationsRepositoryContract;
use CreativeCrafts\LaravelAiAssistant\Contracts\ProgressTrackerContract;
use CreativeCrafts\LaravelAiAssistant\Contracts\RealtimeSessionsRepositoryContract;
use CreativeCrafts\LaravelAiAssistant\Contracts\ResponsesInputItemsRepositoryContract;
use CreativeCrafts\LaravelAiAssistant\Contracts\ResponsesRepositoryContract;
use CreativeCrafts\LaravelAiAssistant\Contracts\VectorStoreFileBatchesRepositoryContract;
use CreativeCrafts\LaravelAiAssistant\Contracts\VectorStoreFilesRepositoryContract;
use CreativeCrafts\LaravelAiAssistant\Contracts\VectorStoresRepositoryContract;
use CreativeCrafts\LaravelAiAssistant\Exceptions\InvalidApiKeyException;
use CreativeCrafts\LaravelAiAssistant\Jobs\ExecuteToolCallJob;
use CreativeCrafts\LaravelAiAssistant\Repositories\Http\AssistantsHttpRepository;
use CreativeCrafts\LaravelAiAssistant\Repositories\Http\BatchesHttpRepository;
use CreativeCrafts\LaravelAiAssistant\Repositories\Http\ConversationsHttpRepository;
use CreativeCrafts\LaravelAiAssistant\Repositories\Http\FilesHttpRepository;
use CreativeCrafts\LaravelAiAssistant\Repositories\Http\ModerationsHttpRepository;
use CreativeCrafts\LaravelAiAssistant\Repositories\Http\RealtimeSessionsHttpRepository;
use CreativeCrafts\LaravelAiAssistant\Repositories\Http\ResponsesHttpRepository;
use CreativeCrafts\LaravelAiAssistant\Repositories\Http\ResponsesInputItemsHttpRepository;
use CreativeCrafts\LaravelAiAssistant\Repositories\Http\VectorStoreFileBatchesHttpRepository;
use CreativeCrafts\LaravelAiAssistant\Repositories\Http\VectorStoreFilesHttpRepository;
use CreativeCrafts\LaravelAiAssistant\Repositories\Http\VectorStoresHttpRepository;
use CreativeCrafts\LaravelAiAssistant\Http\MultipartRequestBuilder;
use CreativeCrafts\LaravelAiAssistant\Services\AiManager;
use CreativeCrafts\LaravelAiAssistant\Services\RequestRouter;
use CreativeCrafts\LaravelAiAssistant\Services\BackgroundJobService;
use CreativeCrafts\LaravelAiAssistant\Services\CacheBackedProgressTracker;
use CreativeCrafts\LaravelAiAssistant\Services\CacheService;
use CreativeCrafts\LaravelAiAssistant\Services\HttpClientFactory;
use CreativeCrafts\LaravelAiAssistant\Services\IdempotencyService;
use CreativeCrafts\LaravelAiAssistant\Services\LazyLoadingService;
use CreativeCrafts\LaravelAiAssistant\Services\LoggingService;
use CreativeCrafts\LaravelAiAssistant\Services\MemoryMonitoringService;
use CreativeCrafts\LaravelAiAssistant\Services\MetricsCollectionService;
use CreativeCrafts\LaravelAiAssistant\Services\OpenAiClient;
use CreativeCrafts\LaravelAiAssistant\Services\ResponseStatusStore;
use CreativeCrafts\LaravelAiAssistant\Services\SecurityService;
use CreativeCrafts\LaravelAiAssistant\Services\StreamingService;
use CreativeCrafts\LaravelAiAssistant\Services\ThreadsToConversationsMapper;
use CreativeCrafts\LaravelAiAssistant\Services\ToolRegistry;
use CreativeCrafts\LaravelAiAssistant\Transport\GuzzleOpenAITransport;
use CreativeCrafts\LaravelAiAssistant\Transport\OpenAITransport;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\ServiceProvider;

class CoreServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(HttpClientFactory::class, function ($app) {
            $apiKey = config('ai-assistant.api_key', '');
            if (!is_string($apiKey)) {
                $apiKey = '';
            }
            $org = config('ai-assistant.organization');
            $headers = [
                'Authorization' => 'Bearer ' . $apiKey,
                'Accept' => 'application/json',
            ];
            if (is_string($org) && $org !== '' && $org !== 'YOUR_OPENAI_ORGANIZATION' && $org !== 'your-organization-id-here') {
                $headers['OpenAI-Organization'] = $org;
            }

            $timeout = config('ai-assistant.responses.timeout', 120);
            if (!is_numeric($timeout)) {
                $timeout = 120;
            }

            $pool = config('ai-assistant.connection_pool', []);
            $pool = is_array($pool) ? $pool : [];

            return new HttpClientFactory(
                defaultHeaders: $headers,
                timeout: (float)$timeout,
                connectionPool: $pool
            );
        });

        // Register OpenAI Transport as a singleton
        $this->app->singleton(OpenAITransport::class, function ($app) {
            $apiKey = config('ai-assistant.api_key', '');
            if (!is_string($apiKey) || $apiKey === '' || $apiKey === 'YOUR_OPENAI_API_KEY' || $apiKey === 'your-api-key-here') {
                throw new InvalidApiKeyException();
            }

            $org = config('ai-assistant.organization');
            $headers = [
                'Authorization' => 'Bearer ' . $apiKey,
                'Accept' => 'application/json',
            ];
            if (is_string($org) && $org !== '' && $org !== 'YOUR_OPENAI_ORGANIZATION' && $org !== 'your-organization-id-here') {
                $headers['OpenAI-Organization'] = $org;
            }

            $timeout = config('ai-assistant.responses.timeout', 120);
            if (!is_numeric($timeout)) {
                $timeout = 120;
            }

            $client = $app->make(HttpClientFactory::class)->make(headers: $headers, timeout: (float)$timeout);

            return new GuzzleOpenAITransport($client, '/v1');
        });

        // Register the Cache Service as a singleton
        $this->app->singleton(CacheService::class);

        // Register Idempotency Service as a singleton
        $this->app->singleton(IdempotencyService::class);

        // Register Security Service as a singleton (used for configuration validation and request signing)
        $this->app->singleton(SecurityService::class, function ($app) {
            return new SecurityService(
                $app->make(CacheService::class),
                $app->make(LoggingService::class)
            );
        });

        // Register ResponseStatusStore as a singleton
        $this->app->singleton(ResponseStatusStore::class, function ($app) {
            return new ResponseStatusStore($app->make(CacheService::class));
        });

        // Register ThreadsToConversationsMapper for backward compatibility
        $this->app->singleton(ThreadsToConversationsMapper::class, function ($app) {
            return new ThreadsToConversationsMapper($app->make(CacheService::class));
        });

        // Register ToolRegistry singleton
        $this->app->singleton(ToolRegistry::class, function ($app) {
            $registry = new ToolRegistry();
            $executor = config('ai-assistant.tool_calling.executor', 'sync');
            if (!is_string($executor)) {
                $executor = 'sync';
            }
            if ($executor === 'queue') {
                // Configure a queue-backed executor. It dispatches jobs; if __parallel is true, use async dispatch, else dispatchSync.
                $registry->setExecutor(function (callable $fn, array $args) {
                    $name = $args['__name'] ?? null;
                    $parallel = (bool)($args['__parallel'] ?? false);
                    if (isset($args['__name'])) {
                        unset($args['__name']);
                    }
                    if (isset($args['__parallel'])) {
                        unset($args['__parallel']);
                    }
                    if (is_string($name) && $name !== '') {
                        if ($parallel) {
                            Log::info('[AI Assistant] Queue executor: dispatch async tool job', ['tool' => $name]);
                            // Fire-and-forget; we cannot collect results in true async without extra storage.
                            Bus::dispatch(new ExecuteToolCallJob($name, $args));
                            return ['queued' => true, 'tool' => $name];
                        }
                        Log::info('[AI Assistant] Queue executor: dispatch sync tool job', ['tool' => $name]);
                        return Bus::dispatchSync(
                            new ExecuteToolCallJob($name, $args)
                        );
                    }
                    // Fallback: execute inline
                    return $fn($args);
                });
            }
            return $registry;
        });

        // Register ProgressTrackerContract
        $this->app->singleton(ProgressTrackerContract::class, function ($app) {
            return new CacheBackedProgressTracker(
                $app->make(LoggingService::class),
                $app->make(MetricsCollectionService::class)
            );
        });

        // Register a streaming service with dependencies
        $this->app->singleton(StreamingService::class, function ($app) {
            return new StreamingService(
                $app->make(ResponsesRepositoryContract::class),
                $app->make(LoggingService::class),
                $app->make(MemoryMonitoringService::class),
                $app->make(ProgressTrackerContract::class),
                (array)(config('ai-assistant.streaming') ?? [])
            );
        });

        // Register background job service with dependencies
        $this->app->singleton(BackgroundJobService::class, function ($app) {
            return new BackgroundJobService(
                $app->make(LoggingService::class),
                $app->make(MetricsCollectionService::class),
                $app->make(ProgressTrackerContract::class),
                (array)(config('ai-assistant.background_jobs') ?? [])
            );
        });

        // Register lazy loading service as singleton
        $this->app->singleton(LazyLoadingService::class, function ($app) {
            return new LazyLoadingService(
                $app->make(LoggingService::class)
            );
        });

        // Register RequestRouter as singleton for unified API routing
        $this->app->singleton(RequestRouter::class, function ($app) {
            $configuredPriority = config('ai-assistant.routing.endpoint_priority');
            $endpointPriority = is_array($configuredPriority) && count($configuredPriority) > 0
                ? $configuredPriority
                : [
                    'audio_transcription',
                    'audio_translation',
                    'audio_speech',
                    'image_generation',
                    'image_edit',
                    'image_variation',
                    'chat_completion',
                    'response_api',
                ];

            $validateConflicts = config('ai-assistant.routing.validate_conflicts', true);
            if (!is_bool($validateConflicts)) {
                $validateConflicts = true;
            }

            $conflictBehavior = config('ai-assistant.routing.conflict_behavior', 'error');
            if (!is_string($conflictBehavior) || !in_array($conflictBehavior, ['error', 'warn', 'silent'], true)) {
                $conflictBehavior = 'error';
            }

            $validateEndpointNames = config('ai-assistant.routing.validate_endpoint_names', true);
            if (!is_bool($validateEndpointNames)) {
                $validateEndpointNames = true;
            }

            return new RequestRouter(
                endpointPriority: $endpointPriority,
                validateConflicts: $validateConflicts,
                conflictBehavior: $conflictBehavior,
                validateEndpointNames: $validateEndpointNames,
                logger: Log::channel()
            );
        });

        // Register AdapterFactory as singleton for endpoint adapters
        $this->app->singleton(AdapterFactory::class, function ($app) {
            return new AdapterFactory();
        });

        // Register MultipartRequestBuilder as singleton for handling file uploads
        $this->app->singleton(MultipartRequestBuilder::class, function ($app) {
            return new MultipartRequestBuilder();
        });

        // Register OpenAiClient as singleton for making HTTP calls to OpenAI endpoints
        $this->app->singleton(OpenAiClient::class, function ($app) {
            return new OpenAiClient(
                $app->make(OpenAITransport::class),
                $app->make(MultipartRequestBuilder::class)
            );
        });

        // Unified entrypoint service: AiManager
        $this->app->singleton(AiManager::class, function ($app) {
            return new AiManager(
                $app->make(\CreativeCrafts\LaravelAiAssistant\Services\AssistantService::class),
                $app->make(RequestRouter::class),
                $app->make(AdapterFactory::class)
            );
        });

        // Register new HTTP repositories for Responses, Conversations, and Files
        $this->app->bind(ResponsesRepositoryContract::class, function ($app) {
            return new ResponsesHttpRepository($app->make(OpenAITransport::class));
        });

        $this->app->bind(ConversationsRepositoryContract::class, function ($app) {
            return new ConversationsHttpRepository($app->make(OpenAITransport::class));
        });

        $this->app->bind(FilesRepositoryContract::class, function ($app) {
            return new FilesHttpRepository($app->make(OpenAITransport::class));
        });

        $this->app->bind(ResponsesInputItemsRepositoryContract::class, function ($app) {
            return new ResponsesInputItemsHttpRepository($app->make(OpenAITransport::class));
        });

        $this->app->bind(ModerationsRepositoryContract::class, function ($app) {
            return new ModerationsHttpRepository($app->make(OpenAITransport::class));
        });

        $this->app->bind(BatchesRepositoryContract::class, function ($app) {
            return new BatchesHttpRepository($app->make(OpenAITransport::class));
        });

        $this->app->bind(RealtimeSessionsRepositoryContract::class, function ($app) {
            return new RealtimeSessionsHttpRepository($app->make(OpenAITransport::class));
        });

        $this->app->bind(VectorStoresRepositoryContract::class, function ($app) {
            return new VectorStoresHttpRepository($app->make(OpenAITransport::class));
        });

        $this->app->bind(VectorStoreFilesRepositoryContract::class, function ($app) {
            return new VectorStoreFilesHttpRepository($app->make(OpenAITransport::class));
        });

        $this->app->bind(VectorStoreFileBatchesRepositoryContract::class, function ($app) {
            return new VectorStoreFileBatchesHttpRepository($app->make(OpenAITransport::class));
        });

        $this->app->bind(AssistantsRepositoryContract::class, function ($app) {
            return new AssistantsHttpRepository($app->make(OpenAITransport::class));
        });
    }
}
