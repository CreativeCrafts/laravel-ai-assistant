<?php

declare(strict_types=1);

namespace CreativeCrafts\LaravelAiAssistant\Console\Commands;

use Illuminate\Console\Command;
use JsonException;
use RuntimeException;
use Throwable;

/**
 * Configuration validation command for AI Assistant
 * This command validates all AI Assistant configuration settings including environment variables,
 * API configuration, model settings, persistence, streaming, responses, tool calling, queues,
 * metrics, and webhooks. It provides detailed validation reports and can output results in
 * JSON format for programmatic consumption.
 */
class ConfigValidateCommand extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'ai:config-validate 
                           {--json : Output results in JSON format}
                           {--strict : Enable strict validation mode}
                           {--ci : CI mode (implies --strict and --json)}
                           {--show-values : Show configuration values (may expose secrets)}';

    /**
     * The console command description.
     */
    protected $description = 'Validate AI Assistant configuration settings';

    private array $validationResults = [];
    private bool $hasErrors = false;
    private bool $hasWarnings = false;
    private bool $strictMode = false;

    /**
     * Execute the console command.
     *
     * @throws JsonException
     */
    public function handle(): int
    {
        $this->info('🔧 AI Assistant Configuration Validation');
        $this->newLine();

        $this->strictMode = $this->boolOption($this->option('strict'));

        $ci = $this->boolOption($this->option('ci'));
        if ($ci) {
            $this->strictMode = true;
        }
        $this->validateConfiguration();

        if ($this->boolOption($this->option('json')) || $this->boolOption($this->option('ci'))) {
            $this->outputJson();
        } else {
            $this->outputReport();
        }

        return $this->hasErrors ? 1 : 0;
    }

    /**
     * Normalize an option value (array|string|bool|null) to bool for static analysis and runtime safety.
     *
     * @param mixed $value
     * @return bool
     */
    private function boolOption(mixed $value): bool
    {
        if (is_bool($value)) {
            return $value;
        }
        $raw = is_array($value) ? reset($value) : $value;
        if ($raw === null) {
            return false;
        }
        return filter_var((string)$raw, FILTER_VALIDATE_BOOLEAN) === true;
    }

    /**
     * Validate all configuration sections
     */
    private function validateConfiguration(): void
    {
        $validations = [
            'Environment Variables' => 'validateEnvironmentVariables',
            'API Configuration' => 'validateApiConfiguration',
            'Model Configuration' => 'validateModelConfiguration',
            'Persistence Configuration' => 'validatePersistenceConfiguration',
            'Streaming Configuration' => 'validateStreamingConfiguration',
            'Response Configuration' => 'validateResponseConfiguration',
            'Conversation Configuration' => 'validateConversationConfiguration',
            'Tool Calling Configuration' => 'validateToolCallingConfiguration',
            'Queue Configuration' => 'validateQueueConfiguration',
            'Metrics Configuration' => 'validateMetricsConfiguration',
            'Webhook Configuration' => 'validateWebhookConfiguration',
        ];

        foreach ($validations as $section => $method) {
            $this->info("Validating {$section}...");

            try {
                $this->$method();
                $this->line("  ✅ {$section} - Valid");
            } catch (Throwable $e) {
                $this->error("  ❌ {$section} - FAILED: {$e->getMessage()}");
                $this->addValidationResult($section, 'error', $e->getMessage());
                $this->hasErrors = true;
            }
        }
    }

    /**
     * Add validation result
     */
    private function addValidationResult(string $section, string $level, string $message): void
    {
        $this->validationResults[$section][] = [
            'level' => $level,
            'message' => $message,
            'timestamp' => now()->toISOString(),
        ];
    }

    /**
     * Output JSON report
     *
     * @throws JsonException
     */
    private function outputJson(): void
    {
        $status = 'valid';
        if ($this->hasErrors) {
            $status = 'invalid';
        } elseif ($this->hasWarnings) {
            $status = 'valid_with_warnings';
        }

        $report = [
            'status' => $status,
            'timestamp' => now()->toISOString(),
            'validation_results' => $this->validationResults,
            'summary' => [
                'errors' => $this->hasErrors,
                'warnings' => $this->hasWarnings,
                'total_sections' => count($this->validationResults),
                'strict_mode' => $this->strictMode,
            ]
        ];

        if ($this->boolOption($this->option('show-values'))) {
            $config = config('ai-assistant');
            if (!is_array($config)) {
                $config = [];
            }
            $report['configuration'] = $this->sanitizeConfigForDisplay($config);
        }

        $json = json_encode($report, JSON_THROW_ON_ERROR | JSON_PRETTY_PRINT);
        $this->line($json ?: '{}');
    }

    /**
     * Sanitize configuration for display
     */
    private function sanitizeConfigForDisplay(array $config): array
    {
        $sensitiveKeys = ['api_key', 'secret', 'password', 'token', 'key'];

        foreach ($config as $key => $value) {
            if (is_array($value)) {
                $config[$key] = $this->sanitizeConfigForDisplay($value);
            } elseif (is_string($value)) {
                foreach ($sensitiveKeys as $sensitiveKey) {
                    if (stripos($key, $sensitiveKey) !== false) {
                        $config[$key] = str_repeat('*', min(strlen($value), 20));
                        break;
                    }
                }
            }
        }

        return $config;
    }

    /**
     * Output detailed report
     */
    private function outputReport(): void
    {
        $this->newLine();
        $this->info('📋 Configuration Validation Results:');
        $this->newLine();

        foreach ($this->validationResults as $section => $results) {
            $this->line("<fg=cyan>{$section}:</>");

            foreach ($results as $result) {
                $icon = match ($result['level']) {
                    'error' => '❌',
                    'warning' => '⚠️',
                    'success' => '✅',
                    default => 'ℹ️'
                };

                $this->line("  {$icon} {$result['message']}");
            }
            $this->newLine();
        }

        // Show configuration values if requested
        if ($this->boolOption($this->option('show-values'))) {
            $this->showConfigurationValues();
        }

        // Summary
        $this->info('📊 Validation Summary:');
        if ($this->hasErrors) {
            $this->error('❌ Configuration validation failed with errors');
        } elseif ($this->hasWarnings) {
            $this->warn('⚠️  Configuration validation completed with warnings');
        } else {
            $this->info('✅ All configuration settings are valid');
        }
    }

    /**
     * Show configuration values (sanitized)
     *
     * @throws JsonException
     */
    private function showConfigurationValues(): void
    {
        $this->newLine();
        $this->info('🔍 Current Configuration Values:');
        $this->newLine();

        $config = config('ai-assistant');
        if (!is_array($config)) {
            $config = [];
        }
        $sanitized = $this->sanitizeConfigForDisplay($config);

        $json = json_encode($sanitized, JSON_THROW_ON_ERROR | JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
        $this->line($json ?: '{}');
        $this->newLine();
    }

    /**
     * Validate environment variables
     */
    private function validateEnvironmentVariables(): void
    {
        $required = [
            'OPENAI_API_KEY' => 'OpenAI API key is required',
        ];

        $optional = [
            'OPENAI_ORGANIZATION' => 'OpenAI organization ID',
            'AI_ASSISTANT_PERSISTENCE_DRIVER' => 'Persistence driver (memory|eloquent)',
            'AI_STREAMING_ENABLED' => 'Streaming feature toggle',
        ];

        // Check required environment variables
        foreach ($required as $envVar => $description) {
            $configKey = $this->getConfigKeyForEnvVar($envVar);
            $value = config($configKey);
            if (!$value) {
                throw new RuntimeException("Required environment variable '{$envVar}' not set: {$description}");
            }

            // Validate API key format
            if ($envVar === 'OPENAI_API_KEY' && is_string($value)) {
                if (!str_starts_with($value, 'sk-') && !str_starts_with($value, 'sk-proj-')) {
                    $this->addValidationResult(
                        'Environment Variables',
                        'warning',
                        'OPENAI_API_KEY does not start with expected prefix (sk- or sk-proj-)'
                    );
                    $this->hasWarnings = true;
                }

                if (strlen($value) < 20) {
                    $this->addValidationResult(
                        'Environment Variables',
                        'warning',
                        'OPENAI_API_KEY appears to be too short'
                    );
                    $this->hasWarnings = true;
                }
            }
        }

        // Check optional environment variables
        foreach ($optional as $envVar => $description) {
            $configKey = $this->getConfigKeyForEnvVar($envVar);
            $value = config($configKey);
            if ($this->strictMode && !$value) {
                $this->addValidationResult(
                    'Environment Variables',
                    'warning',
                    "Optional environment variable '{$envVar}' not set: {$description}"
                );
                $this->hasWarnings = true;
            }
        }

        $this->addValidationResult('Environment Variables', 'success', 'All required environment variables present');
    }

    /**
     * Get config key for environment variable
     */
    private function getConfigKeyForEnvVar(string $envVar): string
    {
        $mapping = [
            'OPENAI_API_KEY' => 'ai-assistant.api_key',
            'OPENAI_ORGANIZATION' => 'ai-assistant.organization',
            'AI_ASSISTANT_PERSISTENCE_DRIVER' => 'ai-assistant.persistence.driver',
            'AI_STREAMING_ENABLED' => 'ai-assistant.streaming.enabled',
        ];

        return $mapping[$envVar] ?? strtolower(str_replace('_', '.', $envVar));
    }

    /**
     * Validate API configuration
     */
    private function validateApiConfiguration(): void
    {
        $apiKey = config('ai-assistant.api_key');
        if (!$apiKey) {
            throw new RuntimeException('OpenAI API key not configured');
        }

        $organization = config('ai-assistant.organization');
        if ($organization && is_string($organization) && !preg_match('/^org-[a-zA-Z0-9]+$/', $organization)) {
            $this->addValidationResult(
                'API Configuration',
                'warning',
                'OpenAI organization ID format appears invalid'
            );
            $this->hasWarnings = true;
        }

        $this->addValidationResult('API Configuration', 'success', 'API configuration valid');
    }

    /**
     * Validate model configuration
     */
    private function validateModelConfiguration(): void
    {
        $models = [
            'chat' => config('ai-assistant.models.chat'),
            'edit' => config('ai-assistant.models.edit'),
            'audio' => config('ai-assistant.models.audio'),
        ];

        $validChatModels = [
            'gpt-5',
            'gpt-5-mini',
            'gpt-5-nano',
            'gpt-4',
            'gpt-4-turbo',
            'gpt-4-turbo-preview',
            'gpt-4o',
            'gpt-4o-mini',
            'gpt-3.5-turbo',
        ];

        foreach ($models as $type => $model) {
            if (!$model) {
                if ($this->strictMode || $type === 'chat') {
                    throw new RuntimeException("Model configuration missing for type: {$type}");
                } else {
                    $this->addValidationResult(
                        'Model Configuration',
                        'warning',
                        "Model not configured for type: {$type}"
                    );
                    $this->hasWarnings = true;
                }
                continue;
            }

            // Validate chat model specifically
            if ($type === 'chat' && !in_array($model, $validChatModels)) {
                $this->addValidationResult(
                    'Model Configuration',
                    'warning',
                    "Chat model '" . (is_string($model) ? $model : 'unknown') . "' may not be supported"
                );
                $this->hasWarnings = true;
            }
        }

        $this->addValidationResult('Model Configuration', 'success', 'Model configuration valid');
    }

    /**
     * Validate persistence configuration
     */
    private function validatePersistenceConfiguration(): void
    {
        $driver = config('ai-assistant.persistence.driver');

        if (!in_array($driver, ['memory', 'eloquent'])) {
            throw new RuntimeException("Invalid persistence driver: " . (is_string($driver) ? $driver : 'unknown') . ". Must be 'memory' or 'eloquent'");
        }

        if ($driver === 'eloquent') {
            // Additional validation for eloquent driver
            $connection = config('ai-assistant.persistence.eloquent.connection');
            if ($connection && is_string($connection) && !config("database.connections." . $connection)) {
                $this->addValidationResult(
                    'Persistence Configuration',
                    'warning',
                    "Database connection '" . $connection . "' not configured"
                );
                $this->hasWarnings = true;
            }
        }

        $this->addValidationResult(
            'Persistence Configuration',
            'success',
            "Persistence driver '" . (is_string($driver) ? $driver : 'unknown') . "' configuration valid"
        );
    }

    /**
     * Validate streaming configuration
     */
    private function validateStreamingConfiguration(): void
    {
        $enabled = config('ai-assistant.streaming.enabled');

        if ($enabled) {
            $timeout = config('ai-assistant.streaming.sse_timeout');
            $bufferSize = config('ai-assistant.streaming.buffer_size');
            $chunkSize = config('ai-assistant.streaming.chunk_size');

            if (!is_numeric($timeout) || $timeout < 0) {
                throw new RuntimeException('Invalid streaming SSE timeout value');
            }

            if (!is_numeric($bufferSize) || $bufferSize < 1024) {
                $this->addValidationResult(
                    'Streaming Configuration',
                    'warning',
                    'Streaming buffer size may be too small'
                );
                $this->hasWarnings = true;
            }

            if (!is_numeric($chunkSize) || $chunkSize < 512) {
                $this->addValidationResult(
                    'Streaming Configuration',
                    'warning',
                    'Streaming chunk size may be too small'
                );
                $this->hasWarnings = true;
            }
        }

        $this->addValidationResult(
            'Streaming Configuration',
            'success',
            'Streaming configuration valid'
        );
    }

    /**
     * Validate response configuration
     */
    private function validateResponseConfiguration(): void
    {
        $timeout = config('ai-assistant.responses.timeout');
        $maxOutputTokens = config('ai-assistant.responses.max_output_tokens');

        if (!is_numeric($timeout) || $timeout <= 0) {
            throw new RuntimeException('Invalid response timeout value');
        }

        if ($timeout > 300) {
            $this->addValidationResult(
                'Response Configuration',
                'warning',
                'Response timeout is very high (>300s)'
            );
            $this->hasWarnings = true;
        }

        if ($maxOutputTokens && (!is_numeric($maxOutputTokens) || $maxOutputTokens <= 0)) {
            throw new RuntimeException('Invalid max output tokens value');
        }

        // Validate retry configuration
        $retryEnabled = config('ai-assistant.responses.retry.enabled');
        if ($retryEnabled) {
            $maxAttempts = config('ai-assistant.responses.retry.max_attempts');
            if (!is_numeric($maxAttempts) || $maxAttempts < 1) {
                throw new RuntimeException('Invalid retry max attempts value');
            }
        }

        $this->addValidationResult(
            'Response Configuration',
            'success',
            'Response configuration valid'
        );
    }

    /**
     * Validate conversation configuration
     */
    private function validateConversationConfiguration(): void
    {
        $timeout = config('ai-assistant.conversations.timeout');

        if (!is_numeric($timeout) || $timeout <= 0) {
            throw new RuntimeException('Invalid conversation timeout value');
        }

        if ($timeout > 300) {
            $this->addValidationResult(
                'Conversation Configuration',
                'warning',
                'Conversation timeout is very high (>300s)'
            );
            $this->hasWarnings = true;
        }

        // Validate retry configuration
        $retryEnabled = config('ai-assistant.conversations.retry.enabled');
        if ($retryEnabled) {
            $maxAttempts = config('ai-assistant.conversations.retry.max_attempts');
            if (!is_numeric($maxAttempts) || $maxAttempts < 1) {
                throw new RuntimeException('Invalid conversation retry max attempts value');
            }
        }

        $this->addValidationResult(
            'Conversation Configuration',
            'success',
            'Conversation configuration valid'
        );
    }

    /**
     * Validate tool calling configuration
     */
    private function validateToolCallingConfiguration(): void
    {
        $maxRounds = config('ai-assistant.tool_calling.max_rounds');
        $executor = config('ai-assistant.tool_calling.executor');
        $parallel = config('ai-assistant.tool_calling.parallel');

        if (!is_numeric($maxRounds) || $maxRounds < 1) {
            throw new RuntimeException('Invalid tool calling max rounds value');
        }

        if (!in_array($executor, ['sync', 'queue'])) {
            throw new RuntimeException("Invalid tool calling executor: " . (is_string($executor) ? $executor : 'unknown') . ". Must be 'sync' or 'queue'");
        }

        if (!is_bool($parallel)) {
            $this->addValidationResult(
                'Tool Calling Configuration',
                'warning',
                'Tool calling parallel setting should be boolean'
            );
            $this->hasWarnings = true;
        }

        $this->addValidationResult(
            'Tool Calling Configuration',
            'success',
            'Tool calling configuration valid'
        );
    }

    /**
     * Validate queue configuration
     */
    private function validateQueueConfiguration(): void
    {
        $enabled = config('ai-assistant.background_jobs.enabled');

        if ($enabled) {
            $connection = config('ai-assistant.queue.connection');
            $queueName = config('ai-assistant.queue.name');

            if (!$connection) {
                throw new RuntimeException('Queue connection not configured');
            }

            if (is_string($connection) && !config("queue.connections." . $connection)) {
                throw new RuntimeException("Queue connection '" . $connection . "' not found in queue configuration");
            }

            if (!$queueName) {
                $this->addValidationResult(
                    'Queue Configuration',
                    'warning',
                    'Queue name not specified'
                );
                $this->hasWarnings = true;
            }
        }

        $this->addValidationResult(
            'Queue Configuration',
            'success',
            'Queue configuration valid'
        );
    }

    /**
     * Validate metrics configuration
     */
    private function validateMetricsConfiguration(): void
    {
        $driver = config('ai-assistant.metrics.driver');

        if (!in_array($driver, ['log', 'redis', 'database'])) {
            throw new RuntimeException("Invalid metrics driver: " . (is_string($driver) ? $driver : 'unknown'));
        }

        if ($driver === 'redis') {
            $connection = config('ai-assistant.metrics.redis.connection');
            if (is_string($connection) && !config("database.redis." . $connection)) {
                throw new RuntimeException("Redis connection '" . $connection . "' not configured");
            }
        }

        $this->addValidationResult(
            'Metrics Configuration',
            'success',
            'Metrics configuration valid'
        );
    }

    /**
     * Validate webhook configuration
     */
    private function validateWebhookConfiguration(): void
    {
        $enabled = config('ai-assistant.webhooks.enabled');

        if ($enabled) {
            $path = config('ai-assistant.webhooks.path');
            $signingSecret = config('ai-assistant.webhooks.signing_secret');

            if (!$path) {
                throw new RuntimeException('Webhook path not configured');
            }

            if (!$signingSecret) {
                $this->addValidationResult(
                    'Webhook Configuration',
                    'warning',
                    'Webhook signing secret not configured'
                );
                $this->hasWarnings = true;
            }

            // Validate webhooks.responses configuration
            $responsesEnabled = config('ai-assistant.webhooks.responses.enabled');
            if ($responsesEnabled !== null && !is_bool($responsesEnabled)) {
                $this->addValidationResult(
                    'Webhook Configuration',
                    'warning',
                    'Webhook responses.enabled should be boolean'
                );
                $this->hasWarnings = true;
            }

            $responsesEvents = config('ai-assistant.webhooks.responses.events');
            if ($responsesEvents !== null && !is_array($responsesEvents)) {
                $this->addValidationResult(
                    'Webhook Configuration',
                    'warning',
                    'Webhook responses.events should be an array'
                );
                $this->hasWarnings = true;
            }
        }

        $this->addValidationResult(
            'Webhook Configuration',
            'success',
            'Webhook configuration valid'
        );
    }
}
