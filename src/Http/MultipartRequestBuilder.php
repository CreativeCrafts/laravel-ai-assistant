<?php

declare(strict_types=1);

namespace CreativeCrafts\LaravelAiAssistant\Http;

use InvalidArgumentException;
use SplFileInfo;

/**
 * Builder for multipart/form-data HTTP requests.
 *
 * This class provides a fluent interface for constructing multipart requests
 * with comprehensive file validation and proper Content-Type management.
 *
 * This builder follows the mutable fluent pattern:
 * - All methods modify internal state ($this->parts) and return $this
 * - No cloning is performed; the same instance is modified throughout the chain
 * - This ensures consistent behavior across all package builders
 *
 * @internal Internal utility for building multipart HTTP requests (used for file uploads).
 * Do not use directly - use Ai::responses() instead.
 */
final class MultipartRequestBuilder
{
    /**
     * @var array<int, array<string, mixed>>
     */
    private array $parts = [];

    /**
     * Maximum file size in bytes (default: 25MB)
     */
    private int $maxFileSize = 26214400;

    /**
     * Track total request size for performance monitoring
     */
    private int $totalRequestSize = 0;

    /**
     * @var array<string, array<string>>
     */
    private array $allowedFormats = [
        'audio' => ['mp3', 'mp4', 'mpeg', 'mpga', 'm4a', 'wav', 'webm'],
        'image' => ['png', 'jpg', 'jpeg', 'gif', 'webp'],
    ];

    /**
     * Add a file to the multipart request.
     *
     * @param string $name The field name for the file
     * @param string|SplFileInfo $file Path to file or SplFileInfo object
     * @param string|null $filename Override filename (optional)
     * @param string|null $contentType Override Content-Type (optional)
     * @param string|null $fileType Type of file for format validation ('audio', 'image', or null for no validation)
     * @return self
     * @throws InvalidArgumentException If the file is invalid
     */
    public function addFile(
        string $name,
        string|SplFileInfo $file,
        ?string $filename = null,
        ?string $contentType = null,
        ?string $fileType = null
    ): self {
        $filePath = $this->resolveFilePath($file);
        $this->validateFile($filePath, $fileType);

        $detectedContentType = $contentType ?? $this->detectContentType($filePath);
        $detectedFilename = $filename ?? basename($filePath);

        $part = [
            'name' => $name,
            'contents' => $file,
        ];

        if ($detectedFilename !== '') {
            $part['filename'] = $detectedFilename;
        }

        if ($detectedContentType !== null && $detectedContentType !== '') {
            $part['content_type'] = $detectedContentType;
        }

        $this->parts[] = $part;

        return $this;
    }

    /**
     * Add a regular field to the multipart request.
     *
     * @param string $name The field name
     * @param mixed $value The field value (arrays/objects will be JSON encoded)
     * @return self
     */
    public function addField(string $name, mixed $value): self
    {
        $this->parts[] = [
            'name' => $name,
            'contents' => $value,
        ];

        return $this;
    }

    /**
     * Set the maximum allowed file size in bytes.
     *
     * @param int $bytes Maximum file size in bytes
     * @return self
     * @throws InvalidArgumentException If bytes is negative
     */
    public function setMaxFileSize(int $bytes): self
    {
        if ($bytes < 0) {
            throw new InvalidArgumentException('Maximum file size must be a positive integer.');
        }

        $this->maxFileSize = $bytes;

        return $this;
    }

    /**
     * Set allowed file formats for a specific file type.
     *
     * @param string $fileType The file type ('audio', 'image', etc.)
     * @param array<string> $formats Array of allowed file extensions
     * @return self
     */
    public function setAllowedFormats(string $fileType, array $formats): self
    {
        $this->allowedFormats[$fileType] = array_map('strtolower', $formats);

        return $this;
    }

