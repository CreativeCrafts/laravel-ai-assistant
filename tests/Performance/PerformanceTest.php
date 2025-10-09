<?php

declare(strict_types=1);

use CreativeCrafts\LaravelAiAssistant\Compat\OpenAI\Responses\Audio\TranscriptionResponse;
use CreativeCrafts\LaravelAiAssistant\Compat\OpenAI\Responses\Chat\CreateResponse as ChatResponse;
use CreativeCrafts\LaravelAiAssistant\Compat\OpenAI\Responses\Completions\CreateResponse as CompletionResponse;
use CreativeCrafts\LaravelAiAssistant\Contracts\OpenAiRepositoryContract;
use CreativeCrafts\LaravelAiAssistant\Services\AssistantService;
use CreativeCrafts\LaravelAiAssistant\Services\CacheService;
use CreativeCrafts\LaravelAiAssistant\Services\AiManager;
use CreativeCrafts\LaravelAiAssistant\Enums\Mode;
use CreativeCrafts\LaravelAiAssistant\Enums\Transport;
use CreativeCrafts\LaravelAiAssistant\DataTransferObjects\CompletionRequest;
use CreativeCrafts\LaravelAiAssistant\Tests\DataFactories\ApiPayloadFactory;

/**
 * Performance tests for measuring API call latencies and response times.
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
    $this->aiManager = new AiManager($this->assistantService);
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


test('text completion performance', function () use (&$performanceMetrics) {
    $iterations = 50;
    $warmupIterations = min(5, $iterations);
    $expectedCalls = $iterations + $warmupIterations;

    $payload = ApiPayloadFactory::completionPayload();
    $mockResponse = Mockery::mock(CompletionResponse::class);

    // Set up the choices property
    $reflection = new ReflectionClass(CompletionResponse::class);
    $property = $reflection->getProperty('choices');
    $property->setAccessible(true);
    $property->setValue($mockResponse, [
        (object)['text' => 'Performance test response']
    ]);

    $this->repositoryMock
        ->shouldReceive('createCompletion')
        ->times($expectedCalls)
        ->andReturn($mockResponse);

    $times = measurePerformance('Text Completion', $iterations, function () use ($payload) {
        return (string) $this->aiManager->complete(Mode::TEXT, Transport::SYNC, CompletionRequest::fromArray($payload));
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

    $payload = ApiPayloadFactory::chatCompletionPayload();
    $mockResponse = Mockery::mock(ChatResponse::class);
    $mockMessage = Mockery::mock();
    $mockMessage->shouldReceive('toArray')->andReturn(['content' => 'Performance test chat response']);

    // Set up the choices property
    $reflection = new ReflectionClass(ChatResponse::class);
    $property = $reflection->getProperty('choices');
    $property->setAccessible(true);
    $property->setValue($mockResponse, [
        (object)['message' => $mockMessage]
    ]);

    $this->repositoryMock
        ->shouldReceive('createChatCompletion')
        ->times($expectedCalls)
        ->andReturn($mockResponse);

    $times = measurePerformance('Chat Completion', $iterations, function () use ($payload) {
        return $this->aiManager->complete(Mode::CHAT, Transport::SYNC, CompletionRequest::fromArray($payload))->toArray();
    }, $performanceMetrics);

    // Use environment-based thresholds
    $averageThreshold = getenv('CI') ? 400 : 120;
    $maxThreshold = getenv('CI') ? 1500 : 600;

    expect($times['average'])->toBeLessThan($averageThreshold, "Chat completion should take less than {$averageThreshold}ms on average");
    expect($times['max'])->toBeLessThan($maxThreshold, "Chat completion should never take more than {$maxThreshold}ms");
});


test('audio processing performance', function () use (&$performanceMetrics) {
    $iterations = 20;
    $warmupIterations = min(5, $iterations);
    $expectedCalls = $iterations + $warmupIterations;

    $file = ApiPayloadFactory::createTestAudioFile();
    $payload = ApiPayloadFactory::audioPayload($file);
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

    $payload = ApiPayloadFactory::completionPayload(['temperature' => 0.0]); // Deterministic for caching
    $cachedResult = 'Cached performance test result';

    // First call should miss cache, subsequent should hit
    $this->cacheServiceMock
        ->shouldReceive('getCompletion')
        ->times($expectedCalls)
        ->andReturn($cachedResult);

    $times = measurePerformance('Cached Completion', $iterations, function () use ($payload) {
        return (string) $this->aiManager->complete(Mode::TEXT, Transport::SYNC, CompletionRequest::fromArray($payload));
    }, $performanceMetrics);

    // Use environment-based thresholds - cached operations should be fast
    $averageThreshold = getenv('CI') ? 50 : 10;
    $maxThreshold = getenv('CI') ? 200 : 50;

    expect($times['average'])->toBeLessThan($averageThreshold, "Cached operations should take less than {$averageThreshold}ms on average");
    expect($times['max'])->toBeLessThan($maxThreshold, "Cached operations should never take more than {$maxThreshold}ms");
});




test('large payload performance', function () use (&$performanceMetrics) {
    $iterations = 1;
    $warmupIterations = min(1, $iterations); // Only 1 warmup for single iteration test
    $expectedCalls = $iterations + $warmupIterations;

    $payload = ApiPayloadFactory::chatCompletionPayload([
        'messages' => generateLargeMessageHistory(50) // 50 messages
    ]);

    $mockResponse = Mockery::mock(ChatResponse::class);
    $mockMessage = Mockery::mock();
    $mockMessage->shouldReceive('toArray')->andReturn(['content' => 'Large payload response']);

    $reflection = new ReflectionClass(ChatResponse::class);
    $property = $reflection->getProperty('choices');
    $property->setAccessible(true);
    $property->setValue($mockResponse, [
        (object)['message' => $mockMessage]
    ]);

    $this->repositoryMock
        ->shouldReceive('createChatCompletion')
        ->times($expectedCalls)
        ->andReturn($mockResponse);

    $times = measurePerformance('Large Payload Processing', $iterations, function () use ($payload) {
        return $this->aiManager->complete(Mode::CHAT, Transport::SYNC, CompletionRequest::fromArray($payload))->toArray();
    }, $performanceMetrics);

    // Use environment-based threshold for large payloads
    $averageThreshold = getenv('CI') ? 1000 : 300;

    expect($times['average'])->toBeLessThan($averageThreshold, "Large payload processing should take less than {$averageThreshold}ms");
});
