<?php

declare(strict_types=1);

namespace CreativeCrafts\LaravelAiAssistant\Services;

use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Service for structured logging throughout the AI Assistant package.
 *
 * This service provides consistent, searchable log formatting for API usage,
 * errors, performance metrics, and other important events.
 */
class LoggingService
{
    private const CONTEXT_PREFIX = 'laravel_ai_assistant';
    private const MAX_MESSAGE_LENGTH = 1000;
    private const MAX_CONTEXT_ITEMS = 20;

    private ?string $correlationId = null;

    public function setCorrelationId(?string $id): void
    {
        $this->correlationId = $id !== null && $id !== '' ? $id : null;
    }

    public function getCorrelationId(): ?string
    {
        return $this->correlationId;
    }

    /**
     * Log API request information.
     *
     * @param string $operation The API operation being performed
     * @param array $payload The request payload (sensitive data will be filtered)
     * @param string $model The model being used
     * @param float|null $duration Duration in milliseconds (optional)
     * @return void
     */
    public function logApiRequest(string $operation, array $payload, string $model, ?float $duration = null): void
    {
        $context = [
            'component' => self::CONTEXT_PREFIX,
            'type' => 'api_request',
            'operation' => $operation,
            'model' => $model,
            'payload_size' => $this->calculatePayloadSize($payload),
        ];

        if ($duration !== null) {
            $context['duration_ms'] = round($duration, 2);
        }

        // Add filtered payload information
        $context['payload_summary'] = $this->createPayloadSummary($payload);

        $context = $this->appendCorrelationContext($context);
        $context = $this->limitContextSize($context);
        Log::info("API Request: {$operation} with {$model}", $context);
    }

    /**
     * Log API response information.
     *
     * @param string $operation The API operation that was performed
     * @param bool $success Whether the operation was successful
     * @param mixed $response The API response (will be summarized)
     * @param float|null $duration Duration in milliseconds (optional)
     * @return void
     */
    public function logApiResponse(string $operation, bool $success, mixed $response, ?float $duration = null): void
    {
        $context = [
            'component' => self::CONTEXT_PREFIX,
            'type' => 'api_response',
            'operation' => $operation,
            'success' => $success,
            'response_type' => gettype($response),
        ];

        if ($duration !== null) {
            $context['duration_ms'] = round($duration, 2);
        }

        if (is_array($response)) {
            $context['response_size'] = count($response);
            $context['response_summary'] = $this->createResponseSummary($response);
        }

        $level = $success ? 'info' : 'warning';
        $status = $success ? 'successful' : 'failed';

        $context = $this->appendCorrelationContext($context);
        $context = $this->limitContextSize($context);
        Log::log($level, "API Response: {$operation} {$status}", $context);
    }

    /**
     * Log caching operations.
     *
     * @param string $operation The cache operation (hit, miss, store, clear)
     * @param string $key The cache key
     * @param string|null $type The type of cached data (optional)
     * @param int|null $size The size of cached data in bytes (optional)
     * @return void
     */
    public function logCacheOperation(string $operation, string $key, ?string $type = null, ?int $size = null): void
    {
        $context = [
            'component' => self::CONTEXT_PREFIX,
            'type' => 'cache_operation',
            'operation' => $operation,
            'cache_key' => $this->sanitizeCacheKey($key),
        ];

        if ($type !== null) {
            $context['data_type'] = $type;
        }

        if ($size !== null) {
            $context['size_bytes'] = $size;
        }

        $context = $this->appendCorrelationContext($context);
        $context = $this->limitContextSize($context);
        Log::info("Cache {$operation}: {$this->sanitizeCacheKey($key)}", $context);
    }

    /**
     * Log performance metrics.
     *
     * @param string $operation The operation being measured
     * @param float $duration Duration in milliseconds
     * @param array $metrics Additional metrics
     * @return void
     */
    public function logPerformanceMetrics(string $operation, float $duration, array $metrics = []): void
    {
        $context = [
            'component' => self::CONTEXT_PREFIX,
            'type' => 'performance',
            'operation' => $operation,
            'duration_ms' => round($duration, 2),
        ];

        // Add additional metrics with validation
        foreach ($metrics as $key => $value) {
            if (is_numeric($value) || is_string($value) || is_bool($value)) {
                $context['metric_' . $key] = $value;
            }
        }

        $context = $this->appendCorrelationContext($context);
        $context = $this->limitContextSize($context);

        Log::info("Performance: {$operation} completed in " . round($duration, 2) . "ms", $context);
    }

