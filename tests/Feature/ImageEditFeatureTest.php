<?php

declare(strict_types=1);

use CreativeCrafts\LaravelAiAssistant\Adapters\ImageEditAdapter;
use CreativeCrafts\LaravelAiAssistant\DataTransferObjects\ResponseDto;
use CreativeCrafts\LaravelAiAssistant\Exceptions\FileValidationException;
use CreativeCrafts\LaravelAiAssistant\Exceptions\ImageEditException;

beforeEach(function () {
    $this->adapter = new ImageEditAdapter();
});

afterEach(function () {
    // Clean up any temporary files created during tests
    if (isset($this->tempImageFile) && file_exists($this->tempImageFile)) {
        unlink($this->tempImageFile);
    }
    if (isset($this->tempMaskFile) && file_exists($this->tempMaskFile)) {
        unlink($this->tempMaskFile);
    }
});

describe('End-to-end image edit flow', function () {
    it('processes complete image edit request with all parameters', function () {
        // Arrange: Create temporary PNG files for image and mask
        $this->tempImageFile = tempnam(sys_get_temp_dir(), 'feature_image_') . '.png';
        $this->tempMaskFile = tempnam(sys_get_temp_dir(), 'feature_mask_') . '.png';

        // Create minimal valid PNG files
        $pngHeader = "\x89\x50\x4e\x47\x0d\x0a\x1a\x0a";
        file_put_contents($this->tempImageFile, $pngHeader . str_repeat('x', 1024));
        file_put_contents($this->tempMaskFile, $pngHeader . str_repeat('y', 1024));

        $unifiedRequest = [
            'image' => [
                'image' => $this->tempImageFile,
                'prompt' => 'Add a rainbow to the sky',
                'mask' => $this->tempMaskFile,
                'model' => 'dall-e-2',
                'n' => 1,
                'size' => '1024x1024',
                'response_format' => 'url',
            ],
        ];

        // Act: Transform request
        $transformedRequest = $this->adapter->transformRequest($unifiedRequest);

        // Assert: Verify request transformation
        expect($transformedRequest)->toBeArray()
            ->and($transformedRequest['image'])->toBe($this->tempImageFile)
            ->and($transformedRequest['prompt'])->toBe('Add a rainbow to the sky')
            ->and($transformedRequest['mask'])->toBe($this->tempMaskFile)
            ->and($transformedRequest['model'])->toBe('dall-e-2')
            ->and($transformedRequest['n'])->toBe(1)
            ->and($transformedRequest['size'])->toBe('1024x1024')
            ->and($transformedRequest['response_format'])->toBe('url');

        // Simulate API response
        $apiResponse = [
            'created' => 1234567890,
            'data' => [
                [
                    'url' => 'https://example.com/edited-image.png',
                ],
            ],
        ];

        // Act: Transform response
        $responseDto = $this->adapter->transformResponse($apiResponse);

        // Assert: Verify response transformation
        expect($responseDto)->toBeInstanceOf(ResponseDto::class)
            ->and($responseDto->status)->toBe('completed')
            ->and($responseDto->text)->toBeNull()
            ->and($responseDto->type)->toBe('image_edit')
            ->and($responseDto->audioContent)->toBeNull()
            ->and($responseDto->images)->toBeArray()
            ->and($responseDto->images)->toHaveCount(1)
            ->and($responseDto->images[0]['url'])->toBe('https://example.com/edited-image.png')
            ->and($responseDto->metadata['created'])->toBe(1234567890)
            ->and($responseDto->metadata['count'])->toBe(1)
            ->and($responseDto->isText())->toBeFalse()
            ->and($responseDto->isAudio())->toBeFalse()
            ->and($responseDto->isImage())->toBeTrue();
    });

    it('handles minimal image edit request without mask', function () {
        // Arrange: Create minimal PNG file
        $this->tempImageFile = tempnam(sys_get_temp_dir(), 'feature_image_') . '.png';
        $pngHeader = "\x89\x50\x4e\x47\x0d\x0a\x1a\x0a";
        file_put_contents($this->tempImageFile, $pngHeader . str_repeat('x', 2048));

        $unifiedRequest = [
            'image' => [
                'image' => $this->tempImageFile,
                'prompt' => 'Change the colors',
            ],
        ];

        // Act: Transform request
        $transformedRequest = $this->adapter->transformRequest($unifiedRequest);

        // Assert: Verify defaults are applied and mask is null
        expect($transformedRequest['image'])->toBe($this->tempImageFile)
            ->and($transformedRequest['prompt'])->toBe('Change the colors')
            ->and($transformedRequest['mask'])->toBeNull()
            ->and($transformedRequest['model'])->toBe('dall-e-2')
            ->and($transformedRequest['n'])->toBe(1)
            ->and($transformedRequest['size'])->toBe('1024x1024')
            ->and($transformedRequest['response_format'])->toBe('url');
    });

    it('validates image file format - only PNG supported', function () {
        // Arrange: Create a JPEG file (unsupported format)
        $this->tempImageFile = tempnam(sys_get_temp_dir(), 'feature_image_') . '.jpg';
        file_put_contents($this->tempImageFile, 'fake jpeg content');

        $unifiedRequest = [
            'image' => [
                'image' => $this->tempImageFile,
                'prompt' => 'Edit this image',
            ],
        ];

        // Act & Assert: Should throw exception for unsupported format
        expect(fn () => $this->adapter->transformRequest($unifiedRequest))
            ->toThrow(ImageEditException::class, 'Unsupported image format');
    });

    it('validates image file existence', function () {
        // Arrange: Use a non-existent file path
        $unifiedRequest = [
            'image' => [
                'image' => '/tmp/non_existent_image_file_12345.png',
                'prompt' => 'Edit this image',
            ],
        ];

        // Act & Assert: Should throw exception for non-existent file
        expect(fn () => $this->adapter->transformRequest($unifiedRequest))
            ->toThrow(FileValidationException::class, 'File not found');
    });

    it('validates image file is readable', function () {
        // Arrange: Create a file and make it unreadable (skip on Windows)
        if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
            $this->markTestSkipped('File permission tests not supported on Windows');
        }

        $this->tempImageFile = tempnam(sys_get_temp_dir(), 'feature_image_') . '.png';
        $pngHeader = "\x89\x50\x4e\x47\x0d\x0a\x1a\x0a";
        file_put_contents($this->tempImageFile, $pngHeader . str_repeat('x', 1024));
        chmod($this->tempImageFile, 0000);

        $unifiedRequest = [
            'image' => [
                'image' => $this->tempImageFile,
                'prompt' => 'Edit this image',
            ],
        ];

        // Act & Assert: Should throw exception for unreadable file
        expect(fn () => $this->adapter->transformRequest($unifiedRequest))
            ->toThrow(FileValidationException::class, 'not readable');

        // Cleanup: Restore permissions
        chmod($this->tempImageFile, 0644);
    });

    it('validates image file size limit (4MB)', function () {
        // Arrange: Create a file larger than 4MB
        $this->tempImageFile = tempnam(sys_get_temp_dir(), 'feature_image_') . '.png';
        $pngHeader = "\x89\x50\x4e\x47\x0d\x0a\x1a\x0a";
        // Create a file just over 4MB (4 * 1024 * 1024 + 1000 bytes)
        file_put_contents($this->tempImageFile, $pngHeader . str_repeat('x', 4 * 1024 * 1024 + 1000));

        $unifiedRequest = [
            'image' => [
                'image' => $this->tempImageFile,
                'prompt' => 'Edit this image',
            ],
        ];

        // Act & Assert: Should throw exception for file too large
        expect(fn () => $this->adapter->transformRequest($unifiedRequest))
            ->toThrow(ImageEditException::class, 'exceeds maximum allowed size (4MB)');
    });

    it('validates mask file format when provided', function () {
        // Arrange: Create valid image but invalid mask format
        $this->tempImageFile = tempnam(sys_get_temp_dir(), 'feature_image_') . '.png';
        $this->tempMaskFile = tempnam(sys_get_temp_dir(), 'feature_mask_') . '.gif';

        $pngHeader = "\x89\x50\x4e\x47\x0d\x0a\x1a\x0a";
        file_put_contents($this->tempImageFile, $pngHeader . str_repeat('x', 1024));
        file_put_contents($this->tempMaskFile, 'fake gif content');

        $unifiedRequest = [
            'image' => [
                'image' => $this->tempImageFile,
                'prompt' => 'Edit with mask',
                'mask' => $this->tempMaskFile,
            ],
        ];

        // Act & Assert: Should throw exception for unsupported mask format
        expect(fn () => $this->adapter->transformRequest($unifiedRequest))
            ->toThrow(ImageEditException::class, 'Mask must be in PNG format');
    });

    it('validates image file path is a string', function () {
        // Arrange: Pass non-string value as image path
        $unifiedRequest = [
            'image' => [
                'image' => 123,
                'prompt' => 'Edit this',
            ],
        ];

        // Act & Assert: Should throw exception for non-string path
        expect(fn () => $this->adapter->transformRequest($unifiedRequest))
            ->toThrow(FileValidationException::class, 'File path must be a string');
    });

    it('handles base64 response format for edited images', function () {
        // Arrange: Create valid PNG file
        $this->tempImageFile = tempnam(sys_get_temp_dir(), 'feature_image_') . '.png';
        $pngHeader = "\x89\x50\x4e\x47\x0d\x0a\x1a\x0a";
        file_put_contents($this->tempImageFile, $pngHeader . str_repeat('x', 1024));

        $unifiedRequest = [
            'image' => [
                'image' => $this->tempImageFile,
                'prompt' => 'Edit to base64',
                'response_format' => 'b64_json',
            ],
        ];

        // Act: Transform request
        $transformedRequest = $this->adapter->transformRequest($unifiedRequest);

        // Assert: Verify base64 format is preserved
        expect($transformedRequest['response_format'])->toBe('b64_json');

        // Simulate base64 response
        $apiResponse = [
            'created' => 1234567890,
            'data' => [
                ['b64_json' => 'iVBORw0KGgoAAAANSUhEUgAAAAUA...'],
            ],
        ];

        // Act: Transform response
        $responseDto = $this->adapter->transformResponse($apiResponse);

        // Assert: Verify base64 data is preserved
        expect($responseDto->images[0]['b64_json'])->toBe('iVBORw0KGgoAAAANSUhEUgAAAAUA...');
    });

    it('processes multiple edited images in response', function () {
        // Arrange: Simulate API response with multiple edited images
        $apiResponse = [
            'created' => 1234567890,
            'data' => [
                ['url' => 'https://example.com/edit1.png'],
                ['url' => 'https://example.com/edit2.png'],
            ],
        ];

        // Act: Transform response
        $responseDto = $this->adapter->transformResponse($apiResponse);

        // Assert: Verify multiple images are handled
        expect($responseDto->images)->toHaveCount(2)
            ->and($responseDto->metadata['count'])->toBe(2)
            ->and($responseDto->images[0]['url'])->toBe('https://example.com/edit1.png')
            ->and($responseDto->images[1]['url'])->toBe('https://example.com/edit2.png');
    });

    it('preserves raw API response in ResponseDto', function () {
        // Arrange: Complete API response
        $apiResponse = [
            'created' => 1234567890,
            'data' => [
                ['url' => 'https://example.com/edited.png'],
            ],
            'model' => 'dall-e-2',
        ];

        // Act: Transform response
        $responseDto = $this->adapter->transformResponse($apiResponse);

        // Assert: Raw response should be preserved
        expect($responseDto->raw)->toBe($apiResponse)
            ->and($responseDto->raw['model'])->toBe('dall-e-2');
    });

    it('handles empty data array in API response', function () {
        // Arrange: API response with empty data
        $apiResponse = [
            'created' => 1234567890,
            'data' => [],
        ];

        // Act: Transform response
        $responseDto = $this->adapter->transformResponse($apiResponse);

        // Assert: Should handle empty data gracefully
        expect($responseDto)->toBeInstanceOf(ResponseDto::class)
            ->and($responseDto->images)->toBeArray()
            ->and($responseDto->images)->toBeEmpty()
            ->and($responseDto->metadata['count'])->toBe(0);
    });
});
