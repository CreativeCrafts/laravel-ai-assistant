<?php

declare(strict_types=1);

/**
 * Example 05: Observability Integration
 *
 * This example demonstrates comprehensive observability for production systems.
 * You'll learn:
 * - Correlation ID tracking for request tracing
 * - Structured logging with context
 * - Performance metrics collection
 * - Memory monitoring and tracking
 * - Error reporting with context
 * - Complete observability integration pattern
 *
 * Time: ~3 minutes
 */

require __DIR__ . '/../vendor/autoload.php';

use CreativeCrafts\LaravelAiAssistant\Facades\{Ai, Observability};
use Illuminate\Support\Str;

echo "=== Laravel AI Assistant: Observability Integration ===\n\n";

try {
    // Example 1: Basic Observability Setup
    echo "1. Setting Up Observability\n";
    echo str_repeat('-', 50) . "\n";

    // Generate and set correlation ID for request tracing
    $correlationId = Str::uuid()->toString();
    Observability::setCorrelationId($correlationId);

    echo "✓ Correlation ID: {$correlationId}\n";
    echo "  (All subsequent operations will be traced with this ID)\n\n";

    // Example 2: AI Request with Full Observability
    echo "2. AI Request with Observability\n";
    echo str_repeat('-', 50) . "\n";

    $startTime = microtime(true);
    $checkpoint = Observability::trackMemory('ai-request');

    // Log the start of operation
    Observability::log(
        'ai-request',
        'info',
        'Starting AI request',
        ['prompt' => 'Explain Laravel']
    );

    // Make AI request
    $response = Ai::quick('Explain Laravel queues in one sentence');
    $duration = microtime(true) - $startTime;

    // Log successful response
    Observability::logApiResponse(
        'ai-completion',
        true,
        [
            'text_length' => strlen($response->text),
            'model' => $response->model ?? 'unknown',
        ],
        $duration
    );

    // Record performance metrics
    Observability::recordApiCall(
        '/ai/complete',
        $duration * 1000, // milliseconds
        200,
        ['operation' => 'quick-request']
    );

    // End memory tracking
    $metrics = Observability::endMemoryTracking($checkpoint);

    // Log performance metrics
    Observability::logPerformanceMetrics(
        'ai-request',
        $duration * 1000,
        $metrics
    );

    echo "✓ Request completed\n";
    echo "  Response: " . substr($response->text, 0, 50) . "...\n";
    echo "  Duration: " . number_format($duration, 3) . "s\n";
    echo "  Memory: " . number_format($metrics['memory_peak'] ?? 0, 2) . "MB\n\n";

    // Example 3: Streaming with Observability
    echo "3. Streaming with Observability\n";
    echo str_repeat('-', 50) . "\n";

    $startTime = microtime(true);
    $checkpoint = Observability::trackMemory('ai-stream');
    $chunkCount = 0;

    Observability::log('ai-stream', 'info', 'Starting stream operation');

    foreach (Ai::stream('List 3 Laravel features') as $chunk) {
        $chunkCount++;
        echo $chunk;
        flush();
    }

    $duration = microtime(true) - $startTime;
    $metrics = Observability::endMemoryTracking($checkpoint);

    // Log streaming metrics
    Observability::logPerformanceMetrics(
        'ai-stream',
        $duration * 1000,
        array_merge($metrics, [
            'chunks' => $chunkCount,
            'operation' => 'streaming',
        ])
    );

    echo "\n\n✓ Streaming completed\n";
    echo "  Chunks: {$chunkCount}\n";
    echo "  Duration: " . number_format($duration, 3) . "s\n\n";

    // Example 4: Error Handling with Observability
    echo "4. Error Handling with Observability\n";
    echo str_repeat('-', 50) . "\n";

    try {
        // Simulate an operation that might fail
        $checkpoint = Observability::trackMemory('risky-operation');

        Observability::log(
            'risky-operation',
            'info',
            'Attempting risky operation'
        );

        // Intentionally cause an error for demonstration
        // throw new \Exception('Simulated error');

        // If no error, clean up
        Observability::endMemoryTracking($checkpoint);
        echo "✓ Operation succeeded (error handling demonstrated)\n\n";

    } catch (Exception $e) {
        Observability::endMemoryTracking($checkpoint);

        // Report error with full context
        $errorId = Observability::report(
            $e,
            [
                'operation' => 'risky-operation',
                'user_context' => 'example-script',
            ],
            ['severity' => 'medium']
        );

        Observability::logError(
            'risky-operation',
            $e->getMessage(),
            ['error_id' => $errorId]
        );

        echo "✓ Error handled and reported (ID: {$errorId})\n\n";
    }

    // Example 5: Memory Threshold Monitoring
    echo "5. Memory Threshold Monitoring\n";
    echo str_repeat('-', 50) . "\n";

    $currentMemory = Observability::getCurrentMemoryUsage();
    echo "Current memory usage: " . number_format($currentMemory, 2) . "MB\n";

    if (Observability::isMemoryThresholdExceeded(80.0)) {
        Observability::reportMemoryIssue(
            'memory-check',
            $currentMemory,
            80.0,
            ['context' => 'example-script']
        );
        echo "⚠ Memory threshold exceeded!\n\n";
    } else {
        echo "✓ Memory usage is within threshold\n\n";
    }

    // Example 6: Token Usage Tracking
    echo "6. Token Usage Tracking\n";
    echo str_repeat('-', 50) . "\n";

    $response = Ai::quick('What is Laravel?');

    // Record token usage (if available from response)
    if (isset($response->usage)) {
        Observability::recordTokenUsage(
            'quick-request',
            $response->usage['prompt_tokens'] ?? 0,
            $response->usage['completion_tokens'] ?? 0,
            $response->model ?? 'gpt-4o-mini'
        );

        $totalTokens = ($response->usage['prompt_tokens'] ?? 0) +
                      ($response->usage['completion_tokens'] ?? 0);

        echo "✓ Token usage recorded\n";
        echo "  Prompt tokens: " . ($response->usage['prompt_tokens'] ?? 0) . "\n";
        echo "  Completion tokens: " . ($response->usage['completion_tokens'] ?? 0) . "\n";
        echo "  Total tokens: {$totalTokens}\n\n";
    } else {
        echo "✓ Response received (token data not available in this context)\n\n";
    }

    // Example 7: Complete Request Lifecycle
    echo "7. Complete Request Lifecycle Tracking\n";
    echo str_repeat('-', 50) . "\n";

    $operationId = 'complete-lifecycle-' . time();
    $startTime = microtime(true);
    $checkpoint = Observability::trackMemory($operationId);

    // Phase 1: Preparation
    Observability::updateMemoryTracking($checkpoint, 'preparation');
    Observability::log($operationId, 'info', 'Phase: preparation');
    usleep(100000); // Simulate work

    // Phase 2: API Call
    Observability::updateMemoryTracking($checkpoint, 'api-call');
    Observability::log($operationId, 'info', 'Phase: api-call');
    $response = Ai::quick('Laravel tip');

    // Phase 3: Processing
    Observability::updateMemoryTracking($checkpoint, 'processing');
    Observability::log($operationId, 'info', 'Phase: processing');
    usleep(100000); // Simulate work

    // Complete operation
    $metrics = Observability::endMemoryTracking($checkpoint);
    $duration = microtime(true) - $startTime;

    Observability::logPerformanceMetrics(
        $operationId,
        $duration * 1000,
        $metrics
    );

    echo "✓ Complete lifecycle tracked\n";
    echo "  Duration: " . number_format($duration, 3) . "s\n";
    echo "  Phases: preparation → api-call → processing\n";
    echo "  Peak memory: " . number_format($metrics['memory_peak'] ?? 0, 2) . "MB\n\n";

    echo "✅ Observability examples completed successfully!\n";
    echo "\nKey Takeaways:\n";
    echo "- Always set correlation IDs for request tracing\n";
    echo "- Track memory for all significant operations\n";
    echo "- Log at appropriate levels (info, warning, error)\n";
    echo "- Record metrics for performance monitoring\n";
    echo "- Report errors with full context\n";
    echo "- Use memory thresholds for proactive monitoring\n";

} catch (Exception $e) {
    echo "\n❌ Error: " . $e->getMessage() . "\n";
    echo "Stack trace:\n" . $e->getTraceAsString() . "\n";

    // Even in failure, report the error
    Observability::report($e, [
        'script' => 'observability-example',
        'phase' => 'execution',
    ]);

    exit(1);
}
