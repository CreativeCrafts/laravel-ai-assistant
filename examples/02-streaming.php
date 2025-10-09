<?php

declare(strict_types=1);

/**
 * Example 02: Streaming Responses
 *
 * This example demonstrates real-time streaming for better user experience.
 * You'll learn:
 * - Basic streaming with Ai::stream()
 * - Streaming with callbacks for real-time processing
 * - Collecting streamed content
 *
 * Time: ~2 minutes
 */

require __DIR__ . '/../vendor/autoload.php';

use CreativeCrafts\LaravelAiAssistant\Facades\Ai;

echo "=== Laravel AI Assistant: Streaming Responses ===\n\n";

try {
    // Example 1: Basic Streaming
    echo "1. Basic Streaming\n";
    echo str_repeat('-', 50) . "\n";
    echo "Streaming a short story (watch it appear token by token):\n\n";

    foreach (Ai::stream('Tell me a very short story about Laravel in 2 sentences') as $chunk) {
        echo $chunk;
        flush(); // Flush output buffer for real-time display
        usleep(50000); // Small delay to make streaming visible (50ms)
    }

    echo "\n\n";

    // Example 2: Streaming with Callback
    echo "2. Streaming with Callback\n";
    echo str_repeat('-', 50) . "\n";
    echo "Processing chunks with callback:\n\n";

    $fullText = '';
    $chunkCount = 0;

    $stream = Ai::stream(
        'List 3 benefits of Laravel',
        onEvent: function ($event) use (&$fullText, &$chunkCount) {
            $fullText .= $event;
            $chunkCount++;
            // In a real app, you could:
            // - Broadcast to WebSocket
            // - Update a progress bar
            // - Log to monitoring system
        }
    );

    foreach ($stream as $chunk) {
        echo $chunk;
        flush();
        usleep(50000);
    }

    echo "\n\n";
    echo "Processed {$chunkCount} chunks\n";
    echo "Full text length: " . strlen($fullText) . " characters\n\n";

    // Example 3: Chat Session with Streaming
    echo "3. Chat Session with Streaming\n";
    echo str_repeat('-', 50) . "\n";
    echo "Streaming a chat response:\n\n";

    $chat = Ai::chat('You are a helpful Laravel expert. Be concise.');
    $chat->message('What is dependency injection in one sentence?');

    foreach ($chat->stream() as $chunk) {
        echo $chunk;
        flush();
        usleep(50000);
    }

    echo "\n\n";

    // Example 4: Collecting Streamed Content
    echo "4. Collecting All Streamed Content\n";
    echo str_repeat('-', 50) . "\n";

    $collectedText = '';
    $stream = Ai::stream('What is middleware?');

    foreach ($stream as $chunk) {
        $collectedText .= $chunk;
    }

    echo "Collected complete response:\n";
    echo $collectedText . "\n\n";

    echo "✅ Streaming examples completed successfully!\n";
    echo "\nKey Takeaways:\n";
    echo "- Streaming provides better UX for long responses\n";
    echo "- Use callbacks for real-time processing\n";
    echo "- Works with both quick requests and chat sessions\n";

} catch (Exception $e) {
    echo "\n❌ Error: " . $e->getMessage() . "\n";
    echo "Make sure your OPENAI_API_KEY is configured in .env\n";
    exit(1);
}
