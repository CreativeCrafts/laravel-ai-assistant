<?php

declare(strict_types=1);

use CreativeCrafts\LaravelAiAssistant\Services\ErrorReportingService;
use CreativeCrafts\LaravelAiAssistant\Services\LoggingService;
use CreativeCrafts\LaravelAiAssistant\Services\MemoryMonitoringService;
use CreativeCrafts\LaravelAiAssistant\Services\MetricsCollectionService;
use CreativeCrafts\LaravelAiAssistant\Services\ObservabilityService;

beforeEach(function () {
    $this->loggingService = Mockery::mock(LoggingService::class);
    $this->metricsService = Mockery::mock(MetricsCollectionService::class);
    $this->errorReportingService = Mockery::mock(ErrorReportingService::class);
    $this->memoryMonitoringService = Mockery::mock(MemoryMonitoringService::class);

    $this->observabilityService = new ObservabilityService(
        $this->loggingService,
        $this->metricsService,
        $this->errorReportingService,
        $this->memoryMonitoringService
    );
});

afterEach(function () {
    Mockery::close();
});

test('constructor initializes properly', function () {
    expect($this->observabilityService)->toBeInstanceOf(ObservabilityService::class);
});

test('setCorrelationId delegates to logging service', function () {
    $correlationId = 'test-correlation-id-123';

    $this->loggingService
        ->shouldReceive('setCorrelationId')
        ->once()
        ->with($correlationId);

    $this->observabilityService->setCorrelationId($correlationId);
});

test('getCorrelationId returns value from logging service', function () {
    $correlationId = 'test-correlation-id-456';

    $this->loggingService
        ->shouldReceive('getCorrelationId')
        ->once()
        ->andReturn($correlationId);

    $result = $this->observabilityService->getCorrelationId();

    expect($result)->toBe($correlationId);
});

test('log delegates to logging service with correlation context', function () {
    $correlationId = 'corr-123';

    $this->loggingService->shouldReceive('getCorrelationId')->andReturn($correlationId);
    $this->loggingService
        ->shouldReceive('logPerformanceEvent')
        ->once()
        ->with('custom', 'test-operation', Mockery::on(function ($data) use ($correlationId) {
            return $data['message'] === 'Test message' &&
                   $data['correlation_id'] === $correlationId &&
                   $data['key'] === 'value';
        }), null);

    $this->observabilityService->log('test-operation', 'info', 'Test message', ['key' => 'value']);
});

test('logApiRequest delegates to logging service', function () {
    $this->loggingService
        ->shouldReceive('logApiRequest')
        ->once()
        ->with('completion', ['prompt' => 'test'], 'gpt-4', 1.5);

    $this->observabilityService->logApiRequest('completion', ['prompt' => 'test'], 'gpt-4', 1.5);
});

test('logApiResponse delegates to logging service', function () {
    $this->loggingService
        ->shouldReceive('logApiResponse')
        ->once()
        ->with('completion', true, ['result' => 'success'], 2.0);

    $this->observabilityService->logApiResponse('completion', true, ['result' => 'success'], 2.0);
});

test('logPerformanceMetrics delegates to logging service', function () {
    $metrics = ['memory' => '128MB'];

    $this->loggingService
        ->shouldReceive('logPerformanceMetrics')
        ->once()
        ->with('operation', 100.5, $metrics);

    $this->observabilityService->logPerformanceMetrics('operation', 100.5, $metrics);
});

test('logError delegates to logging service with correlation context', function () {
    $correlationId = 'corr-error-123';

    $this->loggingService->shouldReceive('getCorrelationId')->andReturn($correlationId);
    $this->loggingService
        ->shouldReceive('logError')
        ->once()
        ->with('operation', 'error message', Mockery::on(function ($context) use ($correlationId) {
            return $context['correlation_id'] === $correlationId && $context['extra'] === 'data';
        }));

    $this->observabilityService->logError('operation', 'error message', ['extra' => 'data']);
});

test('record delegates to metrics service with correlation tags', function () {
    $correlationId = 'corr-metric-123';

    $this->loggingService->shouldReceive('getCorrelationId')->andReturn($correlationId);
    $this->metricsService
        ->shouldReceive('recordCustomMetric')
        ->once()
        ->with('custom_metric', 42, Mockery::on(function ($tags) use ($correlationId) {
            return $tags['correlation_id'] === $correlationId && $tags['env'] === 'test';
        }));

    $this->observabilityService->record('custom_metric', 42, ['env' => 'test']);
});

test('recordApiCall delegates to metrics service with correlation context', function () {
    $correlationId = 'corr-api-123';

    $this->loggingService->shouldReceive('getCorrelationId')->andReturn($correlationId);
    $this->metricsService
        ->shouldReceive('recordApiCall')
        ->once()
        ->with('/api/test', 150.5, 200, Mockery::on(function ($data) use ($correlationId) {
            return $data['correlation_id'] === $correlationId;
        }));

    $this->observabilityService->recordApiCall('/api/test', 150.5, 200, []);
});

