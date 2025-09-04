<?php

declare(strict_types=1);

namespace CreativeCrafts\LaravelAiAssistant\Services;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Throwable;
use Exception;

/**
 * Error reporting service for integrating with external error tracking services.
 *
 * This service provides comprehensive error reporting including:
 * - Integration with external services (Sentry, Bugsnag, etc.)
 * - Sensitive data scrubbing
 * - Context enrichment
 * - Error sampling and rate limiting
 * - Custom error tagging and filtering
 */
class ErrorReportingService
{
    private array $config;
    private LoggingService $loggingService;
    private string $driver;
    private array $sensitiveFields = [
        'api_key', 'password', 'token', 'secret', 'authorization',
        'bearer', 'credentials', 'key', 'private', 'session'
    ];

    public function __construct(LoggingService $loggingService, array $config = [])
    {
        $this->loggingService = $loggingService;
        $this->config = $config;
        $this->driver = is_string($this->config['driver'] ?? null) ? $this->config['driver'] : 'log';
    }

    /**
     * Report an exception with full context.
     *
     * @param Throwable $exception The exception to report
     * @param array $context Additional context data
     * @param array $tags Custom tags for filtering
     * @return string|null Report ID if available
     */
    public function reportException(Throwable $exception, array $context = [], array $tags = []): ?string
    {
        if (!$this->isEnabled() || !$this->shouldReport($exception)) {
            return null;
        }

        $enrichedContext = $this->enrichContext($context);
        $scrubbedContext = $this->scrubSensitiveData($enrichedContext);

        $errorData = [
            'exception_class' => get_class($exception),
            'message' => $exception->getMessage(),
            'file' => $exception->getFile(),
            'line' => $exception->getLine(),
            'trace' => $this->formatStackTrace($exception->getTrace()),
            'context' => $scrubbedContext,
            'tags' => array_merge($this->getDefaultTags(), $tags),
            'environment' => $this->config['environment'] ?? 'production',
            'timestamp' => now()->toISOString(),
            'severity' => $this->determineSeverity($exception),
        ];

        return $this->sendErrorReport($errorData, $exception);
    }

    /**
     * Report a custom error event.
     *
     * @param string $message Error message
     * @param array $context Additional context data
     * @param string $level Error level (debug, info, warning, error, critical)
     * @param array $tags Custom tags
     * @return string|null Report ID if available
     */
    public function reportError(string $message, array $context = [], string $level = 'error', array $tags = []): ?string
    {
        if (!$this->isEnabled()) {
            return null;
        }

        $enrichedContext = $this->enrichContext($context);
        $scrubbedContext = $this->scrubSensitiveData($enrichedContext);

        $errorData = [
            'type' => 'custom_error',
            'message' => $message,
            'level' => $level,
            'context' => $scrubbedContext,
            'tags' => array_merge($this->getDefaultTags(), $tags),
            'environment' => $this->config['environment'] ?? 'production',
            'timestamp' => now()->toISOString(),
            'stack_trace' => $this->captureStackTrace(),
        ];

        return $this->sendErrorReport($errorData);
    }

    /**
     * Report API-related errors with specific context.
     *
     * @param string $operation API operation that failed
     * @param string $endpoint API endpoint
     * @param int $statusCode HTTP status code
     * @param string $errorMessage Error message
     * @param array $requestData Request data (will be scrubbed)
     * @param array $responseData Response data
     * @return string|null Report ID if available
     */
    public function reportApiError(
        string $operation,
        string $endpoint,
        int $statusCode,
        string $errorMessage,
        array $requestData = [],
        array $responseData = []
    ): ?string {
        if (!$this->isEnabled()) {
            return null;
        }

        $context = [
            'api_operation' => $operation,
            'endpoint' => $endpoint,
            'status_code' => $statusCode,
            'request_data' => $requestData,
            'response_data' => $responseData,
        ];

        $tags = [
            'component' => 'api_client',
            'operation' => $operation,
            'status_code' => (string)$statusCode,
            'endpoint' => $this->sanitizeEndpoint($endpoint),
        ];

        return $this->reportError($errorMessage, $context, 'error', $tags);
    }

