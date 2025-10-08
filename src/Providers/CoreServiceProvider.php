<?php

declare(strict_types=1);

namespace CreativeCrafts\LaravelAiAssistant\Providers;

use CreativeCrafts\LaravelAiAssistant\Compat\OpenAI\Client;
use CreativeCrafts\LaravelAiAssistant\Contracts\ConversationsRepositoryContract;
use CreativeCrafts\LaravelAiAssistant\Contracts\FilesRepositoryContract;
use CreativeCrafts\LaravelAiAssistant\Contracts\OpenAiRepositoryContract;
use CreativeCrafts\LaravelAiAssistant\Contracts\ResponsesInputItemsRepositoryContract;
use CreativeCrafts\LaravelAiAssistant\Contracts\ResponsesRepositoryContract;
use CreativeCrafts\LaravelAiAssistant\Exceptions\ConfigurationValidationException;
use CreativeCrafts\LaravelAiAssistant\Jobs\ExecuteToolCallJob;
use CreativeCrafts\LaravelAiAssistant\Repositories\Http\ConversationsHttpRepository;
use CreativeCrafts\LaravelAiAssistant\Repositories\Http\FilesHttpRepository;
use CreativeCrafts\LaravelAiAssistant\Repositories\Http\ResponsesHttpRepository;
use CreativeCrafts\LaravelAiAssistant\Repositories\Http\ResponsesInputItemsHttpRepository;
use CreativeCrafts\LaravelAiAssistant\Repositories\NullOpenAiRepository;
use CreativeCrafts\LaravelAiAssistant\Repositories\OpenAiRepository;
use CreativeCrafts\LaravelAiAssistant\Services\AiManager;
use CreativeCrafts\LaravelAiAssistant\Services\AppConfig;
use CreativeCrafts\LaravelAiAssistant\Services\BackgroundJobService;
use CreativeCrafts\LaravelAiAssistant\Services\CacheService;
use CreativeCrafts\LaravelAiAssistant\Services\IdempotencyService;
use CreativeCrafts\LaravelAiAssistant\Services\LazyLoadingService;
use CreativeCrafts\LaravelAiAssistant\Services\LoggingService;
use CreativeCrafts\LaravelAiAssistant\Services\MemoryMonitoringService;
use CreativeCrafts\LaravelAiAssistant\Services\MetricsCollectionService;
use CreativeCrafts\LaravelAiAssistant\Services\ResponseStatusStore;
use CreativeCrafts\LaravelAiAssistant\Services\SecurityService;
use CreativeCrafts\LaravelAiAssistant\Services\StreamingService;
use CreativeCrafts\LaravelAiAssistant\Services\ThreadsToConversationsMapper;
use CreativeCrafts\LaravelAiAssistant\Services\ToolRegistry;
use GuzzleHttp\Client as GuzzleClient;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\ServiceProvider;

class CoreServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        // Register the OpenAI Client as a singleton
        $this->app->singleton(Client::class, function ($app) {
            return AppConfig::openAiClient();
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

        // Register a streaming service with dependencies
        $this->app->singleton(StreamingService::class, function ($app) {
            return new StreamingService(
                $app->make(LoggingService::class),
                $app->make(MemoryMonitoringService::class),
                (array)(config('ai-assistant.streaming') ?? [])
            );
        });

        // Register background job service with dependencies
        $this->app->singleton(BackgroundJobService::class, function ($app) {
            return new BackgroundJobService(
                $app->make(LoggingService::class),
                $app->make(MetricsCollectionService::class),
                (array)(config('ai-assistant.background_jobs') ?? [])
            );
        });

        // Register lazy loading service as singleton
        $this->app->singleton(LazyLoadingService::class, function ($app) {
            return new LazyLoadingService(
                $app->make(LoggingService::class)
            );
        });

        // Unified entrypoint service: AiManager
        $this->app->singleton(AiManager::class, function ($app) {
            return new AiManager();
        });

        // Register the OpenAI Repository with dependency injection honoring config overrides
        $this->app->bind(OpenAiRepository::class, function ($app) {
            return new OpenAiRepository($app->make(Client::class));
        });
        $this->app->bind(OpenAiRepositoryContract::class, function ($app) {
            $useMock = (bool)config('ai-assistant.mock_responses', false);
            if ($useMock) {
                return $app->make(NullOpenAiRepository::class);
            }
            $override = config('ai-assistant.repository');
            if (is_string($override) && $override !== '') {
                // Validate implements contract; if not, throw a clear configuration exception
                if (!class_exists($override)) {
                    throw new ConfigurationValidationException("Configured ai-assistant.repository class '{$override}' does not exist.");
                }
                if (!is_subclass_of($override, OpenAiRepositoryContract::class)) {
                    throw new ConfigurationValidationException("Configured ai-assistant.repository class '{$override}' must implement " . OpenAiRepositoryContract::class);
                }
                return $app->make($override);
            }
            return $app->make(OpenAiRepository::class);
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
