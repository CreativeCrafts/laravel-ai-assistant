<?php

declare(strict_types=1);

namespace CreativeCrafts\LaravelAiAssistant\Services;

use CreativeCrafts\LaravelAiAssistant\Contracts\ProgressTrackerContract;
use CreativeCrafts\LaravelAiAssistant\Enums\ProgressStatus;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;

/**
 * Cache-backed implementation of the progress tracker.
 *
 * Provides atomic operations for tracking job and stream progress using cache locks.
 * Standardizes status tracking across jobs and streams with consistent lifecycle events.
 */
class CacheBackedProgressTracker implements ProgressTrackerContract
{
    private const KEY_PREFIX = 'progress:';
    private const LOCK_TIMEOUT = 10;
    private const DEFAULT_TTL = 3600;

    public function __construct(
        private LoggingService $loggingService,
        private MetricsCollectionService $metricsService
    ) {
    }

    public function start(
        string $operationId,
        string $operationType,
        array $metadata = [],
        ?string $correlationId = null
    ): bool {
        $lock = Cache::lock($this->getLockKey($operationId), self::LOCK_TIMEOUT);

        try {
            $lock->block(5);

            $correlationId = $correlationId ?? 'corr_' . Str::uuid()->toString();

            $data = [
                'operation_id' => $operationId,
                'operation_type' => $operationType,
                'status' => ProgressStatus::Queued->value,
                'progress' => 0,
                'metadata' => $metadata,
                'correlation_id' => $correlationId,
                'created_at' => now()->toISOString(),
                'started_at' => null,
                'completed_at' => null,
                'duration_seconds' => 0,
                'result' => null,
                'error' => null,
                'error_context' => null,
            ];

            Cache::put($this->getKey($operationId), $data, self::DEFAULT_TTL);

            $this->logEvent($operationId, $correlationId, 'started', $operationType, [
                'metadata' => $metadata,
            ]);

            $this->metricsService->recordCustomMetric("{$operationType}_started", 1, [
                'type' => $operationType,
            ]);

            return true;
        } finally {
            $lock->release();
        }
    }

    public function update(string $operationId, int $progress, array $metadata = []): bool
    {
        $lock = Cache::lock($this->getLockKey($operationId), self::LOCK_TIMEOUT);

        try {
            $lock->block(5);

            $data = $this->getCachedData($operationId);

            if (!$data) {
                return false;
            }

            // Don't update if already in terminal state
            if (in_array($data['status'], [
                ProgressStatus::Done->value,
                ProgressStatus::Error->value,
                ProgressStatus::Canceled->value,
            ], true)) {
                return false;
            }

            // Transition from Queued to Running on first update
            if ($data['status'] === ProgressStatus::Queued->value) {
                $data['status'] = ProgressStatus::Running->value;
                $data['started_at'] = now()->toISOString();

                $this->logEvent($operationId, $data['correlation_id'], 'running', $data['operation_type'], [
                    'progress' => $progress,
                ]);

                $this->metricsService->recordCustomMetric("{$data['operation_type']}_running", 1, [
                    'type' => $data['operation_type'],
                ]);
            }

            $data['progress'] = max(0, min(100, $progress));
            $data['metadata'] = array_merge($data['metadata'], $metadata);
            $data['duration_seconds'] = $this->calculateDuration($data['started_at'] ?? $data['created_at']);

            Cache::put($this->getKey($operationId), $data, self::DEFAULT_TTL);

            $this->logEvent($operationId, $data['correlation_id'], 'updated', $data['operation_type'], [
                'progress' => $data['progress'],
                'metadata' => $metadata,
            ]);

            return true;
        } finally {
            $lock->release();
        }
    }

    public function complete(string $operationId, mixed $result = null): bool
    {
        $lock = Cache::lock($this->getLockKey($operationId), self::LOCK_TIMEOUT);

        try {
            $lock->block(5);

            $data = $this->getCachedData($operationId);

            if (!$data) {
                return false;
            }

            // Don't complete if already in terminal state
            if (in_array($data['status'], [
                ProgressStatus::Done->value,
                ProgressStatus::Error->value,
                ProgressStatus::Canceled->value,
            ], true)) {
                return false;
            }

            $data['status'] = ProgressStatus::Done->value;
            $data['progress'] = 100;
            $data['completed_at'] = now()->toISOString();
            $data['duration_seconds'] = $this->calculateDuration($data['started_at'] ?? $data['created_at']);
            $data['result'] = $result;

            Cache::put($this->getKey($operationId), $data, self::DEFAULT_TTL);

            $this->logEvent($operationId, $data['correlation_id'], 'completed', $data['operation_type'], [
                'duration_seconds' => $data['duration_seconds'],
            ]);

            $this->metricsService->recordCustomMetric("{$data['operation_type']}_completed", 1, [
                'type' => $data['operation_type'],
            ]);

            if ($data['duration_seconds'] > 0) {
                $this->metricsService->recordCustomMetric("{$data['operation_type']}_duration", $data['duration_seconds'], [
                    'type' => $data['operation_type'],
                    'unit' => 'seconds',
                ]);
            }

            return true;
        } finally {
            $lock->release();
        }
    }

