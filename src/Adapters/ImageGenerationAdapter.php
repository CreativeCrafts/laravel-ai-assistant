<?php

declare(strict_types=1);

namespace CreativeCrafts\LaravelAiAssistant\Adapters;

use CreativeCrafts\LaravelAiAssistant\DataTransferObjects\ResponseDto;
use CreativeCrafts\LaravelAiAssistant\Exceptions\ImageGenerationException;
use Illuminate\Support\Str;

/**
 * Adapter for OpenAI Image Generation endpoint.
 *
 * Transforms requests and responses between the unified Response API format
 * and the Image Generation endpoint format. Supports both DALL-E 2 and DALL-E 3.
 *
 * @internal Used internally by ResponsesBuilder to transform requests for specific endpoints.
 * Do not use directly.
 */
final class ImageGenerationAdapter implements EndpointAdapter
{
    /**
     * Transform unified request to OpenAI Image Generation format.
     *
     * @param array<string, mixed> $unifiedRequest
     * @return array<string, mixed>
     * @throws ImageGenerationException If validation fails
     */
    public function transformRequest(array $unifiedRequest): array
    {
        $image = is_array($unifiedRequest['image'] ?? null) ? $unifiedRequest['image'] : [];

        $prompt = $image['prompt'] ?? null;
        $model = $image['model'] ?? 'dall-e-2';
        $n = $image['n'] ?? 1;
        $size = $image['size'] ?? '1024x1024';
        $quality = $image['quality'] ?? null;
        $style = $image['style'] ?? null;

        // Validate prompt
        if ($prompt === null) {
            throw ImageGenerationException::missingPrompt();
        }

        if (!is_string($prompt)) {
            $prompt = (string) $prompt;
        }

        if (trim($prompt) === '') {
            throw ImageGenerationException::emptyPrompt();
        }

        // Validate prompt length (OpenAI limit is 4000 characters)
        if (mb_strlen($prompt) > 4000) {
            throw ImageGenerationException::promptTooLong($prompt, 4000);
        }

        // Validate size
        $this->validateSize($size, $model);

        // Validate image count
        $this->validateImageCount($n, $model);

        // Validate quality (only for DALL-E 3)
        if ($quality !== null) {
            $this->validateQuality($quality);
        }

        // Validate style (only for DALL-E 3)
        if ($style !== null) {
            $this->validateStyle($style);
        }

        return [
            'prompt' => $prompt,
            'model' => $model,
            'n' => $n,
            'size' => $size,
            'quality' => $quality,
            'style' => $style,
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

    /**
     * Validate image size based on model.
     *
     * @throws ImageGenerationException If size is invalid
     */
    private function validateSize(string $size, string $model): void
    {
        $validSizes = match ($model) {
            'dall-e-3' => ['1024x1024', '1792x1024', '1024x1792'],
            'dall-e-2' => ['256x256', '512x512', '1024x1024'],
            default => ['1024x1024', '512x512', '256x256'],
        };

        if (!in_array($size, $validSizes, true)) {
            throw ImageGenerationException::invalidSize($size, $model);
        }
    }

    /**
     * Validate number of images to generate.
     *
     * @throws ImageGenerationException If count is invalid
     */
    private function validateImageCount(int $n, string $model): void
    {
        $maxCount = $model === 'dall-e-3' ? 1 : 10;

        if ($n < 1 || $n > $maxCount) {
            throw ImageGenerationException::invalidImageCount($n, $model);
        }
    }

    /**
     * Validate quality parameter.
     *
     * @throws ImageGenerationException If quality is invalid
     */
    private function validateQuality(string $quality): void
    {
        $validQualities = ['standard', 'hd'];

        if (!in_array($quality, $validQualities, true)) {
            throw ImageGenerationException::invalidQuality($quality);
        }
    }

    /**
     * Validate style parameter.
     *
     * @throws ImageGenerationException If style is invalid
     */
    private function validateStyle(string $style): void
    {
        $validStyles = ['vivid', 'natural'];

        if (!in_array($style, $validStyles, true)) {
            throw ImageGenerationException::invalidStyle($style);
        }
    }
}
