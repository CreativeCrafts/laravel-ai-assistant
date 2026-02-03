<?php

declare(strict_types=1);

namespace CreativeCrafts\LaravelAiAssistant\Adapters;

use CreativeCrafts\LaravelAiAssistant\Contracts\Adapters\ImageEndpointAdapter;
use CreativeCrafts\LaravelAiAssistant\DataTransferObjects\ResponseDto;
use CreativeCrafts\LaravelAiAssistant\Exceptions\FileValidationException;
use CreativeCrafts\LaravelAiAssistant\Exceptions\ImageEditException;
use Illuminate\Support\Str;

/**
 * Adapter for OpenAI Image Edit endpoint.
 *
 * Transforms requests and responses between the unified Response API format
 * and the Image Edit endpoint format.
 *
 * @internal Used internally by ResponsesBuilder to transform requests for specific endpoints.
 * Do not use directly.
 */
final class ImageEditAdapter implements ImageEndpointAdapter
{
    /**
     * Transform unified request to OpenAI Image Edit format.
     *
     * @param array<string, mixed> $unifiedRequest
     * @return array<string, mixed>
     * @throws ImageEditException If validation fails
     * @throws FileValidationException If file validation fails
     */
    public function transformRequest(array $unifiedRequest): array
    {
        $image = is_array($unifiedRequest['image'] ?? null) ? $unifiedRequest['image'] : [];
        $imagePath = $image['image'] ?? null;
        $maskPath = $image['mask'] ?? null;
        $prompt = $image['prompt'] ?? null;

        // Validate required fields
        if ($imagePath === null) {
            throw ImageEditException::missingImage();
        }

        if ($prompt === null || $prompt === '' || trim($prompt) === '') {
            throw ImageEditException::missingPrompt();
        }

        // Validate image file
        $this->validateImageFile($imagePath, false);

        // Validate mask file if provided
        if ($maskPath !== null) {
            $this->validateImageFile($maskPath, true);
        }

        return [
            'image' => $imagePath,
            'prompt' => $prompt,
            'mask' => $maskPath,
            'model' => $image['model'] ?? 'dall-e-2',
            'n' => $image['n'] ?? 1,
            'size' => $image['size'] ?? '1024x1024',
            'response_format' => $image['response_format'] ?? 'url',
        ];
    }

    /**
     * Transform OpenAI Image Edit response to unified ResponseDto.
     *
     * @param array{
     *     created?: int,
     *     data?: array<int, array{url?: string, b64_json?: string}>
     * } $apiResponse
     * @return ResponseDto
     */
    public function transformResponse(array $apiResponse): ResponseDto
    {
        $id = 'image_edit_' . Str::uuid()->toString();
        $images = $apiResponse['data'] ?? [];

        return new ResponseDto(
            id: $id,
            status: 'completed',
            text: null,
            raw: $apiResponse,
            conversationId: null,
            audioContent: null,
            images: $images,
            type: 'image_edit',
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
     * @param bool $isMask Whether this is a mask file
     * @throws FileValidationException If the file is invalid
     * @throws ImageEditException If the image validation fails
     */
    private function validateImageFile(mixed $filePath, bool $isMask): void
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
            if ($isMask) {
                throw ImageEditException::invalidMask($filePath, "Mask must be in PNG format. Current format: {$extension}");
            }
            throw ImageEditException::unsupportedFormat($filePath, $extension);
        }

        $fileSize = filesize($filePath);
        $maxSize = 4 * 1024 * 1024; // 4MB

        if ($fileSize === false) {
            throw FileValidationException::fileNotReadable($filePath);
        }

        if ($fileSize > $maxSize) {
            throw ImageEditException::imageTooLarge($filePath, $fileSize);
        }
    }
}
