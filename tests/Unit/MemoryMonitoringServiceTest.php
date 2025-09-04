<?php

declare(strict_types=1);

use CreativeCrafts\LaravelAiAssistant\Services\LoggingService;
use CreativeCrafts\LaravelAiAssistant\Services\MemoryMonitoringService;

beforeEach(function () {
    $this->loggingServiceMock = Mockery::mock(LoggingService::class);
    $this->service = new MemoryMonitoringService($this->loggingServiceMock);
});

afterEach(function () {
    Mockery::close();
});

test('constructor initializes properly', function () {
    $service = new MemoryMonitoringService($this->loggingServiceMock);

    expect($service)->toBeInstanceOf(MemoryMonitoringService::class);
});

test('start monitoring returns checkpoint id', function () {
    $this->loggingServiceMock
        ->shouldReceive('logPerformanceEvent')
        ->once();

    $checkpointId = $this->service->startMonitoring('test_operation');

    expect($checkpointId)->toBeString()
        ->and($checkpointId)->not->toBeEmpty();
});

test('update monitoring with valid checkpoint', function () {
    $this->loggingServiceMock
        ->shouldReceive('logPerformanceEvent')
        ->twice(); // Once from startMonitoring, once from updateMonitoring

    $checkpointId = $this->service->startMonitoring('test_operation');

    $this->service->updateMonitoring($checkpointId, 'processing');

    // Should not throw any exception
    expect(true)->toBeTrue();
});

test('update monitoring with invalid checkpoint does nothing', function () {
    $this->loggingServiceMock
        ->shouldReceive('logPerformanceEvent')
        ->never();

    $this->service->updateMonitoring('invalid_checkpoint', 'processing');

    // Should not throw any exception
    expect(true)->toBeTrue();
});

test('end monitoring returns statistics', function () {
    $this->loggingServiceMock
        ->shouldReceive('logPerformanceEvent')
        ->times(2); // Once for memory alert, once for usage logging

    $checkpointId = $this->service->startMonitoring('test_operation');

    $stats = $this->service->endMonitoring($checkpointId);

    expect($stats)->toBeArray()
        ->and($stats)->toHaveKeys(['operation', 'duration_seconds', 'initial_memory_mb', 'final_memory_mb', 'peak_memory_mb', 'memory_delta_mb'])
        ->and($stats['operation'])->toBe('test_operation');
});

test('end monitoring with invalid checkpoint returns empty array', function () {
    $stats = $this->service->endMonitoring('invalid_checkpoint');

    expect($stats)->toBeArray()
        ->and($stats)->toBeEmpty();
});

test('get current memory usage returns float', function () {
    $usage = $this->service->getCurrentMemoryUsage();

    expect($usage)->toBeFloat()
        ->and($usage)->toBeGreaterThan(0);
});

test('get peak memory usage returns float', function () {
    $usage = $this->service->getPeakMemoryUsage();

    expect($usage)->toBeFloat()
        ->and($usage)->toBeGreaterThan(0);
});

test('get memory usage percentage returns valid percentage', function () {
    $percentage = $this->service->getMemoryUsagePercentage();

    expect($percentage)->toBeFloat()
        ->and($percentage)->toBeGreaterThanOrEqual(0)
        ->and($percentage)->toBeLessThanOrEqual(100);
});

test('is threshold exceeded returns boolean', function () {
    $result = $this->service->isThresholdExceeded(90.0);

    expect($result)->toBeBool();
});

test('force garbage collection returns memory stats', function () {
    $memoryBefore = $this->service->getCurrentMemoryUsage();
    $stats = $this->service->forceGarbageCollection();
    $memoryAfter = $this->service->getCurrentMemoryUsage();

    expect($stats)->toBeArray()
        ->and($stats)->toHaveKeys(['memory_before_mb', 'memory_after_mb', 'memory_freed_mb']);
});

test('monitoring handles zero duration gracefully', function () {
    $this->loggingServiceMock
        ->shouldReceive('logPerformanceEvent')
        ->times(2);

    $checkpointId = $this->service->startMonitoring('instant_operation');

    // End immediately to test zero duration handling
    $stats = $this->service->endMonitoring($checkpointId);

    expect($stats)->toBeArray()
        ->and($stats['duration_seconds'])->toBeGreaterThanOrEqual(0);
});

