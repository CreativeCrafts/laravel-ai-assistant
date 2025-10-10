<?php

declare(strict_types=1);

use CreativeCrafts\LaravelAiAssistant\Http\MultipartRequestBuilder;

beforeEach(function () {
    $this->builder = new MultipartRequestBuilder();
    $this->tempDir = sys_get_temp_dir() . '/multipart_tests_' . uniqid('', true);
    mkdir($this->tempDir);
});

afterEach(function () {
    if (is_dir($this->tempDir)) {
        $files = glob($this->tempDir . '/*');
        foreach ($files as $file) {
            if (is_file($file)) {
                unlink($file);
            }
        }
        rmdir($this->tempDir);
    }
});

it('can add a file to the request', function () {
    $filePath = $this->tempDir . '/test.mp3';
    file_put_contents($filePath, 'test audio content');

    $result = $this->builder
        ->addFile('file', $filePath)
        ->build();

    expect($result)->toHaveKey('file')
        ->and($result['file'])->toBeArray()
        ->and($result['file'])->toHaveKey('contents')
        ->and($result['file'])->toHaveKey('filename');
});

it('can add multiple files to the request', function () {
    $file1 = $this->tempDir . '/test1.mp3';
    $file2 = $this->tempDir . '/test2.png';
    file_put_contents($file1, 'audio content');
    file_put_contents($file2, 'image content');

    $result = $this->builder
        ->addFile('audio', $file1)
        ->addFile('image', $file2)
        ->build();

    expect($result)->toHaveKey('audio')
        ->and($result)->toHaveKey('image')
        ->and($result['audio'])->toBeArray()
        ->and($result['image'])->toBeArray();
});

it('can add a file with SplFileInfo', function () {
    $filePath = $this->tempDir . '/test.mp3';
    file_put_contents($filePath, 'test content');
    $fileInfo = new SplFileInfo($filePath);

    $result = $this->builder
        ->addFile('file', $fileInfo)
        ->build();

    expect($result)->toHaveKey('file')
        ->and($result['file'])->toBeArray();
});

it('can add a file with custom filename', function () {
    $filePath = $this->tempDir . '/test.mp3';
    file_put_contents($filePath, 'test content');

    $result = $this->builder
        ->addFile('file', $filePath, 'custom_name.mp3')
        ->build();

    expect($result['file']['filename'])->toBe('custom_name.mp3');
});

it('can add a file with custom content type', function () {
    $filePath = $this->tempDir . '/test.mp3';
    file_put_contents($filePath, 'test content');

    $result = $this->builder
        ->addFile('file', $filePath, null, 'audio/custom')
        ->build();

    expect($result['file']['content_type'])->toBe('audio/custom');
});

it('detects content type automatically', function () {
    $filePath = $this->tempDir . '/test.txt';
    file_put_contents($filePath, 'test content');

    $result = $this->builder
        ->addFile('file', $filePath)
        ->build();

    expect($result['file'])->toHaveKey('content_type');
})->skip(!function_exists('finfo_open'), 'finfo extension not available');

it('can add regular fields to the request', function () {
    $result = $this->builder
        ->addField('name', 'value')
        ->addField('number', 42)
        ->build();

    expect($result)->toHaveKey('name')
        ->and($result['name'])->toBe('value')
        ->and($result)->toHaveKey('number')
        ->and($result['number'])->toBe(42);
});

it('can mix files and fields', function () {
    $filePath = $this->tempDir . '/test.mp3';
    file_put_contents($filePath, 'test content');

    $result = $this->builder
        ->addFile('file', $filePath)
        ->addField('model', 'whisper-1')
        ->addField('temperature', 0.5)
        ->build();

    expect($result)->toHaveKey('file')
        ->and($result)->toHaveKey('model')
        ->and($result['model'])->toBe('whisper-1')
        ->and($result)->toHaveKey('temperature')
        ->and($result['temperature'])->toBe(0.5);
});

it('throws exception for non-existent file', function () {
    $this->builder->addFile('file', '/nonexistent/file.mp3');
})->throws(InvalidArgumentException::class, 'File does not exist');

it('throws exception for non-file path', function () {
    $this->builder->addFile('file', $this->tempDir);
})->throws(InvalidArgumentException::class, 'Path is not a file');

it('throws exception for non-readable file', function () {
    $filePath = $this->tempDir . '/test.mp3';
    file_put_contents($filePath, 'test content');
    chmod($filePath, 0000);

    try {
        $this->builder->addFile('file', $filePath);
    } finally {
        chmod($filePath, 0644);
    }
})->throws(InvalidArgumentException::class, 'File is not readable');

it('throws exception for empty file', function () {
    $filePath = $this->tempDir . '/empty.mp3';
    touch($filePath);

    $this->builder->addFile('file', $filePath);
})->throws(InvalidArgumentException::class, 'File is empty');

it('throws exception for file exceeding max size', function () {
    $filePath = $this->tempDir . '/large.mp3';
    file_put_contents($filePath, str_repeat('x', 1024 * 1024)); // 1MB

    $this->builder
        ->setMaxFileSize(512 * 1024) // 512KB max
        ->addFile('file', $filePath);
})->throws(InvalidArgumentException::class, 'exceeds maximum allowed size');

it('validates audio file formats', function () {
    $filePath = $this->tempDir . '/test.txt';
    file_put_contents($filePath, 'test content');

    $this->builder->addFile('file', $filePath, null, null, 'audio');
})->throws(InvalidArgumentException::class, 'Unsupported audio format');

