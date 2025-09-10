<?php

declare(strict_types=1);

namespace CreativeCrafts\LaravelAiAssistant\Http\Responses;

use Generator;
use Symfony\Component\HttpFoundation\StreamedResponse;

class StreamedAiResponse
{
    /**
     * @param Generator<int, array{type?:string,data?:string}|string> $generator
     */
    public static function fromGenerator(Generator $generator, int $heartbeatSeconds = 15): StreamedResponse
    {
        $lastBeat = time();

        $callback = function () use ($generator, $heartbeatSeconds, &$lastBeat) {
            // SSE headers are set by the StreamedResponse instance
            foreach ($generator as $event) {
                if (is_array($event)) {
                    $typeVal = $event['type'] ?? null;
                    $type = is_string($typeVal) ? $typeVal : 'message';
                    $dataVal = $event['data'] ?? null;
                    $data = is_string($dataVal) ? $dataVal : '';
                } else {
                    $type = 'message';
                    $data = (string)$event;
                }

                if ($type !== 'heartbeat') {
                    echo "event: {$type}\n";
                    $lines = preg_split("/\r\n|\n|\r/", $data) ?: [$data];
                    foreach ($lines as $line) {
                        echo 'data: ' . $line . "\n";
                    }
                    echo "\n";
                    @ob_flush();
                    @flush();
                }

                if ($heartbeatSeconds > 0 && (time() - $lastBeat) >= $heartbeatSeconds) {
                    echo ": keep-alive\n\n";
                    @ob_flush();
                    @flush();
                    $lastBeat = time();
                }
            }

            // Signal completion similar to OpenAI
            echo "event: done\n";
            echo "data: [DONE]\n\n";
            @ob_flush();
            @flush();
        };

        return new StreamedResponse(
            $callback,
            200,
            [
                'Content-Type' => 'text/event-stream',
                'Cache-Control' => 'no-cache, no-transform',
                'X-Accel-Buffering' => 'no',
                'Connection' => 'keep-alive',
            ]
        );
    }
}
