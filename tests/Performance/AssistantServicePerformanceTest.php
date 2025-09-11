<?php

declare(strict_types=1);

namespace CreativeCrafts\LaravelAiAssistant\Tests\Performance;

use CreativeCrafts\LaravelAiAssistant\Services\AssistantService;
use CreativeCrafts\LaravelAiAssistant\Tests\TestCase;

/**
 * Performance tests for AssistantService critical paths
 */
class AssistantServicePerformanceTest extends TestCase
{
    private AssistantService $assistantService;
    private array $performanceMetrics = [];

    protected function setUp(): void
    {
        parent::setUp();

        $this->assistantService = app(AssistantService::class);

        // Configure for performance testing
        config([
            'ai-assistant.mock_responses' => true,
            'ai-assistant.persistence.driver' => 'memory',
            'ai-assistant.streaming.enabled' => false,
        ]);
    }

    protected function tearDown(): void
    {
        if (!empty($this->performanceMetrics)) {
            $this->outputPerformanceReport();
        }

        parent::tearDown();
    }

    /**
     * Benchmark basic chat completion performance
     */
    public function test_chat_completion_performance(): void
    {
        $iterations = 100;
        $startTime = microtime(true);
        $memoryStart = memory_get_usage(true);

        for ($i = 0; $i < $iterations; $i++) {
            $response = $this->assistantService->chatTextCompletion([
                'model' => 'gpt-3.5-turbo',
                'messages' => [
                    [
                        'role' => 'user',
                        'content' => "Test message {$i}",
                    ],
                ],
            ]);

            $this->assertNotNull($response);
        }

        $endTime = microtime(true);
        $memoryEnd = memory_get_usage(true);

        $this->recordMetric('chat_completion', [
            'iterations' => $iterations,
            'total_time' => $endTime - $startTime,
            'avg_time_per_request' => ($endTime - $startTime) / $iterations,
            'memory_used' => $memoryEnd - $memoryStart,
            'avg_memory_per_request' => ($memoryEnd - $memoryStart) / $iterations,
        ]);

        // Performance assertions
        $avgTime = ($endTime - $startTime) / $iterations;
        $this->assertLessThan(0.1, $avgTime, 'Chat completion should take less than 100ms per request on average');
    }

    /**
     * Benchmark assistant creation performance
     */
    public function test_assistant_creation_performance(): void
    {
        $iterations = 50;
        $startTime = microtime(true);
        $memoryStart = memory_get_usage(true);

        for ($i = 0; $i < $iterations; $i++) {
            $assistant = $this->assistantService->createAssistant([
                'model' => 'gpt-4',
                'name' => "Test Assistant {$i}",
                'instructions' => "You are a test assistant number {$i}",
            ]);

            $this->assertNotNull($assistant);
        }

        $endTime = microtime(true);
        $memoryEnd = memory_get_usage(true);

        $this->recordMetric('assistant_creation', [
            'iterations' => $iterations,
            'total_time' => $endTime - $startTime,
            'avg_time_per_request' => ($endTime - $startTime) / $iterations,
            'memory_used' => $memoryEnd - $memoryStart,
            'avg_memory_per_request' => ($memoryEnd - $memoryStart) / $iterations,
        ]);

        // Performance assertions
        $avgTime = ($endTime - $startTime) / $iterations;
        $this->assertLessThan(0.2, $avgTime, 'Assistant creation should take less than 200ms per request on average');
    }

    /**
     * Benchmark batch operations performance
     */
    public function test_batch_operations_performance(): void
    {
        $batchSizes = [10, 50, 100];

        foreach ($batchSizes as $batchSize) {
            $startTime = microtime(true);
            $memoryStart = memory_get_usage(true);

            $messages = [];
            for ($i = 0; $i < $batchSize; $i++) {
                $messages[] = [
                    'role' => 'user',
                    'content' => "Batch test message {$i}",
                ];
            }

            $response = $this->assistantService->chatTextCompletion([
                'model' => 'gpt-3.5-turbo',
                'messages' => $messages,
            ]);

            $endTime = microtime(true);
            $memoryEnd = memory_get_usage(true);

            $this->recordMetric("batch_operations_size_{$batchSize}", [
                'batch_size' => $batchSize,
                'total_time' => $endTime - $startTime,
                'memory_used' => $memoryEnd - $memoryStart,
                'time_per_message' => ($endTime - $startTime) / $batchSize,
            ]);

            $this->assertNotNull($response);
        }
    }

