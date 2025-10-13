<?php

declare(strict_types=1);

namespace CreativeCrafts\LaravelAiAssistant\Tests\Unit\Exceptions;

use CreativeCrafts\LaravelAiAssistant\Exceptions\FileValidationException;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class FileValidationExceptionTest extends TestCase
{
    #[Test]
    public function it_creates_file_not_found_exception(): void
    {
        $filePath = '/path/to/missing/file.mp3';
        $exception = FileValidationException::fileNotFound($filePath);

        $this->assertInstanceOf(FileValidationException::class, $exception);
        $this->assertStringContainsString('File not found', $exception->getMessage());
        $this->assertStringContainsString($filePath, $exception->getMessage());
        $this->assertSame($filePath, $exception->getFilePath());
        $this->assertSame('File must exist and be accessible', $exception->getRequirement());
    }

    #[Test]
    public function it_creates_file_not_readable_exception(): void
    {
        $filePath = '/path/to/unreadable/file.mp3';
        $exception = FileValidationException::fileNotReadable($filePath);

        $this->assertInstanceOf(FileValidationException::class, $exception);
        $this->assertStringContainsString('not readable', $exception->getMessage());
        $this->assertStringContainsString($filePath, $exception->getMessage());
        $this->assertSame($filePath, $exception->getFilePath());
        $this->assertSame('File must have read permissions', $exception->getRequirement());
    }

    #[Test]
    public function it_creates_unsupported_format_exception(): void
    {
        $filePath = '/path/to/file.xyz';
        $format = 'xyz';
        $supportedFormats = ['mp3', 'wav', 'mp4'];

        $exception = FileValidationException::unsupportedFormat($filePath, $format, $supportedFormats);

        $this->assertInstanceOf(FileValidationException::class, $exception);
        $this->assertStringContainsString('Unsupported file format', $exception->getMessage());
        $this->assertStringContainsString($format, $exception->getMessage());
        $this->assertStringContainsString('mp3, wav, mp4', $exception->getMessage());
        $this->assertSame($filePath, $exception->getFilePath());
        $this->assertSame($format, $exception->getFileFormat());
    }

    #[Test]
    public function it_creates_file_size_exceeded_exception(): void
    {
        $filePath = '/path/to/large/file.mp3';
        $fileSize = 30 * 1024 * 1024; // 30MB
        $maxSize = 25 * 1024 * 1024; // 25MB

        $exception = FileValidationException::fileSizeExceeded($filePath, $fileSize, $maxSize);

        $this->assertInstanceOf(FileValidationException::class, $exception);
        $this->assertStringContainsString('exceeds maximum', $exception->getMessage());
        $this->assertStringContainsString('25MB', $exception->getMessage());
        $this->assertSame($filePath, $exception->getFilePath());
        $this->assertSame($fileSize, $exception->getFileSize());
    }

    #[Test]
    public function it_creates_invalid_path_type_exception(): void
    {
        $invalidPath = ['not', 'a', 'string'];
        $exception = FileValidationException::invalidPathType($invalidPath);

        $this->assertInstanceOf(FileValidationException::class, $exception);
        $this->assertStringContainsString('must be a string', $exception->getMessage());
        $this->assertStringContainsString('array', $exception->getMessage());
    }

    #[Test]
    public function it_includes_context_in_exception(): void
    {
        $filePath = '/test/file.mp3';
        $requirement = 'Test requirement';
        $fileSize = 1024;
        $fileFormat = 'mp3';

        $exception = new FileValidationException(
            message: 'Test message',
            filePath: $filePath,
            requirement: $requirement,
            fileSize: $fileSize,
            fileFormat: $fileFormat
        );

        $this->assertSame($filePath, $exception->getFilePath());
        $this->assertSame($requirement, $exception->getRequirement());
        $this->assertSame($fileSize, $exception->getFileSize());
        $this->assertSame($fileFormat, $exception->getFileFormat());
    }
}
