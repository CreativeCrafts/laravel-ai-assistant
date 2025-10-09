<?php

declare(strict_types=1);

namespace CreativeCrafts\LaravelAiAssistant\Services;

use CreativeCrafts\LaravelAiAssistant\Contracts\OpenAiRepositoryContract;
use CreativeCrafts\LaravelAiAssistant\Contracts\ProgressTrackerContract;
use CreativeCrafts\LaravelAiAssistant\Contracts\ResponsesRepositoryContract;
use CreativeCrafts\LaravelAiAssistant\DataTransferObjects\CompletionRequest;
use CreativeCrafts\LaravelAiAssistant\Enums\Mode;
use CreativeCrafts\LaravelAiAssistant\Exceptions\ResponseCanceledException;
use Exception;
use Generator;
use Illuminate\Support\Str;
use JsonException;
use Throwable;

/**
 * Streaming service for handling large API responses efficiently.
 *
 * This service provides optimized streaming capabilities including:
 * - Efficient memory management for large responses
 * - Configurable buffer sizes and chunk processing
 * - Stream validation and error handling
 * - Progress tracking and monitoring
 * - Automatic resource cleanup
 */
class StreamingService
{
    private array $config;
    private LoggingService $loggingService;
    private MemoryMonitoringService $memoryMonitor;
    private ResponsesRepositoryContract $responses;
    private OpenAiRepositoryContract $repository;
    private ProgressTrackerContract $progressTracker;

    public function __construct(
        ResponsesRepositoryContract $responses,
        OpenAiRepositoryContract $repository,
        LoggingService $loggingService,
        MemoryMonitoringService $memoryMonitor,
        ProgressTrackerContract $progressTracker,
        array $config = []
    ) {
        $this->responses = $responses;
        $this->repository = $repository;
        $this->loggingService = $loggingService;
        $this->memoryMonitor = $memoryMonitor;
        $this->progressTracker = $progressTracker;
        $this->config = $config;
    }

    /**
     * Stream OpenAI Responses API SSE events, normalizing and accumulating deltas.
     * Supports backpressure via periodic GC and optional stop callback.
     *
     * @param Mode $mode
     * @param CompletionRequest $request
     * @param callable|null $onEvent Optional callback invoked with each normalized event
     * @param callable|null $shouldStop Optional callback returning true to stop early (a client disconnected)
     * @return Generator Yields normalised events: ['type' => string, 'data' => array, 'isFinal' => bool]
     * @throws Exception
     */
    public function process(Mode $mode, CompletionRequest $request, ?callable $onEvent = null, ?callable $shouldStop = null): Generator
    {
        $payload = $request->toArray();

        // For TEXT mode with legacy completions (prompt-based), prefer repository streaming
        // This handles cases where only prompt is provided (no conversation_id or messages)
        if ($mode === Mode::TEXT && isset($payload['prompt'])) {
            // If conversation_id is present, it's a Responses API call, otherwise legacy
            if (!isset($payload['conversation_id'])) {
                $events = $this->streamLegacyCompletion($payload, $onEvent, $shouldStop);
                foreach ($events as $evt) {
                    yield $evt;
                }
                return;
            }
        }

        // For CHAT mode or conversation-based requests, use Responses API streaming
        try {
            $sse = $this->responses->streamResponse($payload);
            $events = $this->streamResponses($sse, $onEvent, $shouldStop);
            foreach ($events as $evt) {
                yield $evt;
            }
        } catch (Throwable $e) {
            // If Responses API fails and we're in TEXT mode with prompt, fallback to legacy
            if ($mode === Mode::TEXT && isset($payload['prompt'])) {
                $events = $this->streamLegacyCompletion($payload, $onEvent, $shouldStop);
                foreach ($events as $evt) {
                    yield $evt;
                }
                return;
            }
            throw $e;
        }
    }

