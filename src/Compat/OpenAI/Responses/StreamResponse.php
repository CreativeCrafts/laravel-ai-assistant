<?php

declare(strict_types=1);

namespace CreativeCrafts\LaravelAiAssistant\Compat\OpenAI\Responses;

use ArrayIterator;
use IteratorAggregate;
use Traversable;

/**
 * Lightweight iterable wrapper used by streaming-compatible methods in tests.
 * Implements IteratorAggregate so it satisfies "iterable" return types
 * while remaining a simple DTO-compatible stub.
 *
 * @implements IteratorAggregate<int, mixed>
 */
final class StreamResponse implements IteratorAggregate
{
    /**
     * @return Traversable<mixed>
     */
    public function getIterator(): Traversable
    {
        // Provide an empty iterator by default; tests may mock this class
        // and won't rely on actual iteration.
        return new ArrayIterator([]);
    }
}