test('monitoring full lifecycle', function () {
    $this->loggingServiceMock
        ->shouldReceive('logPerformanceEvent')
        ->times(3); // start, update, end

    $checkpointId = $this->service->startMonitoring('full_lifecycle_test');

    $this->service->updateMonitoring($checkpointId, 'step_1');

    $stats = $this->service->endMonitoring($checkpointId);

    expect($stats)->toBeArray()
        ->and($stats['operation'])->toBe('full_lifecycle_test')
        ->and($stats['duration_seconds'])->toBeGreaterThanOrEqual(0);
});

test('multiple concurrent monitoring sessions', function () {
    $this->loggingServiceMock
        ->shouldReceive('logPerformanceEvent')
        ->times(4); // 2 starts, 2 ends

    $checkpoint1 = $this->service->startMonitoring('operation_1');
    $checkpoint2 = $this->service->startMonitoring('operation_2');

    $stats1 = $this->service->endMonitoring($checkpoint1);
    $stats2 = $this->service->endMonitoring($checkpoint2);

    expect($stats1)->toBeArray()
        ->and($stats2)->toBeArray()
        ->and($stats1['operation'])->toBe('operation_1')
        ->and($stats2['operation'])->toBe('operation_2');
});

test('memory threshold detection', function () {
    // Test with a very high threshold that should not be exceeded
    $highThreshold = 99.9;
    $highThresholdExceeded = $this->service->isThresholdExceeded($highThreshold);

    // Test with a very low threshold that should be exceeded
    $lowThreshold = 0.1;
    $lowThresholdExceeded = $this->service->isThresholdExceeded($lowThreshold);

    expect($highThresholdExceeded)->toBeBool()
        ->and($lowThresholdExceeded)->toBeBool()
        ->and($lowThresholdExceeded)->toBeTrue(); // Should be exceeded with very low threshold
});

test('garbage collection effectiveness', function () {
    // Create some memory usage first
    $largeArray = range(1, 10000);

    $memoryBefore = $this->service->getCurrentMemoryUsage();
    $stats = $this->service->forceGarbageCollection();
    $memoryAfter = $this->service->getCurrentMemoryUsage();

    expect($stats)->toBeArray()
        ->and($stats)->toHaveKeys(['memory_before_mb', 'memory_after_mb', 'memory_freed_mb'])
        ->and($stats['memory_before_mb'])->toBeFloat()
        ->and($stats['memory_after_mb'])->toBeFloat()
        ->and($stats['memory_freed_mb'])->toBeFloat()
        ->and($stats['memory_freed_mb'])->toBeGreaterThanOrEqual(0);
});

test('edge case empty operation name', function () {
    $this->loggingServiceMock
        ->shouldReceive('logPerformanceEvent')
        ->times(2);

    $checkpointId = $this->service->startMonitoring('');
    $stats = $this->service->endMonitoring($checkpointId);

    expect($stats)->toBeArray()
        ->and($stats['operation'])->toBe('');
});

test('edge case very long operation name', function () {
    $longName = str_repeat('a', 1000);

    $this->loggingServiceMock
        ->shouldReceive('logPerformanceEvent')
        ->times(2);

    $checkpointId = $this->service->startMonitoring($longName);
    $stats = $this->service->endMonitoring($checkpointId);

    expect($stats)->toBeArray()
        ->and($stats['operation'])->toBe($longName);
});

test('boundary condition rapid monitoring cycles', function () {
    $this->loggingServiceMock
        ->shouldReceive('logPerformanceEvent')
        ->times(10); // 5 cycles * 2 calls each

    $checkpoints = [];

    // Start multiple monitoring sessions rapidly
    for ($i = 0; $i < 5; $i++) {
        $checkpoints[] = $this->service->startMonitoring("rapid_cycle_$i");
    }

    // End all sessions
    foreach ($checkpoints as $checkpoint) {
        $stats = $this->service->endMonitoring($checkpoint);
        expect($stats)->toBeArray()
            ->and($stats)->toHaveKey('operation');
    }
});

test('memory usage consistency', function () {
    $usage1 = $this->service->getCurrentMemoryUsage();
    $usage2 = $this->service->getCurrentMemoryUsage();

    expect($usage1)->toBeFloat()
        ->and($usage2)->toBeFloat()
        ->and(abs($usage1 - $usage2))->toBeLessThan(5.0); // Should be relatively consistent
});

test('peak memory is greater than or equal to current', function () {
    $current = $this->service->getCurrentMemoryUsage();
    $peak = $this->service->getPeakMemoryUsage();

    expect($peak)->toBeGreaterThanOrEqual($current);
});
