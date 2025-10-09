<?php

declare(strict_types=1);

namespace CreativeCrafts\LaravelAiAssistant\Facades;

use CreativeCrafts\LaravelAiAssistant\Services\ObservabilityService;
use Illuminate\Support\Facades\Facade;
use Throwable;

/**
 * Unified observability facade for telemetry operations.
 *
 * Provides a single import for logging, metrics, error reporting, and memory tracking.
 * All operations automatically include correlation IDs for request tracing.
 *
 * @method static void setCorrelationId(?string $id)
 * @method static string|null getCorrelationId()
 * @method static void log(string $operation, string $level, string $message, array $context = [])
 * @method static void logApiRequest(string $operation, array $payload, string $model, ?float $duration = null)
 * @method static void logApiResponse(string $operation, bool $success, mixed $response, ?float $duration = null)
 * @method static void logPerformanceMetrics(string $operation, float $duration, array $metrics = [])
 * @method static void logError(string $operation, Throwable|string $error, array $context = [])
 * @method static void record(string $metricName, mixed $value, array $tags = [])
 * @method static void recordApiCall(string $endpoint, float $responseTime, int $statusCode, array $additionalData = [])
 * @method static void recordTokenUsage(string $operation, int $promptTokens, int $completionTokens, string $model)
 * @method static void recordError(string $operation, string $errorType, string $errorMessage, array $context = [])
 * @method static string|null report(Throwable|string $error, array $context = [], array $tags = [])
 * @method static string|null reportApiError(string $operation, string $endpoint, int $statusCode, string $errorMessage, array $requestData = [], array $responseData = [])
 * @method static string|null reportMemoryIssue(string $operation, float $memoryUsageMB, float $thresholdMB, array $additionalContext = [])
 * @method static string|null reportPerformanceIssue(string $operation, float $responseTime, float $threshold, array $metrics = [])
 * @method static string trackMemory(string $operationName)
 * @method static void updateMemoryTracking(string $checkpointId, string $stage)
 * @method static array endMemoryTracking(string $checkpointId)
 * @method static float getCurrentMemoryUsage()
 * @method static bool isMemoryThresholdExceeded(float $thresholdPercentage = 80.0)
 *
 * @see ObservabilityService
 */
class Observability extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return ObservabilityService::class;
    }
}
