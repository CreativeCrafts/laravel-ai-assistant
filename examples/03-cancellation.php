<?php

declare(strict_types=1);

/**
 * Example 03: Cancellation (Stopping Streams)
 *
 * This example demonstrates how to control and stop streaming operations.
 * You'll learn:
 * - Chunk count-based cancellation
 * - Time-based cancellation
 * - User-initiated cancellation patterns
 * - Using shouldStop callback
 *
 * Time: ~2 minutes
 */

require __DIR__ . '/../vendor/autoload.php';

use CreativeCrafts\LaravelAiAssistant\Facades\Ai;

echo "=== Laravel AI Assistant: Cancellation ===\n\n";

try {
    // Example 1: Limit by Chunk Count
    echo "1. Limit Streaming by Chunk Count\n";
    echo str_repeat('-', 50) . "\n";
    echo "Limiting to first 5 chunks:\n\n";

    $maxChunks = 5;
    $chunkCount = 0;

    $stream = Ai::stream(
        'Write a long essay about Laravel',
        onEvent: null,
        shouldStop: function () use (&$chunkCount, $maxChunks): bool {
            $chunkCount++;
            return $chunkCount >= $maxChunks;
        }
    );

    foreach ($stream as $chunk) {
        echo "Chunk {$chunkCount}: {$chunk}";
        flush();
        if ($chunkCount >= $maxChunks) {
            break;
        }
    }

    echo "\n\n✓ Stopped after {$chunkCount} chunks\n\n";

    // Example 2: Time-Based Cancellation
    echo "2. Time-Based Cancellation\n";
    echo str_repeat('-', 50) . "\n";
    echo "Stopping after 3 seconds:\n\n";

    $startTime = microtime(true);
    $maxDuration = 3.0; // seconds
    $chunkCount = 0;

    $stream = Ai::stream(
        'Tell me a very long story about Laravel',
        shouldStop: function () use ($startTime, $maxDuration): bool {
            return (microtime(true) - $startTime) >= $maxDuration;
        }
    );

    foreach ($stream as $chunk) {
        $chunkCount++;
        echo $chunk;
        flush();
        usleep(50000);
    }

    $elapsed = microtime(true) - $startTime;
    echo "\n\n✓ Stopped after {$elapsed} seconds ({$chunkCount} chunks)\n\n";

    // Example 3: Content Length Limit
    echo "3. Content Length Limit\n";
    echo str_repeat('-', 50) . "\n";
    echo "Stopping when 100 characters collected:\n\n";

    $collectedText = '';
    $maxLength = 100;

    $stream = Ai::stream(
        'Explain Laravel routing in detail',
        onEvent: function ($event) use (&$collectedText) {
            $collectedText .= $event;
        },
        shouldStop: function () use (&$collectedText, $maxLength): bool {
            return strlen($collectedText) >= $maxLength;
        }
    );

    foreach ($stream as $chunk) {
        echo $chunk;
        flush();
        if (strlen($collectedText) >= $maxLength) {
            break;
        }
    }

    echo "\n\n✓ Stopped at {$collectedText} characters\n\n";

    // Example 4: User-Initiated Cancellation (Simulated)
    echo "4. User-Initiated Cancellation\n";
    echo str_repeat('-', 50) . "\n";
    echo "Simulating user cancellation after 2 seconds:\n\n";

    $cancelled = false;
    $startTime = microtime(true);

    // Simulate user clicking a "Stop" button after 2 seconds
    $stream = Ai::stream(
        'Write a comprehensive guide to Laravel',
        shouldStop: function () use (&$cancelled, $startTime): bool {
            // In a real app, $cancelled would be set by user action
            // (e.g., WebSocket message, shared memory, signal handler)
            if (!$cancelled && (microtime(true) - $startTime) >= 2.0) {
                $cancelled = true;
            }
            return $cancelled;
        }
    );

    foreach ($stream as $chunk) {
        echo $chunk;
        flush();
        usleep(50000);

        if ($cancelled) {
            echo "\n\n[User cancelled operation]";
            break;
        }
    }

    echo "\n\n✓ Operation cancelled by user\n\n";

    // Example 5: Token Count Estimation
    echo "5. Token Count Estimation\n";
    echo str_repeat('-', 50) . "\n";
    echo "Stopping after ~50 tokens (estimated):\n\n";

    $tokenEstimate = 0;
    $maxTokens = 50;

    $stream = Ai::stream(
        'Explain Laravel Eloquent ORM',
        onEvent: function ($event) use (&$tokenEstimate) {
            // Rough estimation: ~4 characters per token
            $tokenEstimate += strlen($event) / 4;
        },
        shouldStop: function () use (&$tokenEstimate, $maxTokens): bool {
            return $tokenEstimate >= $maxTokens;
        }
    );

    foreach ($stream as $chunk) {
        echo $chunk;
        flush();
        if ($tokenEstimate >= $maxTokens) {
            break;
        }
    }

    echo "\n\n✓ Stopped at ~{$tokenEstimate} tokens\n\n";

    echo "✅ Cancellation examples completed successfully!\n";
    echo "\nKey Takeaways:\n";
    echo "- Use shouldStop callback for graceful cancellation\n";
    echo "- Can limit by chunks, time, length, or user action\n";
    echo "- Always break from foreach after cancellation\n";
    echo "- Useful for cost control and user experience\n";

} catch (Exception $e) {
    echo "\n❌ Error: " . $e->getMessage() . "\n";
    echo "Make sure your OPENAI_API_KEY is configured in .env\n";
    exit(1);
}
