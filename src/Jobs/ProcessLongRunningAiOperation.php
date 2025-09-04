<?php

declare(strict_types=1);

namespace CreativeCrafts\LaravelAiAssistant\Jobs;

use CreativeCrafts\LaravelAiAssistant\Services\BackgroundJobService;
use Exception;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use InvalidArgumentException;
use Throwable;

class ProcessLongRunningAiOperation implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    /**
     * The job data containing operation details and parameters.
     *
     * @var array
     */
    public array $jobData;

    /**
     * The number of times the job may be attempted.
     *
     * @var int
     */
    public int $tries = 3;

    /**
     * The number of seconds the job can run before timing out.
     *
     * @var int
     */
    public int $timeout = 300;

    /**
     * Create a new job instance.
     *
     * @param array $jobData Job data containing operation details
     */
    public function __construct(array $jobData)
    {
        $this->jobData = $jobData;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle(): void
    {
        $operation = $this->jobData['operation'] ?? '';
        $parameters = $this->jobData['parameters'] ?? [];
        $jobId = $this->jobData['job_id'] ?? '';

        // Mark job as started
        $this->updateJobStatus($jobId, 'processing');

        try {
            // Process the operation based on its type
            $result = $this->processOperation($operation, $parameters);

            // Mark the job as completed
            $this->updateJobStatus($jobId, 'completed', $result);

        } catch (Exception $e) {
            // Mark job as failed
            $this->updateJobStatus($jobId, 'failed', null, $e->getMessage());

            // Re-throw to let Laravel handle job failure
            throw $e;
        }
    }

    /**
     * Handle a job failure.
     *
     * @param Throwable $exception
     * @return void
     */
    public function failed(Throwable $exception): void
    {
        $jobId = $this->jobData['job_id'] ?? '';

        // Mark the job as failed in our tracking system
        $this->updateJobStatus($jobId, 'failed', null, $exception->getMessage());
    }

    /**
     * Process the AI operation based on its type.
     *
     * @param string $operation Operation type
     * @param array $parameters Operation parameters
     * @return mixed Operation result
     */
    private function processOperation(string $operation, array $parameters): mixed
    {
        return match ($operation) {
            'text_completion' => $this->processTextCompletion($parameters),
            'chat_completion' => $this->processChatCompletion($parameters),
            'audio_transcription' => $this->processAudioTranscription($parameters),
            default => throw new InvalidArgumentException("Unknown operation type: {$operation}"),
        };
    }

    /**
     * Process text completion operation.
     *
     * @param array $parameters Operation parameters
     * @return array
     */
    private function processTextCompletion(array $parameters): array
    {
        // Placeholder implementation
        return [
            'type' => 'text_completion',
            'result' => 'Generated text based on: ' . ($parameters['prompt'] ?? 'no prompt'),
            'timestamp' => now()->toISOString(),
        ];
    }

    /**
     * Process chat completion operation.
     *
     * @param array $parameters Operation parameters
     * @return array
     */
    private function processChatCompletion(array $parameters): array
    {
        // Placeholder implementation
        return [
            'type' => 'chat_completion',
            'result' => 'Chat response generated',
            'message_count' => count($parameters['messages'] ?? []),
            'timestamp' => now()->toISOString(),
        ];
    }

    /**
     * Process audio transcription operation.
     *
     * @param array $parameters Operation parameters
     * @return array
     */
    private function processAudioTranscription(array $parameters): array
    {
        // Placeholder implementation
        return [
            'type' => 'audio_transcription',
            'result' => 'Transcribed audio content',
            'file' => $parameters['file'] ?? 'no file',
            'timestamp' => now()->toISOString(),
        ];
    }

    /**
     * Update job status in the system.
     *
     * @param string $jobId Job identifier
     * @param string $status Job status
     * @param mixed $result Job result (optional)
     * @param string|null $error Error message (optional)
     */
    private function updateJobStatus(string $jobId, string $status, mixed $result = null, ?string $error = null): void
    {
        // This would typically update the job status in the database or cache.
        // For now, we'll use a simple approach with the BackgroundJobService
        try {
            $backgroundJobService = app(BackgroundJobService::class);

            switch ($status) {
                case 'processing':
                    $backgroundJobService->markJobStarted($jobId);
                    break;
                case 'completed':
                    $backgroundJobService->markJobCompleted($jobId, $result);
                    break;
                case 'failed':
                    $backgroundJobService->markJobFailed($jobId, $error ?? 'Unknown error', []);
                    break;
            }
        } catch (Exception $e) {
            // Silently continue if the status update fails
            // This prevents the main job from failing due to status update issues
        }
    }
}
