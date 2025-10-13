<?php

declare(strict_types=1);

namespace CreativeCrafts\LaravelAiAssistant\Exceptions;

use RuntimeException;
use Throwable;

/**
 * Exception thrown when file validation fails.
 *
 * Includes detailed file information and validation requirements to help
 * developers quickly identify and fix the issue.
 */
final class FileValidationException extends RuntimeException
{
    private ?string $filePath;
    private ?string $requirement;
    private ?int $fileSize;
    private ?string $fileFormat;

    public function __construct(
        string $message,
        ?string $filePath = null,
        ?string $requirement = null,
        ?int $fileSize = null,
        ?string $fileFormat = null,
        int $code = 0,
        ?Throwable $previous = null
    ) {
        parent::__construct($message, $code, $previous);
        $this->filePath = $filePath;
        $this->requirement = $requirement;
        $this->fileSize = $fileSize;
        $this->fileFormat = $fileFormat;
    }

    /**
     * Create exception for missing file.
     */
    public static function fileNotFound(string $filePath): self
    {
        return new self(
            message: "File not found: {$filePath}",
            filePath: $filePath,
            requirement: 'File must exist and be accessible'
        );
    }

    /**
     * Create exception for unreadable file.
     */
    public static function fileNotReadable(string $filePath): self
    {
        return new self(
            message: "File is not readable: {$filePath}. Check file permissions.",
            filePath: $filePath,
            requirement: 'File must have read permissions'
        );
    }

    /**
     * Create exception for unsupported file format.
     */
    public static function unsupportedFormat(string $filePath, string $format, array $supportedFormats): self
    {
        $supported = implode(', ', $supportedFormats);
        return new self(
            message: "Unsupported file format: {$format}. Supported formats: {$supported}",
            filePath: $filePath,
            requirement: "File format must be one of: {$supported}",
            fileFormat: $format
        );
    }

    /**
     * Create exception for file size exceeding limit.
     */
    public static function fileSizeExceeded(string $filePath, int $fileSize, int $maxSize): self
    {
        $fileSizeMb = round($fileSize / 1024 / 1024, 2);
        $maxSizeMb = round($maxSize / 1024 / 1024, 2);
        return new self(
            message: "File size ({$fileSizeMb}MB) exceeds maximum allowed size ({$maxSizeMb}MB)",
            filePath: $filePath,
            requirement: "File must be less than {$maxSizeMb}MB",
            fileSize: $fileSize
        );
    }

    /**
     * Create exception for invalid file path type.
     */
    public static function invalidPathType(mixed $filePath): self
    {
        $type = get_debug_type($filePath);
        return new self(
            message: "File path must be a string, {$type} given",
            requirement: 'File path must be a string'
        );
    }

    public function getFilePath(): ?string
    {
        return $this->filePath;
    }

    public function getRequirement(): ?string
    {
        return $this->requirement;
    }

    public function getFileSize(): ?int
    {
        return $this->fileSize;
    }

    public function getFileFormat(): ?string
    {
        return $this->fileFormat;
    }
}
