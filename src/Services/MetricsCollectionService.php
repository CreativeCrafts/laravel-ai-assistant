<?php

declare(strict_types=1);

namespace CreativeCrafts\LaravelAiAssistant\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Facades\Log;
use Exception;

/**
 * Metrics collection service for tracking usage patterns and performance metrics.
 *
 * This service provides comprehensive analytics including:
 * - API call tracking and performance metrics
 * - Token usage monitoring
 * - Error rate tracking
 * - Response time analysis
 * - System health metrics
 */
class MetricsCollectionService
{
    private array $config;
    private LoggingService $loggingService;
    private string $driver;

    public function __construct(LoggingService $loggingService, array $config = [])
    {
        $this->loggingService = $loggingService;
        $this->config = $config;
        $this->driver = $this->config['driver'] ?? 'log';
    }

    /**
     * Record an API call metric.
     *
     * @param string $endpoint API endpoint called
     * @param float $responseTime Response time in seconds
     * @param int $statusCode HTTP status code
     * @param array $additionalData Additional metric data
     */
    public function recordApiCall(string $endpoint, float $responseTime, int $statusCode, array $additionalData = []): void
    {
        if (!$this->isEnabled()) {
            return;
        }

        $metric = [
            'type' => 'api_call',
            'endpoint' => $endpoint,
            'response_time_ms' => round($responseTime * 1000, 2),
            'status_code' => $statusCode,
            'success' => $statusCode >= 200 && $statusCode < 300,
            'timestamp' => now()->toISOString(),
            'additional_data' => $additionalData,
        ];

        $this->recordMetric($metric);
        $this->updateResponseTimeMetrics($endpoint, $responseTime);
        $this->updateErrorRateMetrics($endpoint, $statusCode);
    }

    /**
     * Record token usage metrics.
     *
     * @param string $operation Operation that used tokens
     * @param int $promptTokens Number of prompt tokens
     * @param int $completionTokens Number of completion tokens
     * @param string $model Model used
     */
    public function recordTokenUsage(string $operation, int $promptTokens, int $completionTokens, string $model): void
    {
        if (!$this->isEnabled() || !$this->shouldTrackTokenUsage()) {
            return;
        }

        $totalTokens = $promptTokens + $completionTokens;

        $metric = [
            'type' => 'token_usage',
            'operation' => $operation,
            'model' => $model,
            'prompt_tokens' => $promptTokens,
            'completion_tokens' => $completionTokens,
            'total_tokens' => $totalTokens,
            'timestamp' => now()->toISOString(),
        ];

        $this->recordMetric($metric);
        $this->updateTokenUsageAggregates($operation, $model, $totalTokens);
    }

    /**
     * Record an error occurrence.
     *
     * @param string $operation Operation that failed
     * @param string $errorType Type of error
     * @param string $errorMessage Error message
     * @param array $context Additional context
     */
    public function recordError(string $operation, string $errorType, string $errorMessage, array $context = []): void
    {
        if (!$this->isEnabled() || !$this->shouldTrackErrorRates()) {
            return;
        }

        $metric = [
            'type' => 'error',
            'operation' => $operation,
            'error_type' => $errorType,
            'error_message' => $errorMessage,
            'context' => $context,
            'timestamp' => now()->toISOString(),
        ];

        $this->recordMetric($metric);
        $this->updateErrorRateAggregates($operation, $errorType);
    }

    /**
     * Record system health metrics.
     *
     * @param array $healthData System health data
     */
    public function recordSystemHealth(array $healthData): void
    {
        if (!$this->isEnabled()) {
            return;
        }

        $metric = [
            'type' => 'system_health',
            'memory_usage_mb' => $healthData['memory_usage_mb'] ?? 0,
            'memory_peak_mb' => $healthData['memory_peak_mb'] ?? 0,
            'cpu_usage_percent' => $healthData['cpu_usage_percent'] ?? 0,
            'disk_usage_percent' => $healthData['disk_usage_percent'] ?? 0,
            'active_connections' => $healthData['active_connections'] ?? 0,
            'timestamp' => now()->toISOString(),
        ];

        $this->recordMetric($metric);
    }

    /**
     * Record custom performance metric.
     *
     * @param string $metricName Name of the metric
     * @param mixed $value Metric value
     * @param array $tags Metric tags for filtering
     */
    public function recordCustomMetric(string $metricName, $value, array $tags = []): void
    {
        if (!$this->isEnabled()) {
            return;
        }

        $metric = [
            'type' => 'custom',
            'metric_name' => $metricName,
            'value' => $value,
            'tags' => $tags,
            'timestamp' => now()->toISOString(),
        ];

        $this->recordMetric($metric);
    }