it('allows valid audio file formats', function () {
    $validFormats = ['mp3', 'mp4', 'mpeg', 'mpga', 'm4a', 'wav', 'webm'];

    foreach ($validFormats as $format) {
        $filePath = $this->tempDir . "/test.{$format}";
        file_put_contents($filePath, 'test content');

        $builder = new MultipartRequestBuilder();
        $result = $builder->addFile('file', $filePath, null, null, 'audio')->build();

        expect($result)->toHaveKey('file');
    }
});

it('validates image file formats', function () {
    $filePath = $this->tempDir . '/test.txt';
    file_put_contents($filePath, 'test content');

    $this->builder->addFile('file', $filePath, null, null, 'image');
})->throws(InvalidArgumentException::class, 'Unsupported image format');

it('allows valid image file formats', function () {
    $validFormats = ['png', 'jpg', 'jpeg', 'gif', 'webp'];

    foreach ($validFormats as $format) {
        $filePath = $this->tempDir . "/test.{$format}";
        file_put_contents($filePath, 'test content');

        $builder = new MultipartRequestBuilder();
        $result = $builder->addFile('file', $filePath, null, null, 'image')->build();

        expect($result)->toHaveKey('file');
    }
});

it('can set custom allowed formats', function () {
    $filePath = $this->tempDir . '/test.custom';
    file_put_contents($filePath, 'test content');

    $result = $this->builder
        ->setAllowedFormats('custom', ['custom'])
        ->addFile('file', $filePath, null, null, 'custom')
        ->build();

    expect($result)->toHaveKey('file');
});

it('can set maximum file size', function () {
    $filePath = $this->tempDir . '/test.mp3';
    file_put_contents($filePath, 'small content');

    $result = $this->builder
        ->setMaxFileSize(1024 * 1024 * 50) // 50MB
        ->addFile('file', $filePath)
        ->build();

    expect($result)->toHaveKey('file');
});

it('throws exception for negative max file size', function () {
    $this->builder->setMaxFileSize(-1);
})->throws(InvalidArgumentException::class, 'Maximum file size must be a positive integer');

it('can clear all parts', function () {
    $filePath = $this->tempDir . '/test.mp3';
    file_put_contents($filePath, 'test content');

    $this->builder
        ->addFile('file', $filePath)
        ->addField('name', 'value')
        ->clear();

    $result = $this->builder->build();

    expect($result)->toBeEmpty();
});

it('returns empty array when no parts added', function () {
    $result = $this->builder->build();

    expect($result)->toBeEmpty();
});

it('uses fluent interface for chaining', function () {
    $filePath = $this->tempDir . '/test.mp3';
    file_put_contents($filePath, 'test content');

    $result = $this->builder
        ->setMaxFileSize(1024 * 1024 * 10)
        ->setAllowedFormats('audio', ['mp3', 'wav'])
        ->addFile('file', $filePath)
        ->addField('model', 'whisper-1')
        ->build();

    expect($result)->toHaveKey('file')
        ->and($result)->toHaveKey('model');
});

it('handles multiple files with same name', function () {
    $file1 = $this->tempDir . '/test1.mp3';
    $file2 = $this->tempDir . '/test2.mp3';
    file_put_contents($file1, 'content 1');
    file_put_contents($file2, 'content 2');

    $result = $this->builder
        ->addFile('file', $file1)
        ->addFile('file', $file2)
        ->build();

    // Last file with same name overwrites
    expect($result)->toHaveKey('file')
        ->and($result['file']['contents'])->toBe($file2);
});

it('preserves original file reference in contents', function () {
    $filePath = $this->tempDir . '/test.mp3';
    file_put_contents($filePath, 'test content');

    $result = $this->builder
        ->addFile('file', $filePath)
        ->build();

    expect($result['file']['contents'])->toBe($filePath);
});

it('preserves SplFileInfo object in contents', function () {
    $filePath = $this->tempDir . '/test.mp3';
    file_put_contents($filePath, 'test content');
    $fileInfo = new SplFileInfo($filePath);

    $result = $this->builder
        ->addFile('file', $fileInfo)
        ->build();

    expect($result['file']['contents'])->toBe($fileInfo);
});

it('validates file before adding to parts', function () {
    $nonExistentFile = '/path/to/nonexistent.mp3';

    try {
        $this->builder->addFile('file', $nonExistentFile);
        $this->fail('Expected InvalidArgumentException was not thrown');
    } catch (InvalidArgumentException $e) {
        expect($e->getMessage())->toContain('File does not exist');
    }

    // Verify that no parts were added
    $result = $this->builder->build();
    expect($result)->toBeEmpty();
});

it('handles file without extension gracefully', function () {
    $filePath = $this->tempDir . '/noextension';
    file_put_contents($filePath, 'test content');

    $result = $this->builder
        ->addFile('file', $filePath)
        ->build();

    expect($result)->toHaveKey('file');
});

it('provides descriptive error message for size validation', function () {
    $filePath = $this->tempDir . '/large.mp3';
    $content = str_repeat('x', 2 * 1024 * 1024); // 2MB
    file_put_contents($filePath, $content);

    try {
        $this->builder
            ->setMaxFileSize(1024 * 1024) // 1MB max
            ->addFile('file', $filePath);
        $this->fail('Expected InvalidArgumentException was not thrown');
    } catch (InvalidArgumentException $e) {
        expect($e->getMessage())
            ->toContain('File size')
            ->toContain('MB')
            ->toContain('exceeds maximum allowed size');
    }
});

it('provides descriptive error message for format validation', function () {
    $filePath = $this->tempDir . '/test.exe';
    file_put_contents($filePath, 'test content');

    try {
        $this->builder->addFile('file', $filePath, null, null, 'audio');
        $this->fail('Expected InvalidArgumentException was not thrown');
    } catch (InvalidArgumentException $e) {
        expect($e->getMessage())
            ->toContain('Unsupported audio format')
            ->toContain('exe')
            ->toContain('Supported formats');
    }
});
