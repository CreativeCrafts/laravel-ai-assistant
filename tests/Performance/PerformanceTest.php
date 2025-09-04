<?php

declare(strict_types=1);

use CreativeCrafts\LaravelAiAssistant\Compat\OpenAI\Responses\Assistants\AssistantResponse;
use CreativeCrafts\LaravelAiAssistant\Compat\OpenAI\Responses\Audio\TranscriptionResponse;
use CreativeCrafts\LaravelAiAssistant\Compat\OpenAI\Responses\Chat\CreateResponse as ChatResponse;
use CreativeCrafts\LaravelAiAssistant\Compat\OpenAI\Responses\Completions\CreateResponse as CompletionResponse;
use CreativeCrafts\LaravelAiAssistant\Compat\OpenAI\Responses\Threads\Messages\ThreadMessageResponse;
use CreativeCrafts\LaravelAiAssistant\Compat\OpenAI\Responses\Threads\ThreadResponse;
use CreativeCrafts\LaravelAiAssistant\Contracts\OpenAiRepositoryContract;
use CreativeCrafts\LaravelAiAssistant\Services\AssistantService;
use CreativeCrafts\LaravelAiAssistant\Services\CacheService;
use CreativeCrafts\LaravelAiAssistant\Tests\DataFactories\AssistantFactory;

/**
 * Performance tests for measuring API call latencies and response times.
 *
 * These tests help identify bottlenecks and ensure the package performs
 * well under various conditions and load scenarios.
 */

// Global variable to store performance metrics
$performanceMetrics = [];

beforeEach(function () use (&$performanceMetrics) {
    $this->repositoryMock = Mockery::mock(OpenAiRepositoryContract::class);
    $this->cacheServiceMock = Mockery::mock(CacheService::class);

    // Set up default cache behaviors
    $this->cacheServiceMock->shouldReceive('getCompletion')->andReturn(null)->byDefault();
    $this->cacheServiceMock->shouldReceive('cacheCompletion')->andReturn(true)->byDefault();
    $this->cacheServiceMock->shouldReceive('getResponse')->andReturn(null)->byDefault();
    $this->cacheServiceMock->shouldReceive('cacheResponse')->andReturn(true)->byDefault();

    $this->assistantService = new AssistantService($this->repositoryMock, $this->cacheServiceMock);
    $performanceMetrics = [];
});

afterEach(function () use (&$performanceMetrics) {
    logPerformanceResults($performanceMetrics);
    Mockery::close();
});

/**
 * Measure performance of a callable function over multiple iterations.
 */
function measurePerformance(string $operationName, int $iterations, callable $operation, array &$performanceMetrics): array
{
    $times = [];

    // Warm up
    for ($i = 0; $i < min(5, $iterations); $i++) {
        $operation();
    }

    // Measure performance
    for ($i = 0; $i < $iterations; $i++) {
        $startTime = microtime(true);
        $operation();
        $endTime = microtime(true);

        $times[] = ($endTime - $startTime) * 1000; // Convert to milliseconds
    }

    $metrics = [
        'min' => min($times),
        'max' => max($times),
        'average' => array_sum($times) / count($times),
        'median' => calculateMedian($times),
        'p95' => calculatePercentile($times, 95),
        'p99' => calculatePercentile($times, 99),
        'iterations' => $iterations,
        'total_time' => array_sum($times)
    ];

    $performanceMetrics[$operationName] = $metrics;

    return $metrics;
}

/**
 * Calculate median of an array of numbers.
 */
function calculateMedian(array $numbers): float
{
    sort($numbers);
    $count = count($numbers);
    $middle = floor($count / 2);

    if ($count % 2 === 0) {
        return ($numbers[$middle - 1] + $numbers[$middle]) / 2;
    }

    return $numbers[$middle];
}

/**
 * Calculate percentile of an array of numbers.
 */
function calculatePercentile(array $numbers, float $percentile): float
{
    sort($numbers);
    $index = ($percentile / 100) * (count($numbers) - 1);

    if (floor($index) === $index) {
        return $numbers[$index];
    }

    $lower = $numbers[floor($index)];
    $upper = $numbers[ceil($index)];
    $fraction = $index - floor($index);

    return $lower + ($fraction * ($upper - $lower));
}

/**
 * Generate a large message history for testing.
 */
