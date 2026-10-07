<?php

declare(strict_types=1);

namespace CreativeCrafts\LaravelAiAssistant\Support;

use InvalidArgumentException;

/**
 * Percent-encodes a single URL path parameter (an id) like the official OpenAI SDKs, so an id
 * cannot add path segments, dot segments, a query string or a fragment to a request.
 *
 * @internal Used by the HTTP repositories.
 */
final class PathSegment
{
    /**
     * @throws InvalidArgumentException For empty, "." and ".." values
     */
    public static function encode(string $value): string
    {
        if ($value === '' || $value === '.' || $value === '..') {
            throw new InvalidArgumentException("Invalid path parameter: '{$value}'.");
        }

        // Keep the RFC 3986 path characters the SDKs leave readable, e.g. ':' in fine-tuned model ids
        return strtr(rawurlencode($value), [
            '%21' => '!', '%24' => '$', '%26' => '&', '%27' => "'", '%28' => '(', '%29' => ')', '%2A' => '*',
            '%2B' => '+', '%2C' => ',', '%3B' => ';', '%3D' => '=', '%3A' => ':', '%40' => '@',
        ]);
    }
}
