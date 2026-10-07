<?php

declare(strict_types=1);

namespace CreativeCrafts\LaravelAiAssistant\Support;

use Generator;

/**
 * Decodes raw Server-Sent Events lines (as yielded by OpenAITransport::streamRequest()) into
 * JSON event payloads.
 *
 * Each "data:" frame carries one JSON document; continuation lines are buffered until the
 * document is complete. When a payload has no "type" property but its frame was preceded by an
 * "event:" line, the event name is used as the type. The "[DONE]" sentinel ends the stream.
 *
 * @internal Used by the HTTP repositories for streaming endpoints.
 */
final class ServerSentEvents
{
    private const MAX_BUFFER_BYTES = 8_388_608;

    /**
     * @param iterable<string> $lines
     * @return Generator<int, array<string, mixed>>
     */
    public static function decode(iterable $lines): Generator
    {
        $event = null;
        $buffer = '';

        foreach ($lines as $line) {
            $line = rtrim($line, "\r\n");

            if ($line === '') {
                $event = null;
                $buffer = '';
                continue;
            }

            if (str_starts_with($line, ':')) {
                continue;
            }

            if (str_starts_with($line, 'event:')) {
                $event = trim(substr($line, strlen('event:')));
                continue;
            }

            if (!str_starts_with($line, 'data:')) {
                continue;
            }

            $data = substr($line, strlen('data:'));
            if (str_starts_with($data, ' ')) {
                $data = substr($data, 1);
            }

            if ($buffer === '' && trim($data) === '[DONE]') {
                return;
            }

            $buffer = $buffer === '' ? $data : $buffer . "\n" . $data;
            $decoded = json_decode($buffer, true);
            if (json_last_error() !== JSON_ERROR_NONE) {
                if (strlen($buffer) > self::MAX_BUFFER_BYTES) {
                    $buffer = '';
                }
                continue;
            }

            $buffer = '';
            if (!is_array($decoded)) {
                $decoded = ['data' => $decoded];
            }
            if (!isset($decoded['type']) && $event !== null && $event !== '') {
                $decoded['type'] = $event;
            }
            $event = null;

            /** @var array<string, mixed> $decoded */
            yield $decoded;
        }
    }
}
