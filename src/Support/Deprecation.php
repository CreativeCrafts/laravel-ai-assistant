<?php

declare(strict_types=1);

namespace CreativeCrafts\LaravelAiAssistant\Support;

use Illuminate\Support\Facades\Config;

final class Deprecation
{
    /** @var array<string, true> */
    private static array $emitted = [];

    /**
     * Emit a deprecation warning once per key if enabled.
     * Opt-in via config('ai-assistant.deprecations.emit') or env AI_ASSISTANT_EMIT_DEPRECATIONS=true
     */
    public static function maybeOnce(string $key, string $message): void
    {
        if (self::$emitted[$key] ?? false) {
            return;
        }

        // Only emit if explicitly enabled to avoid breaking tests/CI.
        $enabled = Config::boolean(key: 'ai-assistant.deprecations.emit', default: false);
        if (!$enabled) {
            return;
        }

        // Mark as emitted and trigger PHP user deprecation.
        self::$emitted[$key] = true;
        @trigger_error($message, E_USER_DEPRECATED);
    }
}
