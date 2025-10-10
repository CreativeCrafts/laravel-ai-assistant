<?php

declare(strict_types=1);

namespace CreativeCrafts\LaravelAiAssistant\Adapters;

use CreativeCrafts\LaravelAiAssistant\DataTransferObjects\ResponseDto;
use Illuminate\Support\Str;

/**
 * Adapter for OpenAI Image Generation endpoint.
 *
 * Transforms requests and responses between the unified Response API format
 * and the Image Generation endpoint format. Supports both DALL-E 2 and DALL-E 3.
 */
final class ImageGenerationAdapter implements EndpointAdapter
{
    /**
     * Transform unified request to OpenAI Image Generation format.
     *
     * @param array<string, mixed> $unifiedRequest
     * @return array<string, mixed>
     */
    public function transformRequest(array $unifiedRequest): array
    {
        $image = is_array($unifiedRequest['image'] ?? null) ? $unifiedRequest['image'] : [];

        return [
            'prompt' => $image['prompt'] ?? null,
            'model' => $image['model'] ?? 'dall-e-2',
            'n' => $image['n'] ?? 1,
            'size' => $image['size'] ?? '1024x1024',
            'quality' => $image['quality'] ?? null,
            'style' => $image['style'] ?? null,
            'response_format' => $image['response_format'] ?? 'url',
        ];
    }

    /**
     * Transform OpenAI Image Generation response to unified ResponseDto.
     *
     * @param array{
     *     created?: int,
     *     data?: array<int, array{url?: string, b64_json?: string, revised_prompt?: string}>
     * } $apiResponse
     * @return ResponseDto
     */
    public function transformResponse(array $apiResponse): ResponseDto
    {
        $id = 'image_generation_' . Str::uuid()->toString();
        $images = $apiResponse['data'] ?? [];

        return new ResponseDto(
            id: $id,
            status: 'completed',
            text: null,
            raw: $apiResponse,
            conversationId: null,
            audioContent: null,
            images: $images,
            type: 'image_generation',
            metadata: [
                'created' => $apiResponse['created'] ?? null,
                'count' => count($images),
            ],
        );
    }
}