    /**
     * Log performance events for services.
     *
     * @param string $category The category of the performance event
     * @param string $event The event name
     * @param array $data Event data
     * @param string|null $source The source component (optional)
     * @return void
     */
    public function logPerformanceEvent(string $category, string $event, array $data = [], ?string $source = null): void
    {
        $context = [
            'component' => self::CONTEXT_PREFIX,
            'type' => 'performance_event',
            'category' => $category,
            'event' => $event,
        ];

        if ($source !== null) {
            $context['source'] = $source;
        }

        // Add event data with sanitization and size limits
        foreach ($data as $key => $value) {
            if (count($context) < self::MAX_CONTEXT_ITEMS) {
                $context['data_' . $key] = $this->sanitizeContextValue($value);
            }
        }

        $context = $this->appendCorrelationContext($context);
        $context = $this->limitContextSize($context);

        Log::info("Performance Event [{$category}]: {$event}", $context);
    }

    /**
     * Log errors and exceptions.
     *
     * @param string $operation The operation where the error occurred
     * @param Throwable|string $error The error or exception
     * @param array $context Additional context information
     * @return void
     */
    public function logError(string $operation, Throwable|string $error, array $context = []): void
    {
        $logContext = [
            'component' => self::CONTEXT_PREFIX,
            'type' => 'error',
            'operation' => $operation,
        ];

        if ($error instanceof Throwable) {
            $logContext['exception'] = get_class($error);
            $logContext['error_message'] = $this->truncateMessage($error->getMessage());
            $logContext['file'] = basename($error->getFile());
            $logContext['line'] = $error->getLine();

            $message = "Error in {$operation}: " . get_class($error) . " - " . $this->truncateMessage($error->getMessage());
        } else {
            $logContext['error_message'] = $this->truncateMessage($error);
            $message = "Error in {$operation}: " . $this->truncateMessage($error);
        }

        // Add additional context with size limits
        foreach ($context as $key => $value) {
            if (count($logContext) < self::MAX_CONTEXT_ITEMS) {
                $logContext['context_' . $key] = $this->sanitizeContextValue($value);
            }
        }

        $logContext = $this->appendCorrelationContext($logContext);
        $logContext = $this->limitContextSize($logContext);
        Log::error($message, $logContext);
    }

    /**
     * Log security-related events.
     *
     * @param string $event The security event type
     * @param string $description Description of the event
     * @param array $context Additional context information
     * @return void
     */
    public function logSecurityEvent(string $event, string $description, array $context = []): void
    {
        $logContext = [
            'component' => self::CONTEXT_PREFIX,
            'type' => 'security',
            'event' => $event,
            'description' => $this->truncateMessage($description),
        ];

        // Add context with sanitization
        foreach ($context as $key => $value) {
            if (count($logContext) < self::MAX_CONTEXT_ITEMS) {
                $logContext['context_' . $key] = $this->sanitizeContextValue($value);
            }
        }

        $logContext = $this->appendCorrelationContext($logContext);
        $logContext = $this->limitContextSize($logContext);
        Log::warning("Security Event: {$event} - {$description}", $logContext);
    }

    /**
     * Log configuration changes or issues.
     *
     * @param string $operation The configuration operation
     * @param string $key The configuration key
     * @param mixed $value The configuration value (will be sanitized)
     * @param string|null $source The source of the configuration (optional)
     * @return void
     */
    public function logConfigurationEvent(string $operation, string $key, mixed $value, ?string $source = null): void
    {
        $context = [
            'component' => self::CONTEXT_PREFIX,
            'type' => 'configuration',
            'operation' => $operation,
            'config_key' => $key,
            'value_type' => gettype($value),
        ];

        if ($source !== null) {
            $context['source'] = $source;
        }

        // Sanitize the value for logging (don't log sensitive information)
        if ($this->isSensitiveConfigKey($key)) {
            $context['value'] = '[REDACTED]';
        } else {
            $context['value'] = $this->sanitizeContextValue($value);
        }

        Log::info("Configuration {$operation}: {$key}", $context);
    }