    /**
     * @throws Exception
     */
    public function streamResponses(iterable $sse, ?callable $onEvent = null, ?callable $shouldStop = null): Generator
    {
        $streamId = $this->initializeStream('responses_sse');
        $memoryCheckpoint = $this->memoryMonitor->startMonitoring('responses_sse');
        $parser = new ResponsesSseParser();

        try {
            $chunkCount = 0;
            $totalSize = 0;
            foreach ($parser->parseWithAccumulation($sse) as $evt) {
                $chunkCount++;
                $serialized = json_encode($evt, JSON_THROW_ON_ERROR);
                $totalSize += strlen($serialized);

                if (($evt['type'] ?? '') === 'response.canceled') {
                    throw new ResponseCanceledException('Response streaming was canceled by client or server.');
                }

                if ($chunkCount % 10 === 0) {
                    $this->memoryMonitor->updateMonitoring($memoryCheckpoint, "evt_{$chunkCount}");
                    $this->updateStreamProgress($streamId, $chunkCount, $totalSize);
                }

                if ($onEvent) {
                    try {
                    $onEvent($evt);
                    } catch (Exception $e) { /* ignore callback errors */
                    }
                }

                yield $evt;

                if ($shouldStop && $shouldStop()) {
                    $this->logStreamEvent($streamId, 'client_disconnected', [
                        'chunks_processed' => $chunkCount,
                        'size_processed' => $totalSize,
                    ]);
                    break;
                }

                if ($chunkCount % 100 === 0) {
                    $this->performGarbageCollection($streamId);
                }
            }

            $this->completeStream($streamId, $chunkCount, $totalSize);
        } catch (Exception $e) {
            $this->handleStreamError($streamId, $e);
            throw $e;
        } finally {
            $this->cleanupStream($streamId);
            $memoryReport = $this->memoryMonitor->endMonitoring($memoryCheckpoint);
            $this->logStreamCompletion($streamId, $memoryReport);
        }
    }

    /**
     * @throws Exception
     */
    public function accumulateText(CompletionRequest $request): string
    {
        $accumulated = '';
        foreach ($this->process(Mode::TEXT, $request) as $evt) {
            if (!is_array($evt)) {
                continue;
            }
            $type = (string)($evt['type'] ?? '');
            $data = (array)($evt['data'] ?? []);

            if ($type === 'response.output_text.delta') {
                $accumulated .= (string)($data['delta'] ?? ($data['text'] ?? ''));
            } elseif ($type === 'response.output_text.completed') {
                if (isset($data['text']) && is_string($data['text']) && $data['text'] !== '') {
                    $accumulated = $data['text'];
                }
            } elseif (in_array($type, ['response.completed', 'response.failed', 'response.canceled'], true)) {
                break;
            }
        }

        return trim($accumulated);
    }

    /**
     * @return array<string,mixed>
     */
    public function accumulateChat(CompletionRequest $request): array
    {
        $role = 'assistant';
        $content = '';
        $toolCalls = [];
        $finishReason = null;

        foreach ($this->process(Mode::CHAT, $request) as $evt) {
            if (!is_array($evt)) {
                continue;
            }
            $type = (string)($evt['type'] ?? '');
            $data = (array)($evt['data'] ?? []);

            if ($type === 'response.output_text.delta') {
                $content .= (string)($data['delta'] ?? ($data['text'] ?? ''));
            } elseif ($type === 'response.output_text.completed') {
                if (isset($data['text']) && is_string($data['text']) && $data['text'] !== '') {
                    $content = $data['text'];
                }
            } elseif (str_contains($type, 'tool_call.created')) {
                $toolCalls[] = $data;
            } elseif (in_array($type, ['response.completed', 'response.failed', 'response.canceled'], true)) {
                $finishReason = $type === 'response.completed' ? 'stop' : $type;
                break;
            }
        }

        $result = [
            'role' => $role,
            'content' => $content,
        ];
        if ($toolCalls !== []) {
            $result['tool_calls'] = $toolCalls;
        }
        if ($finishReason !== null) {
            $result['finish_reason'] = $finishReason;
        }
        return $result;
    }

