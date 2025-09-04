<?php

declare(strict_types=1);

namespace CreativeCrafts\LaravelAiAssistant;

use CreativeCrafts\LaravelAiAssistant\Console\Commands\ConfigValidateCommand;
use CreativeCrafts\LaravelAiAssistant\Console\Commands\HealthCheckCommand;
use CreativeCrafts\LaravelAiAssistant\Console\Commands\TestConnectionCommand;
use CreativeCrafts\LaravelAiAssistant\Contracts\AiAssistantContract;
use CreativeCrafts\LaravelAiAssistant\Contracts\AssistantResourceContract;
use CreativeCrafts\LaravelAiAssistant\Contracts\ConversationsRepositoryContract;
use CreativeCrafts\LaravelAiAssistant\Contracts\FilesRepositoryContract;
use CreativeCrafts\LaravelAiAssistant\Contracts\OpenAiRepositoryContract;
use CreativeCrafts\LaravelAiAssistant\Contracts\ResponsesRepositoryContract;
use CreativeCrafts\LaravelAiAssistant\Exceptions\ConfigurationValidationException;
use CreativeCrafts\LaravelAiAssistant\Providers\CoreServiceProvider;
use CreativeCrafts\LaravelAiAssistant\Providers\HealthCheckServiceProvider;
use CreativeCrafts\LaravelAiAssistant\Providers\MonitoringServiceProvider;
use CreativeCrafts\LaravelAiAssistant\Providers\StorageServiceProvider;
use CreativeCrafts\LaravelAiAssistant\Providers\WebhookServiceProvider;
use CreativeCrafts\LaravelAiAssistant\Services\AssistantService;
use CreativeCrafts\LaravelAiAssistant\Services\CacheService;
use CreativeCrafts\LaravelAiAssistant\Services\LoggingService;
use Exception;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Log;
use Spatie\LaravelPackageTools\Package;
use Spatie\LaravelPackageTools\PackageServiceProvider;

class LaravelAiAssistantServiceProvider extends PackageServiceProvider
{
    public function configurePackage(Package $package): void
    {
        $package
            ->name('laravel-ai-assistant')
            ->hasConfigFile()
            ->hasMigrations(['create_ai_assistant_tables'])
            ->hasCommands([
                HealthCheckCommand::class,
                ConfigValidateCommand::class,
                TestConnectionCommand::class,
            ]);
    }

    public function packageRegistered(): void
    {
        // Register specialized service providers
        $this->app->register(MonitoringServiceProvider::class);
        $this->app->register(StorageServiceProvider::class);
        $this->app->register(CoreServiceProvider::class);
        $this->app->register(HealthCheckServiceProvider::class);
        $this->app->register(WebhookServiceProvider::class);

        // Register main facade/client bindings
        $this->app->singleton(OpenAIClientFacade::class, function ($app) {
            return new OpenAIClientFacade(
                $app->make(ResponsesRepositoryContract::class),
                $app->make(ConversationsRepositoryContract::class),
                $app->make(FilesRepositoryContract::class),
                $app->make(OpenAiRepositoryContract::class)
            );
        });

        // Register the AssistantService with repository and cache dependency injection
        $this->app->bind(AssistantResourceContract::class, AssistantService::class);
        $this->app->bind(AssistantService::class, function ($app) {
            return new AssistantService(
                $app->make(OpenAiRepositoryContract::class),
                $app->make(CacheService::class)
            );
        });

        // Register the main AiAssistant class
        $this->app->bind(AiAssistantContract::class, AiAssistant::class);
        $this->app->bind(AiAssistant::class, function ($app) {
            $assistant = new AiAssistant();
            $assistant->client($app->make(AssistantService::class));
            return $assistant;
        });

        // Register the Assistant class
        $this->app->bind(Assistant::class, function ($app) {
            $assistant = new Assistant();
            $assistant->client($app->make(AssistantService::class));
            return $assistant;
        });

        // Register convenient aliases for the main classes
        $this->app->alias(AiAssistant::class, 'ai-assistant');
        $this->app->alias(Assistant::class, 'assistant');
    }

