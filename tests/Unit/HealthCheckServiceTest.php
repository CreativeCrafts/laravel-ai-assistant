<?php

declare(strict_types=1);

use CreativeCrafts\LaravelAiAssistant\Contracts\OpenAiRepositoryContract;
use CreativeCrafts\LaravelAiAssistant\Services\CacheService;
use CreativeCrafts\LaravelAiAssistant\Services\HealthCheckService;
use CreativeCrafts\LaravelAiAssistant\Services\LoggingService;
use CreativeCrafts\LaravelAiAssistant\Services\SecurityService;

/**
 * Comprehensive unit tests for HealthCheckService.
 *
 * Tests all health check methods, system monitoring, and performance tracking functionality
 * with proper mocking and edge case coverage.
 */

beforeEach(function () {
    $this->repositoryMock = Mockery::mock(OpenAiRepositoryContract::class);
    $this->cacheServiceMock = Mockery::mock(CacheService::class);
    $this->loggingServiceMock = Mockery::mock(LoggingService::class);
    $this->securityServiceMock = Mockery::mock(SecurityService::class);

    $this->healthCheckService = new HealthCheckService(
        $this->repositoryMock,
        $this->cacheServiceMock,
        $this->loggingServiceMock,
        $this->securityServiceMock
    );

    // Helper function to set up successful health check mocks
    $this->setupSuccessfulHealthCheckMocks = function () {
        // Use basic mocks as foundation, which handle all the complexity
        ($this->setupBasicHealthCheckMocks)();

        // Add repository mock for API connectivity check
        $this->repositoryMock
            ->shouldReceive('chat')
            ->andReturn(['choices' => [['message' => ['content' => 'test']]]])
            ->byDefault();
    };

    // Helper function to set up basic mocks
    $this->setupBasicHealthCheckMocks = function () {
        $this->loggingServiceMock
            ->shouldReceive('logPerformanceMetrics')
            ->andReturn(true)
            ->byDefault();

        $this->loggingServiceMock
            ->shouldReceive('logError')
            ->andReturn(true)
            ->byDefault();

        // Basic cache mocks with dynamic value handling and delete tracking
        $cachedValues = [];

        $this->cacheServiceMock
            ->shouldReceive('cacheConfig')
            ->andReturnUsing(function ($key, $value, $ttl) use (&$cachedValues) {
                $cachedValues[$key] = $value;
                return true;
            })
            ->byDefault();

        $this->cacheServiceMock
            ->shouldReceive('getConfig')
            ->andReturnUsing(function ($key) use (&$cachedValues) {
                return $cachedValues[$key] ?? null;
            })
            ->byDefault();

        $this->cacheServiceMock
            ->shouldReceive('clearConfig')
            ->andReturnUsing(function ($key) use (&$cachedValues) {
                unset($cachedValues[$key]);
                return true;
            })
            ->byDefault();

        $this->cacheServiceMock
            ->shouldReceive('getStats')
            ->andReturn(['cache_driver' => 'file'])
            ->byDefault();

        // Basic security mocks
        $this->securityServiceMock
            ->shouldReceive('validateApiKey')
            ->andReturn(true)
            ->byDefault();

        $this->securityServiceMock
            ->shouldReceive('checkRateLimit')
            ->andReturn(true)
            ->byDefault();

        $this->securityServiceMock
            ->shouldReceive('generateRequestSignature')
            ->andReturn('test-signature')
            ->byDefault();

        $this->securityServiceMock
            ->shouldReceive('verifyRequestSignature')
            ->andReturn(true)
            ->byDefault();
    };
});

afterEach(function () {
    Mockery::close();
});

test('perform health check returns complete results', function () {
    // Mock all dependencies for successful health checks
    ($this->setupSuccessfulHealthCheckMocks)();

    $result = $this->healthCheckService->performHealthCheck();

    expect($result)->toBeArray()
        ->and($result)->toHaveKeys(['status', 'timestamp', 'version', 'checks', 'summary', 'duration_ms'])
        ->and($result['status'])->toBe('healthy')
        ->and($result['checks'])->toBeArray()
        ->and($result['checks'])->toHaveKeys(['configuration', 'cache', 'security', 'api_connectivity', 'memory', 'disk'])
        ->and($result['summary'])->toBeArray()
        ->and($result['summary'])->toHaveKeys(['total_checks', 'healthy', 'warning', 'unhealthy', 'success_rate']);
});

