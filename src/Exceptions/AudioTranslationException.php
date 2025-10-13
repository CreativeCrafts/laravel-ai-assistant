<?php

declare(strict_types=1);

namespace CreativeCrafts\LaravelAiAssistant\Exceptions;

use RuntimeException;
use Throwable;

/**
 * Exception thrown when audio translation fails.
 *
 * Includes file path and failure reason to help developers
 * quickly identify and resolve translation issues.
 */
final class AudioTranslationException extends RuntimeException
{
    private ?string $filePath;
    private ?string $reason;
    private ?string $model;

    public function __construct(
        string $message,
        ?string $filePath = null,
        ?string $reason = null,
        ?string $model = null,
        int $code = 0,
        ?Throwable $previous = null
    ) {
        parent::__construct($message, $code, $previous);
        $this->filePath = $filePath;
        $this->reason = $reason;
        $this->model = $model;
    }

    /**
     * Create exception for missing audio file.
     */
    public static function missingFile(): self
    {
        return new self(
            message: 'Audio file is required for translation',
            reason: 'No audio file provided in the request'
        );
    }

    /**
     * Create exception for translation failure.
     */
    public static function translationFailed(string $filePath, string $reason, ?string $model = null): self
    {
        $message = "Audio translation failed for file: {$filePath}. Reason: {$reason}";
        if ($model !== null) {
            $message .= " (Model: {$model})";
        }

        return new self(
            message: $message,
            filePath: $filePath,
            reason: $reason,
            model: $model
        );
    }

    /**
     * Create exception for unsupported audio format.
     */
    public static function unsupportedFormat(string $filePath, string $format): self
    {
        $supportedFormats = ['mp3', 'mp4', 'mpeg', 'mpga', 'm4a', 'wav', 'webm'];
        $supported = implode(', ', $supportedFormats);

        return new self(
            message: "Unsupported audio format: {$format}. Supported formats for translation: {$supported}",
            filePath: $filePath,
            reason: "Format '{$format}' is not supported by OpenAI Whisper model"
        );
    }

    /**
     * Create exception for file size exceeding limit.
     */
    public static function fileTooLarge(string $filePath, int $fileSize): self
    {
        $fileSizeMb = round($fileSize / 1024 / 1024, 2);

        return new self(
            message: "Audio file size ({$fileSizeMb}MB) exceeds maximum allowed size (25MB). Consider compressing the audio or splitting it into smaller segments.",
            filePath: $filePath,
            reason: 'File size exceeds OpenAI limit of 25MB'
        );
    }

    public function getFilePath(): ?string
    {
        return $this->filePath;
    }

    public function getReason(): ?string
    {
        return $this->reason;
    }

    public function getModel(): ?string
    {
        return $this->model;
    }
}
