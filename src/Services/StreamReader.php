<?php

declare(strict_types=1);

namespace CreativeCrafts\LaravelAiAssistant\Services;

use Generator;
use Throwable;

final class StreamReader
{
    /**
     * Normalize Responses SSE events into a simplified stream of higher-level events.
     *
     * @param iterable<array> $events Normalized events from StreamingService::streamResponses
     * @return Generator<array{type:string,data:array,isFinal?:bool}>
     */
    public function normalize(iterable $events): Generator
    {
        foreach ($events as $evt) {
            $type = (string)($evt['type'] ?? '');
            $data = (array)($evt['data'] ?? []);
            $isFinal = (bool)($evt['isFinal'] ?? false);

            switch ($type) {
                case 'response.output_text.delta':
                    $delta = $this->extractDelta($data);
                    yield ['type' => 'message.delta', 'data' => ['text' => $delta, 'typing' => true]];
                    break;
                case 'response.output_text.completed':
                    $text = $this->extractCompleted($data);
                    yield ['type' => 'message.completed', 'data' => ['text' => $text, 'typing' => false]];
                    break;
                default:
                    if (str_contains($type, 'tool_call.created')) {
                        yield ['type' => 'tool_call.started', 'data' => $data];
                    } elseif (str_contains($type, 'tool_call.delta')) {
                        $argsDelta = $this->extractArgsDelta($data);
                        yield ['type' => 'tool_call.args.delta', 'data' => ['delta' => $argsDelta] + $data];
                    } elseif ($type === 'response.completed') {
                        yield ['type' => 'completed', 'data' => $data, 'isFinal' => true];
                    } elseif ($type === 'response.failed') {
                        yield ['type' => 'failed', 'data' => $data, 'isFinal' => true];
                    } elseif ($type === 'response.canceled') {
                        yield ['type' => 'canceled', 'data' => $data, 'isFinal' => true];
                    } else {
                        // pass-through anything else
                        yield ['type' => $type, 'data' => $data] + ($isFinal ? ['isFinal' => true] : []);
                    }
            }
        }
    }

    /**
     * Call simple text callback for text deltas and completions and yield the same text pieces.
     *
     * @param iterable<array> $events
     * @param callable(string $delta):void $onTextChunk
     * @return Generator<string>
     */
    public function onTextChunks(iterable $events, callable $onTextChunk): Generator
    {
        foreach ($events as $evt) {
            $type = (string)($evt['type'] ?? '');
            $data = (array)($evt['data'] ?? []);
            if ($type === 'response.output_text.delta') {
                $delta = $this->extractDelta($data);
                if ($delta !== '') {
                    try {
                    $onTextChunk($delta);
                    } catch (Throwable $e) { /* ignore */
                    }
                    yield $delta;
                }
            } elseif ($type === 'response.output_text.completed') {
                $text = $this->extractCompleted($data);
                if ($text !== '') {
                    try {
                    $onTextChunk($text);
                    } catch (Throwable $e) { /* ignore */
                    }
                    yield $text;
                }
            }
        }
    }

    private function extractDelta(array $data): string
    {
        return (string)($data['delta']
            ?? $data['text']
            ?? ($data['item']['delta'] ?? null)
            ?? ($data['output_text']['delta'] ?? null)
            ?? '');
    }

    private function extractCompleted(array $data): string
    {
        return (string)($data['text'] ?? ($data['output_text'] ?? ($data['item']['text'] ?? '')));
    }

    private function extractArgsDelta(array $data): string
    {
        return (string)($data['delta'] ?? ($data['arguments']['delta'] ?? ''));
    }
}