function generateLargeMessageHistory(int $messageCount): array
{
    $messages = [
        [
            'role' => 'system',
            'content' => 'You are a helpful assistant for performance testing.',
        ]
    ];

    for ($i = 0; $i < $messageCount; $i++) {
        $role = $i % 2 === 0 ? 'user' : 'assistant';
        $content = "This is test message number {$i} with some additional content to make it more realistic for performance testing purposes. " .
                  str_repeat("Additional text to increase payload size. ", 10);

        $messages[] = [
            'role' => $role,
            'content' => $content,
        ];
    }

    return $messages;
}

/**
 * Log performance results for analysis.
 */
function logPerformanceResults(array $performanceMetrics): void
{
    if (empty($performanceMetrics)) {
        return;
    }

    // Only output performance results if explicitly requested via environment variable
    // This prevents "risky test" warnings in PHPUnit while still allowing performance analysis
    if (!getenv('SHOW_PERFORMANCE_RESULTS')) {
        return;
    }

    echo "\n=== Performance Test Results ===\n";

    foreach ($performanceMetrics as $operation => $metrics) {
        echo "\n{$operation}:\n";

        if (isset($metrics['iterations'])) {
            echo "  Iterations: {$metrics['iterations']}\n";
            echo "  Min: " . number_format($metrics['min'], 2) . "ms\n";
            echo "  Max: " . number_format($metrics['max'], 2) . "ms\n";
            echo "  Average: " . number_format($metrics['average'], 2) . "ms\n";
            echo "  Median: " . number_format($metrics['median'], 2) . "ms\n";
            echo "  95th percentile: " . number_format($metrics['p95'], 2) . "ms\n";
            echo "  99th percentile: " . number_format($metrics['p99'], 2) . "ms\n";
            echo "  Total time: " . number_format($metrics['total_time'], 2) . "ms\n";
        } elseif (isset($metrics['memory_increase'])) {
            echo "  Memory increase: " . number_format($metrics['memory_increase'] / 1024, 2) . "KB\n";
            echo "  Peak memory increase: " . number_format($metrics['peak_memory_increase'] / 1024, 2) . "KB\n";
            echo "  Per operation: " . number_format($metrics['per_operation'], 2) . " bytes\n";
        } else {
            echo "  Total time: " . number_format($metrics['total_time'], 2) . "ms\n";
            echo "  Average time: " . number_format($metrics['average_time'], 2) . "ms\n";
            echo "  Operations: {$metrics['operations']}\n";
        }
    }

    echo "\n=== End Performance Results ===\n\n";
}

test('assistant creation performance', function () use (&$performanceMetrics) {
    $iterations = 100;
    $warmupIterations = min(5, $iterations);
    $expectedCalls = $iterations + $warmupIterations;

    $config = AssistantFactory::assistantConfig();
    $response = Mockery::mock(AssistantResponse::class);

    $this->repositoryMock
        ->shouldReceive('createAssistant')
        ->times($expectedCalls)
        ->andReturn($response);

    $times = measurePerformance('Assistant Creation', $iterations, function () use ($config) {
        return $this->assistantService->createAssistant($config);
    }, $performanceMetrics);

    // Use environment-based thresholds - more lenient in testing environments
    $averageThreshold = getenv('CI') ? 500 : 100; // More lenient in CI
    $maxThreshold = getenv('CI') ? 2000 : 500;   // More lenient in CI

    expect($times['average'])->toBeLessThan($averageThreshold, "Assistant creation should take less than {$averageThreshold}ms on average");
    expect($times['max'])->toBeLessThan($maxThreshold, "Assistant creation should never take more than {$maxThreshold}ms");
});

test('text completion performance', function () use (&$performanceMetrics) {
    $iterations = 50;
    $warmupIterations = min(5, $iterations);
    $expectedCalls = $iterations + $warmupIterations;

    $payload = AssistantFactory::completionPayload();
    $mockResponse = Mockery::mock(CompletionResponse::class);

    // Set up the choices property
    $reflection = new ReflectionClass(CompletionResponse::class);
    $property = $reflection->getProperty('choices');
    $property->setAccessible(true);
    $property->setValue($mockResponse, [
        (object) ['text' => 'Performance test response']
    ]);

    $this->repositoryMock
        ->shouldReceive('createCompletion')
        ->times($expectedCalls)
        ->andReturn($mockResponse);

    $times = measurePerformance('Text Completion', $iterations, function () use ($payload) {
        return $this->assistantService->textCompletion($payload);
    }, $performanceMetrics);

    // Use environment-based thresholds
    $averageThreshold = getenv('CI') ? 500 : 150;
    $maxThreshold = getenv('CI') ? 2000 : 800;

    expect($times['average'])->toBeLessThan($averageThreshold, "Text completion should take less than {$averageThreshold}ms on average");
    expect($times['max'])->toBeLessThan($maxThreshold, "Text completion should never take more than {$maxThreshold}ms");
});