    /**
     * Get API performance summary for a specific endpoint.
     *
     * @param string $endpoint API endpoint
     * @param int $hours Hours to look back (default: 24)
     * @return array Performance summary
     */
    public function getApiPerformanceSummary(string $endpoint, int $hours = 24): array
    {
        $cacheKey = "metrics:api_performance:{$endpoint}:{$hours}h";

        $result = Cache::remember($cacheKey, 300, function () use ($endpoint, $hours) {
            $responseTimeKey = "metrics:response_times:{$endpoint}";
            $errorRateKey = "metrics:error_rates:{$endpoint}";
            $callCountKey = "metrics:call_counts:{$endpoint}";

            return [
                'endpoint' => $endpoint,
                'period_hours' => $hours,
                'total_calls' => $this->getAggregateValue($callCountKey, $hours),
                'average_response_time_ms' => $this->getAverageResponseTime($endpoint, $hours),
                'error_rate_percent' => $this->getErrorRate($endpoint, $hours),
                'success_rate_percent' => 100 - $this->getErrorRate($endpoint, $hours),
                'last_updated' => now()->toISOString(),
            ];
        });

        return is_array($result) ? $result : [];
    }

    /**
     * Get token usage summary.
     *
     * @param int $hours Hours to look back (default: 24)
     * @return array Token usage summary
     */
    public function getTokenUsageSummary(int $hours = 24): array
    {
        $cacheKey = "metrics:token_usage_summary:{$hours}h";

        $result = Cache::remember($cacheKey, 300, function () use ($hours) {
            return [
                'period_hours' => $hours,
                'total_tokens' => $this->getTotalTokenUsage($hours),
                'tokens_by_model' => $this->getTokenUsageByModel($hours),
                'tokens_by_operation' => $this->getTokenUsageByOperation($hours),
                'estimated_cost_usd' => $this->calculateEstimatedCost($hours),
                'last_updated' => now()->toISOString(),
            ];
        });

        return is_array($result) ? $result : [];
    }

    /**
     * Get system health summary.
     *
     * @param int $hours Hours to look back (default: 1)
     * @return array System health summary
     */
    public function getSystemHealthSummary(int $hours = 1): array
    {
        $cacheKey = "metrics:system_health_summary:{$hours}h";

        $result = Cache::remember($cacheKey, 60, function () use ($hours) {
            return [
                'period_hours' => $hours,
                'average_memory_usage_mb' => $this->getAverageSystemMetric('memory_usage_mb', $hours),
                'peak_memory_usage_mb' => $this->getPeakSystemMetric('memory_usage_mb', $hours),
                'average_cpu_usage_percent' => $this->getAverageSystemMetric('cpu_usage_percent', $hours),
                'peak_cpu_usage_percent' => $this->getPeakSystemMetric('cpu_usage_percent', $hours),
                'health_status' => $this->determineHealthStatus($hours),
                'last_updated' => now()->toISOString(),
            ];
        });

        return is_array($result) ? $result : [];
    }

    /**
     * Flush pending metrics and cleanup old data.
     */
    public function flushAndCleanup(): void
    {
        if (!$this->isEnabled()) {
            return;
        }

        $this->flushPendingMetrics();
        $this->cleanupOldMetrics();
    }

    /**
     * Check if metrics collection is enabled.
     *
     * @return bool
     */
    private function isEnabled(): bool
    {
        return $this->config['enabled'] ?? true;
    }

    /**
     * Check if response time tracking is enabled.
     *
     * @return bool
     */
    private function shouldTrackResponseTimes(): bool
    {
        return $this->config['track_response_times'] ?? true;
    }

    /**
     * Check if token usage tracking is enabled.
     *
     * @return bool
     */
    private function shouldTrackTokenUsage(): bool
    {
        return $this->config['track_token_usage'] ?? true;
    }

    /**
     * Check if error rate tracking is enabled.
     *
     * @return bool
     */
    private function shouldTrackErrorRates(): bool
    {
        return $this->config['track_error_rates'] ?? true;
    }

    /**
     * Record a metric using the configured driver.
     *
     * @param array $metric Metric data
     */
    private function recordMetric(array $metric): void
    {
        switch ($this->driver) {
            case 'redis':
                $this->recordToRedis($metric);
                break;
            case 'database':
                $this->recordToDatabase($metric);
                break;
            case 'log':
            default:
                $this->recordToLog($metric);
                break;
        }
    }