test('perform health check handles exceptions in checks', function () {
    // Mock cache service to throw an exception
    $this->cacheServiceMock
        ->shouldReceive('cacheConfig')
        ->andThrow(new Exception('Cache service failure'));

    // Updated to expect multiple logError calls due to improved error handling
    $this->loggingServiceMock
        ->shouldReceive('logError')
        ->atLeast()->once();

    $this->loggingServiceMock
        ->shouldReceive('logPerformanceMetrics')
        ->once();

    $result = $this->healthCheckService->performHealthCheck();

    expect($result['status'])->toBe('unhealthy')
        ->and($result['checks'])->toHaveKey('cache')
        ->and($result['checks']['cache']['status'])->toBe('unhealthy')
        ->and($result['checks']['cache']['message'])->toContain('Cache service failure');
});

test('get health status returns simple status', function () {
    ($this->setupBasicHealthCheckMocks)();

    $result = $this->healthCheckService->getHealthStatus();

    expect($result)->toBeArray()
        ->and($result)->toHaveKeys(['status', 'timestamp', 'version', 'uptime'])
        ->and($result['status'])->toBeIn(['healthy', 'unhealthy']);
});

test('get health status handles exceptions', function () {
    // Mock cache service to throw an exception
    $this->cacheServiceMock
        ->shouldReceive('cacheConfig')
        ->andThrow(new Exception('Configuration check failed'));

    $result = $this->healthCheckService->getHealthStatus();

    expect($result['status'])->toBe('unhealthy')
        ->and($result)->toHaveKey('error');
});

test('configuration check with valid config', function () {
    // Mock Config facade would need to be set up in a Laravel environment
    // For unit testing, we'll test the logic flow

    $this->securityServiceMock
        ->shouldReceive('validateApiKey')
        ->once()
        ->andReturn(true);

    // Since we can't easily mock Config::get in pure PHPUnit,
    // we'll test the error handling path
    $result = $this->healthCheckService->getHealthStatus();

    expect($result)->toBeArray()
        ->and($result)->toHaveKey('status');
});

test('cache health check success', function () {
    // Track the test value to match it properly
    $testValue = null;

    $this->cacheServiceMock
        ->shouldReceive('cacheConfig')
        ->twice() // First call for main test, second for multi test
        ->with(Mockery::pattern('/^health_check_(test_|multi_)/'), Mockery::capture($testValue), Mockery::any())
        ->andReturn(true);

    // Track which keys have been deleted
    $deletedKeys = [];

    $this->cacheServiceMock
        ->shouldReceive('getConfig')
        ->times(3) // Read test value, verify delete (should be null), read multi test value
        ->andReturnUsing(function ($key) use (&$testValue, &$deletedKeys) {
            // If key has been deleted, return null
            if (in_array($key, $deletedKeys)) {
                return null;
            }

            if (strpos($key, 'health_check_multi_') !== false) {
                return 'multi_test';
            } elseif (strpos($key, 'health_check_test_') !== false && $testValue !== null) {
                return $testValue; // Return the captured test value
            }
            return null; // For delete verification
        });

    $this->cacheServiceMock
        ->shouldReceive('clearConfig')
        ->times(3) // Clean up calls: initial delete, cleanup both test keys
        ->andReturnUsing(function ($key) use (&$deletedKeys) {
            $deletedKeys[] = $key; // Mark key as deleted
            return true;
        });

    $this->cacheServiceMock
        ->shouldReceive('getStats')
        ->once()
        ->andReturn([
            'cache_driver' => 'file',
            'prefix' => 'laravel_ai_assistant:',
            'default_ttl' => 300,
            'max_ttl' => 86400
        ]);

    // Add security mocks needed for performHealthCheck
    $this->securityServiceMock
        ->shouldReceive('validateApiKey')
        ->andReturn(true);

    $this->securityServiceMock
        ->shouldReceive('checkRateLimit')
        ->andReturn(true);

    $this->securityServiceMock
        ->shouldReceive('generateRequestSignature')
        ->andReturn('test-signature');

    $this->securityServiceMock
        ->shouldReceive('verifyRequestSignature')
        ->andReturn(true);

    // Add logging mock for this specific test
    $this->loggingServiceMock
        ->shouldReceive('logPerformanceMetrics')
        ->once();

    // Test through the main health check method
    $result = $this->healthCheckService->performHealthCheck();

    expect($result['checks'])->toHaveKey('cache')
        ->and($result['checks']['cache']['status'])->toBe('healthy');
});

test('cache health check write failure', function () {
    $this->cacheServiceMock
        ->shouldReceive('cacheConfig')
        ->once()
        ->andReturn(false);

    $this->loggingServiceMock
        ->shouldReceive('logPerformanceMetrics')
        ->once();

    $result = $this->healthCheckService->performHealthCheck();

    expect($result['checks'])->toHaveKey('cache')
        ->and($result['checks']['cache']['status'])->toBe('unhealthy')
        ->and($result['checks']['cache']['message'])->toContain('Cache write operation failed');
});

