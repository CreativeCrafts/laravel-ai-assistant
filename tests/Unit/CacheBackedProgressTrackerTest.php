<?php

declare(strict_types=1);

use CreativeCrafts\LaravelAiAssistant\Enums\ProgressStatus;
use CreativeCrafts\LaravelAiAssistant\Services\CacheBackedProgressTracker;
use CreativeCrafts\LaravelAiAssistant\Services\LoggingService;
use CreativeCrafts\LaravelAiAssistant\Services\MetricsCollectionService;
use Illuminate\Support\Facades\Cache;

beforeEach(function () {
    Cache::flush();
    $this->loggingService = Mockery::mock(LoggingService::class);
    $this->loggingService->shouldReceive('logPerformanceEvent')->andReturn(null);

    $this->metricsService = Mockery::mock(MetricsCollectionService::class);
    $this->metricsService->shouldReceive('recordCustomMetric')->andReturn(null);
    $this->metricsService->shouldReceive('recordError')->andReturn(null);

    $this->tracker = new CacheBackedProgressTracker(
        $this->loggingService,
        $this->metricsService
    );
});

afterEach(function () {
    Mockery::close();
});

it('can start tracking an operation', function () {
    $result = $this->tracker->start('op-123', 'job', ['test' => 'data']);

    expect($result)->toBeTrue();

    $status = $this->tracker->getStatus('op-123');
    expect($status)->not->toBeNull()
        ->and($status['operation_id'])->toBe('op-123')
        ->and($status['operation_type'])->toBe('job')
        ->and($status['status'])->toBe(ProgressStatus::Queued->value)
        ->and($status['progress'])->toBe(0)
        ->and($status['metadata']['test'])->toBe('data')
        ->and($status['correlation_id'])->toStartWith('corr_');
});

it('can start tracking with custom correlation id', function () {
    $correlationId = 'custom-correlation-123';
    $result = $this->tracker->start('op-123', 'job', [], $correlationId);

    expect($result)->toBeTrue();

    $status = $this->tracker->getStatus('op-123');
    expect($status['correlation_id'])->toBe($correlationId);
});

it('can update progress and transitions to running', function () {
    $this->tracker->start('op-123', 'stream', []);

    $result = $this->tracker->update('op-123', 50, ['chunk_count' => 100]);

    expect($result)->toBeTrue();

    $status = $this->tracker->getStatus('op-123');
    expect($status['status'])->toBe(ProgressStatus::Running->value)
        ->and($status['progress'])->toBe(50)
        ->and($status['metadata']['chunk_count'])->toBe(100)
        ->and($status['started_at'])->not->toBeNull();
});

it('can complete an operation', function () {
    $this->tracker->start('op-123', 'job', []);
    $this->tracker->update('op-123', 50, []);

    $result = $this->tracker->complete('op-123', ['data' => 'result']);

    expect($result)->toBeTrue();

    $status = $this->tracker->getStatus('op-123');
    expect($status['status'])->toBe(ProgressStatus::Done->value)
        ->and($status['progress'])->toBe(100)
        ->and($status['result']['data'])->toBe('result')
        ->and($status['completed_at'])->not->toBeNull();
});

it('can fail an operation', function () {
    $this->tracker->start('op-123', 'job', []);

    $result = $this->tracker->fail('op-123', 'Test error', ['context' => 'value']);

    expect($result)->toBeTrue();

    $status = $this->tracker->getStatus('op-123');
    expect($status['status'])->toBe(ProgressStatus::Error->value)
        ->and($status['error'])->toBe('Test error')
        ->and($status['error_context']['context'])->toBe('value')
        ->and($status['completed_at'])->not->toBeNull();
});

it('can cancel an operation', function () {
    $this->tracker->start('op-123', 'job', []);

    $result = $this->tracker->cancel('op-123', 'User cancelled');

    expect($result)->toBeTrue();

    $status = $this->tracker->getStatus('op-123');
    expect($status['status'])->toBe(ProgressStatus::Canceled->value)
        ->and($status['metadata']['cancellation_reason'])->toBe('User cancelled')
        ->and($status['completed_at'])->not->toBeNull();
});

it('prevents updates to completed operations', function () {
    $this->tracker->start('op-123', 'job', []);
    $this->tracker->complete('op-123');

    $result = $this->tracker->update('op-123', 75, []);

    expect($result)->toBeFalse();

    $status = $this->tracker->getStatus('op-123');
    expect($status['status'])->toBe(ProgressStatus::Done->value)
        ->and($status['progress'])->toBe(100);
});

it('prevents updates to failed operations', function () {
    $this->tracker->start('op-123', 'job', []);
    $this->tracker->fail('op-123', 'Error');

    $result = $this->tracker->update('op-123', 75, []);

    expect($result)->toBeFalse();

    $status = $this->tracker->getStatus('op-123');
    expect($status['status'])->toBe(ProgressStatus::Error->value);
});

