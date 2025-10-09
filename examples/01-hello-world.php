<?php

declare(strict_types=1);

/**
 * Example 01: Hello World
 *
 * This example demonstrates the simplest way to get started with Laravel AI Assistant.
 * You'll learn:
 * - Using Ai::quick() for one-off requests
 * - Creating a chat session with Ai::chat()
 * - Basic response handling
 *
 * Time: ~1 minute
 */

require __DIR__ . '/../vendor/autoload.php';

use CreativeCrafts\LaravelAiAssistant\Facades\Ai;

echo "=== Laravel AI Assistant: Hello World ===\n\n";

try {
    // Example 1: Quick One-Off Request
    // This is the simplest way to get an AI response
    echo "1. Quick One-Off Request\n";
    echo str_repeat('-', 50) . "\n";

    $response = Ai::quick('Explain Laravel queues in one sentence');
    echo "Question: Explain Laravel queues in one sentence\n";
    echo "Response: " . $response->text . "\n\n";

    // Example 2: Quick Request with Options
    echo "2. Quick Request with Options\n";
    echo str_repeat('-', 50) . "\n";

    $response = Ai::quick([
        'message' => 'Give me 3 Laravel tips',
        'model' => 'gpt-4o-mini',
        'temperature' => 0.8, // Higher temperature = more creative
    ]);
    echo "Question: Give me 3 Laravel tips\n";
    echo "Response: " . $response->text . "\n\n";

    // Example 3: Chat Session (Multi-Turn Conversation)
    echo "3. Chat Session (Multi-Turn)\n";
    echo str_repeat('-', 50) . "\n";

    $chat = Ai::chat('You are a helpful Laravel expert');

    // First turn
    $chat->message('What are service providers?');
    $response = $chat->send();
    echo "User: What are service providers?\n";
    echo "AI: " . $response->text . "\n\n";

    // Second turn (context is maintained)
    $chat->message('Can you give me a simple example?');
    $response = $chat->send();
    echo "User: Can you give me a simple example?\n";
    echo "AI: " . $response->text . "\n\n";

    // View conversation history
    echo "4. Conversation History\n";
    echo str_repeat('-', 50) . "\n";
    $messages = $chat->getMessages();
    echo "Total messages in conversation: " . count($messages) . "\n\n";

    // Example 4: JSON Response Format
    echo "5. JSON Response Format\n";
    echo str_repeat('-', 50) . "\n";

    $response = Ai::quick([
        'message' => 'List 3 Laravel features as JSON with name and description keys',
        'response_format' => 'json',
    ]);
    echo "Question: List 3 Laravel features as JSON\n";
    echo "Response: " . $response->text . "\n";

    $data = json_decode($response->text, true);
    if ($data) {
        echo "\nParsed JSON successfully!\n";
    }

    echo "\n✅ Hello World example completed successfully!\n";

} catch (Exception $e) {
    echo "\n❌ Error: " . $e->getMessage() . "\n";
    echo "Make sure your OPENAI_API_KEY is configured in .env\n";
    exit(1);
}
