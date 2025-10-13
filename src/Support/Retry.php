<?php

declare(strict_types=1);

namespace CreativeCrafts\LaravelAiAssistant\Support;

use Random\RandomException;
use Throwable;

/**
 * @internal Used internally for retry logic with exponential backoff.
 * Do not use directly.
 */
final class Retry
{
    /**
     * Build exponential backoff delays in milliseconds with jitter.
     *
     * @return int[] delays in milliseconds
     * @throws RandomException
     */
    public static function backoffDelays(int $maxRetries, int $initialMs, int $maxMs): array
    {
        $delays = [];
        $delay = max(0, $initialMs);
        for ($i = 0; $i < max(0, $maxRetries); $i++) {
            $jitter = random_int(0, (int)floor($delay * 0.25));
            $delays[] = min($maxMs, $delay + $jitter);
            $delay = min($maxMs, $delay * 2);
        }
        return $delays;
    }

    /**
     * Decide if an exception is worth retrying.
     */
    public static function shouldRetry(Throwable $e): bool
    {
        $code = (int)$e->getCode();
        if (in_array($code, [408, 425, 429, 500, 502, 503, 504], true)) {
            return true;
        }
        $cls = get_class($e);
        foreach (['Connection', 'Timeout', 'RateLimit', 'Retry', 'TooManyRequests'] as $needle) {
            if (stripos($cls, $needle) !== false || stripos($e->getMessage(), $needle) !== false) {
                return true;
            }
        }
        return false;
    }

    public static function usleepMs(int $ms): void
    {
        if ($ms <= 0) {
            return;
        }
        usleep($ms * 1000);
    }
}