it('prevents updates to canceled operations', function () {
    $this->tracker->start('op-123', 'job', []);
    $this->tracker->cancel('op-123');

    $result = $this->tracker->update('op-123', 75, []);

    expect($result)->toBeFalse();

    $status = $this->tracker->getStatus('op-123');
    expect($status['status'])->toBe(ProgressStatus::Canceled->value);
});

it('handles race condition: cancel during completion', function () {
    $this->tracker->start('op-123', 'job', []);
    $this->tracker->update('op-123', 50, []);

    // Complete first
    $completeResult = $this->tracker->complete('op-123', ['result' => 'data']);
    expect($completeResult)->toBeTrue();

    // Try to cancel after completion (should fail)
    $cancelResult = $this->tracker->cancel('op-123');
    expect($cancelResult)->toBeFalse();

    // Status should remain completed
    $status = $this->tracker->getStatus('op-123');
    expect($status['status'])->toBe(ProgressStatus::Done->value);
});

it('handles race condition: complete during cancellation', function () {
    $this->tracker->start('op-123', 'job', []);
    $this->tracker->update('op-123', 50, []);

    // Cancel first
    $cancelResult = $this->tracker->cancel('op-123');
    expect($cancelResult)->toBeTrue();

    // Try to complete after cancellation (should fail)
    $completeResult = $this->tracker->complete('op-123', ['result' => 'data']);
    expect($completeResult)->toBeFalse();

    // Status should remain canceled
    $status = $this->tracker->getStatus('op-123');
    expect($status['status'])->toBe(ProgressStatus::Canceled->value);
});

it('handles race condition: fail during completion', function () {
    $this->tracker->start('op-123', 'job', []);
    $this->tracker->update('op-123', 90, []);

    // Complete first
    $completeResult = $this->tracker->complete('op-123');
    expect($completeResult)->toBeTrue();

    // Try to fail after completion (should fail)
    $failResult = $this->tracker->fail('op-123', 'Error');
    expect($failResult)->toBeFalse();

    // Status should remain completed
    $status = $this->tracker->getStatus('op-123');
    expect($status['status'])->toBe(ProgressStatus::Done->value);
});

it('can cleanup an operation', function () {
    $this->tracker->start('op-123', 'job', []);

    expect($this->tracker->getStatus('op-123'))->not->toBeNull();

    $result = $this->tracker->cleanup('op-123');

    expect($result)->toBeTrue()
        ->and($this->tracker->getStatus('op-123'))->toBeNull();
});

it('returns null for non-existent operation', function () {
    $status = $this->tracker->getStatus('non-existent');

    expect($status)->toBeNull();
});

it('calculates duration correctly', function () {
    $this->tracker->start('op-123', 'job', []);
    $this->tracker->update('op-123', 10, []);

    sleep(1);

    $this->tracker->complete('op-123');

    $status = $this->tracker->getStatus('op-123');
    expect($status['duration_seconds'])->toBeGreaterThanOrEqual(1);
});

it('handles multiple operations independently', function () {
    $this->tracker->start('op-1', 'job', ['name' => 'job1']);
    $this->tracker->start('op-2', 'stream', ['name' => 'stream1']);

    $this->tracker->update('op-1', 50, []);
    $this->tracker->update('op-2', 25, []);

    $this->tracker->complete('op-1');
    $this->tracker->fail('op-2', 'Error');

    $status1 = $this->tracker->getStatus('op-1');
    $status2 = $this->tracker->getStatus('op-2');

    expect($status1['status'])->toBe(ProgressStatus::Done->value)
        ->and($status1['metadata']['name'])->toBe('job1')
        ->and($status2['status'])->toBe(ProgressStatus::Error->value)
        ->and($status2['metadata']['name'])->toBe('stream1');
});

it('merges metadata on updates', function () {
    $this->tracker->start('op-123', 'job', ['initial' => 'value']);

    $this->tracker->update('op-123', 25, ['step' => 1]);
    $this->tracker->update('op-123', 50, ['step' => 2, 'extra' => 'data']);

    $status = $this->tracker->getStatus('op-123');
    expect($status['metadata']['initial'])->toBe('value')
        ->and($status['metadata']['step'])->toBe(2)
        ->and($status['metadata']['extra'])->toBe('data');
});

it('uses cache locks for atomic operations', function () {
    $this->tracker->start('op-123', 'job', []);

    // This test verifies that locks are acquired by attempting concurrent updates
    // In a real scenario with true concurrency, locks would prevent race conditions
    $result1 = $this->tracker->update('op-123', 50, []);
    $result2 = $this->tracker->update('op-123', 75, []);

    expect($result1)->toBeTrue()
        ->and($result2)->toBeTrue();

    $status = $this->tracker->getStatus('op-123');
    expect($status['progress'])->toBe(75);
});

it('tracks correlation ids across lifecycle', function () {
    $correlationId = 'test-corr-123';

    $this->tracker->start('op-123', 'job', [], $correlationId);
    $this->tracker->update('op-123', 50, []);
    $this->tracker->complete('op-123');

    $status = $this->tracker->getStatus('op-123');
    expect($status['correlation_id'])->toBe($correlationId);
});
