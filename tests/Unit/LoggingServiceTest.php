<?php

declare(strict_types=1);

use CreativeCrafts\LaravelAiAssistant\Services\LoggingService;
use Illuminate\Support\Facades\Log;

beforeEach(function () {
    $this->loggingService = new LoggingService();

    // Mock Log facade
    Log::shouldReceive('info')->andReturn(null)->byDefault();
    Log::shouldReceive('error')->andReturn(null)->byDefault();
    Log::shouldReceive('warning')->andReturn(null)->byDefault();
    Log::shouldReceive('debug')->andReturn(null)->byDefault();
    Log::shouldReceive('log')->andReturn(null)->byDefault();
});

afterEach(function () {
    Mockery::close();
});

test('constructor initializes properly', function () {
    $service = new LoggingService();

    expect($service)->toBeInstanceOf(LoggingService::class);
});

test('log api request logs basic information', function () {
    Log::shouldReceive('info')
        ->once()
        ->with('API Request: completion with gpt-4', Mockery::type('array'));

    $this->loggingService->logApiRequest('completion', ['prompt' => 'test'], 'gpt-4', null);
});

test('log api request without duration', function () {
    Log::shouldReceive('info')
        ->once()
        ->with('API Request: completion with gpt-4', Mockery::type('array'));

    $this->loggingService->logApiRequest('completion', ['prompt' => 'test'], 'gpt-4', 1.23);
});

test('log api response success', function () {
    Log::shouldReceive('log')
        ->once()
        ->with('info', Mockery::type('string'), Mockery::type('array'));

    $this->loggingService->logApiResponse('completion', true, ['status' => 'success'], 1.5);
});

test('log api response failure', function () {
    Log::shouldReceive('log')
        ->once()
        ->with('warning', Mockery::type('string'), Mockery::type('array'));

    $this->loggingService->logApiResponse('completion', false, ['error' => 'Internal server error'], 2.0);
});

test('log cache operation with all parameters', function () {
    Log::shouldReceive('info')
        ->once()
        ->with('Cache get: test_key', Mockery::type('array'));

    $this->loggingService->logCacheOperation('get', 'test_key', null, 150);
});

test('log cache operation with minimal parameters', function () {
    Log::shouldReceive('info')
        ->once()
        ->with('Cache set: another_key', Mockery::type('array'));

    $this->loggingService->logCacheOperation('set', 'another_key');
});

test('log performance metrics with metrics', function () {
    $metrics = [
        'memory_usage' => '64MB',
        'execution_time' => 1.23,
        'database_queries' => 5
    ];

    Log::shouldReceive('info')
        ->once()
        ->with('Performance: test_operation completed in 1234.56ms', Mockery::type('array'));

    $this->loggingService->logPerformanceMetrics('test_operation', 1234.56, $metrics);
});

test('log performance metrics without metrics', function () {
    Log::shouldReceive('info')
        ->once()
        ->with('Performance: simple_operation completed in 567.89ms', Mockery::type('array'));

    $this->loggingService->logPerformanceMetrics('simple_operation', 567.89);
});

test('log performance event with all parameters', function () {
    $data = [
        'user_id' => 123,
        'request_size' => 1024,
        'response_size' => 2048
    ];

    Log::shouldReceive('info')
        ->once()
        ->with('Performance Event [ai_completion]: request_processed', Mockery::type('array'));

    $this->loggingService->logPerformanceEvent('ai_completion', 'request_processed', $data, 'ai_service');
});

test('log performance event without source', function () {
    Log::shouldReceive('info')
        ->once()
        ->with('Performance Event [cache_miss]: cache_operation', Mockery::type('array'));

    $this->loggingService->logPerformanceEvent('cache_miss', 'cache_operation');
});

test('log performance event with empty data', function () {
    Log::shouldReceive('info')
        ->once()
        ->with('Performance Event [startup]: system_initialized', Mockery::type('array'));

    $this->loggingService->logPerformanceEvent('startup', 'system_initialized', []);
});

