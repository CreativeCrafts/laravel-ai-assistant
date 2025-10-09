<?php

declare(strict_types=1);

/**
 * Example 04: Unified Completion API
 *
 * This example demonstrates the modern, recommended unified completion API.
 * You'll learn:
 * - AiManager::complete() method
 * - Mode::TEXT and Mode::CHAT usage
 * - Transport::SYNC and Transport::STREAM differences
 * - CompletionRequest DTO usage
 * - Type-safe, explicit API design
 *
 * Time: ~3 minutes
 */

require __DIR__ . '/../vendor/autoload.php';

use CreativeCrafts\LaravelAiAssistant\Services\AiManager;
use CreativeCrafts\LaravelAiAssistant\Enums\{Mode, Transport};
use CreativeCrafts\LaravelAiAssistant\DataTransferObjects\CompletionRequest;

echo "=== Laravel AI Assistant: Unified Completion API ===\n\n";

try {
    $ai = app(AiManager::class);

    // Example 1: TEXT Mode + SYNC Transport
    echo "1. TEXT Mode + SYNC Transport\n";
    echo str_repeat('-', 50) . "\n";
    echo "Simple text completion (blocking):\n\n";

    $result = $ai->complete(
        Mode::TEXT,
        Transport::SYNC,
        CompletionRequest::fromArray([
            'model' => 'gpt-4o-mini',
            'prompt' => 'Explain Laravel in one sentence',
            'temperature' => 0.7,
            'max_tokens' => 100,
        ])
    );

    echo "Result: " . (string) $result . "\n\n";

    // Example 2: CHAT Mode + SYNC Transport
    echo "2. CHAT Mode + SYNC Transport\n";
    echo str_repeat('-', 50) . "\n";
    echo "Chat completion with message history:\n\n";

    $result = $ai->complete(
        Mode::CHAT,
        Transport::SYNC,
        CompletionRequest::fromArray([
            'model' => 'gpt-4o-mini',
            'messages' => [
                ['role' => 'system', 'content' => 'You are a helpful Laravel expert'],
                ['role' => 'user', 'content' => 'What is dependency injection?'],
            ],
            'temperature' => 0.5,
        ])
    );

    $data = $result->toArray();
    echo "Assistant response: " . ($data['content'] ?? json_encode($data)) . "\n\n";

    // Example 3: TEXT Mode + STREAM Transport
    echo "3. TEXT Mode + STREAM Transport\n";
    echo str_repeat('-', 50) . "\n";
    echo "Streaming text completion (accumulated):\n\n";

    $result = $ai->complete(
        Mode::TEXT,
        Transport::STREAM,
        CompletionRequest::fromArray([
            'model' => 'gpt-4o-mini',
            'prompt' => 'Write a haiku about Laravel',
            'temperature' => 0.9,
        ])
    );

    echo "Final result: " . (string) $result . "\n\n";

    // Example 4: CHAT Mode + STREAM Transport
    echo "4. CHAT Mode + STREAM Transport\n";
    echo str_repeat('-', 50) . "\n";
    echo "Streaming chat completion (accumulated):\n\n";

    $result = $ai->complete(
        Mode::CHAT,
        Transport::STREAM,
        CompletionRequest::fromArray([
            'model' => 'gpt-4o-mini',
            'messages' => [
                ['role' => 'system', 'content' => 'You are concise'],
                ['role' => 'user', 'content' => 'List 3 Laravel features'],
            ],
        ])
    );

    $data = $result->toArray();
    echo "Final result: " . json_encode($data, JSON_PRETTY_PRINT) . "\n\n";

    // Example 5: Advanced Configuration
    echo "5. Advanced Configuration\n";
    echo str_repeat('-', 50) . "\n";
    echo "Using all available parameters:\n\n";

    $result = $ai->complete(
        Mode::TEXT,
        Transport::SYNC,
        CompletionRequest::fromArray([
            'model' => 'gpt-4o-mini',
            'prompt' => 'Give me a creative Laravel tip',
            'temperature' => 1.0,           // High creativity
            'max_tokens' => 150,            // Limit response length
            'top_p' => 0.9,                 // Nucleus sampling
            'frequency_penalty' => 0.5,     // Reduce repetition
            'presence_penalty' => 0.5,      // Encourage topic diversity
        ])
    );

    echo "Result: " . (string) $result . "\n\n";

    // Example 6: Multi-Turn Conversation
    echo "6. Multi-Turn Conversation with CHAT Mode\n";
    echo str_repeat('-', 50) . "\n";

    $messages = [
        ['role' => 'system', 'content' => 'You are a Laravel expert'],
        ['role' => 'user', 'content' => 'What is Eloquent?'],
    ];

    $result = $ai->complete(
        Mode::CHAT,
        Transport::SYNC,
        CompletionRequest::fromArray([
            'model' => 'gpt-4o-mini',
            'messages' => $messages,
        ])
    );

    $data = $result->toArray();
    echo "User: What is Eloquent?\n";
    echo "AI: " . ($data['content'] ?? 'Response') . "\n\n";

    // Add AI response to conversation history
    $messages[] = ['role' => 'assistant', 'content' => $data['content'] ?? 'Eloquent is...'];
    $messages[] = ['role' => 'user', 'content' => 'Can you give an example?'];

    $result = $ai->complete(
        Mode::CHAT,
        Transport::SYNC,
        CompletionRequest::fromArray([
            'model' => 'gpt-4o-mini',
            'messages' => $messages,
        ])
    );

    $data = $result->toArray();
    echo "User: Can you give an example?\n";
    echo "AI: " . ($data['content'] ?? 'Response') . "\n\n";

    echo "✅ Unified Completion API examples completed successfully!\n";
    echo "\nKey Takeaways:\n";
    echo "- complete() is the modern, recommended API\n";
    echo "- Mode::TEXT for simple prompts, Mode::CHAT for conversations\n";
    echo "- Transport::SYNC blocks until complete, STREAM accumulates\n";
    echo "- CompletionRequest provides type-safe configuration\n";
    echo "- Use toArray() for CHAT mode, (string) for TEXT mode\n";

} catch (Exception $e) {
    echo "\n❌ Error: " . $e->getMessage() . "\n";
    echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
    exit(1);
}
