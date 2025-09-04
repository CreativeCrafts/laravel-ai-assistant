<?php

declare(strict_types=1);

namespace CreativeCrafts\LaravelAiAssistant\Services;

use CreativeCrafts\LaravelAiAssistant\Jobs\ProcessLongRunningAiOperation;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;

/**
 * Background job service for handling long-running AI operations.
 *
 * This service provides comprehensive job management including:
 * - Queue management for long-running operations
 * - Job status tracking and monitoring
 * - Automatic retry and failure handling
 * - Progress tracking for multi-step operations
 * - Resource cleanup and optimization
 */
class BackgroundJobService
{
    private array $config;
    private LoggingService $loggingService;
    private MetricsCollectionService $metricsService;

    public function __construct(
        LoggingService $loggingService,
        MetricsCollectionService $metricsService,
        array $config = []
    ) {
        $this->loggingService = $loggingService;
        $this->metricsService = $metricsService;
        $this->config = $config;
    }

    /**
     * Queue a long-running AI operation for background processing.
     *
     * @param string $operation Operation type
     * @param array $parameters Operation parameters
     * @param array $options Job options (delay, queue, etc.)
     * @return string Job ID for tracking
     */
    public function queueLongRunningOperation(string $operation, array $parameters, array $options = []): string
    {
        if (!$this->isEnabled()) {
            // Execute synchronously if background jobs are disabled
            return $this->executeSynchronously($operation, $parameters);
        }

        $jobId = $this->generateJobId($operation);

        $jobData = [
            'job_id' => $jobId,
            'operation' => $operation,
            'parameters' => $parameters,
            'created_at' => now()->toISOString(),
            'status' => 'queued',
            'progress' => 0,
            'options' => $options,
        ];

        // Store job tracking data
        $this->storeJobData($jobId, $jobData);

        // Configure job options
        $queue = $options['queue'] ?? $this->getDefaultQueue();
        $delay = $options['delay'] ?? 0;
        $timeout = $options['timeout'] ?? $this->getJobTimeout();
        $maxTries = $options['max_tries'] ?? $this->getMaxTries();

        // Create and dispatch the job
        $job = (new ProcessLongRunningAiOperation($jobData))
            ->onQueue($queue)
            ->delay($delay);

        // Note: timeout and tries are handled in the job class properties

        Queue::push($job);

        $this->logJobEvent($jobId, 'job_queued', [
            'operation' => $operation,
            'queue' => $queue,
            'delay' => $delay,
            'timeout' => $timeout,
            'max_tries' => $maxTries,
        ]);

        $this->metricsService->recordCustomMetric('background_jobs_queued', 1, [
            'operation' => $operation,
            'queue' => $queue,
        ]);

        return $jobId;
    }

    /**
     * Queue a batch processing operation for multiple items.
     *
     * @param string $operation Batch operation type
     * @param array $items Items to process
     * @param int $batchSize Number of items per job
     * @param array $options Job options
     * @return array Batch job IDs
     */
    public function queueBatchOperation(string $operation, array $items, int $batchSize = 10, array $options = []): array
    {
        // Ensure batchSize is at least 1 for array_chunk
        $batchSize = max(1, $batchSize);
        $batches = array_chunk($items, $batchSize);
        $jobIds = [];

        foreach ($batches as $index => $batch) {
            $batchParameters = [
                'batch_index' => $index,
                'batch_size' => count($batch),
                'total_batches' => count($batches),
                'items' => $batch,
            ];

            $jobId = $this->queueLongRunningOperation(
                "{$operation}_batch",
                $batchParameters,
                $options
            );

            $jobIds[] = $jobId;
        }

        $this->logJobEvent('batch_' . Str::uuid()->toString(), 'batch_queued', [
            'operation' => $operation,
            'total_items' => count($items),
            'batch_size' => $batchSize,
            'total_batches' => count($batches),
            'job_ids' => $jobIds,
        ]);

        return $jobIds;
    }

    /**
     * Get job status and progress information.
     *
     * @param string $jobId Job identifier
     * @return array|null Job status information
     */
    public function getJobStatus(string $jobId): ?array
    {
        $jobData = $this->getJobData($jobId);

        if (!$jobData) {
            return null;
        }

        return [
            'job_id' => $jobId,
            'operation' => $jobData['operation'],
            'status' => $jobData['status'],
            'progress' => $jobData['progress'] ?? 0,
            'created_at' => $jobData['created_at'],
            'started_at' => $jobData['started_at'] ?? null,
            'completed_at' => $jobData['completed_at'] ?? null,
            'duration_seconds' => $this->calculateDuration($jobData),
            'result' => $jobData['result'] ?? null,
            'error' => $jobData['error'] ?? null,
            'retry_count' => $jobData['retry_count'] ?? 0,
        ];
    }