test('recordTokenUsage delegates to metrics service', function () {
    $this->metricsService
        ->shouldReceive('recordTokenUsage')
        ->once()
        ->with('completion', 100, 50, 'gpt-4');

    $this->observabilityService->recordTokenUsage('completion', 100, 50, 'gpt-4');
});

test('recordError delegates to metrics service with correlation context', function () {
    $correlationId = 'corr-err-rec-123';

    $this->loggingService->shouldReceive('getCorrelationId')->andReturn($correlationId);
    $this->metricsService
        ->shouldReceive('recordError')
        ->once()
        ->with('operation', 'ApiError', 'Connection failed', Mockery::on(function ($context) use ($correlationId) {
            return $context['correlation_id'] === $correlationId;
        }));

    $this->observabilityService->recordError('operation', 'ApiError', 'Connection failed', []);
});

test('report exception delegates to error reporting service with correlation', function () {
    $exception = new Exception('Test exception');
    $correlationId = 'corr-exc-123';

    $this->loggingService->shouldReceive('getCorrelationId')->andReturn($correlationId);
    $this->errorReportingService
        ->shouldReceive('reportException')
        ->once()
        ->with($exception, Mockery::on(function ($context) use ($correlationId) {
            return $context['correlation_id'] === $correlationId && $context['user'] === 'test';
        }), Mockery::on(function ($tags) use ($correlationId) {
            return $tags['correlation_id'] === $correlationId && $tags['env'] === 'test';
        }))
        ->andReturn('error-id-123');

    $result = $this->observabilityService->report($exception, ['user' => 'test'], ['env' => 'test']);

    expect($result)->toBe('error-id-123');
});

test('report string error delegates to error reporting service', function () {
    $correlationId = 'corr-str-err-123';

    $this->loggingService->shouldReceive('getCorrelationId')->andReturn($correlationId);
    $this->errorReportingService
        ->shouldReceive('reportError')
        ->once()
        ->with('Error message', Mockery::on(function ($context) use ($correlationId) {
            return $context['correlation_id'] === $correlationId;
        }), 'error', Mockery::on(function ($tags) use ($correlationId) {
            return $tags['correlation_id'] === $correlationId;
        }))
        ->andReturn('error-id-456');

    $result = $this->observabilityService->report('Error message', [], []);

    expect($result)->toBe('error-id-456');
});

test('reportApiError emits exactly one set of logs metrics and errors with correlation', function () {
    $correlationId = 'corr-api-err-123';

    $this->loggingService->shouldReceive('getCorrelationId')->andReturn($correlationId);
    $this->loggingService
        ->shouldReceive('logError')
        ->once()
        ->with('api-operation', 'API failed', Mockery::on(function ($context) use ($correlationId) {
            return $context['correlation_id'] === $correlationId &&
                   $context['endpoint'] === '/api/endpoint' &&
                   $context['status_code'] === 500;
        }));

    $this->metricsService
        ->shouldReceive('recordError')
        ->once()
        ->with('api-operation', 'api_error', 'API failed', Mockery::on(function ($context) use ($correlationId) {
            return $context['correlation_id'] === $correlationId &&
                   $context['endpoint'] === '/api/endpoint' &&
                   $context['status_code'] === 500;
        }));

    $this->errorReportingService
        ->shouldReceive('reportApiError')
        ->once()
        ->with('api-operation', '/api/endpoint', 500, 'API failed', ['request' => 'data'], ['response' => 'data'])
        ->andReturn('api-error-id-789');

    $result = $this->observabilityService->reportApiError(
        'api-operation',
        '/api/endpoint',
        500,
        'API failed',
        ['request' => 'data'],
        ['response' => 'data']
    );

    expect($result)->toBe('api-error-id-789');
});

test('reportMemoryIssue emits logs and delegates to error reporting with correlation', function () {
    $correlationId = 'corr-mem-123';

    $this->loggingService->shouldReceive('getCorrelationId')->andReturn($correlationId);
    $this->loggingService
        ->shouldReceive('logError')
        ->once()
        ->with('memory-operation', 'Memory threshold exceeded: 256MB / 200MB', Mockery::on(function ($context) use ($correlationId) {
            return $context['correlation_id'] === $correlationId && $context['extra'] === 'info';
        }));

    $this->errorReportingService
        ->shouldReceive('reportMemoryIssue')
        ->once()
        ->with('memory-operation', 256.0, 200.0, Mockery::on(function ($context) use ($correlationId) {
            return $context['correlation_id'] === $correlationId && $context['extra'] === 'info';
        }))
        ->andReturn('mem-error-id-123');

    $result = $this->observabilityService->reportMemoryIssue('memory-operation', 256.0, 200.0, ['extra' => 'info']);

    expect($result)->toBe('mem-error-id-123');
});

