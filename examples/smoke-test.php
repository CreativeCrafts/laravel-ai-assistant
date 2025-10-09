<?php

declare(strict_types=1);

/**
 * Smoke Test Script
 *
 * This script verifies that the Laravel AI Assistant package is properly
 * installed and configured. Run this first to ensure everything is set up correctly.
 *
 * Usage: php examples/smoke-test.php
 */

require __DIR__ . '/../vendor/autoload.php';

use CreativeCrafts\LaravelAiAssistant\Facades\{Ai, Observability};
use CreativeCrafts\LaravelAiAssistant\Services\AiManager;
use CreativeCrafts\LaravelAiAssistant\Enums\{Mode, Transport};
use CreativeCrafts\LaravelAiAssistant\DataTransferObjects\CompletionRequest;
use Illuminate\Support\Str;

echo "🚀 Laravel AI Assistant - Smoke Test\n";
echo str_repeat('=', 50) . "\n\n";

$passed = 0;
$failed = 0;
$errors = [];

/**
 * Test helper function
 */
function test(string $name, callable $callback, array &$errors, int &$passed, int &$failed): void
{
    echo "Testing: {$name}... ";
    try {
        $callback();
        echo "✓ PASS\n";
        $passed++;
    } catch (Exception $e) {
        echo "✗ FAIL\n";
        $failed++;
        $errors[] = [
            'test' => $name,
            'error' => $e->getMessage(),
        ];
    }
}

// Test 1: Package Installation
test('Package installed', function () {
    if (!class_exists(AiManager::class)) {
        throw new Exception('AiManager class not found');
    }
    if (!class_exists(Ai::class)) {
        throw new Exception('Ai facade not found');
    }
}, $errors, $passed, $failed);

// Test 2: OpenAI API Key Configuration
test('OpenAI API key configured', function () {
    $apiKey = env('OPENAI_API_KEY');
    if (empty($apiKey)) {
        throw new Exception('OPENAI_API_KEY not set in .env');
    }
    if (!str_starts_with($apiKey, 'sk-')) {
        throw new Exception('OPENAI_API_KEY appears to be invalid (should start with sk-)');
    }
}, $errors, $passed, $failed);

// Test 3: Basic Quick Request
test('Basic AI request (Ai::quick)', function () {
    $response = Ai::quick('Say "test" in one word');
    if (empty($response->text)) {
        throw new Exception('No response text received');
    }
}, $errors, $passed, $failed);

// Test 4: Chat Session
test('Chat session works', function () {
    $chat = Ai::chat('You are helpful. Be very brief.');
    $chat->message('Say hi in one word');
    $response = $chat->send();
    if (empty($response->text)) {
        throw new Exception('No response from chat session');
    }
}, $errors, $passed, $failed);

// Test 5: Streaming
test('Streaming works', function () {
    $chunks = 0;
    $stream = Ai::stream('Count to 3');
    foreach ($stream as $chunk) {
        $chunks++;
        if ($chunks >= 3) {
            break; // Don't need to wait for full response
        }
    }
    if ($chunks === 0) {
        throw new Exception('No chunks received from stream');
    }
}, $errors, $passed, $failed);

// Test 6: Unified Completion API
test('Unified completion API (AiManager::complete)', function () {
    $ai = app(AiManager::class);
    $result = $ai->complete(
        Mode::TEXT,
        Transport::SYNC,
        CompletionRequest::fromArray([
            'model' => 'gpt-4o-mini',
            'prompt' => 'Say "ok" in one word',
            'max_tokens' => 10,
        ])
    );
    $text = (string) $result;
    if (empty($text)) {
        throw new Exception('No result from unified API');
    }
}, $errors, $passed, $failed);

// Test 7: Observability Integration
test('Observability works', function () {
    $correlationId = Str::uuid()->toString();
    Observability::setCorrelationId($correlationId);

    $retrievedId = Observability::getCorrelationId();
    if ($retrievedId !== $correlationId) {
        throw new Exception('Correlation ID not set correctly');
    }

    // Test logging
    Observability::log('test', 'info', 'Test log message');

    // Test memory tracking
    $checkpoint = Observability::trackMemory('test-operation');
    Observability::endMemoryTracking($checkpoint);
}, $errors, $passed, $failed);

// Test 8: Enums
test('Enums available', function () {
    if (!enum_exists(Mode::class)) {
        throw new Exception('Mode enum not found');
    }
    if (!enum_exists(Transport::class)) {
        throw new Exception('Transport enum not found');
    }

    // Test enum values
    $textMode = Mode::TEXT;
    $chatMode = Mode::CHAT;
    $syncTransport = Transport::SYNC;
    $streamTransport = Transport::STREAM;

    if ($textMode->value !== 'text') {
        throw new Exception('Mode::TEXT value incorrect');
    }
}, $errors, $passed, $failed);

// Test 9: DTOs
test('DTOs available', function () {
    $request = CompletionRequest::fromArray([
        'model' => 'gpt-4o-mini',
        'prompt' => 'test',
    ]);

    if (!$request instanceof CompletionRequest) {
        throw new Exception('CompletionRequest not created correctly');
    }
}, $errors, $passed, $failed);

// Test 10: Facades
test('Facades work', function () {
    // Test Ai facade
    $aiClass = get_class(Ai::getFacadeRoot());
    if (!$aiClass) {
        throw new Exception('Ai facade not resolving');
    }

    // Test Observability facade
    $obsClass = get_class(Observability::getFacadeRoot());
    if (!$obsClass) {
        throw new Exception('Observability facade not resolving');
    }
}, $errors, $passed, $failed);

// Print Results
echo "\n" . str_repeat('=', 50) . "\n";
echo "Results:\n";
echo "  Passed: {$passed}\n";
echo "  Failed: {$failed}\n\n";

if ($failed > 0) {
    echo "❌ Some tests failed:\n\n";
    foreach ($errors as $error) {
        echo "  • {$error['test']}\n";
        echo "    Error: {$error['error']}\n\n";
    }
    echo "Please check your configuration and try again.\n";
    echo "\nCommon issues:\n";
    echo "  - OPENAI_API_KEY not set in .env\n";
    echo "  - Package not installed (run: composer require creativecrafts/laravel-ai-assistant)\n";
    echo "  - Internet connection issues\n";
    echo "  - OpenAI API rate limits\n";
    exit(1);
} else {
    echo "✅ All tests passed!\n\n";
    echo "Your Laravel AI Assistant installation is working correctly.\n";
    echo "You can now run the other examples:\n\n";
    echo "  php examples/01-hello-world.php\n";
    echo "  php examples/02-streaming.php\n";
    echo "  php examples/03-cancellation.php\n";
    echo "  php examples/04-complete-api.php\n";
    echo "  php examples/05-observability.php\n\n";
    echo "See examples/README.md for more information.\n";
    exit(0);
}
