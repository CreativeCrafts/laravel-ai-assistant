<?php

declare(strict_types=1);

namespace CreativeCrafts\LaravelAiAssistant\Adapters;

use CreativeCrafts\LaravelAiAssistant\DataTransferObjects\ResponseDto;
use CreativeCrafts\LaravelAiAssistant\Exceptions\FileValidationException;
use CreativeCrafts\LaravelAiAssistant\Exceptions\ImageVariationException;
use Illuminate\Support\Str;

/**
 * Adapter for OpenAI Image Variation endpoint.
 *
 * Transforms requests and responses between the unified Response API format
 * and the Image Variation endpoint format.
 *
 * @internal Used internally by ResponsesBuilder to transform requests for specific endpoints.
 * Do not use directly.
 */
final class ImageVariationAdapter implements ImageEndpointAdapter
{
    /**
     * Transform unified request to OpenAI Image Variation format.
     *
     * @param array<string, mixed> $unifiedRequest
     * @return array<string, mixed>
     * @throws ImageVariationException If validation fails
     * @throws FileValidationException If file validation fails
     */
    public function transformRequest(array $unifiedRequest): array
    {
        $image = is_array($unifiedRequest['image'] ?? null) ? $unifiedRequest['image'] : [];
        $imagePath = $image['image'] ?? null;
        $size = $image['size'] ?? '1024x1024';
        $n = $image['n'] ?? 1;

        // Validate required image
        if ($imagePath === null) {
            throw ImageVariationException::missingImage();
        }

        // Validate image file
        $this->validateImageFile($imagePath);

        // Validate size
        $validSizes = ['256x256', '512x512', '1024x1024'];
        if (!in_array($size, $validSizes, true)) {
            throw ImageVariationException::invalidSize($size);
        }

        // Validate variation count
        if ($n < 1 || $n > 10) {
            throw ImageVariationException::invalidVariationCount($n);
        }

        return [
            'image' => $imagePath,
            'model' => $image['model'] ?? 'dall-e-2',
            'n' => $n,
            'size' => $size,
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
     * @throws FileValidationException If the file is invalid
     * @throws ImageVariationException If the image validation fails
     */
    private function validateImageFile(mixed $filePath): void
    {
        if (!is_string($filePath)) {
            throw FileValidationException::invalidPathType($filePath);
        }

        if (!file_exists($filePath)) {
            throw FileValidationException::fileNotFound($filePath);
        }

        if (!is_readable($filePath)) {
            throw FileValidationException::fileNotReadable($filePath);
        }

        $supportedFormats = ['png'];
        $extension = strtolower(pathinfo($filePath, PATHINFO_EXTENSION));

        if (!in_array($extension, $supportedFormats, true)) {
            throw ImageVariationException::mustBePng($filePath, $extension);
        }

        $fileSize = filesize($filePath);
        $maxSize = 4 * 1024 * 1024; // 4MB

        if ($fileSize === false) {
            throw FileValidationException::fileNotReadable($filePath);
        }

        if ($fileSize > $maxSize) {
            throw ImageVariationException::imageTooLarge($filePath, $fileSize);
        }
    }
}
