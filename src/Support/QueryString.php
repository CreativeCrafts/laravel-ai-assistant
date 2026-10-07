<?php

declare(strict_types=1);

namespace CreativeCrafts\LaravelAiAssistant\Support;

use BackedEnum;
use Stringable;

/**
 * Encodes query parameters the way the OpenAI API expects them:
 * - list arrays become repeated bracket keys: include[]=a&include[]=b
 * - associative arrays become nested keys: metadata[key]=value
 * - booleans become "true"/"false" and null values are skipped
 *
 * @internal Used by the transport and HTTP repositories.
 */
final class QueryString
{
    /**
     * @param array<array-key, mixed> $params
     */
    public static function build(array $params): string
    {
        $pairs = [];
        foreach ($params as $key => $value) {
            self::encode((string)$key, $value, $pairs);
        }

        return implode('&', $pairs);
    }

    /**
     * Append encoded parameters to a path that may already carry a query string.
     *
     * @param array<array-key, mixed> $params
     */
    public static function append(string $path, array $params): string
    {
        $query = self::build($params);
        if ($query === '') {
            return $path;
        }

        return $path . (str_contains($path, '?') ? '&' : '?') . $query;
    }

    /**
     * @param array<int, string> $pairs
     */
    private static function encode(string $key, mixed $value, array &$pairs): void
    {
        if ($value === null) {
            return;
        }

        if (is_array($value)) {
            $isList = array_is_list($value);
            foreach ($value as $subKey => $subValue) {
                self::encode($isList ? $key . '[]' : $key . '[' . $subKey . ']', $subValue, $pairs);
            }

            return;
        }

        if (is_bool($value)) {
            $value = $value ? 'true' : 'false';
        } elseif ($value instanceof BackedEnum) {
            $value = $value->value;
        }

        if (!is_scalar($value) && !$value instanceof Stringable) {
            return;
        }

        $pairs[] = rawurlencode($key) . '=' . rawurlencode((string)$value);
    }
}