    public function packageBooted(): void
    {
        // Apply environment overlay defaults before validation so tests and runtime
        // overrides remain the highest priority.
        $this->applyEnvironmentOverlayDefaults();

        if (!app()->runningUnitTests() && !defined('PHPSTAN_RUNNING')) {
            $this->validateConfiguration();
        }

        // Publish model stubs for developers to customize
        $this->publishes([
            __DIR__ . '/../stubs/models/AssistantProfile.php.stub' => app_path('Models/AssistantProfile.php'),
            __DIR__ . '/../stubs/models/Conversation.php.stub' => app_path('Models/Conversation.php'),
            __DIR__ . '/../stubs/models/ConversationItem.php.stub' => app_path('Models/ConversationItem.php'),
            __DIR__ . '/../stubs/models/ResponseRecord.php.stub' => app_path('Models/ResponseRecord.php'),
            __DIR__ . '/../stubs/models/ToolInvocation.php.stub' => app_path('Models/ToolInvocation.php'),
        ], 'ai-assistant-models');

        // Publish migrations with a timestamp for easy customization
        $timestamp = date('Y_m_d_His');
        $this->publishes([
            __DIR__ . '/../database/migrations/create_ai_assistant_tables.php' => database_path("migrations/{$timestamp}_create_ai_assistant_tables.php"),
        ], 'ai-assistant-migrations');
    }

    /**
     * Apply environment overlay defaults into ai-assistant config with predictable precedence.
     * Precedence (highest to lowest):
     *  - Runtime overrides (config([...]) / env())
     *  - Environment overlay defaults (config/environments/{env}.php)
     *  - Base package config (config/ai-assistant.php)
     */
    protected function applyEnvironmentOverlayDefaults(): void
    {
        // Read the current config (may already include runtime overrides set before boot)
        $current = Config::get('ai-assistant', []);
        $current = is_array($current) ? $current : [];

        // Load base package config directly from file to compare and compose
        $base = $this->loadPackageBaseConfig();

        // Determine overlay path(s) based on environment
        $env = $this->app->environment();
        $overlayPath = null;
        if (in_array($env, ['local', 'development'], true)) {
            $overlayPath = __DIR__ . '/../config/environments/development.php';
        } elseif ($env === 'testing') {
            $overlayPath = __DIR__ . '/../config/environments/testing.php';
        } elseif ($env === 'production') {
            $overlayPath = __DIR__ . '/../config/environments/production.php';
        }

        $overlay = [];
        if ($overlayPath && is_file($overlayPath)) {
            $loaded = require $overlayPath;
            if (is_array($loaded)) {
                $overlay = $loaded;
            }
        }

        // Compose with precedence: overlay overrides base defaults for the current environment
        $composed = $this->arrayReplaceRecursive($base, $overlay);

        // Compute runtime overrides as the delta between current config and base
        $runtimeOverrides = $this->arrayRecursiveDiff($current, $base);

        // Final: composed overridden by runtime overrides
        $final = $this->arrayReplaceRecursive($composed, $runtimeOverrides);

        Config::set('ai-assistant', $final);
    }

    /**
     * Load the base package config without any overlays or runtime overrides
     */
    protected function loadPackageBaseConfig(): array
    {
        $configPath = __DIR__ . '/../config/ai-assistant.php';
        if (!is_file($configPath)) {
            return [];
        }
        $config = require $configPath;
        return is_array($config) ? $config : [];
    }

    /**
     * Recursively replace values in the base array with values from the replacements array
     */
    protected function arrayReplaceRecursive(array $base, array $replacements): array
    {
        return array_replace_recursive($base, $replacements);
    }

    /**
     * Compute the recursive difference between two arrays (what's in $a but differs from $b)
     */
    protected function arrayRecursiveDiff(array $a, array $b): array
    {
        $diff = [];
        foreach ($a as $key => $value) {
            if (!array_key_exists($key, $b)) {
                $diff[$key] = $value;
            } elseif (is_array($value) && is_array($b[$key])) {
                $subDiff = $this->arrayRecursiveDiff($value, $b[$key]);
                if (!empty($subDiff)) {
                    $diff[$key] = $subDiff;
                }
            } elseif ($value !== $b[$key]) {
                $diff[$key] = $value;
            }
        }
        return $diff;
    }