    /**
     * Process a streamed response with memory-efficient chunking.
     *
     * @param iterable $stream The stream to process
     * @param string $operation Operation name for monitoring
     * @param callable|null $chunkProcessor Optional callback to process each chunk
     * @return Generator Processed chunks
     * @throws Exception
     */
    public function processStream(iterable $stream, string $operation, ?callable $chunkProcessor = null): Generator
    {
        if (!$this->isEnabled()) {
            // If streaming is disabled, collect all data and return as single chunk
            $data = [];
            foreach ($stream as $chunk) {
                $data[] = $chunk;
            }
            yield $data;
            return;
        }

        $streamId = $this->initializeStream($operation);
        $memoryCheckpoint = $this->memoryMonitor->startMonitoring("stream_{$operation}");

        try {
            $chunkCount = 0;
            $totalSize = 0;
            $bufferSize = $this->getBufferSize();
            $maxResponseSize = $this->getMaxResponseSize();

            foreach ($stream as $chunk) {
                $chunkCount++;
                $chunkSize = $this->getChunkSize($chunk);
                $totalSize += $chunkSize;

                // Check memory usage periodically
                if ($chunkCount % 10 === 0) {
                    $this->memoryMonitor->updateMonitoring($memoryCheckpoint, "chunk_{$chunkCount}");
                    $this->updateStreamProgress($streamId, $chunkCount, $totalSize);
                }

                // Check the max response size limit
                if ($maxResponseSize > 0 && $totalSize > $maxResponseSize * 1024 * 1024) {
                    $this->logStreamEvent($streamId, 'max_size_exceeded', [
                        'total_size_mb' => round($totalSize / (1024 * 1024), 2),
                        'max_size_mb' => $maxResponseSize,
                        'chunks_processed' => $chunkCount,
                    ]);
                    break;
                }

                // Process chunk if processor provided
                $processedChunk = $chunkProcessor ? $chunkProcessor($chunk) : $chunk;

                // Yield the processed chunk
                yield $processedChunk;

                // Force garbage collection periodically for large streams
                if ($chunkCount % 100 === 0) {
                    $this->performGarbageCollection($streamId);
                }
            }

            $this->completeStream($streamId, $chunkCount, $totalSize);

        } catch (Exception $e) {
            $this->handleStreamError($streamId, $e);
            throw $e;
        } finally {
            $this->cleanupStream($streamId);
            $memoryReport = $this->memoryMonitor->endMonitoring($memoryCheckpoint);
            $this->logStreamCompletion($streamId, $memoryReport);
        }
    }

    /**
     * Stream text completion responses with real-time processing.
     *
     * @param iterable $stream Text stream from OpenAI
     * @param string $operation Operation name
     * @return Generator Processed text chunks
     * @throws Exception
     */
    public function streamTextCompletion(iterable $stream, string $operation = 'text_completion'): Generator
    {
        return $this->processStream($stream, $operation, function ($chunk) {
            // Extract text content from OpenAI streaming response
            if (isset($chunk->choices[0]->text)) {
                return $chunk->choices[0]->text;
            }

            if (isset($chunk->choices[0]->delta->content)) {
                return $chunk->choices[0]->delta->content;
            }
            return '';
        });
    }

    /**
     * Stream chat completion responses with message processing.
     *
     * @param iterable $stream Chat stream from OpenAI
     * @param string $operation Operation name
     * @return Generator Processed chat chunks
     * @throws Exception
     */
    public function streamChatCompletion(iterable $stream, string $operation = 'chat_completion'): Generator
    {
        return $this->processStream($stream, $operation, function ($chunk) {
            // Extract message content from OpenAI streaming response
            if (isset($chunk->choices[0]->delta->content)) {
                return [
                    'role' => $chunk->choices[0]->delta->role ?? 'assistant',
                    'content' => $chunk->choices[0]->delta->content,
                    'finish_reason' => $chunk->choices[0]->finish_reason ?? null,
                ];
            }
            return null;
        });
    }