    /**
     * Report memory usage issues.
     *
     * @param string $operation Operation that caused memory issues
     * @param float $memoryUsageMB Current memory usage in MB
     * @param float $thresholdMB Memory threshold in MB
     * @param array $additionalContext Additional context
     * @return string|null Report ID if available
     */
    public function reportMemoryIssue(
        string $operation,
        float $memoryUsageMB,
        float $thresholdMB,
        array $additionalContext = []
    ): ?string {
        if (!$this->isEnabled()) {
            return null;
        }

        $context = array_merge([
            'operation' => $operation,
            'memory_usage_mb' => $memoryUsageMB,
            'memory_threshold_mb' => $thresholdMB,
            'memory_percentage' => $this->getMemoryUsagePercentage(),
            'php_memory_limit' => ini_get('memory_limit'),
        ], $additionalContext);

        $tags = [
            'component' => 'memory_monitoring',
            'operation' => $operation,
            'severity' => $memoryUsageMB > ($thresholdMB * 1.5) ? 'critical' : 'warning',
        ];

        $message = "High memory usage detected: {$memoryUsageMB}MB (threshold: {$thresholdMB}MB) during operation '{$operation}'";

        return $this->reportError($message, $context, 'warning', $tags);
    }

    /**
     * Report performance issues.
     *
     * @param string $operation Operation that had performance issues
     * @param float $responseTime Response time in seconds
     * @param float $threshold Performance threshold in seconds
     * @param array $metrics Additional performance metrics
     * @return string|null Report ID if available
     */
    public function reportPerformanceIssue(
        string $operation,
        float $responseTime,
        float $threshold,
        array $metrics = []
    ): ?string {
        if (!$this->isEnabled()) {
            return null;
        }

        $context = array_merge([
            'operation' => $operation,
            'response_time_seconds' => $responseTime,
            'response_time_ms' => round($responseTime * 1000, 2),
            'threshold_seconds' => $threshold,
            'threshold_ms' => round($threshold * 1000, 2),
        ], $metrics);

        $tags = [
            'component' => 'performance_monitoring',
            'operation' => $operation,
            'severity' => $responseTime > ($threshold * 2) ? 'critical' : 'warning',
        ];

        $message = "Slow operation detected: '{$operation}' took {$responseTime}s (threshold: {$threshold}s)";

        return $this->reportError($message, $context, 'warning', $tags);
    }

    /**
     * Add user context to error reports.
     *
     * @param string|int $userId User identifier
     * @param array $userData Additional user data
     */
    public function setUserContext($userId, array $userData = []): void
    {
        if (!$this->isEnabled()) {
            return;
        }

        $this->config['user_context'] = [
            'id' => (string)$userId,
            'data' => $this->scrubSensitiveData($userData),
        ];
    }

    /**
     * Add custom tags to all subsequent error reports.
     *
     * @param array $tags Key-value pairs of tags
     */
    public function addTags(array $tags): void
    {
        if (!$this->isEnabled()) {
            return;
        }

        $this->config['custom_tags'] = array_merge(
            $this->config['custom_tags'] ?? [],
            $tags
        );
    }

    /**
     * Test error reporting integration.
     *
     * @return array Test results
     */
    public function testIntegration(): array
    {
        $results = [
            'enabled' => $this->isEnabled(),
            'driver' => $this->driver,
            'config_valid' => $this->validateConfiguration(),
            'test_report_sent' => false,
            'error_message' => null,
        ];

        if ($results['enabled'] && $results['config_valid']) {
            try {
                $reportId = $this->reportError('Test error report from Laravel AI Assistant', [
                    'test' => true,
                    'timestamp' => now()->toISOString(),
                ], 'info', ['test' => 'integration']);

                $results['test_report_sent'] = !is_null($reportId);
                $results['report_id'] = $reportId;
            } catch (Throwable $e) {
                $results['error_message'] = $e->getMessage();
            }
        }

        return $results;
    }

    /**
     * Check if error reporting is enabled.
     *
     * @return bool
     */
    private function isEnabled(): bool
    {
        return $this->config['enabled'] ?? true;
    }

    /**
     * Check if context should be included in reports.
     *
     * @return bool
     */
    private function shouldIncludeContext(): bool
    {
        return $this->config['include_context'] ?? true;
    }

    /**
     * Check if sensitive data should be scrubbed.
     *
     * @return bool
     */
    private function shouldScrubSensitiveData(): bool
    {
        return $this->config['scrub_sensitive_data'] ?? true;
    }

    /**
     * Determine if an exception should be reported.
     *
     * @param Throwable $exception
     * @return bool
     */
    private function shouldReport(Throwable $exception): bool
    {
        // Apply sampling rate
        $sampleRate = $this->config['sample_rate'] ?? 1.0;
        if ($sampleRate < 1.0 && random_int(0, PHP_INT_MAX) / PHP_INT_MAX > $sampleRate) {
            return false;
        }

        // Skip certain exception types if configured
        $skipExceptions = $this->config['skip_exceptions'] ?? [];
        foreach ($skipExceptions as $skipException) {
            if ($exception instanceof $skipException) {
                return false;
            }
        }

        return true;
    }