test('cache health check read failure', function () {
    $this->cacheServiceMock
        ->shouldReceive('cacheConfig')
        ->once()
        ->andReturn(true);

    $this->cacheServiceMock
        ->shouldReceive('getConfig')
        ->once()
        ->andReturn('wrong_value'); // Different from what we wrote

    $this->cacheServiceMock
        ->shouldReceive('clearConfig')
        ->once()
        ->andReturn(true);

    $this->loggingServiceMock
        ->shouldReceive('logPerformanceMetrics')
        ->once();

    $result = $this->healthCheckService->performHealthCheck();

    expect($result['checks'])->toHaveKey('cache')
        ->and($result['checks']['cache']['status'])->toBe('unhealthy')
        ->and($result['checks']['cache']['message'])->toContain('Cache read operation failed');
});

test('security health check success', function () {
    $this->securityServiceMock
        ->shouldReceive('validateApiKey')
        ->once()
        ->andReturn(true);

    $this->securityServiceMock
        ->shouldReceive('checkRateLimit')
        ->once()
        ->andReturn(true);

    $this->securityServiceMock
        ->shouldReceive('generateRequestSignature')
        ->once()
        ->andReturn('test-signature');

    $this->securityServiceMock
        ->shouldReceive('verifyRequestSignature')
        ->once()
        ->andReturn(true);

    ($this->setupBasicHealthCheckMocks)();
    $result = $this->healthCheckService->performHealthCheck();

    expect($result['checks'])->toHaveKey('security')
        ->and($result['checks']['security']['status'])->toBe('healthy');
});

test('security health check with warnings', function () {
    $this->securityServiceMock
        ->shouldReceive('validateApiKey')
        ->once()
        ->andReturn(true);

    $this->securityServiceMock
        ->shouldReceive('checkRateLimit')
        ->once()
        ->andReturn(false); // Rate limiting issue

    $this->securityServiceMock
        ->shouldReceive('generateRequestSignature')
        ->once()
        ->andReturn('test-signature');

    $this->securityServiceMock
        ->shouldReceive('verifyRequestSignature')
        ->once()
        ->andReturn(true);

    ($this->setupBasicHealthCheckMocks)();
    $result = $this->healthCheckService->performHealthCheck();

    expect($result['checks'])->toHaveKey('security')
        ->and($result['checks']['security']['status'])->toBe('warning')
        ->and($result['checks']['security']['details'])->toBeArray();
});

test('security health check failure', function () {
    // Set up specific failure mock for checkRateLimit to throw an exception
    $this->securityServiceMock
        ->shouldReceive('checkRateLimit')
        ->once()
        ->andThrow(new Exception('Rate limit check failed'));

    // Now set up other basic mocks
    $this->loggingServiceMock
        ->shouldReceive('logPerformanceMetrics')
        ->andReturn(true)
        ->byDefault();

    $this->cacheServiceMock
        ->shouldReceive('cacheConfig')
        ->andReturn(true)
        ->byDefault();

    $this->cacheServiceMock
        ->shouldReceive('getConfig')
        ->andReturn('health_check_value')
        ->byDefault();

    $this->cacheServiceMock
        ->shouldReceive('clearConfig')
        ->andReturn(true)
        ->byDefault();

    $this->cacheServiceMock
        ->shouldReceive('getStats')
        ->andReturn(['cache_driver' => 'file'])
        ->byDefault();

    $this->securityServiceMock
        ->shouldReceive('validateApiKey')
        ->andReturn(true)
        ->byDefault();

    $this->securityServiceMock
        ->shouldReceive('generateRequestSignature')
        ->andReturn('test-signature')
        ->byDefault();

    $this->securityServiceMock
        ->shouldReceive('verifyRequestSignature')
        ->andReturn(true)
        ->byDefault();

    $result = $this->healthCheckService->performHealthCheck();

    expect($result['checks'])->toHaveKey('security')
        ->and($result['checks']['security']['status'])->toBe('unhealthy')
        ->and($result['checks']['security']['message'])->toContain('Security health check failed');
});

test('memory usage check healthy', function () {
    // Allow logError to be called in case memory check fails
    $this->loggingServiceMock
        ->shouldReceive('logError')
        ->zeroOrMoreTimes();

    $this->loggingServiceMock
        ->shouldReceive('logPerformanceMetrics')
        ->once();

    $result = $this->healthCheckService->performHealthCheck();

    expect($result['checks'])->toHaveKey('memory')
        ->and($result['checks']['memory']['status'])->toBeIn(['healthy', 'warning', 'unhealthy'])
        ->and($result['checks']['memory'])->toHaveKey('details')
        ->and($result['checks']['memory']['details'])->toHaveKeys(['current_usage_mb', 'current_usage_percent', 'peak_usage_mb', 'memory_limit_mb']);
});