test('chat completion performance', function () use (&$performanceMetrics) {
    $iterations = 50;
    $warmupIterations = min(5, $iterations);
    $expectedCalls = $iterations + $warmupIterations;

    $payload = AssistantFactory::chatCompletionPayload();
    $mockResponse = Mockery::mock(ChatResponse::class);
    $mockMessage = Mockery::mock();
    $mockMessage->shouldReceive('toArray')->andReturn(['content' => 'Performance test chat response']);

    // Set up the choices property
    $reflection = new ReflectionClass(ChatResponse::class);
    $property = $reflection->getProperty('choices');
    $property->setAccessible(true);
    $property->setValue($mockResponse, [
        (object) ['message' => $mockMessage]
    ]);

    $this->repositoryMock
        ->shouldReceive('createChatCompletion')
        ->times($expectedCalls)
        ->andReturn($mockResponse);

    $times = measurePerformance('Chat Completion', $iterations, function () use ($payload) {
        return $this->assistantService->chatTextCompletion($payload);
    }, $performanceMetrics);

    // Use environment-based thresholds
    $averageThreshold = getenv('CI') ? 400 : 120;
    $maxThreshold = getenv('CI') ? 1500 : 600;

    expect($times['average'])->toBeLessThan($averageThreshold, "Chat completion should take less than {$averageThreshold}ms on average");
    expect($times['max'])->toBeLessThan($maxThreshold, "Chat completion should never take more than {$maxThreshold}ms");
});

test('thread operations performance', function () use (&$performanceMetrics) {
    $iterations = 30;
    $warmupIterations = min(5, $iterations);

    $threadParams = AssistantFactory::threadParams();
    $threadId = AssistantFactory::threadId();
    $messageData = AssistantFactory::messageData();

    $threadResponse = Mockery::mock(ThreadResponse::class);
    $messageResponse = Mockery::mock(ThreadMessageResponse::class);

    // Each operation has its own warmup + iterations
    $threadExpectedCalls = $iterations + $warmupIterations;
    $messageExpectedCalls = $iterations + $warmupIterations;

    $this->repositoryMock
        ->shouldReceive('createThread')
        ->times($threadExpectedCalls)
        ->andReturn($threadResponse);

    $this->repositoryMock
        ->shouldReceive('createThreadMessage')
        ->times($messageExpectedCalls)
        ->andReturn($messageResponse);

    // Test thread creation performance
    $threadTimes = measurePerformance('Thread Creation', $iterations, function () use ($threadParams) {
        return $this->assistantService->createThread($threadParams);
    }, $performanceMetrics);

    // Test message writing performance
    $messageTimes = measurePerformance('Message Writing', $iterations, function () use ($threadId, $messageData) {
        return $this->assistantService->writeMessage($threadId, $messageData);
    }, $performanceMetrics);

    // Use environment-based thresholds
    $threadAverageThreshold = getenv('CI') ? 300 : 100;
    $messageAverageThreshold = getenv('CI') ? 250 : 80;

    expect($threadTimes['average'])->toBeLessThan($threadAverageThreshold, "Thread creation should take less than {$threadAverageThreshold}ms on average");
    expect($messageTimes['average'])->toBeLessThan($messageAverageThreshold, "Message writing should take less than {$messageAverageThreshold}ms on average");
});