    /**
     * Enrich context with additional system information.
     *
     * @param array $context
     * @return array
     */
    private function enrichContext(array $context): array
    {
        if (!$this->shouldIncludeContext()) {
            return $context;
        }

        $enrichedContext = $context;

        // Add system context
        $enrichedContext['system'] = [
            'php_version' => PHP_VERSION,
            'laravel_version' => app()->version(),
            'memory_usage_mb' => round(memory_get_usage(true) / (1024 * 1024), 2),
            'memory_peak_mb' => round(memory_get_peak_usage(true) / (1024 * 1024), 2),
            'request_id' => request()->header('X-Request-ID', 'req_' . Str::uuid()->toString()),
        ];

        // Add request context if available
        if (app()->bound('request')) {
            $request = request();
            $enrichedContext['request'] = [
                'url' => $request->fullUrl(),
                'method' => $request->method(),
                'ip' => $request->ip(),
                'user_agent' => $request->userAgent(),
                'headers' => $this->scrubSensitiveData($request->headers->all()),
            ];
        }

        // Add user context if set
        if (isset($this->config['user_context'])) {
            $enrichedContext['user'] = $this->config['user_context'];
        }

        return $enrichedContext;
    }

    /**
     * Scrub sensitive data from context.
     *
     * @param array $data
     * @return array
     */
    private function scrubSensitiveData(array $data): array
    {
        if (!$this->shouldScrubSensitiveData()) {
            return $data;
        }

        $result = $this->recursiveScrub($data);
        return is_array($result) ? $result : [];
    }

    /**
     * Recursively scrub sensitive fields.
     *
     * @param mixed $data
     * @return mixed
     */
    private function recursiveScrub($data)
    {
        if (is_array($data)) {
            $scrubbed = [];
            foreach ($data as $key => $value) {
                if ($this->isSensitiveField($key)) {
                    $scrubbed[$key] = '[REDACTED]';
                } else {
                    $scrubbed[$key] = $this->recursiveScrub($value);
                }
            }
            return $scrubbed;
        }

        if (is_object($data)) {
            // For objects, convert to array first
            $jsonEncoded = json_encode($data);
            if ($jsonEncoded !== false) {
                $decoded = json_decode($jsonEncoded, true);
                return $this->recursiveScrub(is_array($decoded) ? $decoded : []);
            }
            return [];
        }

        return $data;
    }