    /**
     * Record metric to Redis.
     *
     * @param array $metric Metric data
     */
    private function recordToRedis(array $metric): void
    {
        try {
            $key = "metrics:{$metric['type']}:" . date('Y-m-d-H');
            $encoded = json_encode($metric);
            if ($encoded !== false) {
                Redis::lpush($key, $encoded);
                Redis::expire($key, $this->getRetentionSeconds());
            }
        } catch (Exception $e) {
            $this->recordToLog($metric);
            Log::warning('Failed to record metric to Redis, falling back to log', [
                'error' => $e->getMessage(),
                'metric' => $metric
            ]);
        }
    }

    /**
     * Record metric to database.
     *
     * @param array $metric Metric data
     */
    private function recordToDatabase(array $metric): void
    {
        try {
            DB::table('ai_assistant_metrics')->insert([
                'type' => $metric['type'],
                'data' => json_encode($metric),
                'created_at' => now(),
            ]);
        } catch (Exception $e) {
            $this->recordToLog($metric);
            Log::warning('Failed to record metric to database, falling back to log', [
                'error' => $e->getMessage(),
                'metric' => $metric
            ]);
        }
    }

    /**
     * Record metric to log.
     *
     * @param array $metric Metric data
     */
    private function recordToLog(array $metric): void
    {
        $this->loggingService->logPerformanceEvent(
            'metrics_collection',
            $metric['type'],
            $metric,
            'metrics_collector'
        );
    }

    /**
     * Update response time metrics aggregates.
     *
     * @param string $endpoint
     * @param float $responseTime
     */
    private function updateResponseTimeMetrics(string $endpoint, float $responseTime): void
    {
        if (!$this->shouldTrackResponseTimes()) {
            return;
        }

        $key = "metrics:response_times:{$endpoint}";
        $this->updateMovingAverage($key, $responseTime);
    }

    /**
     * Update error rate metrics aggregates.
     *
     * @param string $endpoint
     * @param int $statusCode
     */
    private function updateErrorRateMetrics(string $endpoint, int $statusCode): void
    {
        if (!$this->shouldTrackErrorRates()) {
            return;
        }

        $callCountKey = "metrics:call_counts:{$endpoint}";
        $errorCountKey = "metrics:error_counts:{$endpoint}";

        Cache::increment($callCountKey, 1);

        if ($statusCode >= 400) {
            Cache::increment($errorCountKey, 1);
        }
    }

    /**
     * Update token usage aggregates.
     *
     * @param string $operation
     * @param string $model
     * @param int $tokens
     */
    private function updateTokenUsageAggregates(string $operation, string $model, int $tokens): void
    {
        $operationKey = "metrics:tokens_by_operation:{$operation}";
        $modelKey = "metrics:tokens_by_model:{$model}";
        $totalKey = "metrics:total_tokens";

        Cache::increment($operationKey, $tokens);
        Cache::increment($modelKey, $tokens);
        Cache::increment($totalKey, $tokens);
    }

    /**
     * Update error rate aggregates.
     *
     * @param string $operation
     * @param string $errorType
     */
    private function updateErrorRateAggregates(string $operation, string $errorType): void
    {
        $operationKey = "metrics:errors_by_operation:{$operation}";
        $typeKey = "metrics:errors_by_type:{$errorType}";
        $totalKey = "metrics:total_errors";

        Cache::increment($operationKey, 1);
        Cache::increment($typeKey, 1);
        Cache::increment($totalKey, 1);
    }

    /**
     * Update moving average for a metric.
     *
     * @param string $key Cache key
     * @param float $value New value
     */
    private function updateMovingAverage(string $key, float $value): void
    {
        $current = Cache::get($key, ['count' => 0, 'sum' => 0.0]);
        if (!is_array($current)) {
            $current = ['count' => 0, 'sum' => 0.0];
        }
        $current['count'] = ($current['count'] ?? 0) + 1;
        $current['sum'] = ($current['sum'] ?? 0.0) + $value;

        Cache::put($key, $current, $this->getRetentionSeconds());
    }

    /**
     * Get aggregate value for a cache key.
     *
     * @param string $key Cache key
     * @param int $hours Hours to look back
     * @return int Aggregate value
     */
    private function getAggregateValue(string $key, int $hours): int
    {
        $value = Cache::get($key, 0);
        return is_numeric($value) ? (int) $value : 0;
    }