    /**
     * Helper to ensure correlation_id is present in context.
     */
    private function appendCorrelationContext(array $context): array
    {
        if (!isset($context['correlation_id']) && $this->correlationId) {
            $context['correlation_id'] = $this->correlationId;
        }
        return $context;
    }

    /**
     * Calculate the size of a payload for logging purposes.
     *
     * @param array $payload The payload to measure
     * @return int The size in bytes
     */
    private function calculatePayloadSize(array $payload): int
    {
        $encoded = json_encode($payload, JSON_UNESCAPED_UNICODE);
        return $encoded !== false ? strlen($encoded) : 0;
    }

    /**
     * Create a summary of the payload for logging.
     *
     * @param array $payload The payload to summarize
     * @return array Summary information
     */
    private function createPayloadSummary(array $payload): array
    {
        $summary = [
            'keys' => array_keys($payload),
            'key_count' => count($payload),
        ];

        // Add specific information for known payload types
        if (isset($payload['messages']) && is_array($payload['messages'])) {
            $summary['message_count'] = count($payload['messages']);
        }

        if (isset($payload['model'])) {
            $summary['model'] = $payload['model'];
        }

        if (isset($payload['temperature'])) {
            $summary['temperature'] = $payload['temperature'];
        }

        return $summary;
    }

    /**
     * Create a summary of the response for logging.
     *
     * @param array $response The response to summarize
     * @return array Summary information
     */
    private function createResponseSummary(array $response): array
    {
        $summary = [
            'keys' => array_keys($response),
        ];

        // Add specific information for known response types
        if (isset($response['choices']) && is_array($response['choices'])) {
            $summary['choice_count'] = count($response['choices']);
        }

        if (isset($response['usage'])) {
            $summary['has_usage'] = true;
        }

        return $summary;
    }

    /**
     * Sanitize cache key for logging.
     *
     * @param string $key The cache key
     * @return string Sanitized key
     */
    private function sanitizeCacheKey(string $key): string
    {
        // Truncate very long keys and replace potentially sensitive hash values
        if (strlen($key) > 100) {
            return substr($key, 0, 50) . '...' . substr($key, -20);
        }

        return $key;
    }

    /**
     * Truncate messages to prevent log bloat.
     *
     * @param string $message The message to truncate
     * @return string Truncated message
     */
    private function truncateMessage(string $message): string
    {
        if (strlen($message) > self::MAX_MESSAGE_LENGTH) {
            return substr($message, 0, self::MAX_MESSAGE_LENGTH - 3) . '...';
        }

        return $message;
    }

    /**
     * Sanitize context values for logging.
     *
     * @param mixed $value The value to sanitize
     * @return mixed Sanitized value
     */
    private function sanitizeContextValue(mixed $value): mixed
    {
        if (is_string($value)) {
            return $this->truncateMessage($value);
        }

        if (is_array($value)) {
            return array_slice($value, 0, 10); // Limit array size
        }

        if (is_object($value)) {
            return get_class($value);
        }

        return $value;
    }

    /**
     * Check if a configuration key is sensitive.
     *
     * @param string $key The configuration key
     * @return bool True if the key is sensitive
     */
    private function isSensitiveConfigKey(string $key): bool
    {
        $sensitiveKeys = ['api_key', 'token', 'secret', 'password', 'organization'];

        foreach ($sensitiveKeys as $sensitiveKey) {
            if (stripos($key, $sensitiveKey) !== false) {
                return true;
            }
        }

        return false;
    }

    /**
     * Limit the size of context arrays to prevent memory issues.
     *
     * @param array $context The context array
     * @return array Limited context array
     */
    private function limitContextSize(array $context): array
    {
        if (count($context) > self::MAX_CONTEXT_ITEMS) {
            return array_slice($context, 0, self::MAX_CONTEXT_ITEMS, true);
        }

        return $context;
    }
}