    /**
     * Buffer streaming data to reduce memory usage.
     *
     * @param iterable $stream Input stream
     * @param int|null $bufferSize Buffer size in bytes
     * @return Generator Buffered chunks
     * @throws JsonException
     */
    public function bufferStream(iterable $stream, int $bufferSize = null): Generator
    {
        $bufferSize = $bufferSize ?? $this->getBufferSize();
        $buffer = '';
        $bufferLength = 0;

        foreach ($stream as $chunk) {
            $chunkData = is_string($chunk) ? $chunk : json_encode($chunk, JSON_THROW_ON_ERROR);
            $buffer .= $chunkData;
            $bufferLength += strlen($chunkData);

            if ($bufferLength >= $bufferSize) {
                yield $buffer;
                $buffer = '';
                $bufferLength = 0;
            }
        }

        // Yield remaining buffer content
        if ($bufferLength > 0) {
            yield $buffer;
        }
    }

    /**
     * Validate streaming configuration and capabilities.
     *
     * @return array Validation results
     * @throws JsonException
     */
    public function validateStreamingCapabilities(): array
    {
        return [
            'enabled' => $this->isEnabled(),
            'buffer_size' => $this->getBufferSize(),
            'chunk_size' => $this->getChunkSize(''),
            'max_response_size_mb' => $this->getMaxResponseSize(),
            'timeout_seconds' => $this->getStreamingTimeout(),
            'memory_limit_mb' => $this->getMemoryLimitMB(),
            'streaming_support' => function_exists('stream_get_contents'),
            'generator_support' => version_compare(PHP_VERSION, '5.5.0') >= 0,
        ];
    }

    /**
     * Get streaming performance metrics.
     *
     * @return array Performance metrics
     */
    public function getStreamingMetrics(): array
    {
        // Metrics are tracked via ProgressTracker and MetricsCollectionService
        // This method provides basic capabilities info
        return [
            'active_streams' => 0,
            'total_streams_processed' => 0,
            'average_stream_size_mb' => 0.0,
            'total_data_processed_mb' => 0.0,
            'streaming_errors' => 0,
        ];
    }

    /**
     * Stream legacy text completions via repository with metrics and monitoring.
     *
     * @param array $payload
     * @param callable|null $onEvent
     * @param callable|null $shouldStop
     * @return Generator
     * @throws Exception
     */
    private function streamLegacyCompletion(array $payload, ?callable $onEvent = null, ?callable $shouldStop = null): Generator
    {
        $streamId = $this->initializeStream('legacy_completion');
        $memoryCheckpoint = $this->memoryMonitor->startMonitoring('legacy_completion');

        try {
            $chunkCount = 0;
            $totalSize = 0;
            $stream = $this->repository->createStreamedCompletion($payload);

            foreach ($stream as $chunk) {
                $chunkCount++;
                $serialized = json_encode($chunk, JSON_THROW_ON_ERROR);
                $totalSize += strlen($serialized);

                // Normalize chunk to event format
                $choices = [];
                if (is_array($chunk)) {
                    $choices = $chunk['choices'] ?? [];
                } elseif (is_object($chunk)) {
                    $choices = $chunk->choices ?? [];
                }

                $first = $choices[0] ?? null;
                $text = null;
                if (is_array($first)) {
                    $text = $first['text'] ?? null;
                } elseif (is_object($first)) {
                    $text = $first->text ?? null;
                }

                $evt = [
                    'type' => 'response.output_text.delta',
                    'data' => [
                        'delta' => is_string($text) ? $text : '',
                        'text' => is_string($text) ? $text : '',
                    ],
                    'isFinal' => false,
                ];

                if ($chunkCount % 10 === 0) {
                    $this->memoryMonitor->updateMonitoring($memoryCheckpoint, "chunk_{$chunkCount}");
                    $this->updateStreamProgress($streamId, $chunkCount, $totalSize);
                }

                if ($onEvent) {
                    try {
                        $onEvent($evt);
                    } catch (Throwable $e) {
                        // Ignore callback errors
                    }
                }

                yield $evt;

                if ($shouldStop && $shouldStop()) {
                    $this->logStreamEvent($streamId, 'client_disconnected', [
                        'chunks_processed' => $chunkCount,
                        'size_processed' => $totalSize,
                    ]);
                    break;
                }

                if ($chunkCount % 100 === 0) {
                    $this->performGarbageCollection($streamId);
                }

                // Legacy completions typically return first chunk only, break after
                break;
            }

            // Emit completion event
            yield [
                'type' => 'response.completed',
                'data' => [],
                'isFinal' => true,
            ];

            $this->completeStream($streamId, $chunkCount, $totalSize);
        } catch (Exception $e) {
            $this->handleStreamError($streamId, $e);
            throw $e;
        } finally {
            $this->cleanupStream($streamId);
            $memoryReport = $this->memoryMonitor->endMonitoring($memoryCheckpoint);
            $this->logStreamCompletion($streamId, $memoryReport);
        }
    }