    /**
     * Get average response time for an endpoint.
     *
     * @param string $endpoint
     * @param int $hours
     * @return float Average response time in ms
     */
    private function getAverageResponseTime(string $endpoint, int $hours): float
    {
        $key = "metrics:response_times:{$endpoint}";
        $data = Cache::get($key, ['count' => 0, 'sum' => 0.0]);

        if (!is_array($data)) {
            $data = ['count' => 0, 'sum' => 0.0];
        }

        $count = is_numeric($data['count'] ?? 0) ? (float) $data['count'] : 0.0;
        $sum = is_numeric($data['sum'] ?? 0.0) ? (float) $data['sum'] : 0.0;

        return $count > 0 ? round(($sum / $count) * 1000, 2) : 0.0;
    }

    /**
     * Get error rate for an endpoint.
     *
     * @param string $endpoint
     * @param int $hours
     * @return float Error rate as percentage
     */
    private function getErrorRate(string $endpoint, int $hours): float
    {
        $totalCalls = Cache::get("metrics:call_counts:{$endpoint}", 0);
        $errorCalls = Cache::get("metrics:error_counts:{$endpoint}", 0);

        $totalCallsNum = is_numeric($totalCalls) ? (int) $totalCalls : 0;
        $errorCallsNum = is_numeric($errorCalls) ? (int) $errorCalls : 0;

        return $totalCallsNum > 0 ? round(($errorCallsNum / $totalCallsNum) * 100, 2) : 0.0;
    }

    /**
     * Get total token usage.
     *
     * @param int $hours
     * @return int Total tokens
     */
    private function getTotalTokenUsage(int $hours): int
    {
        $value = Cache::get("metrics:total_tokens", 0);
        return is_numeric($value) ? (int) $value : 0;
    }

    /**
     * Get token usage by model.
     *
     * @param int $hours
     * @return array Token usage by model
     */
    private function getTokenUsageByModel(int $hours): array
    {
        // This would be implemented based on the driver
        return [];
    }

    /**
     * Get token usage by operation.
     *
     * @param int $hours
     * @return array Token usage by operation
     */
    private function getTokenUsageByOperation(int $hours): array
    {
        // This would be implemented based on the driver
        return [];
    }

    /**
     * Calculate estimated cost based on token usage.
     *
     * @param int $hours
     * @return float Estimated cost in USD
     */
    private function calculateEstimatedCost(int $hours): float
    {
        // Simplified cost calculation - would need actual pricing per model
        $totalTokens = $this->getTotalTokenUsage($hours);
        $avgCostPerThousandTokens = 0.002; // Example rate

        return round(($totalTokens / 1000) * $avgCostPerThousandTokens, 4);
    }

    /**
     * Get average system metric.
     *
     * @param string $metric
     * @param int $hours
     * @return float
     */
    private function getAverageSystemMetric(string $metric, int $hours): float
    {
        // Implementation would depend on driver and storage
        return 0.0;
    }

    /**
     * Get peak system metric.
     *
     * @param string $metric
     * @param int $hours
     * @return float
     */
    private function getPeakSystemMetric(string $metric, int $hours): float
    {
        // Implementation would depend on driver and storage
        return 0.0;
    }

    /**
     * Determine overall system health status.
     *
     * @param int $hours
     * @return string
     */
    private function determineHealthStatus(int $hours): string
    {
        $errorRate = Cache::get("metrics:total_errors", 0);
        $totalCalls = Cache::get("metrics:call_counts:total", 1);

        $errorPercentage = ($errorRate / $totalCalls) * 100;

        if ($errorPercentage > 10) {
            return 'critical';
        } elseif ($errorPercentage > 5) {
            return 'warning';
        } else {
            return 'healthy';
        }
    }

    /**
     * Flush pending metrics to storage.
     */
    private function flushPendingMetrics(): void
    {
        // Implementation for flushing batched metrics
    }

    /**
     * Clean up old metrics based on retention period.
     */
    private function cleanupOldMetrics(): void
    {
        $retentionDays = $this->config['retention_days'] ?? 30;

        switch ($this->driver) {
            case 'database':
                DB::table('ai_assistant_metrics')
                    ->where('created_at', '<', now()->subDays($retentionDays))
                    ->delete();
                break;
            case 'redis':
                // Redis keys with expiry are automatically cleaned up
                break;
        }
    }

    /**
     * Get retention period in seconds.
     *
     * @return int
     */
    private function getRetentionSeconds(): int
    {
        $retentionDays = $this->config['retention_days'] ?? 30;
        return $retentionDays * 24 * 60 * 60;
    }
}