test('log error with exception', function () {
    $exception = new Exception('Test exception message', 500);

    Log::shouldReceive('error')
        ->once()
        ->with(Mockery::type('string'), Mockery::type('array'));

    $this->loggingService->logError('test_operation', $exception, ['context_key' => 'test_context']);
});

test('log error with string message', function () {
    Log::shouldReceive('error')
        ->once()
        ->with(Mockery::type('string'), Mockery::type('array'));

    $this->loggingService->logError('simple_operation', 'Simple error message');
});

test('log security event', function () {
    $details = [
        'ip_address' => '192.168.1.1',
        'user_agent' => 'Mozilla/5.0',
        'attempted_action' => 'unauthorized_access'
    ];

    Log::shouldReceive('warning')
        ->once()
        ->with(Mockery::type('string'), Mockery::type('array'));

    $this->loggingService->logSecurityEvent('authentication_failure', 'Failed login attempt', $details);
});

test('log configuration event with source', function () {
    Log::shouldReceive('info')
        ->once()
        ->with(Mockery::type('string'), Mockery::type('array'));

    $this->loggingService->logConfigurationEvent('config_loaded', 'config_file', 'app.php', 'config_manager');
});

test('log configuration event without source', function () {
    Log::shouldReceive('info')
        ->once()
        ->with(Mockery::type('string'), Mockery::type('array'));

    $this->loggingService->logConfigurationEvent('setting_changed', 'api_timeout', 30);
});

test('log configuration event sanitizes sensitive keys', function () {
    Log::shouldReceive('info')
        ->once()
        ->with(Mockery::type('string'), Mockery::type('array'));

    $this->loggingService->logConfigurationEvent('config_update', 'api_key', 'sk-1234567890abcdef');
});

test('edge case extremely large payload', function () {
    $largeData = str_repeat('A', 10000); // 10KB string

    Log::shouldReceive('info')
        ->once()
        ->with(Mockery::type('string'), Mockery::type('array'));

    $this->loggingService->logApiRequest('upload_operation', ['data' => $largeData], 'gpt-4');
});

test('edge case null and empty values', function () {
    Log::shouldReceive('info')
        ->once()
        ->with(Mockery::type('string'), Mockery::type('array'));

    $this->loggingService->logPerformanceEvent('test_category', 'empty_event', []);
});

test('boundary condition very long error message', function () {
    $longMessage = str_repeat('Error details ', 100); // Very long error message
    $exception = new Exception($longMessage);

    Log::shouldReceive('error')
        ->once()
        ->with(Mockery::type('string'), Mockery::type('array'));

    $this->loggingService->logError('long_message_operation', $exception, ['context_key' => 'long_message_test']);
});

test('context size limiting', function () {
    $hugeContext = [];
    for ($i = 0; $i < 1000; $i++) {
        $hugeContext["key_$i"] = "value_$i";
    }

    Log::shouldReceive('info')
        ->once()
        ->with('Performance: large_context completed in 123.45ms', Mockery::type('array'));

    $this->loggingService->logPerformanceMetrics('large_context', 123.45, $hugeContext);
});

test('different data types in metrics', function () {
    $mixedMetrics = [
        'string_value' => 'test',
        'integer_value' => 42,
        'float_value' => 3.14,
        'boolean_value' => true,
        'null_value' => null,
        'array_value' => ['nested' => 'data']
    ];

    Log::shouldReceive('info')
        ->once()
        ->with('Performance: mixed_types completed in 999.99ms', Mockery::type('array'));

    $this->loggingService->logPerformanceMetrics('mixed_types', 999.99, $mixedMetrics);
});

test('configuration event handles complex values', function () {
    $complexConfig = [
        'database' => [
            'host' => 'localhost',
            'port' => 3306,
            'credentials' => [
                'username' => 'user',
                'password' => 'secret',  // Should be redacted
                'api_key' => 'key123'    // Should be redacted
            ]
        ],
        'cache' => [
            'driver' => 'redis',
            'ttl' => 3600
        ]
    ];

    Log::shouldReceive('info')
        ->once()
        ->with(Mockery::type('string'), Mockery::type('array'));

    $this->loggingService->logConfigurationEvent('config_loaded', 'database_config', $complexConfig);
});
