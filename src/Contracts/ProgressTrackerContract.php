<?php

declare(strict_types=1);

namespace CreativeCrafts\LaravelAiAssistant\Contracts;

interface ProgressTrackerContract
{
    /**
     * Start tracking a new operation.
     *
     * @param string $operationId Unique identifier for the operation
     * @param string $operationType Type of operation (e.g., 'job', 'stream')
     * @param array $metadata Additional metadata
     * @param string|null $correlationId Optional correlation ID for tracking across systems
     * @return bool True if tracking started successfully
     */
    public function start(
        string $operationId,
        string $operationType,
        array $metadata = [],
        ?string $correlationId = null
    ): bool;

    /**
     * Update progress for an operation.
     *
     * @param string $operationId Operation identifier
     * @param int $progress Progress value (0-100 for percentage, or absolute value)
     * @param array $metadata Additional progress metadata
     * @return bool True if update was successful
     */
    public function update(string $operationId, int $progress, array $metadata = []): bool;

    /**
     * Cancel an operation.
     *
     * @param string $operationId Operation identifier
     * @param string|null $reason Optional cancellation reason
     * @return bool True if cancellation was successful, false if already in terminal state
     */
    public function cancel(string $operationId, ?string $reason = null): bool;

    /**
     * Mark an operation as successfully completed.
     *
     * @param string $operationId Operation identifier
     * @param mixed $result Optional result data
     * @return bool True if completion was successful
     */
    public function complete(string $operationId, mixed $result = null): bool;

    /**
     * Mark an operation as failed.
     *
     * @param string $operationId Operation identifier
     * @param string $error Error message
     * @param array $context Additional error context
     * @return bool True if failure was recorded successfully
     */
    public function fail(string $operationId, string $error, array $context = []): bool;

    /**
     * Get current status and progress information for an operation.
     *
     * @param string $operationId Operation identifier
     * @return array|null Status information or null if operation not found
     */
    public function getStatus(string $operationId): ?array;

    /**
     * Clean up tracking data for an operation.
     *
     * @param string $operationId Operation identifier
     * @return bool True if cleanup was successful
     */
    public function cleanup(string $operationId): bool;

    /**
     * Clean up old tracking data based on retention policy.
     *
     * @param int $retentionHours Hours to retain completed/failed operations
     * @return int Number of operations cleaned up
     */
    public function cleanupOld(int $retentionHours = 168): int;
}
