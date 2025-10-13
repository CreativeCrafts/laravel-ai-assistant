<?php

declare(strict_types=1);

namespace CreativeCrafts\LaravelAiAssistant\Support;

use CreativeCrafts\LaravelAiAssistant\DataTransferObjects\StreamingEventDto;
use Generator;
use Traversable;

/**
 * @internal Used internally for converting streaming events into text chunks.
 * Do not use directly.
 */
final class StreamReader
{
    /**
     * Convert a stream of events into text chunks (strings).
     * Accepts:
     *  - StreamingEventDto
     *  - array events with known shapes
     *  - raw strings (already-normalised text chunks)
     * Optionally calls $onText for each emitted chunk.
     *
     * @param iterable<StreamingEventDto|array|string> $events
     * @return Generator<string>
     */
    public static function toTextChunks(iterable $events, ?callable $onText = null): Generator
    {
        // Make sure we can foreach even if something passes a Traversable
        if ($events instanceof Traversable) {
            $events = iterator_to_array($events);
        }

        foreach ($events as $evt) {
            // If already a string, it is a normalised chunk
            if (is_string($evt)) {
                if ($evt !== '') {
                    if ($onText) {
                        $onText($evt);
                    }
                    yield $evt;
                }
                continue;
            }

            // If it's a DTO, turn it into an array for inspection
            if ($evt instanceof StreamingEventDto) {
                $evt = $evt->toArray();
            }

            if (is_array($evt)) {
                // Commonly normalised shapes we support:
                // 1) ['type' => 'response.output_text.delta', 'data' => ['delta' => '...']]
                $type = $evt['type'] ?? null;
                $data = $evt['data'] ?? null;

                if ($type === 'response.output_text.delta') {
                    $delta = (string)($data['delta'] ?? '');
                    if ($delta !== '') {
                        if ($onText) {
                            $onText($delta);
                        }
                        yield $delta;
                    }
                    continue;
                }

                // 2) Simple content envelope: ['content' => '...'] (fallback)
                if (isset($evt['content']) && is_string($evt['content']) && $evt['content'] !== '') {
                    if ($onText) {
                        $onText($evt['content']);
                    }
                    yield $evt['content'];
                    continue;
                }

                // 3) Some streams may ship as ['delta' => '...'] directly
                if (isset($evt['delta']) && is_string($evt['delta']) && $evt['delta'] !== '') {
                    if ($onText) {
                        $onText($evt['delta']);
                    }
                    yield $evt['delta'];
                    continue;
                }
            }
            // Unknown shapes are safely ignored for text-mode
        }
    }
}