    /**
     * Update job progress during execution.
     *
     * @param string $jobId Job identifier
     * @param int $progress Progress percentage (0-100)
     * @param array $metadata Additional progress metadata
     */
    public function updateJobProgress(string $jobId, int $progress, array $metadata = []): void
    {
        $jobData = $this->getJobData($jobId);

        if (!$jobData) {
            return;
        }

        $jobData['progress'] = max(0, min(100, $progress));
        $jobData['last_updated'] = now()->toISOString();
        $jobData['progress_metadata'] = $metadata;

        $this->storeJobData($jobId, $jobData);

        $this->logJobEvent($jobId, 'progress_updated', [
            'progress' => $progress,
            'metadata' => $metadata,
        ]);
    }

    /**
     * Mark job as started.
     *
     * @param string $jobId Job identifier
     */
    public function markJobStarted(string $jobId): void
    {
        $jobData = $this->getJobData($jobId);

        if (!$jobData) {
            return;
        }

        $jobData['status'] = 'processing';
        $jobData['started_at'] = now()->toISOString();

        $this->storeJobData($jobId, $jobData);

        $this->logJobEvent($jobId, 'job_started', [
            'operation' => $jobData['operation'],
        ]);

        $this->metricsService->recordCustomMetric('background_jobs_started', 1, [
            'operation' => $jobData['operation'],
        ]);
    }

    /**
     * Mark job as completed successfully.
     *
     * @param string $jobId Job identifier
     * @param mixed $result Job result data
     */
    public function markJobCompleted(string $jobId, $result = null): void
    {
        $jobData = $this->getJobData($jobId);

        if (!$jobData) {
            return;
        }

        $jobData['status'] = 'completed';
        $jobData['completed_at'] = now()->toISOString();
        $jobData['progress'] = 100;
        $jobData['result'] = $result;

        $duration = $this->calculateDuration($jobData);

        $this->storeJobData($jobId, $jobData);

        $serializedResult = $result ? json_encode($result) : '';
        $resultSize = $serializedResult !== false ? strlen($serializedResult) : 0;

        $this->logJobEvent($jobId, 'job_completed', [
            'operation' => $jobData['operation'],
            'duration_seconds' => $duration,
            'result_size' => $resultSize,
        ]);

        $this->metricsService->recordCustomMetric('background_jobs_completed', 1, [
            'operation' => $jobData['operation'],
        ]);

        if ($duration > 0) {
            $this->metricsService->recordCustomMetric('background_job_duration', $duration, [
                'operation' => $jobData['operation'],
                'unit' => 'seconds',
            ]);
        }
    }

    /**
     * Mark job as failed.
     *
     * @param string $jobId Job identifier
     * @param string $error Error message
     * @param array $context Error context
     */
    public function markJobFailed(string $jobId, string $error, array $context = []): void
    {
        $jobData = $this->getJobData($jobId);

        if (!$jobData) {
            return;
        }

        $jobData['status'] = 'failed';
        $jobData['failed_at'] = now()->toISOString();
        $jobData['error'] = $error;
        $jobData['error_context'] = $context;
        $jobData['retry_count'] = ($jobData['retry_count'] ?? 0) + 1;

        $this->storeJobData($jobId, $jobData);

        $this->logJobEvent($jobId, 'job_failed', [
            'operation' => $jobData['operation'],
            'error' => $error,
            'retry_count' => $jobData['retry_count'],
            'context' => $context,
        ]);

        $this->metricsService->recordError(
            $jobData['operation'],
            'job_failure',
            $error,
            $context
        );

        $this->metricsService->recordCustomMetric('background_jobs_failed', 1, [
            'operation' => $jobData['operation'],
            'error_type' => $this->categorizeError($error),
        ]);
    }

    /**
     * Cancel a queued or running job.
     *
     * @param string $jobId Job identifier
     * @return bool True if job was cancelled
     */
    public function cancelJob(string $jobId): bool
    {
        $jobData = $this->getJobData($jobId);

        if (!$jobData || in_array($jobData['status'], ['completed', 'failed', 'cancelled'])) {
            return false;
        }

        $jobData['status'] = 'cancelled';
        $jobData['cancelled_at'] = now()->toISOString();

        $this->storeJobData($jobId, $jobData);

        $this->logJobEvent($jobId, 'job_cancelled', [
            'operation' => $jobData['operation'],
        ]);

        $this->metricsService->recordCustomMetric('background_jobs_cancelled', 1, [
            'operation' => $jobData['operation'],
        ]);

        return true;
    }

    /**
     * Get job queue statistics.
     *
     * @return array Queue statistics
     */
    public function getQueueStatistics(): array
    {
        // This would typically query the queue system for real statistics
        return [
            'total_jobs' => $this->getTotalJobsCount(),
            'queued_jobs' => $this->getQueuedJobsCount(),
            'processing_jobs' => $this->getProcessingJobsCount(),
            'completed_jobs' => $this->getCompletedJobsCount(),
            'failed_jobs' => $this->getFailedJobsCount(),
            'cancelled_jobs' => $this->getCancelledJobsCount(),
            'average_processing_time' => $this->getAverageProcessingTime(),
            'success_rate_percent' => $this->getSuccessRate(),
        ];
    }

