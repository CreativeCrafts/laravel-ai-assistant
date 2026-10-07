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
 * "event:" line, the event name is used as the type. Data that is not JSON is yielded as
 * ['data' => string] instead of being dropped. The "[DONE]" sentinel ends the stream.
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
                // A blank line ends the frame (most transports strip these; handle them when present)
                if ($buffer !== '') {
                    yield ['data' => $buffer];
                    $buffer = '';
                }
                $event = null;
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

            if (trim($data) === '[DONE]') {
                if ($buffer !== '') {
                    yield ['data' => $buffer];
                }
                return;
            }

            if ($buffer !== '') {
                $combined = $buffer . "\n" . $data;
                if (self::isJson($combined)) {
                    $buffer = '';
                    yield self::event($combined, $event);
                    $event = null;
                    continue;
                }

                if (!self::isJson($data)) {
                    $buffer = strlen($combined) > self::MAX_BUFFER_BYTES ? '' : $combined;
                    continue;
                }

                // The buffered data never became valid JSON; surface it and start over with this frame
                yield ['data' => $buffer];
                $buffer = '';
            }

            if (!self::isJson($data)) {
                $buffer = $data;
                continue;
            }

            yield self::event($data, $event);
            $event = null;
        }

        if ($buffer !== '') {
            yield ['data' => $buffer];
        }
    }

    private static function isJson(string $data): bool
    {
        json_decode($data);

        return json_last_error() === JSON_ERROR_NONE;
    }

    /**
     * @return array<string, mixed>
     */
    private static function event(string $json, ?string $event): array
    {
        $decoded = json_decode($json, true);
        if (!is_array($decoded)) {
            $decoded = ['data' => $decoded];
        }
        if (!isset($decoded['type']) && $event !== null && $event !== '') {
            $decoded['type'] = $event;
        }

        /** @var array<string, mixed> $decoded */
        return $decoded;
    }
}