test('reportPerformanceIssue emits logs metrics and errors with correlation', function () {
    $correlationId = 'corr-perf-123';

    $this->loggingService->shouldReceive('getCorrelationId')->andReturn($correlationId);
    $this->loggingService
        ->shouldReceive('logError')
        ->once()
        ->with('perf-operation', 'Performance threshold exceeded: 5.5s / 3s', Mockery::on(function ($context) use ($correlationId) {
            return $context['correlation_id'] === $correlationId && $context['metric'] === 'value';
        }));

    $this->metricsService
        ->shouldReceive('recordCustomMetric')
        ->once()
        ->with('performance_issue', 5.5, Mockery::on(function ($tags) use ($correlationId) {
            return $tags['correlation_id'] === $correlationId &&
                   $tags['operation'] === 'perf-operation' &&
                   $tags['threshold'] === 3.0;
        }));

    $this->errorReportingService
        ->shouldReceive('reportPerformanceIssue')
        ->once()
        ->with('perf-operation', 5.5, 3.0, Mockery::on(function ($metrics) use ($correlationId) {
            return $metrics['correlation_id'] === $correlationId && $metrics['metric'] === 'value';
        }))
        ->andReturn('perf-error-id-123');

    $result = $this->observabilityService->reportPerformanceIssue('perf-operation', 5.5, 3.0, ['metric' => 'value']);

    expect($result)->toBe('perf-error-id-123');
});

test('trackMemory delegates to memory monitoring service', function () {
    $this->memoryMonitoringService
        ->shouldReceive('startMonitoring')
        ->once()
        ->with('test-operation')
        ->andReturn('checkpoint-123');

    $result = $this->observabilityService->trackMemory('test-operation');

    expect($result)->toBe('checkpoint-123');
});

test('updateMemoryTracking delegates to memory monitoring service', function () {
    $this->memoryMonitoringService
        ->shouldReceive('updateMonitoring')
        ->once()
        ->with('checkpoint-123', 'processing');

    $this->observabilityService->updateMemoryTracking('checkpoint-123', 'processing');
});

test('endMemoryTracking delegates to memory monitoring service', function () {
    $expectedResult = ['duration' => 1.5, 'memory' => 128.5];

    $this->memoryMonitoringService
        ->shouldReceive('endMonitoring')
        ->once()
        ->with('checkpoint-123')
        ->andReturn($expectedResult);

    $result = $this->observabilityService->endMemoryTracking('checkpoint-123');

    expect($result)->toBe($expectedResult);
});

test('getCurrentMemoryUsage delegates to memory monitoring service', function () {
    $this->memoryMonitoringService
        ->shouldReceive('getCurrentMemoryUsage')
        ->once()
        ->andReturn(128.5);

    $result = $this->observabilityService->getCurrentMemoryUsage();

    expect($result)->toBe(128.5);
});

test('isMemoryThresholdExceeded delegates to memory monitoring service', function () {
    $this->memoryMonitoringService
        ->shouldReceive('isThresholdExceeded')
        ->once()
        ->with(85.0)
        ->andReturn(true);

    $result = $this->observabilityService->isMemoryThresholdExceeded(85.0);

    expect($result)->toBeTrue();
});

test('fault injection: error reporting throws exception still logs error', function () {
    $exception = new Exception('Test exception');
    $correlationId = 'corr-fault-123';

    $this->loggingService->shouldReceive('getCorrelationId')->andReturn($correlationId);
    $this->errorReportingService
        ->shouldReceive('reportException')
        ->once()
        ->andThrow(new RuntimeException('Error reporting service unavailable'));

    expect(fn () => $this->observabilityService->report($exception, [], []))
        ->toThrow(RuntimeException::class, 'Error reporting service unavailable');
});

test('fault injection: metrics service failure during reportApiError still logs and reports error', function () {
    $correlationId = 'corr-fault-api-123';

    $this->loggingService->shouldReceive('getCorrelationId')->andReturn($correlationId);
    $this->loggingService
        ->shouldReceive('logError')
        ->once()
        ->with('api-op', 'Failed', Mockery::type('array'));

    $this->metricsService
        ->shouldReceive('recordError')
        ->once()
        ->andThrow(new RuntimeException('Metrics service down'));

    expect(fn () => $this->observabilityService->reportApiError('api-op', '/api/test', 500, 'Failed', [], []))
        ->toThrow(RuntimeException::class, 'Metrics service down');
});

test('correlation id is threaded through all operations consistently', function () {
    $correlationId = 'consistent-corr-123';

    $this->loggingService->shouldReceive('getCorrelationId')->andReturn($correlationId);
    $this->loggingService->shouldReceive('logError')->with('op', 'error', Mockery::on(function ($ctx) use ($correlationId) {
        return $ctx['correlation_id'] === $correlationId &&
               $ctx['endpoint'] === '/test' &&
               $ctx['status_code'] === 500;
    }));
    $this->metricsService->shouldReceive('recordError')->with('op', 'api_error', 'error', Mockery::on(function ($ctx) use ($correlationId) {
        return $ctx['correlation_id'] === $correlationId &&
               $ctx['endpoint'] === '/test' &&
               $ctx['status_code'] === 500;
    }));
    $this->errorReportingService->shouldReceive('reportApiError')->andReturn('id');

    $result = $this->observabilityService->reportApiError('op', '/test', 500, 'error', [], []);

    expect($result)->toBe('id');
});
