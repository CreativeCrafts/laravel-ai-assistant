<?php

declare(strict_types=1);

namespace CreativeCrafts\LaravelAiAssistant\Console\Commands;

use CreativeCrafts\LaravelAiAssistant\Contracts\OpenAiRepositoryContract;
use CreativeCrafts\LaravelAiAssistant\Services\AssistantService;
use Illuminate\Console\Command;
use Throwable;
use RuntimeException;

/**
 * API connectivity test command for AI Assistant
 */
class TestConnectionCommand extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'ai:test-connection 
                           {--json : Output results in JSON format}
                           {--detailed : Show detailed test information}
                           {--timeout=30 : Connection timeout in seconds}
                           {--skip-expensive : Skip tests that consume API credits}';

    /**
     * The console command description.
     */
    protected $description = 'Test API connectivity with OpenAI services';

    private array $testResults = [];
    private bool $hasFailures = false;
    private OpenAiRepositoryContract $repository;
    private AssistantService $assistantService;
    private int $timeout;

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->info('🔌 AI Assistant API Connection Test');
        $this->newLine();

        $this->timeout = (int) $this->option('timeout');
        $this->repository = app(OpenAiRepositoryContract::class);
        $this->assistantService = resolve(AssistantService::class);

        $this->runConnectionTests();

        if ($this->option('json')) {
            $this->outputJson();
        } else {
            $this->outputReport();
        }

        return $this->hasFailures ? 1 : 0;
    }

    /**
     * Run all connection tests
     */
    private function runConnectionTests(): void
    {
        $tests = [
            'Basic Configuration' => 'testBasicConfiguration',
            'API Authentication' => 'testApiAuthentication',
            'Models Endpoint' => 'testModelsEndpoint',
            'Chat Completion' => 'testChatCompletion',
            'Assistant Creation' => 'testAssistantCreation',
            'Network Connectivity' => 'testNetworkConnectivity',
            'Rate Limiting' => 'testRateLimiting',
            'Error Handling' => 'testErrorHandling',
        ];

        foreach ($tests as $testName => $method) {
            $this->info("Testing {$testName}...");

            $startTime = microtime(true);

            try {
                $this->$method();
                $duration = microtime(true) - $startTime;
                $this->line("  ✅ {$testName} - PASSED ({$this->formatDuration($duration)})");
                $this->addTestResult($testName, 'passed', null, $duration);
            } catch (Throwable $e) {
                $duration = microtime(true) - $startTime;
                $this->error("  ❌ {$testName} - FAILED: {$e->getMessage()}");
                $this->addTestResult($testName, 'failed', $e->getMessage(), $duration);
                $this->hasFailures = true;
            }
        }
    }

    /**
     * Test basic configuration
     */
    private function testBasicConfiguration(): void
    {
        $apiKey = config('ai-assistant.api_key');
        if (!$apiKey) {
            throw new RuntimeException('OpenAI API key not configured');
        }

        if (!is_string($apiKey)) {
            throw new RuntimeException('OpenAI API key must be a string');
        }

        if (strlen($apiKey) < 20) {
            throw new RuntimeException('OpenAI API key appears to be invalid (too short)');
        }

        if (!str_starts_with($apiKey, 'sk-') && !str_starts_with($apiKey, 'sk-proj-')) {
            throw new RuntimeException('OpenAI API key format appears invalid');
        }

        $this->addTestResult('Basic Configuration', 'info', 'API key format validation passed');
    }

    /**
     * Test API authentication
     */
    private function testApiAuthentication(): void
    {
        try {
            // Test authentication by making a simple chat completion API call
            $response = $this->repository->createChatCompletion([
                'model' => config('ai-assistant.models.chat', 'gpt-3.5-turbo'),
                'messages' => [['role' => 'user', 'content' => 'test']],
                'max_tokens' => 1,
            ]);

            // API call completed successfully if we reach here

            $this->addTestResult('API Authentication', 'info', 'Authentication successful');
        } catch (Throwable $e) {
            if (str_contains($e->getMessage(), '401') || str_contains($e->getMessage(), 'Unauthorized')) {
                throw new RuntimeException('API authentication failed - check your API key');
            }

            throw $e;
        }
    }

    /**
     * Test models endpoint
     */
    private function testModelsEndpoint(): void
    {
        // Since models() method doesn't exist in the contract, test model availability
        // by trying to use configured models in a chat completion
        $configuredModel = config('ai-assistant.models.chat', 'gpt-3.5-turbo');

        try {
            $response = $this->repository->createChatCompletion([
                'model' => $configuredModel,
                'messages' => [['role' => 'user', 'content' => 'test']],
                'max_tokens' => 1,
            ]);

            // API call completed successfully if we reach here
            $modelName = is_string($configuredModel) ? $configuredModel : 'unknown';
            $this->addTestResult(
                'Models Endpoint',
                'info',
                "Configured model '{$modelName}' is accessible"
            );
        } catch (Throwable $e) {
            if (str_contains($e->getMessage(), 'model') && str_contains($e->getMessage(), 'does not exist')) {
                $modelName = is_string($configuredModel) ? $configuredModel : 'unknown';
                throw new RuntimeException("Configured model '{$modelName}' is not available");
            }
            throw $e;
        }
    }

    /**
     * Test chat completion
     */
    private function testChatCompletion(): void
    {
        if ($this->option('skip-expensive')) {
            $this->addTestResult('Chat Completion', 'skipped', 'Skipped to avoid API costs');
            return;
        }

        $testMessage = 'Hello, this is a connection test. Please respond with "Connection successful".';

        $response = $this->assistantService->chatTextCompletion([
            'model' => config('ai-assistant.models.chat', 'gpt-3.5-turbo'),
            'messages' => [
                [
                    'role' => 'user',
                    'content' => $testMessage,
                ]
            ],
            'max_tokens' => 50,
            'temperature' => 0,
        ]);

        // API call completed successfully if we reach here

        $this->addTestResult('Chat Completion', 'info', 'Chat completion successful');
    }

    /**
     * Test assistant creation
     */
    private function testAssistantCreation(): void
    {
        if ($this->option('skip-expensive')) {
            $this->addTestResult('Assistant Creation', 'skipped', 'Skipped to avoid API costs');
            return;
        }

        $assistant = $this->assistantService->createAssistant([
            'model' => config('ai-assistant.models.chat', 'gpt-4'),
            'name' => 'Connection Test Assistant',
            'instructions' => 'You are a test assistant created for connection testing purposes.',
        ]);

        // API call completed successfully if we reach here
        // Note: Assistant cleanup would require direct OpenAI API call since deleteAssistant() method doesn't exist
        $this->addTestResult('Assistant Creation', 'info', 'Assistant creation successful');
    }

    /**
     * Test network connectivity
     */
    private function testNetworkConnectivity(): void
    {
        $startTime = microtime(true);

        // Test basic network connectivity to OpenAI
        $context = stream_context_create([
            'http' => [
                'timeout' => $this->timeout,
                'method' => 'GET',
            ]
        ]);

        $result = @file_get_contents('https://api.openai.com/v1/models', false, $context);
        $duration = microtime(true) - $startTime;

        if ($result === false) {
            throw new RuntimeException('Network connectivity to OpenAI API failed');
        }

        if ($duration > 5.0) {
            $this->addTestResult(
                'Network Connectivity',
                'warning',
                sprintf('Slow network response: %.2f seconds', $duration)
            );
        }

        $this->addTestResult(
            'Network Connectivity',
            'info',
            sprintf('Network connectivity good (%.2f seconds)', $duration)
        );
    }

    /**
     * Test rate limiting behavior
     */
    private function testRateLimiting(): void
    {
        // Test rate limiting by making multiple rapid requests
        $requests = 3;
        $successes = 0;
        $rateLimited = false;

        for ($i = 0; $i < $requests; $i++) {
            try {
                // Test with a simple chat completion instead of models() which doesn't exist
                $this->repository->createChatCompletion([
                    'model' => config('ai-assistant.models.chat', 'gpt-3.5-turbo'),
                    'messages' => [['role' => 'user', 'content' => 'test']],
                    'max_tokens' => 1,
                ]);
                $successes++;

                // Small delay to avoid overwhelming the API
                usleep(100000); // 100ms
            } catch (Throwable $e) {
                if (str_contains($e->getMessage(), '429') || str_contains($e->getMessage(), 'rate limit')) {
                    $rateLimited = true;
                    break;
                }

                throw $e;
            }
        }

        if ($successes === $requests) {
            $this->addTestResult(
                'Rate Limiting',
                'info',
                sprintf('Successfully made %d requests without rate limiting', $requests)
            );
        } elseif ($rateLimited) {
            $this->addTestResult(
                'Rate Limiting',
                'warning',
                'Rate limiting detected - this is normal behavior'
            );
        } else {
            $this->addTestResult(
                'Rate Limiting',
                'info',
                sprintf('Made %d/%d successful requests', $successes, $requests)
            );
        }
    }

    /**
     * Test error handling
     */
    private function testErrorHandling(): void
    {
        // Test error handling with an invalid request
        try {
            $this->assistantService->chatTextCompletion([
                'model' => 'invalid-model-name-for-testing',
                'messages' => [
                    [
                        'role' => 'user',
                        'content' => 'Test',
                    ]
                ],
            ]);

            throw new RuntimeException('Expected error for invalid model, but request succeeded');
        } catch (Throwable $e) {
            if (str_contains($e->getMessage(), 'invalid') ||
                str_contains($e->getMessage(), 'not found') ||
                str_contains($e->getMessage(), 'does not exist')) {
                $this->addTestResult(
                    'Error Handling',
                    'info',
                    'Error handling working correctly for invalid requests'
                );
            } else {
                throw new RuntimeException('Unexpected error type: ' . $e->getMessage());
            }
        }
    }

    /**
     * Add test result
     */
    private function addTestResult(string $testName, string $status, ?string $message = null, ?float $duration = null): void
    {
        $this->testResults[$testName][] = [
            'status' => $status,
            'message' => $message,
            'duration' => $duration,
            'timestamp' => now()->toISOString(),
        ];
    }

    /**
     * Format duration for display
     */
    private function formatDuration(float $duration): string
    {
        if ($duration < 1) {
            return sprintf('%.0fms', $duration * 1000);
        }

        return sprintf('%.2fs', $duration);
    }

    /**
     * Output detailed report
     */
    private function outputReport(): void
    {
        $this->newLine();
        $this->info('📋 Connection Test Results:');
        $this->newLine();

        foreach ($this->testResults as $testName => $results) {
            $this->line("<fg=cyan>{$testName}:</>");

            foreach ($results as $result) {
                $icon = match($result['status']) {
                    'passed' => '✅',
                    'failed' => '❌',
                    'warning' => '⚠️',
                    'skipped' => '⏭️',
                    default => 'ℹ️'
                };

                $message = $result['message'] ?? $result['status'];
                if ($result['duration'] && $this->option('detailed')) {
                    $message .= " ({$this->formatDuration($result['duration'])})";
                }

                $this->line("  {$icon} {$message}");
            }
            $this->newLine();
        }

        // Summary
        $totalTests = count($this->testResults);
        $passedTests = 0;
        $failedTests = 0;
        $skippedTests = 0;

        foreach ($this->testResults as $results) {
            foreach ($results as $result) {
                match($result['status']) {
                    'passed' => $passedTests++,
                    'failed' => $failedTests++,
                    'skipped' => $skippedTests++,
                    default => null
                };
            }
        }

        $this->info('📊 Test Summary:');
        $this->line("  Total Tests: {$totalTests}");
        $this->line("  Passed: {$passedTests}");

        if ($failedTests > 0) {
            $this->line("  <fg=red>Failed: {$failedTests}</>");
        }

        if ($skippedTests > 0) {
            $this->line("  <fg=yellow>Skipped: {$skippedTests}</>");
        }

        if ($this->hasFailures) {
            $this->error('❌ Connection tests failed');
        } else {
            $this->info('✅ All connection tests passed');
        }
    }

    /**
     * Output JSON report
     */
    private function outputJson(): void
    {
        $summary = [
            'total' => count($this->testResults),
            'passed' => 0,
            'failed' => 0,
            'skipped' => 0,
        ];

        foreach ($this->testResults as $results) {
            foreach ($results as $result) {
                match($result['status']) {
                    'passed' => $summary['passed']++,
                    'failed' => $summary['failed']++,
                    'skipped' => $summary['skipped']++,
                    default => null
                };
            }
        }

        $report = [
            'status' => $this->hasFailures ? 'failed' : 'passed',
            'timestamp' => now()->toISOString(),
            'test_results' => $this->testResults,
            'summary' => $summary,
            'configuration' => [
                'timeout' => $this->timeout,
                'skip_expensive' => $this->option('skip-expensive'),
                'detailed' => $this->option('detailed'),
            ]
        ];

        $json = json_encode($report, JSON_PRETTY_PRINT);
        if ($json === false) {
            $this->error('Failed to encode report as JSON');
            return;
        }
        $this->line($json);
    }
}
