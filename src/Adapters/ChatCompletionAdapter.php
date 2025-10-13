<?php

declare(strict_types=1);

namespace CreativeCrafts\LaravelAiAssistant\Adapters;

use CreativeCrafts\LaravelAiAssistant\DataTransferObjects\ResponseDto;
use Illuminate\Support\Str;

/**
 * Adapter for OpenAI Chat Completion endpoint.
 *
 * Transforms requests and responses between the unified Response API format
 * and the Chat Completion endpoint format. Supports audio input in chat context,
 * tool calls, and function calling.
 *
 * @internal Used internally by ResponsesBuilder to transform requests for specific endpoints.
 * Do not use directly.
 */
final class ChatCompletionAdapter implements EndpointAdapter
{
    /**
     * Transform unified request to OpenAI Chat Completion format.
     *
     * Handles audio_input by converting to appropriate message format,
     * supports tools, tool_choice, response_format parameters.
     *
     * @param array<string, mixed> $unifiedRequest
     * @return array<string, mixed>
     */
    public function transformRequest(array $unifiedRequest): array
    {
        $messages = $this->buildMessages($unifiedRequest);

        $request = [
            'model' => $unifiedRequest['model'] ?? 'gpt-4o-mini',
            'messages' => $messages,
        ];

        // Add optional parameters if provided
        if (isset($unifiedRequest['temperature'])) {
            $request['temperature'] = $unifiedRequest['temperature'];
        }

        if (isset($unifiedRequest['max_tokens'])) {
            $request['max_tokens'] = $unifiedRequest['max_tokens'];
        }

        if (isset($unifiedRequest['top_p'])) {
            $request['top_p'] = $unifiedRequest['top_p'];
        }

        if (isset($unifiedRequest['frequency_penalty'])) {
            $request['frequency_penalty'] = $unifiedRequest['frequency_penalty'];
        }

        if (isset($unifiedRequest['presence_penalty'])) {
            $request['presence_penalty'] = $unifiedRequest['presence_penalty'];
        }

        if (isset($unifiedRequest['tools'])) {
            $request['tools'] = $unifiedRequest['tools'];
        }

        if (isset($unifiedRequest['tool_choice'])) {
            $request['tool_choice'] = $unifiedRequest['tool_choice'];
        }

        if (isset($unifiedRequest['response_format'])) {
            $request['response_format'] = $unifiedRequest['response_format'];
        }

        if (isset($unifiedRequest['stream'])) {
            $request['stream'] = $unifiedRequest['stream'];
        }

        if (isset($unifiedRequest['user'])) {
            $request['user'] = $unifiedRequest['user'];
        }

        return $request;
    }

    /**
     * Transform OpenAI Chat Completion response to unified ResponseDto.
     *
     * Extracts message content and normalizes the response structure,
     * including usage statistics and metadata.
     *
     * @param array{
     *     id?: string,
     *     object?: string,
     *     created?: int,
     *     model?: string,
     *     choices?: array<int, array{
     *         index?: int,
     *         message?: array{role?: string, content?: string|null, tool_calls?: array<mixed>},
     *         finish_reason?: string
     *     }>,
     *     usage?: array{prompt_tokens?: int, completion_tokens?: int, total_tokens?: int}
     * } $apiResponse
     * @return ResponseDto
     */
    public function transformResponse(array $apiResponse): ResponseDto
    {
        $id = isset($apiResponse['id']) ? (string) $apiResponse['id'] : 'chatcmpl_' . Str::uuid()->toString();
        $choice = $apiResponse['choices'][0] ?? [];
        $message = $choice['message'] ?? [];
        $content = $message['content'] ?? null;
        $text = is_string($content) ? $content : null;

        return new ResponseDto(
            id: $id,
            status: 'completed',
            text: $text,
            raw: $apiResponse,
            conversationId: null,
            audioContent: null,
            images: null,
            type: 'chat_completion',
            metadata: [
                'model' => $apiResponse['model'] ?? null,
                'created' => $apiResponse['created'] ?? null,
                'finish_reason' => $choice['finish_reason'] ?? null,
                'message' => $message,
                'usage' => $apiResponse['usage'] ?? null,
            ],
        );
    }

    /**
     * Build messages array from unified request.
     *
     * Handles audio_input by converting to appropriate message format.
     *
     * @param array<string, mixed> $unifiedRequest
     * @return array<int, array<string, mixed>>
     */
    private function buildMessages(array $unifiedRequest): array
    {
        $messages = [];

        // Handle existing messages array
        if (isset($unifiedRequest['messages']) && is_array($unifiedRequest['messages'])) {
            $messages = $unifiedRequest['messages'];
        }

        // Handle simple text input
        if (isset($unifiedRequest['input']) && is_string($unifiedRequest['input'])) {
            $messages[] = [
                'role' => 'user',
                'content' => $unifiedRequest['input'],
            ];
        }

        // Handle audio_input in the chat context
        if (isset($unifiedRequest['audio_input']) && is_array($unifiedRequest['audio_input'])) {
            $audioMessage = $this->buildAudioInputMessage($unifiedRequest['audio_input']);
            if ($audioMessage !== null) {
                $messages[] = $audioMessage;
            }
        }

        return $messages;
    }

    /**
     * Build message with audio input.
     *
     * Converts audio input to appropriate message format for chat completion.
     *
     * @param array<string, mixed> $audioInput
     * @return array<string, mixed>|null
     */
    private function buildAudioInputMessage(array $audioInput): ?array
    {
        // OpenAI Chat Completion API supports audio in messages
        // Format: {"role": "user", "content": [{"type": "input_audio", "input_audio": {"data": "...", "format": "..."}}]}
        if (isset($audioInput['data'])) {
            return [
                'role' => 'user',
                'content' => [
                    [
                        'type' => 'input_audio',
                        'input_audio' => [
                            'data' => $audioInput['data'],
                            'format' => $audioInput['format'] ?? 'wav',
                        ],
                    ],
                ],
            ];
        }

        return null;
    }
}