    /**
     * Check if streaming is enabled.
     *
     * @return bool
     */
    private function isEnabled(): bool
    {
        return $this->config['enabled'] ?? true;
    }

    /**
     * Get buffer size configuration.
     *
     * @return int Buffer size in bytes
     */
    private function getBufferSize(): int
    {
        return $this->config['buffer_size'] ?? 8192;
    }

    /**
     * Get chunk size for data.
     *
     * @param mixed $chunk Data chunk
     * @return int Size in bytes
     * @throws JsonException
     */
    private function getChunkSize($chunk): int
    {
        if (is_string($chunk)) {
            return strlen($chunk);
        }

        if (is_array($chunk) || is_object($chunk)) {
            $encoded = json_encode($chunk, JSON_THROW_ON_ERROR);
            return strlen($encoded);
        }
        return $this->config['chunk_size'] ?? 1024;
    }

    /**
     * Get maximum response size limit.
     *
     * @return int Size in MB (0 for unlimited)
     */
    private function getMaxResponseSize(): int
    {
        return $this->config['max_response_size'] ?? 50;
    }

    /**
     * Get streaming timeout.
     *
     * @return int Timeout in seconds
     */
    private function getStreamingTimeout(): int
    {
        return $this->config['timeout'] ?? 120;
    }

    /**
     * Get memory limit in MB.
     *
     * @return float Memory limit in MB
     */
    private function getMemoryLimitMB(): float
    {
        $memoryLimit = ini_get('memory_limit');
        if ($memoryLimit === '-1') {
            return -1;
        }

        $unit = strtolower(substr($memoryLimit, -1));
        $value = (float) substr($memoryLimit, 0, -1);

        return match ($unit) {
            'g' => $value * 1024,
            'm' => $value,
            'k' => $value / 1024,
            default => $value / (1024 * 1024),
        };
    }

    /**
     * Initialize a new stream for tracking.
     *
     * @param string $operation Operation name
     * @return string Stream ID
     */
    private function initializeStream(string $operation): string
    {
        $streamId = 'stream_' . Str::uuid()->toString();

        $this->progressTracker->start(
            $streamId,
            'stream',
            [
                'operation' => $operation,
                'start_time' => microtime(true),
                'chunk_count' => 0,
                'total_size' => 0,
            ]
        );

        $this->logStreamEvent($streamId, 'stream_initialized', [
            'operation' => $operation,
        ]);

        return $streamId;
    }

    /**
     * Update stream progress.
     *
     * @param string $streamId Stream identifier
     * @param int $chunkCount Number of chunks processed
     * @param int $totalSize Total size processed
     */
    private function updateStreamProgress(string $streamId, int $chunkCount, int $totalSize): void
    {
        $this->progressTracker->update($streamId, $chunkCount, [
            'chunk_count' => $chunkCount,
            'total_size' => $totalSize,
        ]);

        // Log progress periodically
        if ($chunkCount % 50 === 0) {
            $status = $this->progressTracker->getStatus($streamId);
            $startTime = $status ? strtotime($status['started_at'] ?? $status['created_at']) : time();

            $this->logStreamEvent($streamId, 'progress_update', [
                'chunks_processed' => $chunkCount,
                'size_mb' => round($totalSize / (1024 * 1024), 2),
                'duration_seconds' => time() - $startTime,
            ]);
        }
    }

