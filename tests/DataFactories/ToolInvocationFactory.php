<?php

declare(strict_types=1);

namespace CreativeCrafts\LaravelAiAssistant\Tests\DataFactories;

/**
 * Factory for creating test ToolInvocation instances and data
 */
final class ToolInvocationFactory
{
    /**
     * Create a tool invocation data array
     */
    public static function create(array $overrides = []): array
    {
        return array_merge([
            'id' => self::generateId(),
            'response_id' => self::generateId(),
            'name' => self::randomToolName(),
            'arguments' => self::sampleArguments(),
            'state' => self::randomState(),
            'result_summary' => self::sampleResultSummary(),
            'created_at' => now()->toDateTimeString(),
            'updated_at' => now()->toDateTimeString(),
        ], $overrides);
    }

    /**
     * Create multiple tool invocations
     */
    public static function createMultiple(int $count, array $overrides = []): array
    {
        $invocations = [];
        for ($i = 0; $i < $count; $i++) {
            $invocations[] = self::create($overrides);
        }
        return $invocations;
    }

    /**
     * Create tool invocation for specific response
     */
    public static function forResponse(string $responseId, array $overrides = []): array
    {
        return self::create(array_merge([
            'response_id' => $responseId,
        ], $overrides));
    }

    /**
     * Create pending tool invocation
     */
    public static function pending(string $toolName, array $arguments = [], array $overrides = []): array
    {
        return self::create(array_merge([
            'name' => $toolName,
            'arguments' => $arguments,
            'state' => 'pending',
            'result_summary' => null,
        ], $overrides));
    }

    /**
     * Create completed tool invocation
     */
    public static function completed(string $toolName, array $result = [], array $overrides = []): array
    {
        return self::create(array_merge([
            'name' => $toolName,
            'state' => 'completed',
            'result_summary' => $result ?: self::sampleSuccessResult(),
        ], $overrides));
    }

    /**
     * Create failed tool invocation
     */
    public static function failed(string $toolName, string $error = 'Tool execution failed', array $overrides = []): array
    {
        return self::create(array_merge([
            'name' => $toolName,
            'state' => 'failed',
            'result_summary' => [
                'success' => false,
                'error' => $error,
                'timestamp' => now()->toISOString(),
            ],
        ], $overrides));
    }

    /**
     * Create function call tool invocation
     */
    public static function functionCall(string $functionName, array $parameters = [], array $overrides = []): array
    {
        return self::create(array_merge([
            'name' => $functionName,
            'arguments' => $parameters,
            'state' => 'pending',
        ], $overrides));
    }

    /**
     * Create web search tool invocation
     */
    public static function webSearch(string $query, array $overrides = []): array
    {
        return self::create(array_merge([
            'name' => 'web_search',
            'arguments' => ['query' => $query],
            'state' => 'completed',
            'result_summary' => [
                'success' => true,
                'results_count' => random_int(1, 10),
                'query' => $query,
                'timestamp' => now()->toISOString(),
            ],
        ], $overrides));
    }

    /**
     * Create code execution tool invocation
     */
    public static function codeExecution(string $code, string $language = 'python', array $overrides = []): array
    {
        return self::create(array_merge([
            'name' => 'code_interpreter',
            'arguments' => [
                'code' => $code,
                'language' => $language,
            ],
            'state' => 'completed',
            'result_summary' => [
                'success' => true,
                'output' => 'Code executed successfully',
                'execution_time' => random_int(100, 5000) . 'ms',
                'timestamp' => now()->toISOString(),
            ],
        ], $overrides));
    }

    /**
     * Generate a unique ID
     */
    private static function generateId(): string
    {
        return 'tool_' . uniqid('', true) . '_' . random_int(1000, 9999);
    }

    /**
     * Get random tool name
     */
    private static function randomToolName(): string
    {
        $tools = [
            'web_search',
            'code_interpreter',
            'file_reader',
            'calculator',
            'weather_check',
            'data_analyzer',
            'image_generator',
            'text_translator',
        ];
        return $tools[array_rand($tools)];
    }

    /**
     * Get random state
     */
    private static function randomState(): string
    {
        $states = ['pending', 'running', 'completed', 'failed', 'cancelled'];
        return $states[array_rand($states)];
    }

    /**
     * Generate sample arguments
     */
    private static function sampleArguments(): array
    {
        $argumentSets = [
            ['query' => 'sample search query'],
            ['code' => 'print("Hello World")', 'language' => 'python'],
            ['file_path' => '/tmp/sample.txt'],
            ['expression' => '2 + 2'],
            ['location' => 'New York'],
            ['data' => [1, 2, 3, 4, 5]],
            ['prompt' => 'Generate an image of a sunset'],
            ['text' => 'Hello world', 'target_language' => 'spanish'],
        ];

        return $argumentSets[array_rand($argumentSets)];
    }

    /**
     * Generate sample result summary
     */
    private static function sampleResultSummary(): ?array
    {
        $hasResult = random_int(0, 1);

        if (!$hasResult) {
            return null;
        }

        return self::sampleSuccessResult();
    }

    /**
     * Generate sample success result
     */
    private static function sampleSuccessResult(): array
    {
        $results = [
            [
                'success' => true,
                'output' => 'Tool executed successfully',
                'timestamp' => now()->toISOString(),
            ],
            [
                'success' => true,
                'results_count' => random_int(1, 20),
                'processing_time' => random_int(100, 2000) . 'ms',
                'timestamp' => now()->toISOString(),
            ],
            [
                'success' => true,
                'data' => ['result' => 'processed'],
                'status' => 'completed',
                'timestamp' => now()->toISOString(),
            ],
        ];

        return $results[array_rand($results)];
    }
}