    /**
     * Build and return the multipart array structure.
     *
     * @return array<string, mixed>
     */
    public function build(): array
    {
        $result = [];

        foreach ($this->parts as $part) {
            $name = $part['name'];
            $contents = $part['contents'];

            // Handle file parts
            if (isset($part['filename']) || isset($part['content_type'])) {
                $filePart = [
                    'contents' => $contents,
                ];

                if (isset($part['filename'])) {
                    $filePart['filename'] = $part['filename'];
                }

                if (isset($part['content_type'])) {
                    $filePart['content_type'] = $part['content_type'];
                }

                $result[$name] = $filePart;
            } else {
                // Regular field
                $result[$name] = $contents;
            }
        }

        return $result;
    }

    /**
     * Clear all parts from the builder.
     *
     * @return self
     */
    public function clear(): self
    {
        $this->parts = [];
        $this->totalRequestSize = 0;

        return $this;
    }

    /**
     * Get the total size of all files in the request.
     *
     * This is useful for performance monitoring and progress tracking.
     *
     * @return int Total size in bytes
     */
    public function getTotalRequestSize(): int
    {
        return $this->totalRequestSize;
    }

    /**
     * Resolve the file path from various input types.
     *
     * @param string|SplFileInfo $file
     * @return string
     */
    private function resolveFilePath(string|SplFileInfo $file): string
    {
        if ($file instanceof SplFileInfo) {
            return $file->getRealPath() ?: $file->getPathname();
        }

        return $file;
    }

    /**
     * Validate that the file exists, is readable, has proper permissions, size, and format.
     *
     * @param string $filePath
     * @param string|null $fileType Type of file for format validation
     * @throws InvalidArgumentException If the file is invalid
     */
    private function validateFile(string $filePath, ?string $fileType): void
    {
        if (!file_exists($filePath)) {
            throw new InvalidArgumentException("File does not exist: {$filePath}");
        }

        if (!is_file($filePath)) {
            throw new InvalidArgumentException("Path is not a file: {$filePath}");
        }

        if (!is_readable($filePath)) {
            throw new InvalidArgumentException("File is not readable: {$filePath}");
        }

        $fileSize = filesize($filePath);
        if ($fileSize === false) {
            throw new InvalidArgumentException("Unable to determine file size: {$filePath}");
        }

        if ($fileSize > $this->maxFileSize) {
            $maxSizeMB = round($this->maxFileSize / 1048576, 2);
            $actualSizeMB = round($fileSize / 1048576, 2);
            throw new InvalidArgumentException(
                "File size ({$actualSizeMB}MB) exceeds maximum allowed size ({$maxSizeMB}MB): {$filePath}"
            );
        }

        if ($fileSize === 0) {
            throw new InvalidArgumentException("File is empty: {$filePath}");
        }

        // Track file size for performance monitoring
        $this->totalRequestSize += $fileSize;

        if ($fileType !== null && isset($this->allowedFormats[$fileType])) {
            $this->validateFileFormat($filePath, $fileType);
        }
    }

    /**
     * Validate that the file has a supported format.
     *
     * @param string $filePath
     * @param string $fileType
     * @throws InvalidArgumentException If the format is not supported
     */
    private function validateFileFormat(string $filePath, string $fileType): void
    {
        $extension = strtolower(pathinfo($filePath, PATHINFO_EXTENSION));
        $allowedFormats = $this->allowedFormats[$fileType] ?? [];

        if (!in_array($extension, $allowedFormats, true)) {
            throw new InvalidArgumentException(
                "Unsupported {$fileType} format: {$extension}. Supported formats: " . implode(', ', $allowedFormats)
            );
        }
    }

    /**
     * Detect the Content-Type of a file using finfo.
     *
     * @param string $filePath
     * @return string|null
     */
    private function detectContentType(string $filePath): ?string
    {
        if (!function_exists('finfo_open')) {
            return null;
        }

        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        if ($finfo === false) {
            return null;
        }

        $contentType = finfo_file($finfo, $filePath);
        finfo_close($finfo);

        return is_string($contentType) && $contentType !== '' ? $contentType : null;
    }
}
