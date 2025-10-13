<?php

declare(strict_types=1);

namespace CreativeCrafts\LaravelAiAssistant\Providers;

use CreativeCrafts\LaravelAiAssistant\Adapters\AdapterFactory;
use CreativeCrafts\LaravelAiAssistant\Contracts\ConversationsRepositoryContract;
use CreativeCrafts\LaravelAiAssistant\Contracts\FilesRepositoryContract;
use CreativeCrafts\LaravelAiAssistant\Contracts\ProgressTrackerContract;
use CreativeCrafts\LaravelAiAssistant\Contracts\ResponsesInputItemsRepositoryContract;
use CreativeCrafts\LaravelAiAssistant\Contracts\ResponsesRepositoryContract;
use CreativeCrafts\LaravelAiAssistant\Exceptions\InvalidApiKeyException;
use CreativeCrafts\LaravelAiAssistant\Jobs\ExecuteToolCallJob;
use CreativeCrafts\LaravelAiAssistant\Repositories\Http\ConversationsHttpRepository;
use CreativeCrafts\LaravelAiAssistant\Repositories\Http\FilesHttpRepository;
use CreativeCrafts\LaravelAiAssistant\Repositories\Http\ResponsesHttpRepository;
use CreativeCrafts\LaravelAiAssistant\Repositories\Http\ResponsesInputItemsHttpRepository;
use CreativeCrafts\LaravelAiAssistant\Http\MultipartRequestBuilder;
use CreativeCrafts\LaravelAiAssistant\Services\AiManager;
use CreativeCrafts\LaravelAiAssistant\Services\RequestRouter;
use CreativeCrafts\LaravelAiAssistant\Services\BackgroundJobService;
use CreativeCrafts\LaravelAiAssistant\Services\CacheBackedProgressTracker;
use CreativeCrafts\LaravelAiAssistant\Services\CacheService;
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
use GuzzleHttp\Client as GuzzleClient;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\ServiceProvider;

class CoreServiceProvider extends ServiceProvider
{
    public function register(): void
    {
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

            $guzzle = new GuzzleClient([
                'base_uri' => 'https://api.openai.com',
                'headers' => $headers,
                'http_errors' => false,
                'timeout' => (float)$timeout,
                'connect_timeout' => 10,
            ]);

            return new GuzzleOpenAITransport($guzzle, '/v1');
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
                            // For SDK determinism, we still return the immediate inline result to the caller.
                            Bus::dispatch(new ExecuteToolCallJob($name, $args));
                            return $fn($args);
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
            return new RequestRouter();
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
            $client = new GuzzleClient([
                'base_uri' => 'https://api.openai.com',
                'headers' => $headers,
                'http_errors' => false,
                'timeout' => (float)$timeout,
                'connect_timeout' => 10,
            ]);
            return new ResponsesHttpRepository($client);
        });

        $this->app->bind(ConversationsRepositoryContract::class, function ($app) {
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
            $client = new GuzzleClient([
                'base_uri' => 'https://api.openai.com',
                'headers' => $headers,
                'http_errors' => false,
                'timeout' => (float)$timeout,
                'connect_timeout' => 10,
            ]);
            return new ConversationsHttpRepository($client);
        });

        $this->app->bind(FilesRepositoryContract::class, function ($app) {
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
            $client = new GuzzleClient([
                'base_uri' => 'https://api.openai.com',
                'headers' => $headers,
                'http_errors' => false,
                'timeout' => (float)$timeout,
                'connect_timeout' => 10,
            ]);
            return new FilesHttpRepository($client);
        });

        $this->app->bind(ResponsesInputItemsRepositoryContract::class, function ($app) {
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
            $client = new GuzzleClient([
                'base_uri' => 'https://api.openai.com',
                'headers' => $headers,
                'http_errors' => false,
                'timeout' => (float)$timeout,
                'connect_timeout' => 10,
            ]);
            return new ResponsesInputItemsHttpRepository($client);
        });
    }
}