test('audio processing performance', function () use (&$performanceMetrics) {
    $iterations = 20;
    $warmupIterations = min(5, $iterations);
    $expectedCalls = $iterations + $warmupIterations;

    $file = AssistantFactory::createTestAudioFile();
    $payload = AssistantFactory::audioPayload($file);
    $mockResponse = Mockery::mock(TranscriptionResponse::class);

    // Set up the text property
    $reflection = new ReflectionClass(TranscriptionResponse::class);
    $property = $reflection->getProperty('text');
    $property->setAccessible(true);
    $property->setValue($mockResponse, 'Performance test transcription');

    $this->repositoryMock
        ->shouldReceive('transcribeAudio')
        ->times($expectedCalls)
        ->andReturn($mockResponse);

    $times = measurePerformance('Audio Transcription', $iterations, function () use ($payload) {
        return $this->assistantService->transcribeTo($payload);
    }, $performanceMetrics);

    // Use environment-based thresholds
    $averageThreshold = getenv('CI') ? 600 : 200;
    $maxThreshold = getenv('CI') ? 3000 : 1000;

    expect($times['average'])->toBeLessThan($averageThreshold, "Audio transcription should take less than {$averageThreshold}ms on average");
    expect($times['max'])->toBeLessThan($maxThreshold, "Audio transcription should never take more than {$maxThreshold}ms");

    fclose($file);
});

test('caching performance', function () use (&$performanceMetrics) {
    $iterations = 100;
    $warmupIterations = min(5, $iterations);
    $expectedCalls = $iterations + $warmupIterations;

    $payload = AssistantFactory::completionPayload(['temperature' => 0.0]); // Deterministic for caching
    $cachedResult = 'Cached performance test result';

    // First call should miss cache, subsequent should hit
    $this->cacheServiceMock
        ->shouldReceive('getCompletion')
        ->times($expectedCalls)
        ->andReturn($cachedResult);

    $times = measurePerformance('Cached Completion', $iterations, function () use ($payload) {
        return $this->assistantService->textCompletion($payload);
    }, $performanceMetrics);

    // Use environment-based thresholds - cached operations should be fast
    $averageThreshold = getenv('CI') ? 50 : 10;
    $maxThreshold = getenv('CI') ? 200 : 50;

    expect($times['average'])->toBeLessThan($averageThreshold, "Cached operations should take less than {$averageThreshold}ms on average");
    expect($times['max'])->toBeLessThan($maxThreshold, "Cached operations should never take more than {$maxThreshold}ms");
});

test('validation performance', function () use (&$performanceMetrics) {
    $iterations = 200;
    $validConfig = AssistantFactory::assistantConfig();

    $times = measurePerformance('Input Validation', $iterations, function () use ($validConfig) {
        try {
            // This will run validation but not create the assistant due to no mock expectation
            $this->assistantService->createAssistant($validConfig);
        } catch (Exception $e) {
            // Expected since we're not setting up repository expectations
        }
    }, $performanceMetrics);

    // Use environment-based thresholds - validation should be fast but allow for CI overhead
    $averageThreshold = getenv('CI') ? 20 : 5;
    $maxThreshold = getenv('CI') ? 100 : 25;

    expect($times['average'])->toBeLessThan($averageThreshold, "Input validation should take less than {$averageThreshold}ms on average");
    expect($times['max'])->toBeLessThan($maxThreshold, "Input validation should never take more than {$maxThreshold}ms");
});

test('concurrent operations performance', function () use (&$performanceMetrics) {
    $concurrentOps = 10;
    $config = AssistantFactory::assistantConfig();
    $response = Mockery::mock(AssistantResponse::class);

    $this->repositoryMock
        ->shouldReceive('createAssistant')
        ->times($concurrentOps)
        ->andReturn($response);

    $startTime = microtime(true);

    // Simulate concurrent operations
    $operations = [];
    for ($i = 0; $i < $concurrentOps; $i++) {
        $operations[] = function () use ($config) {
            return $this->assistantService->createAssistant($config);
        };
    }

    // Execute operations
    foreach ($operations as $operation) {
        $operation();
    }

    $endTime = microtime(true);
    $totalTime = ($endTime - $startTime) * 1000; // Convert to milliseconds
    $averageTime = $totalTime / $concurrentOps;

    $performanceMetrics['Concurrent Operations'] = [
        'total_time' => $totalTime,
        'average_time' => $averageTime,
        'operations' => $concurrentOps
    ];

    // Use environment-based threshold
    $averageThreshold = getenv('CI') ? 500 : 150;

    expect($averageTime)->toBeLessThan($averageThreshold, "Concurrent operations should maintain good performance with average time less than {$averageThreshold}ms");
});