    /**
     * Validate the current package configuration
     */
    protected function validateConfiguration(): void
    {
        try {
            $config = config('ai-assistant', []);
            if (!is_array($config)) {
                throw new ConfigurationValidationException('Configuration must be an array');
            }
            $this->validateRequiredSettings($config);
            $this->validateModelConfiguration($config);
            $this->validateParameterRanges($config);
            $this->validateAdvancedConfiguration($config);
            $this->validateWebhooksConfiguration($config);
            $this->validateEnvironmentSpecificSettings($config);
        } catch (Exception $e) {
            app(LoggingService::class)->logError(
                'configuration_validation',
                $e,
                ['validation_stage' => 'package_boot']
            );
            throw $e;
        }
    }

    /**
     * Validate required configuration settings
     */
    protected function validateRequiredSettings(array $config): void
    {
        $apiKey = $config['api_key'] ?? '';
        if (!is_string($apiKey) || $apiKey === '' || $apiKey === 'YOUR_OPENAI_API_KEY_HERE') {
            throw new ConfigurationValidationException(
                'OpenAI API key is required. Set OPENAI_API_KEY environment variable or publish and configure ai-assistant.php'
            );
        }

        // Validate API key format (should start with sk- for OpenAI keys, unless it's a test key)
        if (!$this->isTestApiKey($apiKey) && !str_starts_with($apiKey, 'sk-')) {
            app(LoggingService::class)->logSecurityEvent(
                'api_key_format_validation',
                'API key does not follow expected OpenAI format',
                ['key_prefix' => 'non-standard']
            );
        }

        // Validate organization if provided
        $org = $config['organization'] ?? null;
        if ($org !== null && (!is_string($org) || $org === 'YOUR_OPENAI_ORGANIZATION' || $org === 'your-organization-id-here')) {
            throw new ConfigurationValidationException(
                'OpenAI organization must be a valid string or null. Update OPENAI_ORGANIZATION or your config.'
            );
        }

        // Validate persistence driver
        $driver = $config['persistence']['driver'] ?? 'memory';
        if (!in_array($driver, ['memory', 'eloquent'], true)) {
            throw new ConfigurationValidationException("Persistence driver must be 'memory' or 'eloquent', got: {$driver}");
        }
    }

    /**
     * Validate model configuration settings
     */
    protected function validateModelConfiguration(array $config): void
    {
        $models = [
            'chat_model' => $config['models']['chat'] ?? 'gpt-4o-mini',
            'edit_model' => $config['models']['edit'] ?? 'gpt-4o-mini',
            'audio_model' => $config['models']['audio'] ?? 'whisper-1',
        ];

        foreach ($models as $type => $model) {
            if (!is_string($model) || $model === '') {
                throw new ConfigurationValidationException("Model configuration '{$type}' must be a non-empty string");
            }

            // Basic validation for known model patterns
            $validPatterns = [
                'gpt-3.5-turbo', 'gpt-4', 'gpt-4o', 'gpt-4o-mini',
                'text-davinci', 'text-curie', 'text-babbage', 'text-ada',
                'whisper-1', 'tts-1', 'dall-e-2', 'dall-e-3'
            ];

            $isValid = false;
            foreach ($validPatterns as $pattern) {
                if (str_starts_with($model, $pattern)) {
                    $isValid = true;
                    break;
                }
            }

            if (!$isValid) {
                app(LoggingService::class)->logConfigurationEvent(
                    'validation_warning',
                    "models.{$type}",
                    'unrecognized_pattern',
                    'configuration_validation'
                );
            }
        }
    }

    /**
     * Validate numeric parameter ranges
     */
    protected function validateParameterRanges(array $config): void
    {
        $ranges = [
            'responses.timeout' => [1, 300],
            'responses.max_output_tokens' => [1, 32000],
            'streaming.timeout' => [1, 300],
            'streaming.buffer_size' => [1024, 65536],
            'streaming.chunk_size' => [512, 8192],
            'tool_calling.max_rounds' => [1, 10],
        ];

        foreach ($ranges as $key => $range) {
            $value = data_get($config, $key);
            if ($value !== null && is_numeric($value)) {
                $numValue = (float)$value;
                if ($numValue < $range[0] || $numValue > $range[1]) {
                    throw new ConfigurationValidationException(
                        "Configuration '{$key}' must be between {$range[0]} and {$range[1]}, got: {$numValue}"
                    );
                }
            }
        }
    }

