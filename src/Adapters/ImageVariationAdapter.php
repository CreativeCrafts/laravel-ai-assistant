<?php

declare(strict_types=1);

namespace CreativeCrafts\LaravelAiAssistant\Adapters;

use CreativeCrafts\LaravelAiAssistant\DataTransferObjects\ResponseDto;
use Illuminate\Support\Str;
use InvalidArgumentException;

/**
 * Adapter for OpenAI Image Variation endpoint.
 *
 * Transforms requests and responses between the unified Response API format
 * and the Image Variation endpoint format.
 */
final class ImageVariationAdapter implements EndpointAdapter
{
    /**
     * Transform unified request to OpenAI Image Variation format.
     *
     * @param array<string, mixed> $unifiedRequest
     * @return array<string, mixed>
     * @throws InvalidArgumentException If the image file is invalid
     */
    public function transformRequest(array $unifiedRequest): array
    {
        $image = is_array($unifiedRequest['image'] ?? null) ? $unifiedRequest['image'] : [];
        $imagePath = $image['image'] ?? null;

        if ($imagePath !== null) {
            $this->validateImageFile($imagePath);
        }

        return [
            'image' => $imagePath,
            'model' => $image['model'] ?? 'dall-e-2',
            'n' => $image['n'] ?? 1,
            'size' => $image['size'] ?? '1024x1024',
            'response_format' => $image['response_format'] ?? 'url',
        ];
    }

    /**
     * Transform OpenAI Image Variation response to unified ResponseDto.
     *
     * @param array{
     *     created?: int,
     *     data?: array<int, array{url?: string, b64_json?: string}>
     * } $apiResponse
     * @return ResponseDto
     */
    public function transformResponse(array $apiResponse): ResponseDto
    {
        $id = 'image_variation_' . Str::uuid()->toString();
        $images = $apiResponse['data'] ?? [];

        return new ResponseDto(
            id: $id,
            status: 'completed',
            text: null,
            raw: $apiResponse,
            conversationId: null,
            audioContent: null,
            images: $images,
            type: 'image_variation',
            metadata: [
                'created' => $apiResponse['created'] ?? null,
                'count' => count($images),
            ],
        );
    }

    /**
     * Validate that the image file exists, is readable, and has a supported format.
     *
     * @param mixed $filePath
     * @throws InvalidArgumentException If the file is invalid
     */
    private function validateImageFile(mixed $filePath): void
    {
        if (!is_string($filePath)) {
            throw new InvalidArgumentException('Image file path must be a string.');
        }

        if (!file_exists($filePath)) {
            throw new InvalidArgumentException("Image file does not exist: {$filePath}");
        }

        if (!is_readable($filePath)) {
            throw new InvalidArgumentException("Image file is not readable: {$filePath}");
        }

        $supportedFormats = ['png'];
        $extension = strtolower(pathinfo($filePath, PATHINFO_EXTENSION));

        if (!in_array($extension, $supportedFormats, true)) {
            throw new InvalidArgumentException(
                "Unsupported image format: {$extension}. Image variation only supports PNG format."
            );
        }

        $fileSize = filesize($filePath);
        $maxSize = 4 * 1024 * 1024;

        if ($fileSize === false || $fileSize > $maxSize) {
            throw new InvalidArgumentException(
                "Image file size must be less than 4MB. Current size: " . ($fileSize !== false ? round($fileSize / 1024 / 1024, 2) . 'MB' : 'unknown')
            );
        }
    }
}