test('memory usage during operations', function () use (&$performanceMetrics) {
    $iterations = 50;
    $config = AssistantFactory::assistantConfig();
    $response = Mockery::mock(AssistantResponse::class);

    $this->repositoryMock
        ->shouldReceive('createAssistant')
        ->times($iterations)
        ->andReturn($response);

    // Clear any existing memory and get baseline
    gc_collect_cycles();
    $memoryBefore = memory_get_usage(true); // Use real memory usage
    $peakMemoryBefore = memory_get_peak_usage(true);

    // Track memory at different points to detect leaks
    $memorySnapshots = [];

    // Perform operations without storing unnecessary data
    for ($i = 0; $i < $iterations; $i++) {
        $result = $this->assistantService->createAssistant($config);

        // Take memory snapshots every 10 operations to detect trends
        if ($i % 10 === 0) {
            $memorySnapshots[] = memory_get_usage(true);
        }

        // Force garbage collection periodically to ensure proper cleanup
        if ($i % 25 === 0) {
            gc_collect_cycles();
        }
    }

    // Final cleanup and measurement
    gc_collect_cycles();
    $memoryAfter = memory_get_usage(true);
    $peakMemoryAfter = memory_get_peak_usage(true);

    $memoryIncrease = $memoryAfter - $memoryBefore;
    $peakMemoryIncrease = $peakMemoryAfter - $peakMemoryBefore;

    $performanceMetrics['Memory Usage'] = [
        'memory_before' => $memoryBefore,
        'memory_after' => $memoryAfter,
        'memory_increase' => $memoryIncrease,
        'peak_memory_increase' => $peakMemoryIncrease,
        'per_operation' => $memoryIncrease / $iterations,
        'snapshots' => $memorySnapshots,
        'operations' => $iterations
    ];

    // Use more realistic memory thresholds
    $maxMemoryIncrease = getenv('CI') ? 5 * 1024 * 1024 : 2 * 1024 * 1024; // 5MB CI, 2MB local
    $maxPerOperation = getenv('CI') ? 100 * 1024 : 50 * 1024; // 100KB CI, 50KB local

    // Assert reasonable memory usage without memory leaks
    expect($memoryIncrease)->toBeLessThan($maxMemoryIncrease, "Memory usage should not increase by more than {$maxMemoryIncrease} bytes");
    expect($memoryIncrease / $iterations)->toBeLessThan($maxPerOperation, "Memory per operation should be less than {$maxPerOperation} bytes");

    // Check for memory leak pattern in snapshots
    if (count($memorySnapshots) >= 2) {
        $firstSnapshot = $memorySnapshots[0];
        $lastSnapshot = end($memorySnapshots);
        $snapshotIncrease = $lastSnapshot - $firstSnapshot;
        $maxSnapshotIncrease = 1 * 1024 * 1024; // 1MB max increase across snapshots

        expect($snapshotIncrease)->toBeLessThan($maxSnapshotIncrease, 'Memory should not continuously increase indicating a memory leak');
    }
});

test('large payload performance', function () use (&$performanceMetrics) {
    $iterations = 1;
    $warmupIterations = min(1, $iterations); // Only 1 warmup for single iteration test
    $expectedCalls = $iterations + $warmupIterations;

    $payload = AssistantFactory::chatCompletionPayload([
        'messages' => generateLargeMessageHistory(50) // 50 messages
    ]);

    $mockResponse = Mockery::mock(ChatResponse::class);
    $mockMessage = Mockery::mock();
    $mockMessage->shouldReceive('toArray')->andReturn(['content' => 'Large payload response']);

    $reflection = new ReflectionClass(ChatResponse::class);
    $property = $reflection->getProperty('choices');
    $property->setAccessible(true);
    $property->setValue($mockResponse, [
        (object) ['message' => $mockMessage]
    ]);

    $this->repositoryMock
        ->shouldReceive('createChatCompletion')
        ->times($expectedCalls)
        ->andReturn($mockResponse);

    $times = measurePerformance('Large Payload Processing', $iterations, function () use ($payload) {
        return $this->assistantService->chatTextCompletion($payload);
    }, $performanceMetrics);

    // Use environment-based threshold for large payloads
    $averageThreshold = getenv('CI') ? 1000 : 300;

    expect($times['average'])->toBeLessThan($averageThreshold, "Large payload processing should take less than {$averageThreshold}ms");
});