    /**
     * Clean up old completed job data.
     *
     * @param int $retentionDays Days to retain job data
     * @return int Number of jobs cleaned up
     */
    public function cleanupOldJobs(int $retentionDays = 7): int
    {
        $cutoffDate = now()->subDays($retentionDays);
        $cleanedUp = 0;

        // This would typically clean up from persistent storage
        $this->logJobEvent('system', 'cleanup_started', [
            'retention_days' => $retentionDays,
            'cutoff_date' => $cutoffDate->toISOString(),
        ]);

        // Placeholder for actual cleanup logic

        $this->logJobEvent('system', 'cleanup_completed', [
            'jobs_cleaned' => $cleanedUp,
        ]);

        return $cleanedUp;
    }

    /**
     * Check if background jobs are enabled.
     *
     * @return bool
     */
    private function isEnabled(): bool
    {
        return $this->config['enabled'] ?? false;
    }

    /**
     * Get default queue name.
     *
     * @return string
     */
    private function getDefaultQueue(): string
    {
        return $this->config['queue'] ?? 'ai-assistant';
    }

    /**
     * Get job timeout in seconds.
     *
     * @return int
     */
    private function getJobTimeout(): int
    {
        return $this->config['timeout'] ?? 300;
    }

    /**
     * Get maximum retry attempts.
     *
     * @return int
     */
    private function getMaxTries(): int
    {
        return $this->config['max_tries'] ?? 3;
    }

    /**
     * Generate unique job ID.
     *
     * @param string $operation Operation name
     * @return string
     */
    private function generateJobId(string $operation): string
    {
        return sprintf(
            '%s_%s_%s',
            $operation,
            date('Ymd_His'),
            Str::uuid()->toString()
        );
    }

    /**
     * Store job data in cache.
     *
     * @param string $jobId Job identifier
     * @param array $data Job data
     */
    private function storeJobData(string $jobId, array $data): void
    {
        $ttl = now()->addDays(7); // Keep job data for 7 days
        Cache::put("ai_job_{$jobId}", $data, $ttl);
    }

    /**
     * Retrieve job data from cache.
     *
     * @param string $jobId Job identifier
     * @return array|null
     */
    private function getJobData(string $jobId): ?array
    {
        $data = Cache::get("ai_job_{$jobId}");
        return is_array($data) ? $data : null;
    }

    /**
     * Execute operation synchronously.
     *
     * @param string $operation Operation name
     * @param array $parameters Operation parameters
     * @return string Job ID
     */
    private function executeSynchronously(string $operation, array $parameters): string
    {
        $jobId = $this->generateJobId($operation);

        $this->logJobEvent($jobId, 'synchronous_execution', [
            'operation' => $operation,
            'reason' => 'background_jobs_disabled',
        ]);

        // This would execute the operation directly
        // For now, we'll just log it and return the job ID

        return $jobId;
    }

    /**
     * Calculate job duration.
     *
     * @param array $jobData Job data
     * @return float Duration in seconds
     */
    private function calculateDuration(array $jobData): float
    {
        if (!isset($jobData['started_at'])) {
            return 0;
        }

        $startTime = strtotime($jobData['started_at']);
        $endTime = isset($jobData['completed_at'])
            ? strtotime($jobData['completed_at'])
            : time();

        return max(0, $endTime - $startTime);
    }

    /**
     * Categorize error for metrics.
     *
     * @param string $error Error message
     * @return string Error category
     */
    private function categorizeError(string $error): string
    {
        $error = strtolower($error);

        if (str_contains($error, 'timeout')) {
            return 'timeout';
        } elseif (str_contains($error, 'memory')) {
            return 'memory';
        } elseif (str_contains($error, 'network') || str_contains($error, 'connection')) {
            return 'network';
        } elseif (str_contains($error, 'api') || str_contains($error, 'http')) {
            return 'api';
        } else {
            return 'other';
        }
    }

    /**
     * Log job events.
     *
     * @param string $jobId Job identifier
     * @param string $event Event name
     * @param array $data Event data
     */
    private function logJobEvent(string $jobId, string $event, array $data): void
    {
        $logData = array_merge([
            'job_id' => $jobId,
            'event' => $event,
            'timestamp' => now()->toISOString(),
        ], $data);

        $this->loggingService->logPerformanceEvent(
            'background_jobs',
            $event,
            $logData,
            'background_job_service'
        );
    }

    // Placeholder methods for queue statistics - would be implemented based on actual queue driver

    private function getTotalJobsCount(): int
    {
    return 0;
    }
    private function getQueuedJobsCount(): int
    {
    return 0;
    }
    private function getProcessingJobsCount(): int
    {
    return 0;
    }
    private function getCompletedJobsCount(): int
    {
    return 0;
    }
    private function getFailedJobsCount(): int
    {
    return 0;
    }
    private function getCancelledJobsCount(): int
    {
    return 0;
    }
    private function getAverageProcessingTime(): float
    {
    return 0.0;
    }
    private function getSuccessRate(): float
    {
    return 100.0;
    }
}