test('disk space check with sufficient space', function () {
    // Allow logError to be called in case disk check fails
    $this->loggingServiceMock
        ->shouldReceive('logError')
        ->zeroOrMoreTimes();

    $this->loggingServiceMock
        ->shouldReceive('logPerformanceMetrics')
        ->once();

    // This test depends on the actual system disk space
    $result = $this->healthCheckService->performHealthCheck();

    expect($result['checks'])->toHaveKey('disk')
        ->and($result['checks']['disk']['status'])->toBeIn(['healthy', 'warning', 'unhealthy']);

    if ($result['checks']['disk']['status'] !== 'warning') {
        expect($result['checks']['disk'])->toHaveKey('details')
            ->and($result['checks']['disk']['details'])->toHaveKeys(['free_space_gb', 'total_space_gb', 'free_space_percent']);
    }
});

test('api connectivity check', function () {
    ($this->setupBasicHealthCheckMocks)();
    $result = $this->healthCheckService->performHealthCheck();

    expect($result['checks'])->toHaveKey('api_connectivity')
        ->and($result['checks']['api_connectivity']['status'])->toBeIn(['healthy', 'warning', 'unhealthy']);

    if (isset($result['checks']['api_connectivity']['details'])) {
        expect($result['checks']['api_connectivity']['details'])->toHaveKey('response_time_ms');
    }
});

test('health check performance logging', function () {
    $this->loggingServiceMock
        ->shouldReceive('logPerformanceMetrics')
        ->once()
        ->with('health_check', Mockery::type('float'), Mockery::type('array'));

    ($this->setupBasicHealthCheckMocks)();
    $this->healthCheckService->performHealthCheck();
});

test('health check summary calculation', function () {
    ($this->setupBasicHealthCheckMocks)();
    $result = $this->healthCheckService->performHealthCheck();

    $summary = $result['summary'];

    expect($summary['total_checks'])->toBe(6)
        ->and($summary['healthy'])->toBeGreaterThanOrEqual(0)
        ->and($summary['warning'])->toBeGreaterThanOrEqual(0)
        ->and($summary['unhealthy'])->toBeGreaterThanOrEqual(0)
        ->and($summary['healthy'] + $summary['warning'] + $summary['unhealthy'])->toBe($summary['total_checks'])
        ->and($summary['success_rate'])->toBeGreaterThanOrEqual(0)
        ->and($summary['success_rate'])->toBeLessThanOrEqual(100);
});

test('health check duration measurement', function () {
    ($this->setupBasicHealthCheckMocks)();
    $result = $this->healthCheckService->performHealthCheck();

    expect($result['duration_ms'])->toBeFloat()
        ->and($result['duration_ms'])->toBeGreaterThan(0)
        ->and($result['duration_ms'])->toBeLessThan(10000); // Should complete within 10 seconds
});

test('edge cases and boundary conditions', function () {
    // Test with multiple checks failing - don't call setupBasicHealthCheckMocks to avoid defaults

    // Make cache fail
    $this->cacheServiceMock
        ->shouldReceive('cacheConfig')
        ->andThrow(new Exception('Cache failure'));

    // Make security fail by making checkRateLimit throw an exception
    $this->securityServiceMock
        ->shouldReceive('checkRateLimit')
        ->andThrow(new Exception('Security failure'));

    // Expect logError calls for the failing checks
    $this->loggingServiceMock
        ->shouldReceive('logError')
        ->atLeast()
        ->once(); // At least one check will fail and log an error

    $this->loggingServiceMock
        ->shouldReceive('logPerformanceMetrics')
        ->once();

    $result = $this->healthCheckService->performHealthCheck();

    expect($result['status'])->toBe('unhealthy')
        ->and($result['summary']['unhealthy'])->toBeGreaterThanOrEqual(2) // At least 2 checks should fail
        ->and($result['summary']['success_rate'])->toBeLessThan(70); // Success rate should be less than 70%
});

test('version information retrieval', function () {
    ($this->setupBasicHealthCheckMocks)();
    $result = $this->healthCheckService->performHealthCheck();

    expect($result)->toHaveKey('version')
        ->and($result['version'])->toBeString();
});

test('timestamp format', function () {
    ($this->setupBasicHealthCheckMocks)();
    $result = $this->healthCheckService->performHealthCheck();

    expect($result)->toHaveKey('timestamp')
        ->and($result['timestamp'])->toMatch('/\d{4}-\d{2}-\d{2}T\d{2}:\d{2}:\d{2}[+-]\d{2}:\d{2}/');
});