    /**
     * Check if a field name is sensitive.
     *
     * @param string $fieldName
     * @return bool
     */
    private function isSensitiveField(string $fieldName): bool
    {
        $fieldName = strtolower($fieldName);

        foreach ($this->sensitiveFields as $sensitive) {
            if (str_contains($fieldName, $sensitive)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Format stack trace for reporting.
     *
     * @param array $trace
     * @return array
     */
    private function formatStackTrace(array $trace): array
    {
        return array_map(function ($frame) {
            return [
                'file' => $frame['file'] ?? '[internal]',
                'line' => $frame['line'] ?? 0,
                'function' => $frame['function'] ?? '[unknown]',
                'class' => $frame['class'] ?? null,
            ];
        }, array_slice($trace, 0, 20)); // Limit to 20 frames
    }

    /**
     * Capture current stack trace.
     *
     * @return array
     */
    private function captureStackTrace(): array
    {
        $trace = (new Exception())->getTrace();
        return $this->formatStackTrace(array_slice($trace, 1)); // Skip this method
    }

    /**
     * Determine error severity based on exception type.
     *
     * @param Throwable $exception
     * @return string
     */
    private function determineSeverity(Throwable $exception): string
    {
        $className = get_class($exception);

        // Critical errors
        if (str_contains($className, 'Fatal') ||
            str_contains($className, 'OutOfMemory') ||
            str_contains($className, 'ParseError')) {
            return 'fatal';
        }

        // Warnings
        if (str_contains($className, 'Warning') ||
            str_contains($className, 'Notice') ||
            str_contains($className, 'Deprecated')) {
            return 'warning';
        }

        return 'error';
    }

    /**
     * Get default tags for error reports.
     *
     * @return array
     */
    private function getDefaultTags(): array
    {
        return array_merge([
            'component' => 'laravel_ai_assistant',
            'environment' => config('app.env', 'production'),
            'php_version' => PHP_VERSION,
        ], $this->config['custom_tags'] ?? []);
    }

    /**
     * Send error report using configured driver.
     *
     * @param array $errorData
     * @param Throwable|null $exception
     * @return string|null Report ID if available
     */
    private function sendErrorReport(array $errorData, ?Throwable $exception = null): ?string
    {
        switch ($this->driver) {
            case 'sentry':
                return $this->sendToSentry($errorData, $exception);
            case 'bugsnag':
                return $this->sendToBugsnag($errorData, $exception);
            case 'log':
            default:
                return $this->sendToLog($errorData);
        }
    }

    /**
     * Send error report to Sentry.
     *
     * @param array $errorData
     * @param Throwable|null $exception
     * @return string|null
     */
    private function sendToSentry(array $errorData, ?Throwable $exception = null): ?string
    {
        try {
            if (function_exists('\Sentry\captureException') && $exception && function_exists('\Sentry\configureScope')) {
                // Check if Sentry classes exist before using them
                if (class_exists('\Sentry\State\Scope')) {
                    try {
                        \Sentry\configureScope(function ($scope) use ($errorData) {
                            if (is_object($scope) && method_exists($scope, 'setContext')) {
                                $scope->setContext('ai_assistant', $errorData['context'] ?? []);
                            }
                            if (is_object($scope) && method_exists($scope, 'setTag')) {
                                foreach ($errorData['tags'] as $key => $value) {
                                    $scope->setTag($key, $value);
                                }
                            }
                        });
                    } catch (Exception $e) {
                        // Silently continue if Sentry configuration fails
                    }
                }

                return \Sentry\captureException($exception);
            } elseif (function_exists('\Sentry\captureMessage')) {
                return \Sentry\captureMessage($errorData['message']);
            }
        } catch (Throwable $e) {
            Log::warning('Failed to send error to Sentry', ['error' => $e->getMessage()]);
        }

        return $this->sendToLog($errorData);
    }

    /**
     * Send error report to Bugsnag.
     *
     * @param array $errorData
     * @param Throwable|null $exception
     * @return string
     */
    private function sendToBugsnag(array $errorData, ?Throwable $exception = null): string
    {
        try {
            if (class_exists('\Bugsnag\Client') && $exception) {
                $client = \Bugsnag\Client::make();
                if (is_object($client) && method_exists($client, 'notifyException')) {
                    $client->notifyException($exception, function ($report) use ($errorData) {
                        if (is_object($report) && method_exists($report, 'setMetaData')) {
                            $report->setMetaData($errorData['context'] ?? []);
                        }
                    });
                }
                return 'bugsnag_' . Str::uuid()->toString();
            }
        } catch (Throwable $e) {
            Log::warning('Failed to send error to Bugsnag', ['error' => $e->getMessage()]);
        }

        return $this->sendToLog($errorData);
    }

    /**
     * Send error report to log.
     *
     * @param array $errorData
     * @return string
     */
    private function sendToLog(array $errorData): string
    {
        $reportId = 'error_' . Str::uuid()->toString();
        $errorData['report_id'] = $reportId;

        // Use existing logging method instead of undefined logErrorEvent
        $this->loggingService->logPerformanceEvent(
            'error_reported',
            'error_reporting',
            $errorData,
            'error_reporter'
        );

        return $reportId;
    }

    /**
     * Validate error reporting configuration.
     *
     * @return bool
     */
    private function validateConfiguration(): bool
    {
        if ($this->driver === 'sentry') {
            return !empty($this->config['dsn']) && function_exists('\Sentry\init');
        }

        if ($this->driver === 'bugsnag') {
            return !empty($this->config['dsn']) && class_exists('\Bugsnag\Client');
        }

        return true; // Log driver is always valid
    }

    /**
     * Sanitize endpoint for tagging.
     *
     * @param string $endpoint
     * @return string
     */
    private function sanitizeEndpoint(string $endpoint): string
    {
        // Remove query parameters and IDs for cleaner tags
        $path = parse_url($endpoint, PHP_URL_PATH);
        $endpoint = is_string($path) ? $path : $endpoint;

        $result = preg_replace('/\/\d+/', '/{id}', $endpoint);
        $endpoint = is_string($result) ? $result : $endpoint;

        $result = preg_replace('/\/[a-f0-9-]{36}/', '/{uuid}', $endpoint);
        $endpoint = is_string($result) ? $result : $endpoint;

        return $endpoint;
    }

    /**
     * Get current memory usage percentage.
     *
     * @return float
     */
    private function getMemoryUsagePercentage(): float
    {
        $memoryLimit = ini_get('memory_limit');
        if ($memoryLimit === '-1') {
            return 0.0;
        }

        $limitBytes = $this->convertToBytes($memoryLimit);
        $usedBytes = memory_get_usage(true);

        return ($usedBytes / $limitBytes) * 100;
    }

    /**
     * Convert memory limit string to bytes.
     *
     * @param string $size
     * @return int
     */
    private function convertToBytes(string $size): int
    {
        $unit = strtolower(substr($size, -1));
        $value = (int) substr($size, 0, -1);

        switch ($unit) {
            case 'g':
                return $value * 1024 * 1024 * 1024;
            case 'm':
                return $value * 1024 * 1024;
            case 'k':
                return $value * 1024;
            default:
                return (int) $size;
        }
    }
}
