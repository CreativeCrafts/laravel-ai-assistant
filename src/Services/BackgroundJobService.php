<?php

declare(strict_types=1);

namespace CreativeCrafts\LaravelAiAssistant\Services;

use CreativeCrafts\LaravelAiAssistant\Contracts\ProgressTrackerContract;
use CreativeCrafts\LaravelAiAssistant\Jobs\ProcessLongRunningAiOperation;
use Illuminate\Support\Facades\Queue;
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
    private ProgressTrackerContract $progressTracker;

    public function __construct(
        LoggingService $loggingService,
        MetricsCollectionService $metricsService,
        ProgressTrackerContract $progressTracker,
        array $config = []
    ) {
        $this->loggingService = $loggingService;
        $this->metricsService = $metricsService;
        $this->progressTracker = $progressTracker;
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

        $metadata = [
            'operation' => $operation,
            'parameters' => $parameters,
            'options' => $options,
        ];

        // Start tracking the job
        $this->progressTracker->start($jobId, 'job', $metadata);

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
        $status = $this->progressTracker->getStatus($jobId);

        if (!$status) {
            return null;
        }

        return [
            'job_id' => $jobId,
            'operation' => $status['metadata']['operation'] ?? 'unknown',
            'status' => $status['status'],
            'progress' => $status['progress'],
            'created_at' => $status['created_at'],
            'started_at' => $status['started_at'],
            'completed_at' => $status['completed_at'],
            'duration_seconds' => $status['duration_seconds'],
            'result' => $status['result'],
            'error' => $status['error'],
            'correlation_id' => $status['correlation_id'],
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
        $progress = max(0, min(100, $progress));

        $this->progressTracker->update($jobId, $progress, $metadata);

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
        $status = $this->progressTracker->getStatus($jobId);

        if (!$status) {
            return;
        }

        $operation = $status['metadata']['operation'] ?? 'unknown';

        // Update with progress 0 to transition from Queued to Running
        $this->progressTracker->update($jobId, 0, []);

        $this->logJobEvent($jobId, 'job_started', [
            'operation' => $operation,
        ]);

        $this->metricsService->recordCustomMetric('background_jobs_started', 1, [
            'operation' => $operation,
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
        $status = $this->progressTracker->getStatus($jobId);

        if (!$status) {
            return;
        }

        $operation = $status['metadata']['operation'] ?? 'unknown';

        $this->progressTracker->complete($jobId, $result);

        $duration = $status['duration_seconds'];

        $serializedResult = $result ? json_encode($result) : '';
        $resultSize = $serializedResult !== false ? strlen($serializedResult) : 0;

        $this->logJobEvent($jobId, 'job_completed', [
            'operation' => $operation,
            'duration_seconds' => $duration,
            'result_size' => $resultSize,
        ]);

        $this->metricsService->recordCustomMetric('background_jobs_completed', 1, [
            'operation' => $operation,
        ]);

        if ($duration > 0) {
            $this->metricsService->recordCustomMetric('background_job_duration', $duration, [
                'operation' => $operation,
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
        $status = $this->progressTracker->getStatus($jobId);

        if (!$status) {
            return;
        }

        $operation = $status['metadata']['operation'] ?? 'unknown';

        $this->progressTracker->fail($jobId, $error, $context);

        $this->logJobEvent($jobId, 'job_failed', [
            'operation' => $operation,
            'error' => $error,
            'context' => $context,
        ]);

        $this->metricsService->recordError(
            $operation,
            'job_failure',
            $error,
            $context
        );

        $this->metricsService->recordCustomMetric('background_jobs_failed', 1, [
            'operation' => $operation,
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
        $status = $this->progressTracker->getStatus($jobId);

        if (!$status) {
            return false;
        }

        $operation = $status['metadata']['operation'] ?? 'unknown';

        $success = $this->progressTracker->cancel($jobId, 'Job cancelled by user');

        if ($success) {
            $this->logJobEvent($jobId, 'job_cancelled', [
                'operation' => $operation,
            ]);

            $this->metricsService->recordCustomMetric('background_jobs_cancelled', 1, [
                'operation' => $operation,
            ]);
        }

        return $success;
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
