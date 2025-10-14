<?php

declare(strict_types=1);

namespace CreativeCrafts\LaravelAiAssistant\Adapters;

use CreativeCrafts\LaravelAiAssistant\DataTransferObjects\ResponseDto;
use Illuminate\Support\Str;

/**
 * Adapter for OpenAI Response API endpoint.
 *
 * Transforms requests and responses between the unified Response API format
 * and the Response API endpoint format. The Response API is OpenAI's unified
 * endpoint that handles standard text/chat requests with multimodal support.
 *
 * @internal Used internally by ResponsesBuilder to transform requests for specific endpoints.
 * Do not use directly.
 */
final class ResponseApiAdapter implements TextEndpointAdapter
{
    /**
     * Transform unified request to OpenAI Response API format.
     *
     * The Response API format is similar to the unified format, so this is
     * primarily a pass-through with parameter normalization.
     *
     * @param array<string, mixed> $unifiedRequest
     * @return array<string, mixed>
     */
    public function transformRequest(array $unifiedRequest): array
    {
        $request = [
            'model' => $unifiedRequest['model'] ?? 'gpt-4o-mini',
        ];

        // Handle input/message content
        if (isset($unifiedRequest['input'])) {
            $request['input'] = $unifiedRequest['input'];
        }

        if (isset($unifiedRequest['messages'])) {
            $request['messages'] = $unifiedRequest['messages'];
        }

        // Handle conversation context
        if (isset($unifiedRequest['conversation_id'])) {
            $request['conversation_id'] = $unifiedRequest['conversation_id'];
        }

        // Handle modalities (text, audio, etc.)
        if (isset($unifiedRequest['modalities'])) {
            $request['modalities'] = $unifiedRequest['modalities'];
        }

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
            $request['text'] = [
                'format' => $unifiedRequest['response_format'],
            ];
        }

        if (isset($unifiedRequest['stream'])) {
            $request['stream'] = $unifiedRequest['stream'];
        }

        if (isset($unifiedRequest['user'])) {
            $request['user'] = $unifiedRequest['user'];
        }

        if (isset($unifiedRequest['metadata'])) {
            $request['metadata'] = $unifiedRequest['metadata'];
        }

        if (isset($unifiedRequest['store'])) {
            $request['store'] = $unifiedRequest['store'];
        }

        return $request;
    }

    /**
     * Transform OpenAI Response API response to unified ResponseDto.
     *
     * Extracts content and normalizes the response structure into the
     * standardized ResponseDto format.
     *
     * @param array{
     *     id?: string,
     *     object?: string,
     *     created?: int,
     *     model?: string,
     *     output_text?: string,
     *     content?: string,
     *     conversation?: array{id?: string},
     *     conversationId?: string,
     *     status?: string,
     *     usage?: array{prompt_tokens?: int, completion_tokens?: int, total_tokens?: int},
     *     metadata?: array<string, mixed>
     * } $apiResponse
     * @return ResponseDto
     */
    public function transformResponse(array $apiResponse): ResponseDto
    {
        $id = isset($apiResponse['id']) ? (string) $apiResponse['id'] : 'resp_' . Str::uuid()->toString();
        $status = $apiResponse['status'] ?? 'completed';

        // Extract text from various possible locations
        $text = $this->extractText($apiResponse);

        // Extract conversation ID from various possible locations
        $conversationId = null;
        if (isset($apiResponse['conversationId'])) {
            $conversationId = (string) $apiResponse['conversationId'];
        } elseif (isset($apiResponse['conversation']['id'])) {
            $conversationId = (string) $apiResponse['conversation']['id'];
        }

        return new ResponseDto(
            id: $id,
            status: $status,
            text: $text,
            raw: $apiResponse,
            conversationId: $conversationId,
            audioContent: null,
            images: null,
            type: 'response_api',
            metadata: [
                'model' => $apiResponse['model'] ?? null,
                'created' => $apiResponse['created'] ?? null,
                'usage' => $apiResponse['usage'] ?? null,
                'metadata' => $apiResponse['metadata'] ?? null,
            ],
        );
    }

    /**
     * Extract text content from API response.
     *
     * The Response API may return text in different formats depending on
     * the response structure.
     *
     * @param array<string, mixed> $response
     * @return string|null
     */
    private function extractText(array $response): ?string
    {
        // Check for output_text (common in Response API)
        if (isset($response['output_text']) && is_string($response['output_text'])) {
            return $response['output_text'];
        }

        // Check for the content field
        if (isset($response['content']) && is_string($response['content'])) {
            return $response['content'];
        }

        // Check for text field
        if (isset($response['text']) && is_string($response['text'])) {
            return $response['text'];
        }

        // Check for messages field (if string)
        if (isset($response['messages']) && is_string($response['messages'])) {
            return $response['messages'];
        }

        return null;
    }
}
