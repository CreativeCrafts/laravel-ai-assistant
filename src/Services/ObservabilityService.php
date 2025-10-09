<?php

declare(strict_types=1);

namespace CreativeCrafts\LaravelAiAssistant\Services;

use CreativeCrafts\LaravelAiAssistant\Contracts\ObservabilityContract;
use Throwable;

readonly class ObservabilityService implements ObservabilityContract
{
    public function __construct(
        private LoggingService $loggingService,
        private MetricsCollectionService $metricsCollectionService,
        private ErrorReportingService $errorReportingService,
        private MemoryMonitoringService $memoryMonitoringService
    ) {
    }

    public function setCorrelationId(?string $id): void
    {
        $this->loggingService->setCorrelationId($id);
    }

    public function getCorrelationId(): ?string
    {
        return $this->loggingService->getCorrelationId();
    }

    public function log(string $operation, string $level, string $message, array $context = []): void
    {
        $contextWithCorrelation = $this->appendCorrelationContext($context);

        match ($level) {
            'debug' => $this->loggingService->logPerformanceEvent('custom', $operation, array_merge(['message' => $message], $contextWithCorrelation), null),
            'info' => $this->loggingService->logPerformanceEvent('custom', $operation, array_merge(['message' => $message], $contextWithCorrelation), null),
            'warning' => $this->loggingService->logPerformanceEvent('custom', $operation, array_merge(['message' => $message], $contextWithCorrelation), null),
            'error' => $this->loggingService->logError($operation, $message, $contextWithCorrelation),
            default => $this->loggingService->logPerformanceEvent('custom', $operation, array_merge(['message' => $message], $contextWithCorrelation), null),
        };
    }

    public function logApiRequest(string $operation, array $payload, string $model, ?float $duration = null): void
    {
        $this->loggingService->logApiRequest($operation, $payload, $model, $duration);
    }

    public function logApiResponse(string $operation, bool $success, mixed $response, ?float $duration = null): void
    {
        $this->loggingService->logApiResponse($operation, $success, $response, $duration);
    }

    public function logPerformanceMetrics(string $operation, float $duration, array $metrics = []): void
    {
        $this->loggingService->logPerformanceMetrics($operation, $duration, $metrics);
    }

    public function logError(string $operation, Throwable|string $error, array $context = []): void
    {
        $contextWithCorrelation = $this->appendCorrelationContext($context);
        $this->loggingService->logError($operation, $error, $contextWithCorrelation);
    }

    public function record(string $metricName, mixed $value, array $tags = []): void
    {
        $tagsWithCorrelation = $this->appendCorrelationToTags($tags);
        $this->metricsCollectionService->recordCustomMetric($metricName, $value, $tagsWithCorrelation);
    }

    public function recordApiCall(string $endpoint, float $responseTime, int $statusCode, array $additionalData = []): void
    {
        $dataWithCorrelation = $this->appendCorrelationContext($additionalData);
        $this->metricsCollectionService->recordApiCall($endpoint, $responseTime, $statusCode, $dataWithCorrelation);
    }

    public function recordTokenUsage(string $operation, int $promptTokens, int $completionTokens, string $model): void
    {
        $this->metricsCollectionService->recordTokenUsage($operation, $promptTokens, $completionTokens, $model);
    }

    public function recordError(string $operation, string $errorType, string $errorMessage, array $context = []): void
    {
        $contextWithCorrelation = $this->appendCorrelationContext($context);
        $this->metricsCollectionService->recordError($operation, $errorType, $errorMessage, $contextWithCorrelation);
    }

    public function report(Throwable|string $error, array $context = [], array $tags = []): ?string
    {
        $contextWithCorrelation = $this->appendCorrelationContext($context);
        $tagsWithCorrelation = $this->appendCorrelationToTags($tags);

        if ($error instanceof Throwable) {
            return $this->errorReportingService->reportException($error, $contextWithCorrelation, $tagsWithCorrelation);
        }

        return $this->errorReportingService->reportError($error, $contextWithCorrelation, 'error', $tagsWithCorrelation);
    }

    public function reportApiError(string $operation, string $endpoint, int $statusCode, string $errorMessage, array $requestData = [], array $responseData = []): ?string
    {
        $this->logError($operation, $errorMessage, [
            'endpoint' => $endpoint,
            'status_code' => $statusCode,
        ]);

        $this->recordError($operation, 'api_error', $errorMessage, [
            'endpoint' => $endpoint,
            'status_code' => $statusCode,
        ]);

        return $this->errorReportingService->reportApiError(
            $operation,
            $endpoint,
            $statusCode,
            $errorMessage,
            $requestData,
            $responseData
        );
    }

    public function reportMemoryIssue(string $operation, float $memoryUsageMB, float $thresholdMB, array $additionalContext = []): ?string
    {
        $contextWithCorrelation = $this->appendCorrelationContext($additionalContext);

        $this->logError($operation, "Memory threshold exceeded: {$memoryUsageMB}MB / {$thresholdMB}MB", $contextWithCorrelation);

        return $this->errorReportingService->reportMemoryIssue($operation, $memoryUsageMB, $thresholdMB, $contextWithCorrelation);
    }

    public function reportPerformanceIssue(string $operation, float $responseTime, float $threshold, array $metrics = []): ?string
    {
        $contextWithCorrelation = $this->appendCorrelationContext($metrics);

        $this->logError($operation, "Performance threshold exceeded: {$responseTime}s / {$threshold}s", $contextWithCorrelation);

        $this->record('performance_issue', $responseTime, [
            'operation' => $operation,
            'threshold' => $threshold,
        ]);

        return $this->errorReportingService->reportPerformanceIssue($operation, $responseTime, $threshold, $contextWithCorrelation);
    }

    public function trackMemory(string $operationName): string
    {
        return $this->memoryMonitoringService->startMonitoring($operationName);
    }

    public function updateMemoryTracking(string $checkpointId, string $stage): void
    {
        $this->memoryMonitoringService->updateMonitoring($checkpointId, $stage);
    }

    public function endMemoryTracking(string $checkpointId): array
    {
        return $this->memoryMonitoringService->endMonitoring($checkpointId);
    }

    public function getCurrentMemoryUsage(): float
    {
        return $this->memoryMonitoringService->getCurrentMemoryUsage();
    }

    public function isMemoryThresholdExceeded(float $thresholdPercentage = 80.0): bool
    {
        return $this->memoryMonitoringService->isThresholdExceeded($thresholdPercentage);
    }

    private function appendCorrelationContext(array $context): array
    {
        $correlationId = $this->getCorrelationId();

        if ($correlationId !== null) {
            $context['correlation_id'] = $correlationId;
        }

        return $context;
    }

    private function appendCorrelationToTags(array $tags): array
    {
        $correlationId = $this->getCorrelationId();

        if ($correlationId !== null) {
            $tags['correlation_id'] = $correlationId;
        }

        return $tags;
    }
}
