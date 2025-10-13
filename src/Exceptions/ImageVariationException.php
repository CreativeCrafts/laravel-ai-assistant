<?php

declare(strict_types=1);

namespace CreativeCrafts\LaravelAiAssistant\Exceptions;

use RuntimeException;
use Throwable;

/**
 * Exception thrown when image variation generation fails.
 *
 * Includes image information and failure reason to help developers
 * quickly identify and resolve image variation issues.
 */
final class ImageVariationException extends RuntimeException
{
    private ?string $imagePath;
    private ?string $reason;
    private ?string $model;
    private ?string $size;
    private ?int $count;

    public function __construct(
        string $message,
        ?string $imagePath = null,
        ?string $reason = null,
        ?string $model = null,
        ?string $size = null,
        ?int $count = null,
        int $code = 0,
        ?Throwable $previous = null
    ) {
        parent::__construct($message, $code, $previous);
        $this->imagePath = $imagePath;
        $this->reason = $reason;
        $this->model = $model;
        $this->size = $size;
        $this->count = $count;
    }

    /**
     * Create exception for missing image.
     */
    public static function missingImage(): self
    {
        return new self(
            message: 'Source image is required for variation generation',
            reason: 'No image provided in the request'
        );
    }

    /**
     * Create exception for variation generation failure.
     */
    public static function variationFailed(string $imagePath, string $reason, ?string $model = null): self
    {
        $message = "Image variation generation failed for image: {$imagePath}. Reason: {$reason}";
        if ($model !== null) {
            $message .= " (Model: {$model})";
        }

        return new self(
            message: $message,
            imagePath: $imagePath,
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
            message: "Unsupported image format: {$format}. Supported formats for variations: {$supported}",
            imagePath: $imagePath,
            reason: "Format '{$format}' is not supported for image variations"
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
            reason: 'Image file size exceeds OpenAI limit of 4MB for variations'
        );
    }

    /**
     * Create exception for invalid image dimensions.
     */
    public static function invalidDimensions(string $imagePath, int $width, int $height): self
    {
        return new self(
            message: "Invalid image dimensions: {$width}x{$height}. Image must be square. Recommended sizes: 256x256, 512x512, or 1024x1024.",
            imagePath: $imagePath,
            reason: "Image dimensions {$width}x{$height} are not valid (must be square)"
        );
    }

    /**
     * Create exception for invalid size parameter.
     */
    public static function invalidSize(string $size): self
    {
        $validSizes = ['256x256', '512x512', '1024x1024'];
        $supported = implode(', ', $validSizes);

        return new self(
            message: "Invalid size: {$size}. Supported sizes for variations: {$supported}",
            reason: "Size '{$size}' is not supported",
            size: $size
        );
    }

    /**
     * Create exception for invalid number of variations.
     */
    public static function invalidVariationCount(int $count): self
    {
        return new self(
            message: "Invalid number of variations: {$count}. Must be between 1 and 10.",
            reason: "Variation count {$count} is outside valid range (1-10)",
            count: $count
        );
    }

    /**
     * Create exception for non-PNG image format.
     */
    public static function mustBePng(string $imagePath, string $format): self
    {
        return new self(
            message: "Image must be in PNG format for variations. Current format: {$format}. Please convert the image to PNG.",
            imagePath: $imagePath,
            reason: "Only PNG format is supported for image variations"
        );
    }

    /**
     * Create exception for invalid image content.
     */
    public static function invalidImageContent(string $imagePath, string $reason): self
    {
        return new self(
            message: "Invalid image content in: {$imagePath}. {$reason}",
            imagePath: $imagePath,
            reason: $reason
        );
    }

    /**
     * Create exception for corrupted image file.
     */
    public static function corruptedImage(string $imagePath): self
    {
        return new self(
            message: "Image file appears to be corrupted or unreadable: {$imagePath}. Please verify the file integrity.",
            imagePath: $imagePath,
            reason: 'Image file is corrupted or cannot be read'
        );
    }

    public function getImagePath(): ?string
    {
        return $this->imagePath;
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

    public function getCount(): ?int
    {
        return $this->count;
    }
}
