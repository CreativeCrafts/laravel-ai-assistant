<?php

declare(strict_types=1);

namespace CreativeCrafts\LaravelAiAssistant\Exceptions;

use RuntimeException;
use Throwable;

/**
 * Exception thrown when image generation fails.
 *
 * Includes prompt and constraints to help developers
 * quickly identify and resolve image generation issues.
 */
final class ImageGenerationException extends RuntimeException
{
    private ?string $prompt;
    private ?string $reason;
    private ?string $model;
    private ?string $size;
    private ?string $quality;
    private ?string $style;

    public function __construct(
        string $message,
        ?string $prompt = null,
        ?string $reason = null,
        ?string $model = null,
        ?string $size = null,
        ?string $quality = null,
        ?string $style = null,
        int $code = 0,
        ?Throwable $previous = null
    ) {
        parent::__construct($message, $code, $previous);
        $this->prompt = $prompt;
        $this->reason = $reason;
        $this->model = $model;
        $this->size = $size;
        $this->quality = $quality;
        $this->style = $style;
    }

    /**
     * Create exception for missing prompt.
     */
    public static function missingPrompt(): self
    {
        return new self(
            message: 'Image prompt is required for generation',
            reason: 'No prompt provided in the request'
        );
    }

    /**
     * Create exception for image generation failure.
     */
    public static function generationFailed(string $prompt, string $reason, ?string $model = null): self
    {
        $promptPreview = mb_strlen($prompt) > 100 ? mb_substr($prompt, 0, 100) . '...' : $prompt;
        $message = "Image generation failed for prompt: '{$promptPreview}'. Reason: {$reason}";
        if ($model !== null) {
            $message .= " (Model: {$model})";
        }

        return new self(
            message: $message,
            prompt: $prompt,
            reason: $reason,
            model: $model
        );
    }

    /**
     * Create exception for prompt that's too long.
     */
    public static function promptTooLong(string $prompt, int $maxLength = 4000): self
    {
        $length = mb_strlen($prompt);
        $promptPreview = mb_substr($prompt, 0, 100) . '...';

        return new self(
            message: "Prompt length ({$length} characters) exceeds maximum allowed length ({$maxLength} characters). Please shorten your prompt.",
            prompt: $prompt,
            reason: "Prompt length exceeds OpenAI limit of {$maxLength} characters"
        );
    }

    /**
     * Create exception for invalid size.
     */
    public static function invalidSize(string $size, string $model): self
    {
        $validSizes = match ($model) {
            'dall-e-3' => ['1024x1024', '1792x1024', '1024x1792'],
            'dall-e-2' => ['256x256', '512x512', '1024x1024'],
            default => ['1024x1024', '512x512', '256x256'],
        };
        $supported = implode(', ', $validSizes);

        return new self(
            message: "Invalid size: {$size} for model {$model}. Supported sizes: {$supported}",
            reason: "Size '{$size}' is not supported by {$model}",
            model: $model,
            size: $size
        );
    }

    /**
     * Create exception for invalid quality setting.
     */
    public static function invalidQuality(string $quality): self
    {
        return new self(
            message: "Invalid quality: {$quality}. Supported quality values: standard, hd",
            reason: "Quality '{$quality}' is not supported (only available for DALL-E 3)",
            quality: $quality
        );
    }

    /**
     * Create exception for invalid style.
     */
    public static function invalidStyle(string $style): self
    {
        return new self(
            message: "Invalid style: {$style}. Supported styles: vivid, natural",
            reason: "Style '{$style}' is not supported (only available for DALL-E 3)",
            style: $style
        );
    }

    /**
     * Create exception for invalid number of images.
     */
    public static function invalidImageCount(int $count, string $model): self
    {
        $maxCount = $model === 'dall-e-3' ? 1 : 10;

        return new self(
            message: "Invalid number of images: {$count}. {$model} supports generating 1-{$maxCount} images at a time.",
            reason: "Image count {$count} exceeds {$model} limit of {$maxCount}",
            model: $model
        );
    }

    /**
     * Create exception for empty prompt.
     */
    public static function emptyPrompt(): self
    {
        return new self(
            message: 'Image prompt cannot be empty',
            reason: 'Empty prompt provided in the request'
        );
    }

    /**
     * Create exception for content policy violation.
     */
    public static function contentPolicyViolation(string $prompt): self
    {
        $promptPreview = mb_strlen($prompt) > 100 ? mb_substr($prompt, 0, 100) . '...' : $prompt;

        return new self(
            message: "Prompt violates OpenAI content policy: '{$promptPreview}'. Please revise your prompt to comply with content guidelines.",
            prompt: $prompt,
            reason: 'Prompt contains content that violates OpenAI usage policies'
        );
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

    public function getQuality(): ?string
    {
        return $this->quality;
    }

    public function getStyle(): ?string
    {
        return $this->style;
    }
}
