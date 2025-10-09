<?php

declare(strict_types=1);

namespace CreativeCrafts\LaravelAiAssistant\Contracts;

use Throwable;

interface ObservabilityContract
{
    public function setCorrelationId(?string $id): void;

    public function getCorrelationId(): ?string;

    public function log(string $operation, string $level, string $message, array $context = []): void;

    public function logApiRequest(string $operation, array $payload, string $model, ?float $duration = null): void;

    public function logApiResponse(string $operation, bool $success, mixed $response, ?float $duration = null): void;

    public function logPerformanceMetrics(string $operation, float $duration, array $metrics = []): void;

    public function logError(string $operation, Throwable|string $error, array $context = []): void;

    public function record(string $metricName, mixed $value, array $tags = []): void;

    public function recordApiCall(string $endpoint, float $responseTime, int $statusCode, array $additionalData = []): void;

    public function recordTokenUsage(string $operation, int $promptTokens, int $completionTokens, string $model): void;

    public function recordError(string $operation, string $errorType, string $errorMessage, array $context = []): void;

    public function report(Throwable|string $error, array $context = [], array $tags = []): ?string;

    public function reportApiError(string $operation, string $endpoint, int $statusCode, string $errorMessage, array $requestData = [], array $responseData = []): ?string;

    public function reportMemoryIssue(string $operation, float $memoryUsageMB, float $thresholdMB, array $additionalContext = []): ?string;

    public function reportPerformanceIssue(string $operation, float $responseTime, float $threshold, array $metrics = []): ?string;

    public function trackMemory(string $operationName): string;

    public function updateMemoryTracking(string $checkpointId, string $stage): void;

    public function endMemoryTracking(string $checkpointId): array;

    public function getCurrentMemoryUsage(): float;

    public function isMemoryThresholdExceeded(float $thresholdPercentage = 80.0): bool;
}
