<?php

declare(strict_types=1);

namespace CreativeCrafts\LaravelAiAssistant\Tests\DataFactories;

final class ResponsesFactory
{
    public static function id(string $prefix = 'resp_'): string
    {
        return $prefix . str_pad(bin2hex(random_bytes(12)), 24, '0');
    }

    public static function conversationId(string $prefix = 'conv_'): string
    {
        return $prefix . str_pad(bin2hex(random_bytes(12)), 24, '0');
    }

    public static function syncTextResponse(string $conversationId, string $text, array $overrides = []): array
    {
        $base = [
            'id' => self::id(),
            'conversation_id' => $conversationId,
            'status' => 'completed',
            'output' => [
                [
                    'type' => 'output_text',
                    'content' => [ ['text' => $text] ],
                ],
            ],
            'usage' => [ 'input_tokens' => 3, 'output_tokens' => strlen($text) ],
            'finish_reason' => 'stop',
        ];
        return array_replace_recursive($base, $overrides);
    }

    public static function toolCallItem(string $id, string $name, array $arguments): array
    {
        return [
            'type' => 'tool_call',
            'id' => $id,
            'name' => $name,
            'arguments' => $arguments,
        ];
    }

    public static function withToolCalls(string $conversationId, array $toolCalls, array $overrides = []): array
    {
        // $toolCalls: array of [id, name, arguments(array)]
        $items = [];
        foreach ($toolCalls as $tc) {
            $items[] = self::toolCallItem($tc['id'] ?? self::id('tool_'), (string)$tc['name'], (array)($tc['arguments'] ?? []));
        }
        $base = [
            'id' => self::id(),
            'conversation_id' => $conversationId,
            'status' => 'in_progress',
            'output' => $items,
        ];
        return array_replace_recursive($base, $overrides);
    }

    public static function afterToolResultsFinal(string $conversationId, string $text): array
    {
        return self::syncTextResponse($conversationId, $text);
    }

    /**
     * Build SSE lines for a simple streaming session with deltas and completion.
     * @param array<int,string> $deltas
     * @param bool $includeCompleted
     * @return array<int,string>
     */
    public static function streamingTextSse(array $deltas, bool $includeCompleted = true): array
    {
        $lines = [];
        foreach ($deltas as $d) {
            $payload = json_encode(['delta' => $d], JSON_UNESCAPED_SLASHES);
            $lines[] = 'event: response.output_text.delta';
            $lines[] = 'data: ' . $payload;
            $lines[] = '';
        }
        if ($includeCompleted) {
            $payload = json_encode(['type' => 'response.completed'], JSON_UNESCAPED_SLASHES);
            $lines[] = 'event: response.completed';
            $lines[] = 'data: ' . $payload;
            $lines[] = '';
        }
        return $lines;
    }

    /**
     * SSE lines that emit a tool_call created event followed by completion.
     */
    public static function streamingToolCallSse(string $name, array $args, ?string $toolCallId = null): array
    {
        $toolCallId = $toolCallId ?: self::id('call_');
        $toolCall = [ 'id' => $toolCallId, 'name' => $name, 'arguments' => $args ];
        $delta = [ 'tool_call' => $toolCall ];

        $lines = [];
        $lines[] = 'event: response.tool_call.created';
        $lines[] = 'data: ' . json_encode($delta, JSON_UNESCAPED_SLASHES);
        $lines[] = '';
        $lines[] = 'event: response.completed';
        $lines[] = 'data: ' . json_encode(['type' => 'response.completed'], JSON_UNESCAPED_SLASHES);
        $lines[] = '';
        return $lines;
    }
}
