<?php

declare(strict_types=1);

namespace CreativeCrafts\LaravelAiAssistant\Exceptions;

use RuntimeException;
use Throwable;

/**
 * Exception thrown when image editing fails.
 *
 * Includes image information, prompt, and failure reason to help developers
 * quickly identify and resolve image editing issues.
 */
final class ImageEditException extends RuntimeException
{
    private ?string $imagePath;
    private ?string $maskPath;
    private ?string $prompt;
    private ?string $reason;
    private ?string $model;
    private ?string $size;

    public function __construct(
        string $message,
        ?string $imagePath = null,
        ?string $maskPath = null,
        ?string $prompt = null,
        ?string $reason = null,
        ?string $model = null,
        ?string $size = null,
        int $code = 0,
        ?Throwable $previous = null
    ) {
        parent::__construct($message, $code, $previous);
        $this->imagePath = $imagePath;
        $this->maskPath = $maskPath;
        $this->prompt = $prompt;
        $this->reason = $reason;
        $this->model = $model;
        $this->size = $size;
    }

    /**
     * Create exception for missing image.
     */
    public static function missingImage(): self
    {
        return new self(
            message: 'Source image is required for image editing',
            reason: 'No image provided in the request'
        );
    }

    /**
     * Create exception for missing prompt.
     */
    public static function missingPrompt(): self
    {
        return new self(
            message: 'Prompt is required for image editing',
            reason: 'No prompt provided in the request'
        );
    }

    /**
     * Create exception for image editing failure.
     */
    public static function editFailed(string $imagePath, string $prompt, string $reason, ?string $model = null): self
    {
        $promptPreview = mb_strlen($prompt) > 100 ? mb_substr($prompt, 0, 100) . '...' : $prompt;
        $message = "Image editing failed for image: {$imagePath} with prompt: '{$promptPreview}'. Reason: {$reason}";
        if ($model !== null) {
            $message .= " (Model: {$model})";
        }

        return new self(
            message: $message,
            imagePath: $imagePath,
            prompt: $prompt,
            reason: $reason,
            model: $model
        );
    }

    /**
     * Create exception for unsupported image format.
     */
    public static function unsupportedFormat(string $imagePath, string $format): self
    {
        $supportedFormats = ['png', 'jpg', 'jpeg', 'webp'];
        $supported = implode(', ', $supportedFormats);

        return new self(
            message: "Unsupported image format: {$format}. Supported formats for editing: {$supported}. Note: Image must have transparency (PNG recommended).",
            imagePath: $imagePath,
            reason: "Format '{$format}' is not supported for image editing"
        );
    }

    /**
     * Create exception for image file too large.
     */
    public static function imageTooLarge(string $imagePath, int $fileSize): self
    {
        $maxSize = 4 * 1024 * 1024; // 4MB
        $fileSizeMb = round($fileSize / 1024 / 1024, 2);

        return new self(
            message: "Image file size ({$fileSizeMb}MB) exceeds maximum allowed size (4MB). Please compress or resize the image.",
            imagePath: $imagePath,
            reason: 'Image file size exceeds OpenAI limit of 4MB for editing'
        );
    }

    /**
     * Create exception for invalid image dimensions.
     */
    public static function invalidDimensions(string $imagePath, int $width, int $height): self
    {
        return new self(
            message: "Invalid image dimensions: {$width}x{$height}. Image must be square and less than 4MB. Recommended sizes: 256x256, 512x512, or 1024x1024.",
            imagePath: $imagePath,
            reason: "Image dimensions {$width}x{$height} are not valid (must be square)"
        );
    }

    /**
     * Create exception for missing transparency in PNG.
     */
    public static function missingTransparency(string $imagePath): self
    {
        return new self(
            message: "Image must have transparency (alpha channel) for editing. Convert your image to PNG with transparency or provide a mask image.",
            imagePath: $imagePath,
            reason: 'Image does not contain transparency required for editing'
        );
    }

    /**
     * Create exception for invalid mask image.
     */
    public static function invalidMask(string $maskPath, string $reason): self
    {
        return new self(
            message: "Invalid mask image: {$maskPath}. {$reason}",
            maskPath: $maskPath,
            reason: $reason
        );
    }

    /**
     * Create exception for mask/image size mismatch.
     */
    public static function maskSizeMismatch(string $imagePath, string $maskPath, string $imageSize, string $maskSize): self
    {
        return new self(
            message: "Mask dimensions ({$maskSize}) must match image dimensions ({$imageSize}). Please resize the mask to match the source image.",
            imagePath: $imagePath,
            maskPath: $maskPath,
            reason: "Mask and image dimensions do not match"
        );
    }

    public function getImagePath(): ?string
    {
        return $this->imagePath;
    }

    public function getMaskPath(): ?string
    {
        return $this->maskPath;
    }

    public function getPrompt(): ?string
    {
        return $this->prompt;
    }

    public function getReason(): ?string
    {
        return $this->reason;
    }

    public function getModel(): ?string
    {
        return $this->model;
    }

    public function getSize(): ?string
    {
        return $this->size;
    }
}
