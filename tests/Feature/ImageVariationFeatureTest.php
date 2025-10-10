<?php

declare(strict_types=1);

use CreativeCrafts\LaravelAiAssistant\Adapters\ImageVariationAdapter;
use CreativeCrafts\LaravelAiAssistant\DataTransferObjects\ResponseDto;

beforeEach(function () {
    $this->adapter = new ImageVariationAdapter();
});

afterEach(function () {
    // Clean up any temporary files created during tests
    if (isset($this->tempImageFile) && file_exists($this->tempImageFile)) {
        unlink($this->tempImageFile);
    }
});

describe('End-to-end image variation flow', function () {
    it('processes complete image variation request with all parameters', function () {
        // Arrange: Create temporary PNG file
        $this->tempImageFile = tempnam(sys_get_temp_dir(), 'feature_image_') . '.png';

        // Create minimal valid PNG file
        $pngHeader = "\x89\x50\x4e\x47\x0d\x0a\x1a\x0a";
        file_put_contents($this->tempImageFile, $pngHeader . str_repeat('x', 1024));

        $unifiedRequest = [
            'image' => [
                'image' => $this->tempImageFile,
                'model' => 'dall-e-2',
                'n' => 3,
                'size' => '512x512',
                'response_format' => 'url',
            ],
        ];

        // Act: Transform request
        $transformedRequest = $this->adapter->transformRequest($unifiedRequest);

        // Assert: Verify request transformation
        expect($transformedRequest)->toBeArray()
            ->and($transformedRequest['image'])->toBe($this->tempImageFile)
            ->and($transformedRequest['model'])->toBe('dall-e-2')
            ->and($transformedRequest['n'])->toBe(3)
            ->and($transformedRequest['size'])->toBe('512x512')
            ->and($transformedRequest['response_format'])->toBe('url');

        // Simulate API response
        $apiResponse = [
            'created' => 1234567890,
            'data' => [
                ['url' => 'https://example.com/variation1.png'],
                ['url' => 'https://example.com/variation2.png'],
                ['url' => 'https://example.com/variation3.png'],
            ],
        ];

        // Act: Transform response
        $responseDto = $this->adapter->transformResponse($apiResponse);

        // Assert: Verify response transformation
        expect($responseDto)->toBeInstanceOf(ResponseDto::class)
            ->and($responseDto->status)->toBe('completed')
            ->and($responseDto->text)->toBeNull()
            ->and($responseDto->type)->toBe('image_variation')
            ->and($responseDto->audioContent)->toBeNull()
            ->and($responseDto->images)->toBeArray()
            ->and($responseDto->images)->toHaveCount(3)
            ->and($responseDto->images[0]['url'])->toBe('https://example.com/variation1.png')
            ->and($responseDto->images[1]['url'])->toBe('https://example.com/variation2.png')
            ->and($responseDto->images[2]['url'])->toBe('https://example.com/variation3.png')
            ->and($responseDto->metadata['created'])->toBe(1234567890)
            ->and($responseDto->metadata['count'])->toBe(3)
            ->and($responseDto->isText())->toBeFalse()
            ->and($responseDto->isAudio())->toBeFalse()
            ->and($responseDto->isImage())->toBeTrue();
    });

    it('handles minimal image variation request with defaults', function () {
        // Arrange: Create minimal PNG file
        $this->tempImageFile = tempnam(sys_get_temp_dir(), 'feature_image_') . '.png';
        $pngHeader = "\x89\x50\x4e\x47\x0d\x0a\x1a\x0a";
        file_put_contents($this->tempImageFile, $pngHeader . str_repeat('x', 2048));

        $unifiedRequest = [
            'image' => [
                'image' => $this->tempImageFile,
            ],
        ];

        // Act: Transform request
        $transformedRequest = $this->adapter->transformRequest($unifiedRequest);

        // Assert: Verify defaults are applied
        expect($transformedRequest['image'])->toBe($this->tempImageFile)
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
            ],
        ];

        // Act & Assert: Should throw exception for unsupported format
        expect(fn () => $this->adapter->transformRequest($unifiedRequest))
            ->toThrow(InvalidArgumentException::class, 'Unsupported image format');
    });

    it('validates image file existence', function () {
        // Arrange: Use a non-existent file path
        $unifiedRequest = [
            'image' => [
                'image' => '/tmp/non_existent_image_file_99999.png',
            ],
        ];

        // Act & Assert: Should throw exception for non-existent file
        expect(fn () => $this->adapter->transformRequest($unifiedRequest))
            ->toThrow(InvalidArgumentException::class, 'Image file does not exist');
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
            ],
        ];

        // Act & Assert: Should throw exception for unreadable file
        expect(fn () => $this->adapter->transformRequest($unifiedRequest))
            ->toThrow(InvalidArgumentException::class, 'Image file is not readable');

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
            ],
        ];

        // Act & Assert: Should throw exception for file too large
        expect(fn () => $this->adapter->transformRequest($unifiedRequest))
            ->toThrow(InvalidArgumentException::class, 'Image file size must be less than 4MB');
    });

    it('validates image file path is a string', function () {
        // Arrange: Pass non-string value as image path
        $unifiedRequest = [
            'image' => [
                'image' => ['not', 'a', 'string'],
            ],
        ];

        // Act & Assert: Should throw exception for non-string path
        expect(fn () => $this->adapter->transformRequest($unifiedRequest))
            ->toThrow(InvalidArgumentException::class, 'Image file path must be a string');
    });

    it('handles base64 response format for variations', function () {
        // Arrange: Create valid PNG file
        $this->tempImageFile = tempnam(sys_get_temp_dir(), 'feature_image_') . '.png';
        $pngHeader = "\x89\x50\x4e\x47\x0d\x0a\x1a\x0a";
        file_put_contents($this->tempImageFile, $pngHeader . str_repeat('x', 1024));

        $unifiedRequest = [
            'image' => [
                'image' => $this->tempImageFile,
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

    it('handles different image sizes', function () {
        // Arrange: Create valid PNG file
        $this->tempImageFile = tempnam(sys_get_temp_dir(), 'feature_image_') . '.png';
        $pngHeader = "\x89\x50\x4e\x47\x0d\x0a\x1a\x0a";
        file_put_contents($this->tempImageFile, $pngHeader . str_repeat('x', 1024));

        $sizes = ['256x256', '512x512', '1024x1024'];

        foreach ($sizes as $size) {
            // Arrange: Request with specific size
            $unifiedRequest = [
                'image' => [
                    'image' => $this->tempImageFile,
                    'size' => $size,
                ],
            ];

            // Act: Transform request
            $transformedRequest = $this->adapter->transformRequest($unifiedRequest);

            // Assert: Size should be preserved
            expect($transformedRequest['size'])->toBe($size);
        }
    });

    it('handles multiple variation count', function () {
        // Arrange: Create valid PNG file
        $this->tempImageFile = tempnam(sys_get_temp_dir(), 'feature_image_') . '.png';
        $pngHeader = "\x89\x50\x4e\x47\x0d\x0a\x1a\x0a";
        file_put_contents($this->tempImageFile, $pngHeader . str_repeat('x', 1024));

        $unifiedRequest = [
            'image' => [
                'image' => $this->tempImageFile,
                'n' => 4,
            ],
        ];

        // Act: Transform request
        $transformedRequest = $this->adapter->transformRequest($unifiedRequest);

        // Assert: Number of variations should be preserved
        expect($transformedRequest['n'])->toBe(4);
    });

    it('preserves raw API response in ResponseDto', function () {
        // Arrange: Complete API response
        $apiResponse = [
            'created' => 1234567890,
            'data' => [
                ['url' => 'https://example.com/variation.png'],
            ],
            'model' => 'dall-e-2',
            'custom_field' => 'custom_value',
        ];

        // Act: Transform response
        $responseDto = $this->adapter->transformResponse($apiResponse);

        // Assert: Raw response should be preserved
        expect($responseDto->raw)->toBe($apiResponse)
            ->and($responseDto->raw['model'])->toBe('dall-e-2')
            ->and($responseDto->raw['custom_field'])->toBe('custom_value');
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

    it('handles API response without created timestamp', function () {
        // Arrange: Minimal API response
        $apiResponse = [
            'data' => [
                ['url' => 'https://example.com/variation.png'],
            ],
        ];

        // Act: Transform response
        $responseDto = $this->adapter->transformResponse($apiResponse);

        // Assert: Should handle missing created field gracefully
        expect($responseDto)->toBeInstanceOf(ResponseDto::class)
            ->and($responseDto->metadata['created'])->toBeNull()
            ->and($responseDto->metadata['count'])->toBe(1);
    });

    it('validates unsupported image formats', function () {
        $unsupportedFormats = ['gif', 'bmp', 'svg', 'webp'];

        foreach ($unsupportedFormats as $format) {
            // Arrange: Create file with unsupported format
            $tempFile = tempnam(sys_get_temp_dir(), 'feature_image_') . '.' . $format;
            file_put_contents($tempFile, 'fake image content');

            $unifiedRequest = [
                'image' => [
                    'image' => $tempFile,
                ],
            ];

            // Act & Assert: Should throw exception for unsupported format
            expect(fn () => $this->adapter->transformRequest($unifiedRequest))
                ->toThrow(InvalidArgumentException::class, 'Unsupported image format');

            // Cleanup
            unlink($tempFile);
        }
    });
});
