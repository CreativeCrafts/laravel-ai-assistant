<?php

declare(strict_types=1);

namespace CreativeCrafts\LaravelAiAssistant\Services;

use Generator;

/**
 * Parses OpenAI Responses API Server-Sent Events and accumulates output_text deltas.
 * Emits normalized events: ['type' => string, 'data' => array, 'isFinal' => bool]
 */
final class ResponsesSseParser
{
    /**
     * Parse raw SSE lines into normalized events (no accumulation).
     * Keeps generic event/data parsing to be composed by higher-level helpers.
     *
     * @param iterable $lines
     * @return Generator<array>
     */
    public function parse(iterable $lines): Generator
    {
        $event = null;
        $data = '';
        foreach ($lines as $line) {
            $line = trim((string) $line);
            if ($line === '') {
                if ($event !== null && $data !== '') {
                    $decoded = json_decode($data, true);
                    if (!is_array($decoded)) {
                        $decoded = ['data' => $data];
                    }
                    yield [
                        'type' => $event,
                        'data' => $decoded,
                        'isFinal' => in_array($event, ['response.completed', 'response.failed', 'response.canceled'], true),
                    ];
                }
                $event = null;
                $data = '';
                continue;
            }
            if (str_starts_with($line, 'event:')) {
                $event = trim(substr($line, strlen('event:')));
            } elseif (str_starts_with($line, 'data:')) {
                $dataPart = trim(substr($line, strlen('data:')));
                if ($data !== '') {
                    $data .= "\n";
                }
                $data .= $dataPart;
            }
        }
        if ($event !== null && $data !== '') {
            $decoded = json_decode($data, true);
            if (!is_array($decoded)) {
                $decoded = ['data' => $data];
            }
            yield [
                'type' => $event,
                'data' => $decoded,
                'isFinal' => in_array($event, ['response.completed', 'response.failed', 'response.canceled'], true),
            ];
        }
    }

    /**
     * High-level parser that normalizes known Responses SSE event names and
     * accumulates output_text deltas into coherent chunks. Also surfaces typing indicator.
     *
     * Normalized event types it yields:
     * - response.output_text.delta (adds { delta, accumulated, typing: true })
     * - response.output_text.completed (adds { text, typing: false })
     * - response.step.* (passed-through)
     * - response.tool_call.created (passed-through)
     * - response.completed / response.failed / response.canceled (passed-through with isFinal = true)
     *
     * @param iterable $lines Raw SSE lines
     * @return Generator<array>
     */
    public function parseWithAccumulation(iterable $lines): Generator
    {
        $accumulated = '';
        foreach ($this->parse($lines) as $evt) {
            $type = (string)($evt['type'] ?? '');
            $data = $evt['data'] ?? [];

            if ($type === 'response.output_text.delta') {
                $delta = $this->extractDeltaText($data);
                if ($delta !== '') {
                    $accumulated .= $delta;
                }
                $evt['data'] = array_merge((array)$data, [
                    'delta' => $delta,
                    'accumulated' => $accumulated,
                    'typing' => true,
                ]);
                $evt['isFinal'] = false;
                yield $evt;
                continue;
            }

            if ($type === 'response.output_text.completed') {
                // If event contains full text, prefer it; otherwise use accumulated
                $text = $this->extractCompletedText($data);
                if ($text === '') {
                    $text = $accumulated;
                } else {
                    $accumulated = $text; // keep consistent
                }
                $evt['data'] = array_merge((array)$data, [
                    'text' => $text,
                    'typing' => false,
                ]);
                $evt['isFinal'] = false; // not necessarily final for overall response
                yield $evt;
                continue;
            }

            // Pass-through for response.step.*, response.tool_call.created, response.completed/failed/canceled
            yield $evt;

            // Reset accumulator on terminal events
            if (in_array($type, ['response.completed', 'response.failed', 'response.canceled'], true)) {
                $accumulated = '';
            }
        }
    }

    private function extractDeltaText(array $data): string
    {
        // Common shapes: { "delta": "..." } or nested under item/block
        if (isset($data['delta']) && is_string($data['delta'])) {
            return (string) $data['delta'];
        }
        if (isset($data['text']) && is_string($data['text'])) {
            return (string) $data['text'];
        }
        // Step/item nested variants
        if (isset($data['item']['delta']) && is_string($data['item']['delta'])) {
            return (string) $data['item']['delta'];
        }
        if (isset($data['output_text']['delta']) && is_string($data['output_text']['delta'])) {
            return (string) $data['output_text']['delta'];
        }
        return '';
    }

    private function extractCompletedText(array $data): string
    {
        if (isset($data['text']) && is_string($data['text'])) {
            return (string) $data['text'];
        }
        if (isset($data['output_text']) && is_string($data['output_text'])) {
            return (string) $data['output_text'];
        }
        if (isset($data['item']['text']) && is_string($data['item']['text'])) {
            return (string) $data['item']['text'];
        }
        return '';
    }
}
