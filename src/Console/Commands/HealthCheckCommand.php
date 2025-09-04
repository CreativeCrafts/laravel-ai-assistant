<?php

declare(strict_types=1);

namespace CreativeCrafts\LaravelAiAssistant\Console\Commands;

use CreativeCrafts\LaravelAiAssistant\Contracts\AppConfigContract;
use CreativeCrafts\LaravelAiAssistant\Contracts\OpenAiRepositoryContract;
use CreativeCrafts\LaravelAiAssistant\Services\AssistantService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Throwable;
use Artisan;
use RuntimeException;

/**
 * Health check command for AI Assistant system diagnostics
 */
class HealthCheckCommand extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'ai:health-check 
                           {--detailed : Show detailed diagnostic information}
                           {--json : Output results in JSON format}
                           {--fix : Attempt to fix common issues}';

    /**
     * The console command description.
     */
    protected $description = 'Perform comprehensive system diagnostics for AI Assistant';

    private array $diagnostics = [];
    private bool $hasErrors = false;
    private bool $hasWarnings = false;

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->info('🔍 AI Assistant Health Check');
        $this->newLine();

        $this->runDiagnostics();

        if ($this->option('json')) {
            $this->outputJson();
        } else {
            $this->outputReport();
        }

        if ($this->option('fix')) {
            $this->attemptFixes();
        }

        return $this->hasErrors ? 1 : 0;
    }

    /**
     * Run all diagnostic checks
     */
    private function runDiagnostics(): void
    {
        $checks = [
            'Configuration' => 'checkConfiguration',
            'Dependencies' => 'checkDependencies',
            'Database' => 'checkDatabase',
            'Cache' => 'checkCache',
            'OpenAI Connection' => 'checkOpenAiConnection',
            'Services' => 'checkServices',
            'Permissions' => 'checkPermissions',
            'Performance' => 'checkPerformance',
        ];

        foreach ($checks as $name => $method) {
            $this->info("Checking {$name}...");

            try {
                $this->$method();
                $this->line("  ✅ {$name} - OK");
            } catch (Throwable $e) {
                $this->error("  ❌ {$name} - FAILED: {$e->getMessage()}");
                $this->addDiagnostic($name, 'error', $e->getMessage());
                $this->hasErrors = true;
            }
        }
    }

    /**
     * Check configuration
     */
    private function checkConfiguration(): void
    {
        // Check API key
        $apiKey = config('ai-assistant.api_key');
        if (!$apiKey) {
            throw new RuntimeException('OpenAI API key not configured');
        }

        if (is_string($apiKey) && strlen($apiKey) < 20) {
            $this->addDiagnostic('Configuration', 'warning', 'API key appears to be too short');
            $this->hasWarnings = true;
        }

        // Check required configurations
        $requiredConfigs = [
            'persistence.driver',
            'models.chat',
            'responses.timeout',
        ];

        foreach ($requiredConfigs as $configKey) {
            $value = config("ai-assistant.{$configKey}");
            if ($value === null) {
                $this->addDiagnostic('Configuration', 'warning', "Missing configuration: {$configKey}");
                $this->hasWarnings = true;
            }
        }

        $this->addDiagnostic('Configuration', 'success', 'All required configurations present');
    }

    /**
     * Check PHP dependencies
     */
    private function checkDependencies(): void
    {
        $requirements = [
            'php' => ['version' => '8.2.0', 'current' => PHP_VERSION],
            'curl' => ['extension' => 'curl'],
            'json' => ['extension' => 'json'],
            'mbstring' => ['extension' => 'mbstring'],
            'openssl' => ['extension' => 'openssl'],
        ];

        foreach ($requirements as $name => $requirement) {
            if (isset($requirement['version'])) {
                if (version_compare(PHP_VERSION, $requirement['version'], '<')) {
                    throw new RuntimeException("PHP {$requirement['version']}+ required, {$requirement['current']} found");
                }
            } elseif (isset($requirement['extension'])) {
                if (!extension_loaded($requirement['extension'])) {
                    throw new RuntimeException("PHP extension '{$requirement['extension']}' not loaded");
                }
            }
        }

        $this->addDiagnostic('Dependencies', 'success', 'All PHP dependencies satisfied');
    }

    /**
     * Check database connection and migrations
     */
    private function checkDatabase(): void
    {
        try {
            DB::connection()->getPdo();
            $this->addDiagnostic('Database', 'success', 'Database connection working');
        } catch (Throwable $e) {
            throw new RuntimeException("Database connection failed: {$e->getMessage()}");
        }

        // Check if using eloquent persistence
        if (config('ai-assistant.persistence.driver') === 'eloquent') {
            $tables = ['ai_assistants', 'ai_conversations', 'ai_conversation_items', 'ai_response_records', 'ai_tool_invocations'];

            foreach ($tables as $table) {
                try {
                    DB::table($table)->limit(1)->get();
                } catch (Throwable $e) {
                    $this->addDiagnostic('Database', 'warning', "Table '{$table}' not accessible. Run migrations?");
                    $this->hasWarnings = true;
                }
            }
        }
    }

    /**
     * Check cache system
     */
    private function checkCache(): void
    {
        $testKey = 'ai_assistant_health_check_' . time();
        $testValue = 'test_value';

        try {
            Cache::put($testKey, $testValue, 60);
            $retrieved = Cache::get($testKey);
            Cache::forget($testKey);

            if ($retrieved !== $testValue) {
                throw new RuntimeException('Cache write/read mismatch');
            }

            $this->addDiagnostic('Cache', 'success', 'Cache system working');
        } catch (Throwable $e) {
            throw new RuntimeException("Cache system failed: {$e->getMessage()}");
        }
    }

    /**
     * Check OpenAI connection
     */
    private function checkOpenAiConnection(): void
    {
        try {
            $repository = app(OpenAiRepositoryContract::class);

            // Try a simple API connectivity test via basic completion
            $testParams = [
                'model' => config('ai-assistant.models.chat', 'gpt-3.5-turbo'),
                'messages' => [['role' => 'user', 'content' => 'test']],
                'max_tokens' => 1,
            ];

            $response = $repository->createChatCompletion($testParams);

            // API call completed successfully if we reach here
            $this->addDiagnostic('OpenAI Connection', 'success', 'API connection test successful');
        } catch (Throwable $e) {
            throw new RuntimeException("OpenAI connection failed: {$e->getMessage()}");
        }
    }

    /**
     * Check core services
     */
    private function checkServices(): void
    {
        $services = [
            AssistantService::class,
            AppConfigContract::class,
            OpenAiRepositoryContract::class,
        ];

        foreach ($services as $service) {
            try {
                app($service);
                // If we reach here, the service was resolved successfully
            } catch (Throwable $e) {
                throw new RuntimeException("Service {$service} failed: {$e->getMessage()}");
            }
        }

        $this->addDiagnostic('Services', 'success', 'All core services available');
    }

    /**
     * Check file permissions
     */
    private function checkPermissions(): void
    {
        $paths = [
            storage_path(),
            storage_path('logs'),
            storage_path('framework/cache'),
        ];

        foreach ($paths as $path) {
            if (!is_writable($path)) {
                throw new RuntimeException("Path not writable: {$path}");
            }
        }

        $this->addDiagnostic('Permissions', 'success', 'Storage paths writable');
    }

    /**
     * Check performance indicators
     */
    private function checkPerformance(): void
    {
        $memoryLimit = ini_get('memory_limit');
        $memoryUsage = memory_get_usage(true);
        $memoryUsageMB = $memoryUsage / 1024 / 1024;

        if ($memoryUsageMB > 128) {
            $this->addDiagnostic(
                'Performance',
                'warning',
                sprintf('High memory usage: %.2f MB', $memoryUsageMB)
            );
            $this->hasWarnings = true;
        }

        $maxExecutionTime = ini_get('max_execution_time');
        if ($maxExecutionTime && $maxExecutionTime < 60) {
            $this->addDiagnostic(
                'Performance',
                'warning',
                "Low max execution time: {$maxExecutionTime}s"
            );
            $this->hasWarnings = true;
        }

        $this->addDiagnostic('Performance', 'info', [
            'memory_limit' => $memoryLimit,
            'memory_usage' => sprintf('%.2f MB', $memoryUsageMB),
            'max_execution_time' => $maxExecutionTime,
        ]);
    }

    /**
     * Add diagnostic result
     */
    private function addDiagnostic(string $category, string $level, mixed $message): void
    {
        $this->diagnostics[$category][] = [
            'level' => $level,
            'message' => $message,
            'timestamp' => now()->toISOString(),
        ];
    }

    /**
     * Output detailed report
     */
    private function outputReport(): void
    {
        $this->newLine();
        $this->info('📋 Diagnostic Results:');
        $this->newLine();

        foreach ($this->diagnostics as $category => $results) {
            $this->line("<fg=cyan>{$category}:</>");

            foreach ($results as $result) {
                $icon = match($result['level']) {
                    'error' => '❌',
                    'warning' => '⚠️',
                    'success' => '✅',
                    default => 'ℹ️'
                };

                if (is_array($result['message'])) {
                    $this->line("  {$icon} Details:");
                    foreach ($result['message'] as $key => $value) {
                        $this->line("    - {$key}: {$value}");
                    }
                } else {
                    $this->line("  {$icon} {$result['message']}");
                }
            }
            $this->newLine();
        }

        // Summary
        $this->info('📊 Summary:');
        if ($this->hasErrors) {
            $this->error('❌ Health check failed with errors');
        } elseif ($this->hasWarnings) {
            $this->warn('⚠️  Health check completed with warnings');
        } else {
            $this->info('✅ All systems healthy');
        }
    }

    /**
     * Output JSON report
     */
    private function outputJson(): void
    {
        $report = [
            'status' => $this->hasErrors ? 'error' : ($this->hasWarnings ? 'warning' : 'healthy'),
            'timestamp' => now()->toISOString(),
            'diagnostics' => $this->diagnostics,
            'summary' => [
                'errors' => $this->hasErrors,
                'warnings' => $this->hasWarnings,
                'total_checks' => count($this->diagnostics),
            ]
        ];

        $json = json_encode($report, JSON_PRETTY_PRINT);
        $this->line($json ?: '{}');
    }

    /**
     * Attempt to fix common issues
     */
    private function attemptFixes(): void
    {
        $this->newLine();
        $this->info('🔧 Attempting automatic fixes...');

        // Clear cache if cache issues detected
        if (isset($this->diagnostics['Cache'])) {
            foreach ($this->diagnostics['Cache'] as $diagnostic) {
                if ($diagnostic['level'] === 'error') {
                    $this->line('  - Clearing cache...');
                    Artisan::call('cache:clear');
                    $this->info('    ✅ Cache cleared');
                }
            }
        }

        // Additional fix attempts could be added here
        $this->info('🔧 Automatic fixes completed');
    }
}
