<?php

declare(strict_types=1);

namespace CreativeCrafts\LaravelAiAssistant\Tests\DataFactories;

final class WebhooksFactory
{
    public static function responseId(string $prefix = 'resp_'): string
    {
        return $prefix . str_pad(bin2hex(random_bytes(12)), 24, '0');
    }

    public static function completed(string $responseId = null, array $overrides = []): array
    {
        $responseId = $responseId ?: self::responseId();
        $base = [
            'type' => 'response.completed',
            'response' => [
                'id' => $responseId,
                'status' => 'completed',
            ],
        ];
        return array_replace_recursive($base, $overrides);
    }

    public static function failed(string $responseId = null, string $message = 'failure', array $overrides = []): array
    {
        $responseId = $responseId ?: self::responseId();
        $base = [
            'type' => 'response.failed',
            'response' => [
                'id' => $responseId,
                'status' => 'failed',
            ],
            'error' => [
                'message' => $message,
            ],
        ];
        return array_replace_recursive($base, $overrides);
    }

    public static function toolCallRequested(string $responseId = null, array $toolCalls = [], array $overrides = []): array
    {
        $responseId = $responseId ?: self::responseId();
        if ($toolCalls === []) {
            $toolCalls = [
                [
                    'id' => 'call_' . substr($responseId, -6),
                    'name' => 'lookup',
                    'arguments' => ['q' => 'ping'],
                ],
            ];
        }
        $base = [
            'type' => 'response.tool_call.created',
            'response' => [
                'id' => $responseId,
                'status' => 'requires_action',
                'required_action' => [
                    'submit_tool_outputs' => [
                        'tool_calls' => $toolCalls,
                    ],
                ],
            ],
        ];
        return array_replace_recursive($base, $overrides);
    }
}
