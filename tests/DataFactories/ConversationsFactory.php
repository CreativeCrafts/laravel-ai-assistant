<?php

declare(strict_types=1);

namespace CreativeCrafts\LaravelAiAssistant\Tests\DataFactories;

final class ConversationsFactory
{
    public static function conversationId(string $prefix = 'conv_'): string
    {
        return $prefix . str_pad(bin2hex(random_bytes(12)), 24, '0');
    }

    /**
     * Build a list of conversation items in Responses API shape.
     * @param array<int,array> $items
     */
    public static function items(array $items = []): array
    {
        if ($items === []) {
            $items = [
                [
                    'id' => self::conversationItemId(),
                    'type' => 'message',
                    'role' => 'user',
                    'content' => [
                        ['type' => 'text', 'text' => 'Hello!'],
                    ],
                ],
            ];
        }
        return ['data' => $items, 'object' => 'list'];
    }

    public static function conversationItemId(string $prefix = 'item_'): string
    {
        return $prefix . str_pad(bin2hex(random_bytes(12)), 24, '0');
    }

    /**
     * Helper to build a tool_result item for continuation.
     */
    public static function toolResultItem(string $toolCallId, string $text): array
    {
        return [
            'type' => 'tool_result',
            'role' => 'tool',
            'tool_call_id' => $toolCallId,
            'content' => [
                ['type' => 'output_text', 'text' => $text],
            ],
        ];
    }
}