    /**
     * Benchmark concurrent operations simulation
     */
    public function test_concurrent_operations_simulation(): void
    {
        $concurrentRequests = 20;
        $startTime = microtime(true);
        $memoryStart = memory_get_usage(true);

        // Simulate concurrent requests by rapid sequential execution
        $responses = [];
        for ($i = 0; $i < $concurrentRequests; $i++) {
            $responses[] = $this->assistantService->chatTextCompletion([
                'model' => 'gpt-3.5-turbo',
                'messages' => [
                    [
                        'role' => 'user',
                        'content' => "Concurrent test {$i}",
                    ],
                ],
            ]);
        }

        $endTime = microtime(true);
        $memoryEnd = memory_get_usage(true);

        $this->recordMetric('concurrent_simulation', [
            'concurrent_requests' => $concurrentRequests,
            'total_time' => $endTime - $startTime,
            'avg_time_per_request' => ($endTime - $startTime) / $concurrentRequests,
            'memory_used' => $memoryEnd - $memoryStart,
            'successful_responses' => count(array_filter($responses)),
        ]);

        // Verify all requests succeeded
        $this->assertCount($concurrentRequests, array_filter($responses));
    }

    /**
     * Benchmark memory usage with large payloads
     */
    public function test_large_payload_memory_performance(): void
    {
        $payloadSizes = [1000, 5000, 10000]; // characters

        foreach ($payloadSizes as $size) {
            $largeContent = str_repeat('This is a test message for memory performance. ', $size / 50);

            $memoryStart = memory_get_usage(true);
            $startTime = microtime(true);

            $response = $this->assistantService->chatTextCompletion([
                'model' => 'gpt-3.5-turbo',
                'messages' => [
                    [
                        'role' => 'user',
                        'content' => $largeContent,
                    ],
                ],
            ]);

            $endTime = microtime(true);
            $memoryPeak = memory_get_peak_usage(true);
            $memoryEnd = memory_get_usage(true);

            $this->recordMetric("large_payload_{$size}_chars", [
                'payload_size_chars' => strlen($largeContent),
                'execution_time' => $endTime - $startTime,
                'memory_used' => $memoryEnd - $memoryStart,
                'peak_memory' => $memoryPeak,
            ]);

            $this->assertNotNull($response);

            // Memory usage should be reasonable
            $memoryUsedMB = ($memoryEnd - $memoryStart) / 1024 / 1024;
            $this->assertLessThan(50, $memoryUsedMB, "Memory usage should be less than 50MB for payload size {$size}");
        }
    }

    /**
     * Record performance metric
     */
    private function recordMetric(string $testName, array $metrics): void
    {
        $this->performanceMetrics[$testName] = array_merge($metrics, [
            'timestamp' => now()->toISOString(),
            'php_version' => PHP_VERSION,
            'memory_limit' => ini_get('memory_limit'),
        ]);
    }

    /**
     * Output performance report
     */
    private function outputPerformanceReport(): void
    {
        // Only output performance report if explicitly requested via environment variable
        if (!env('OUTPUT_PERFORMANCE_REPORTS', false)) {
            return;
        }

        echo "\n" . str_repeat('=', 80) . "\n";
        echo "ASSISTANT SERVICE PERFORMANCE REPORT\n";
        echo str_repeat('=', 80) . "\n";

        foreach ($this->performanceMetrics as $testName => $metrics) {
            echo "\n{$testName}:\n";
            echo str_repeat('-', 40) . "\n";

            foreach ($metrics as $key => $value) {
                if (is_numeric($value)) {
                    if (strpos($key, 'time') !== false) {
                        echo sprintf("  %-25s: %.4f seconds\n", $key, $value);
                    } elseif (strpos($key, 'memory') !== false) {
                        echo sprintf("  %-25s: %.2f MB\n", $key, $value / 1024 / 1024);
                    } else {
                        echo sprintf("  %-25s: %s\n", $key, $value);
                    }
                } else {
                    echo sprintf("  %-25s: %s\n", $key, $value);
                }
            }
        }

        echo "\n" . str_repeat('=', 80) . "\n";
    }
}