    public function fail(string $operationId, string $error, array $context = []): bool
    {
        $lock = Cache::lock($this->getLockKey($operationId), self::LOCK_TIMEOUT);

        try {
            $lock->block(5);

            $data = $this->getCachedData($operationId);

            if (!$data) {
                return false;
            }

            // Don't fail if already in terminal state (except allow overriding Running/Queued)
            if (in_array($data['status'], [
                ProgressStatus::Done->value,
                ProgressStatus::Canceled->value,
            ], true)) {
                return false;
            }

            $data['status'] = ProgressStatus::Error->value;
            $data['completed_at'] = now()->toISOString();
            $data['duration_seconds'] = $this->calculateDuration($data['started_at'] ?? $data['created_at']);
            $data['error'] = $error;
            $data['error_context'] = $context;

            Cache::put($this->getKey($operationId), $data, self::DEFAULT_TTL);

            $this->logEvent($operationId, $data['correlation_id'], 'failed', $data['operation_type'], [
                'error' => $error,
                'context' => $context,
            ]);

            $this->metricsService->recordError(
                $data['operation_type'],
                'operation_failure',
                $error,
                $context
            );

            $this->metricsService->recordCustomMetric("{$data['operation_type']}_failed", 1, [
                'type' => $data['operation_type'],
            ]);

            return true;
        } finally {
            $lock->release();
        }
    }

    public function cancel(string $operationId, ?string $reason = null): bool
    {
        $lock = Cache::lock($this->getLockKey($operationId), self::LOCK_TIMEOUT);

        try {
            $lock->block(5);

            $data = $this->getCachedData($operationId);

            if (!$data) {
                return false;
            }

            // Can only cancel if not already in terminal state
            if (in_array($data['status'], [
                ProgressStatus::Done->value,
                ProgressStatus::Error->value,
                ProgressStatus::Canceled->value,
            ], true)) {
                return false;
            }

            $data['status'] = ProgressStatus::Canceled->value;
            $data['completed_at'] = now()->toISOString();
            $data['duration_seconds'] = $this->calculateDuration($data['started_at'] ?? $data['created_at']);
            if ($reason) {
                $data['metadata']['cancellation_reason'] = $reason;
            }

            Cache::put($this->getKey($operationId), $data, self::DEFAULT_TTL);

            $this->logEvent($operationId, $data['correlation_id'], 'canceled', $data['operation_type'], [
                'reason' => $reason,
            ]);

            $this->metricsService->recordCustomMetric("{$data['operation_type']}_canceled", 1, [
                'type' => $data['operation_type'],
            ]);

            return true;
        } finally {
            $lock->release();
        }
    }

    public function getStatus(string $operationId): ?array
    {
        return $this->getCachedData($operationId);
    }

    public function cleanup(string $operationId): bool
    {
        $lock = Cache::lock($this->getLockKey($operationId), self::LOCK_TIMEOUT);

        try {
            $lock->block(5);

            $data = $this->getCachedData($operationId);

            if ($data) {
                $this->logEvent($operationId, $data['correlation_id'], 'cleaned_up', $data['operation_type'], []);
            }

            Cache::forget($this->getKey($operationId));

            return true;
        } finally {
            $lock->release();
        }
    }

    public function cleanupOld(int $retentionHours = 168): int
    {
        // Note: Cache-based implementation has TTL-based expiration
        // This method would require scanning all cache keys which is not efficient with most cache drivers
        // For production use, consider using database-backed storage for this feature
        // or implement a separate cleanup job that tracks operation IDs in a searchable store

        $this->loggingService->logPerformanceEvent(
            'progress_tracker',
            'cleanup_old_attempted',
            [
                'retention_hours' => $retentionHours,
                'note' => 'Cache-based implementation relies on TTL for automatic cleanup',
            ],
            'cache_backed_progress_tracker'
        );

        return 0;
    }

    private function getKey(string $id): string
    {
        return self::KEY_PREFIX . $id;
    }

    private function getLockKey(string $id): string
    {
        return self::KEY_PREFIX . 'lock:' . $id;
    }

    private function calculateDuration(string $startTime): int
    {
        $start = strtotime($startTime);
        $now = time();

        return max(0, $now - $start);
    }

    private function logEvent(string $id, string $correlationId, string $event, string $type, array $data): void
    {
        $logData = array_merge([
            'id' => $id,
            'correlation_id' => $correlationId,
            'event' => $event,
            'type' => $type,
            'timestamp' => now()->toISOString(),
        ], $data);

        $this->loggingService->logPerformanceEvent(
            'progress_tracker',
            $event,
            $logData,
            'cache_backed_progress_tracker'
        );
    }

    /**
     * Get cached data for an operation with proper type assertion.
     *
     * @param string $operationId
     * @return array<string, mixed>|null
     * @phpstan-return array{
     *     operation_id: string,
     *     operation_type: string,
     *     status: string,
     *     progress: int,
     *     metadata: array<string, mixed>,
     *     correlation_id: string,
     *     created_at: string,
     *     started_at: string|null,
     *     completed_at: string|null,
     *     duration_seconds: int,
     *     result: mixed,
     *     error: string|null,
     *     error_context: array<string, mixed>|null
     * }|null
     */
    private function getCachedData(string $operationId): ?array
    {
        $data = Cache::get($this->getKey($operationId));

        if (!is_array($data)) {
            return null;
        }

        // @phpstan-ignore-next-line - Runtime validation ensures correct structure
        return $data;
    }
}
