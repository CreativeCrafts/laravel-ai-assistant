<?php

declare(strict_types=1);

use CreativeCrafts\LaravelAiAssistant\Contracts\ConversationsRepositoryContract;
use CreativeCrafts\LaravelAiAssistant\Contracts\FilesRepositoryContract;
use CreativeCrafts\LaravelAiAssistant\Contracts\ResponsesRepositoryContract;
use CreativeCrafts\LaravelAiAssistant\OpenAIClientFacade;
use CreativeCrafts\LaravelAiAssistant\Services\AssistantService;
use CreativeCrafts\LaravelAiAssistant\Tests\Fakes\FakeConversationsRepository;
use CreativeCrafts\LaravelAiAssistant\Tests\Fakes\FakeFilesRepository;
use CreativeCrafts\LaravelAiAssistant\Tests\Fakes\FakeResponsesRepository;

beforeEach(function () {
    config()->set('ai-assistant.api_key', 'test_key_123');

    $fakeResponses = new FakeResponsesRepository();
    $fakeConversations = new FakeConversationsRepository();
    $fakeFiles = new FakeFilesRepository();

    app()->instance(ResponsesRepositoryContract::class, $fakeResponses);
    app()->instance(ConversationsRepositoryContract::class, $fakeConversations);
    app()->instance(FilesRepositoryContract::class, $fakeFiles);

    app()->forgetInstance(OpenAIClientFacade::class);
    app()->singleton(OpenAIClientFacade::class, function ($app) use ($fakeResponses, $fakeConversations, $fakeFiles) {
        return new OpenAIClientFacade(
            $fakeResponses,
            $fakeConversations,
            $fakeFiles,
            $app->make(CreativeCrafts\LaravelAiAssistant\Contracts\OpenAiRepositoryContract::class),
            $app->make(CreativeCrafts\LaravelAiAssistant\Contracts\ResponsesInputItemsRepositoryContract::class),
        );
    });

    $this->assistantService = app(AssistantService::class);
    $this->performanceMetrics = [];
});

afterEach(function () {
    if (!empty($this->performanceMetrics)) {
        outputPerformanceReport($this->performanceMetrics);
    }
});

it('chat completion performance', function () {
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

        expect($response)->not->toBeNull();
    }

    $endTime = microtime(true);
    $memoryEnd = memory_get_usage(true);

    recordMetric($this->performanceMetrics, 'chat_completion', [
        'iterations' => $iterations,
        'total_time' => $endTime - $startTime,
        'avg_time_per_request' => ($endTime - $startTime) / $iterations,
        'memory_used' => $memoryEnd - $memoryStart,
        'avg_memory_per_request' => ($memoryEnd - $memoryStart) / $iterations,
    ]);

    $avgTime = ($endTime - $startTime) / $iterations;
    expect($avgTime)->toBeLessThan(0.1);
})->group('performance');

it('assistant creation performance', function () {
    $iterations = 50;
    $startTime = microtime(true);
    $memoryStart = memory_get_usage(true);

    for ($i = 0; $i < $iterations; $i++) {
        $conversationId = $this->assistantService->createConversation([
            'name' => "Test Conversation {$i}",
            'purpose' => 'performance_test',
        ]);

        expect($conversationId)->toBeString()
            ->and($conversationId)->not->toBe('');
    }

    $endTime = microtime(true);
    $memoryEnd = memory_get_usage(true);

    recordMetric($this->performanceMetrics, 'assistant_creation', [
        'iterations' => $iterations,
        'total_time' => $endTime - $startTime,
        'avg_time_per_request' => ($endTime - $startTime) / $iterations,
        'memory_used' => $memoryEnd - $memoryStart,
        'avg_memory_per_request' => ($memoryEnd - $memoryStart) / $iterations,
    ]);

    $avgTime = ($endTime - $startTime) / $iterations;
    expect($avgTime)->toBeLessThan(0.2);
})->group('performance');

it('batch operations performance', function () {
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

        recordMetric($this->performanceMetrics, "batch_operations_size_{$batchSize}", [
            'batch_size' => $batchSize,
            'total_time' => $endTime - $startTime,
            'memory_used' => $memoryEnd - $memoryStart,
            'time_per_message' => ($endTime - $startTime) / $batchSize,
        ]);

        expect($response)->not->toBeNull();
    }
})->group('performance');

it('concurrent operations simulation', function () {
    $concurrentRequests = 20;
    $startTime = microtime(true);
    $memoryStart = memory_get_usage(true);

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

    recordMetric($this->performanceMetrics, 'concurrent_simulation', [
        'concurrent_requests' => $concurrentRequests,
        'total_time' => $endTime - $startTime,
        'avg_time_per_request' => ($endTime - $startTime) / $concurrentRequests,
        'memory_used' => $memoryEnd - $memoryStart,
        'successful_responses' => count(array_filter($responses)),
    ]);

    expect(count(array_filter($responses)))->toBe($concurrentRequests);
})->group('performance');

it('large payload memory performance', function () {
    $payloadSizes = [1000, 5000, 10000];

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

        recordMetric($this->performanceMetrics, "large_payload_{$size}_chars", [
            'payload_size_chars' => strlen($largeContent),
            'execution_time' => $endTime - $startTime,
            'memory_used' => $memoryEnd - $memoryStart,
            'peak_memory' => $memoryPeak,
        ]);

        expect($response)->not->toBeNull();

        $memoryUsedMB = ($memoryEnd - $memoryStart) / 1024 / 1024;
        expect($memoryUsedMB)->toBeLessThan(50);
    }
})->group('performance');

function recordMetric(array &$performanceMetrics, string $testName, array $metrics): void
{
    $performanceMetrics[$testName] = array_merge($metrics, [
        'timestamp' => now()->toISOString(),
        'php_version' => PHP_VERSION,
        'memory_limit' => ini_get('memory_limit'),
    ]);
}

function outputPerformanceReport(array $performanceMetrics): void
{
    if (!env('OUTPUT_PERFORMANCE_REPORTS', false)) {
        return;
    }

    echo "\n" . str_repeat('=', 80) . "\n";
    echo "ASSISTANT SERVICE PERFORMANCE REPORT\n";
    echo str_repeat('=', 80) . "\n";

    foreach ($performanceMetrics as $testName => $metrics) {
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