    /**
     * Complete stream processing.
     *
     * @param string $streamId Stream identifier
     * @param int $chunkCount Final chunk count
     * @param int $totalSize Final total size
     */
    private function completeStream(string $streamId, int $chunkCount, int $totalSize): void
    {
        $status = $this->progressTracker->getStatus($streamId);
        if (!$status) {
            return;
        }

        $startTime = strtotime($status['started_at'] ?? $status['created_at']);
        $duration = time() - $startTime;

        $result = [
            'chunk_count' => $chunkCount,
            'total_size' => $totalSize,
            'duration' => $duration,
        ];

        $this->progressTracker->complete($streamId, $result);

        $this->logStreamEvent($streamId, 'stream_completed', [
            'total_chunks' => $chunkCount,
            'total_size_mb' => round($totalSize / (1024 * 1024), 2),
            'duration_seconds' => $duration,
            'throughput_mbps' => $totalSize > 0 ? round(($totalSize / (1024 * 1024)) / max(1, $duration), 2) : 0,
        ]);
    }

    /**
     * Handle stream errors.
     *
     * @param string $streamId Stream identifier
     * @param Exception $exception Exception that occurred
     */
    private function handleStreamError(string $streamId, Exception $exception): void
    {
        $status = $this->progressTracker->getStatus($streamId);

        $context = [
            'error_class' => get_class($exception),
            'chunks_processed' => $status['metadata']['chunk_count'] ?? 0,
            'size_processed_mb' => round(($status['metadata']['total_size'] ?? 0) / (1024 * 1024), 2),
        ];

        $this->progressTracker->fail($streamId, $exception->getMessage(), $context);

        $this->logStreamEvent($streamId, 'stream_error', array_merge([
            'error_message' => $exception->getMessage(),
        ], $context));
    }

    /**
     * Clean up stream resources.
     *
     * @param string $streamId Stream identifier
     */
    private function cleanupStream(string $streamId): void
    {
        // Progress tracker maintains data for configured TTL for metrics/audit
        // Only cleanup if explicitly needed, otherwise let TTL handle it
        // $this->progressTracker->cleanup($streamId);
    }

    /**
     * Perform garbage collection for memory management.
     *
     * @param string $streamId Stream identifier
     */
    private function performGarbageCollection(string $streamId): void
    {
        if (function_exists('gc_collect_cycles')) {
            $cycles = gc_collect_cycles();

            if ($cycles > 0) {
                $this->logStreamEvent($streamId, 'garbage_collection', [
                    'cycles_collected' => $cycles,
                    'memory_usage_mb' => round(memory_get_usage(true) / (1024 * 1024), 2),
                ]);
            }
        }
    }

    /**
     * Log stream events.
     *
     * @param string $streamId Stream identifier
     * @param string $event Event name
     * @param array $data Event data
     */
    private function logStreamEvent(string $streamId, string $event, array $data): void
    {
        $logData = array_merge([
            'stream_id' => $streamId,
            'event' => $event,
            'timestamp' => now()->toISOString(),
        ], $data);

        $this->loggingService->logPerformanceEvent(
            'streaming',
            $event,
            $logData,
            'streaming_service'
        );
    }

    /**
     * Log stream completion with a memory report.
     *
     * @param string $streamId Stream identifier
     * @param array $memoryReport Memory monitoring report
     */
    private function logStreamCompletion(string $streamId, array $memoryReport): void
    {
        $this->logStreamEvent($streamId, 'stream_completed_with_memory', $memoryReport);
    }
}