    /**
     * Validate advanced configuration settings
     */
    protected function validateAdvancedConfiguration(array $config): void
    {
        // Validate tool calling executor
        $executor = $config['tool_calling']['executor'] ?? 'sync';
        if (!in_array($executor, ['sync', 'queue'], true)) {
            throw new ConfigurationValidationException("Tool calling executor must be 'sync' or 'queue', got: {$executor}");
        }

        // Validate queue configuration if queue executor is used
        if ($executor === 'queue') {
            $queueConnection = $config['queue']['connection'] ?? config('queue.default');
            if (!is_string($queueConnection) || $queueConnection === '') {
                throw new ConfigurationValidationException('Queue connection must be configured when using queue executor');
            }
        }

        // Validate retry configuration
        $retryEnabled = $config['responses']['retry']['enabled'] ?? true;
        if ($retryEnabled) {
            $maxAttempts = $config['responses']['retry']['max_attempts'] ?? 3;
            if (!is_int($maxAttempts) || $maxAttempts < 1 || $maxAttempts > 10) {
                throw new ConfigurationValidationException('Retry max_attempts must be between 1 and 10');
            }
        }

        // Validate connection pool settings if enabled
        $poolEnabled = $config['connection_pool']['enabled'] ?? false;
        if ($poolEnabled) {
            $maxConnections = $config['connection_pool']['max_connections'] ?? 100;
            if (!is_int($maxConnections) || $maxConnections < 1 || $maxConnections > 1000) {
                throw new ConfigurationValidationException('Connection pool max_connections must be between 1 and 1000');
            }
        }
    }

    /**
     * Validate webhook configuration
     */
    protected function validateWebhooksConfiguration(array $config): void
    {
        $webhooksEnabled = $config['webhooks']['enabled'] ?? false;
        if (!$webhooksEnabled) {
            return;
        }

        $path = $config['webhooks']['path'] ?? '/ai-assistant/webhook';
        if (!is_string($path) || !str_starts_with($path, '/')) {
            throw new ConfigurationValidationException('Webhook path must be a string starting with /');
        }

        $signingSecret = $config['webhooks']['signing_secret'] ?? '';
        if (!is_string($signingSecret) || strlen($signingSecret) < 32) {
            throw new ConfigurationValidationException(
                'Webhook signing secret must be at least 32 characters when webhooks are enabled'
            );
        }

        $signatureHeader = $config['webhooks']['signature_header'] ?? 'X-Webhook-Signature';
        if (!is_string($signatureHeader) || $signatureHeader === '') {
            throw new ConfigurationValidationException('Webhook signature header must be a non-empty string');
        }
    }

    /**
     * Validate environment-specific settings
     */
    protected function validateEnvironmentSpecificSettings(array $config): void
    {
        $env = $this->app->environment();

        // In production, certain debugging features should be disabled
        if ($env === 'production') {
            $mockResponses = $config['mock_responses'] ?? false;
            if ($mockResponses) {
                Log::warning('[AI Assistant] Mock responses are enabled in production environment');
            }

            $debugMode = $config['debug'] ?? false;
            if ($debugMode) {
                Log::warning('[AI Assistant] Debug mode is enabled in production environment');
            }
        }

        // In testing, ensure test-friendly defaults
        if ($env === 'testing') {
            $apiKey = $config['api_key'] ?? '';
            if (!$this->isTestApiKey($apiKey) && !str_starts_with($apiKey, 'sk-')) {
                Log::info('[AI Assistant] Using test API key in testing environment');
            }
        }
    }

    /**
     * Check if the given API key appears to be a test/mock key
     */
    protected function isTestApiKey(string $apiKey): bool
    {
        $testPatterns = [
            'test',
            'mock',
            'fake',
            'demo',
            'example',
            'YOUR_OPENAI_API_KEY',
        ];

        $lowerKey = strtolower($apiKey);
        foreach ($testPatterns as $pattern) {
            if (str_contains($lowerKey, strtolower($pattern))) {
                return true;
            }
        }

        return false;
    }
}
