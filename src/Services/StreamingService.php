<?php

declare(strict_types=1);

namespace CreativeCrafts\LaravelAiAssistant\Services;

use Exception;
use Generator;
use Illuminate\Support\Str;
use CreativeCrafts\LaravelAiAssistant\Exceptions\ResponseCanceledException;
use JsonException;

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
    private array $activeStreams = [];

    public function __construct(
        LoggingService $loggingService,
        MemoryMonitoringService $memoryMonitor,
        array $config = []
    ) {
        $this->loggingService = $loggingService;
        $this->memoryMonitor = $memoryMonitor;
        $this->config = $config;
    }

    /**
     * Stream OpenAI Responses API SSE events, normalizing and accumulating deltas.
     * Supports backpressure via periodic GC and optional stop callback.
     *
     * @param iterable $sse Iterable of raw SSE lines (from ResponsesHttpRepository::streamResponse)
     * @param callable|null $onEvent Optional callback invoked with each normalized event
     * @param callable|null $shouldStop Optional callback returning true to stop early (client disconnected)
     * @return Generator Yields normalized events: ['type' => string, 'data' => array, 'isFinal' => bool]
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

                // Surface cancellation immediately
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
     * @param int $bufferSize Buffer size in bytes
     * @return Generator Buffered chunks
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
        $totalStreams = count($this->activeStreams);
        $metrics = [
            'active_streams' => $totalStreams,
            'total_streams_processed' => $this->getTotalStreamsProcessed(),
            'average_stream_size_mb' => $this->getAverageStreamSize(),
            'total_data_processed_mb' => $this->getTotalDataProcessed(),
            'streaming_errors' => $this->getStreamingErrorCount(),
        ];

        return $metrics;
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

        switch ($unit) {
            case 'g':
                return $value * 1024;
            case 'm':
                return $value;
            case 'k':
                return $value / 1024;
            default:
                return $value / (1024 * 1024);
        }
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

        $this->activeStreams[$streamId] = [
            'operation' => $operation,
            'start_time' => microtime(true),
            'chunk_count' => 0,
            'total_size' => 0,
            'status' => 'active',
        ];

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
        if (!isset($this->activeStreams[$streamId])) {
            return;
        }

        $this->activeStreams[$streamId]['chunk_count'] = $chunkCount;
        $this->activeStreams[$streamId]['total_size'] = $totalSize;

        // Log progress periodically
        if ($chunkCount % 50 === 0) {
            $this->logStreamEvent($streamId, 'progress_update', [
                'chunks_processed' => $chunkCount,
                'size_mb' => round($totalSize / (1024 * 1024), 2),
                'duration_seconds' => microtime(true) - $this->activeStreams[$streamId]['start_time'],
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
        if (!isset($this->activeStreams[$streamId])) {
            return;
        }

        $duration = microtime(true) - $this->activeStreams[$streamId]['start_time'];

        $this->activeStreams[$streamId]['status'] = 'completed';
        $this->activeStreams[$streamId]['chunk_count'] = $chunkCount;
        $this->activeStreams[$streamId]['total_size'] = $totalSize;
        $this->activeStreams[$streamId]['duration'] = $duration;

        $this->logStreamEvent($streamId, 'stream_completed', [
            'total_chunks' => $chunkCount,
            'total_size_mb' => round($totalSize / (1024 * 1024), 2),
            'duration_seconds' => $duration,
            'throughput_mbps' => $totalSize > 0 ? round(($totalSize / (1024 * 1024)) / $duration, 2) : 0,
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
        if (!isset($this->activeStreams[$streamId])) {
            return;
        }

        $this->activeStreams[$streamId]['status'] = 'error';
        $this->activeStreams[$streamId]['error'] = $exception->getMessage();

        $this->logStreamEvent($streamId, 'stream_error', [
            'error_message' => $exception->getMessage(),
            'error_class' => get_class($exception),
            'chunks_processed' => $this->activeStreams[$streamId]['chunk_count'],
            'size_processed_mb' => round($this->activeStreams[$streamId]['total_size'] / (1024 * 1024), 2),
        ]);
    }

    /**
     * Clean up stream resources.
     *
     * @param string $streamId Stream identifier
     */
    private function cleanupStream(string $streamId): void
    {
        // Move to completed streams for metrics
        if (isset($this->activeStreams[$streamId])) {
            // Keep stream data for metrics collection
            unset($this->activeStreams[$streamId]);
        }
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
     * Log stream completion with memory report.
     *
     * @param string $streamId Stream identifier
     * @param array $memoryReport Memory monitoring report
     */
    private function logStreamCompletion(string $streamId, array $memoryReport): void
    {
        $this->logStreamEvent($streamId, 'stream_completed_with_memory', $memoryReport);
    }

    /**
     * Get total streams processed (placeholder for metrics).
     *
     * @return int
     */
    private function getTotalStreamsProcessed(): int
    {
        // This would be stored in a cache or database for persistence
        return 0;
    }

    /**
     * Get average stream size (placeholder for metrics).
     *
     * @return float
     */
    private function getAverageStreamSize(): float
    {
        // This would be calculated from historical data
        return 0.0;
    }

    /**
     * Get total data processed (placeholder for metrics).
     *
     * @return float
     */
    private function getTotalDataProcessed(): float
    {
        // This would be stored in a cache or database for persistence
        return 0.0;
    }

    /**
     * Get streaming error count (placeholder for metrics).
     *
     * @return int
     */
    private function getStreamingErrorCount(): int
    {
        // This would be stored in a cache or database for persistence
        return 0;
    }
}
