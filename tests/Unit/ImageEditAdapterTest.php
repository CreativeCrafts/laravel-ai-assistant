<?php

declare(strict_types=1);

use CreativeCrafts\LaravelAiAssistant\Adapters\ImageEditAdapter;
use CreativeCrafts\LaravelAiAssistant\DataTransferObjects\ResponseDto;
use CreativeCrafts\LaravelAiAssistant\Exceptions\ImageEditException;
use CreativeCrafts\LaravelAiAssistant\Exceptions\FileValidationException;

beforeEach(function () {
    $this->adapter = new ImageEditAdapter();
});

describe('transformRequest', function () {
    it('transforms unified request with all parameters', function () {
        $imageFile = tempnam(sys_get_temp_dir(), 'test_image_') . '.png';
        $maskFile = tempnam(sys_get_temp_dir(), 'test_mask_') . '.png';
        file_put_contents($imageFile, 'fake png content');
        file_put_contents($maskFile, 'fake mask content');

        $unifiedRequest = [
            'image' => [
                'image' => $imageFile,
                'prompt' => 'Add a sunset to this image',
                'mask' => $maskFile,
                'model' => 'dall-e-2',
                'n' => 2,
                'size' => '512x512',
                'response_format' => 'b64_json',
            ],
        ];

        $result = $this->adapter->transformRequest($unifiedRequest);

        expect($result)->toBe([
            'image' => $imageFile,
            'prompt' => 'Add a sunset to this image',
            'mask' => $maskFile,
            'model' => 'dall-e-2',
            'n' => 2,
            'size' => '512x512',
            'response_format' => 'b64_json',
        ]);

        unlink($imageFile);
        unlink($maskFile);
    });

    it('applies default values for missing parameters', function () {
        $imageFile = tempnam(sys_get_temp_dir(), 'test_image_') . '.png';
        file_put_contents($imageFile, 'fake png content');

        $unifiedRequest = [
            'image' => [
                'image' => $imageFile,
                'prompt' => 'Edit this image',
            ],
        ];

        $result = $this->adapter->transformRequest($unifiedRequest);

        expect($result)->toBe([
            'image' => $imageFile,
            'prompt' => 'Edit this image',
            'mask' => null,
            'model' => 'dall-e-2',
            'n' => 1,
            'size' => '1024x1024',
            'response_format' => 'url',
        ]);

        unlink($imageFile);
    });

    it('throws exception when image array is empty', function () {
        $unifiedRequest = ['image' => []];

        expect(fn () => $this->adapter->transformRequest($unifiedRequest))
            ->toThrow(ImageEditException::class, 'Source image is required for image editing');
    });

    it('throws exception when image key is missing', function () {
        $unifiedRequest = [];

        expect(fn () => $this->adapter->transformRequest($unifiedRequest))
            ->toThrow(ImageEditException::class, 'Source image is required for image editing');
    });

    it('throws exception when image file path is not a string', function () {
        $unifiedRequest = [
            'image' => [
                'image' => 123,
                'prompt' => 'Test prompt',
            ],
        ];

        expect(fn () => $this->adapter->transformRequest($unifiedRequest))
            ->toThrow(FileValidationException::class, 'File path must be a string');
    });

    it('throws exception when image file does not exist', function () {
        $unifiedRequest = [
            'image' => [
                'image' => '/non/existent/path/image.png',
                'prompt' => 'Test prompt',
            ],
        ];

        expect(fn () => $this->adapter->transformRequest($unifiedRequest))
            ->toThrow(FileValidationException::class, 'File not found');
    });

    it('throws exception when image file is not readable', function () {
        $tempFile = tempnam(sys_get_temp_dir(), 'test_image_') . '.png';
        file_put_contents($tempFile, 'test');
        chmod($tempFile, 0000);

        $unifiedRequest = [
            'image' => [
                'image' => $tempFile,
                'prompt' => 'Test prompt',
            ],
        ];

        try {
            expect(fn () => $this->adapter->transformRequest($unifiedRequest))
                ->toThrow(FileValidationException::class, 'not readable');
        } finally {
            chmod($tempFile, 0644);
            unlink($tempFile);
        }
    });

    it('throws exception for unsupported image format', function () {
        $tempFile = tempnam(sys_get_temp_dir(), 'test_image_') . '.jpg';
        file_put_contents($tempFile, 'test');

        $unifiedRequest = [
            'image' => [
                'image' => $tempFile,
                'prompt' => 'Test prompt',
            ],
        ];

        try {
            expect(fn () => $this->adapter->transformRequest($unifiedRequest))
                ->toThrow(ImageEditException::class, 'Unsupported image format');
        } finally {
            unlink($tempFile);
        }
    });

    it('only accepts PNG format', function () {
        $tempFile = tempnam(sys_get_temp_dir(), 'test_image_') . '.png';
        file_put_contents($tempFile, 'fake png content');

        $unifiedRequest = [
            'image' => [
                'image' => $tempFile,
                'prompt' => 'Test',
            ],
        ];

        $result = $this->adapter->transformRequest($unifiedRequest);
        expect($result['image'])->toBe($tempFile);

        unlink($tempFile);
    });

    it('throws exception when image file exceeds 4MB', function () {
        $tempFile = tempnam(sys_get_temp_dir(), 'test_image_') . '.png';
        $largeContent = str_repeat('x', 5 * 1024 * 1024);
        file_put_contents($tempFile, $largeContent);

        $unifiedRequest = [
            'image' => [
                'image' => $tempFile,
                'prompt' => 'Test prompt',
            ],
        ];

        try {
            expect(fn () => $this->adapter->transformRequest($unifiedRequest))
                ->toThrow(ImageEditException::class, 'exceeds maximum allowed size (4MB)');
        } finally {
            unlink($tempFile);
        }
    });

    it('validates mask file when provided', function () {
        $imageFile = tempnam(sys_get_temp_dir(), 'test_image_') . '.png';
        file_put_contents($imageFile, 'fake png content');

        $unifiedRequest = [
            'image' => [
                'image' => $imageFile,
                'prompt' => 'Test prompt',
                'mask' => '/non/existent/mask.png',
            ],
        ];

        try {
            expect(fn () => $this->adapter->transformRequest($unifiedRequest))
                ->toThrow(FileValidationException::class, 'File not found');
        } finally {
            unlink($imageFile);
        }
    });

    it('validates both image and mask files', function () {
        $imageFile = tempnam(sys_get_temp_dir(), 'test_image_') . '.png';
        $maskFile = tempnam(sys_get_temp_dir(), 'test_mask_') . '.png';
        file_put_contents($imageFile, 'fake png content');
        file_put_contents($maskFile, 'fake mask content');

        $unifiedRequest = [
            'image' => [
                'image' => $imageFile,
                'mask' => $maskFile,
                'prompt' => 'Test',
            ],
        ];

        $result = $this->adapter->transformRequest($unifiedRequest);
        expect($result['image'])->toBe($imageFile);
        expect($result['mask'])->toBe($maskFile);

        unlink($imageFile);
        unlink($maskFile);
    });

    it('allows null mask without validation', function () {
        $imageFile = tempnam(sys_get_temp_dir(), 'test_image_') . '.png';
        file_put_contents($imageFile, 'fake png content');

        $unifiedRequest = [
            'image' => [
                'image' => $imageFile,
                'mask' => null,
                'prompt' => 'Test',
            ],
        ];

        $result = $this->adapter->transformRequest($unifiedRequest);
        expect($result['mask'])->toBeNull();

        unlink($imageFile);
    });
});

describe('transformResponse', function () {
    it('transforms OpenAI API response with all fields', function () {
        $apiResponse = [
            'created' => 1234567890,
            'data' => [
                ['url' => 'https://example.com/edited1.png'],
                ['url' => 'https://example.com/edited2.png'],
            ],
        ];

        $result = $this->adapter->transformResponse($apiResponse);

        expect($result)->toBeInstanceOf(ResponseDto::class);
        expect($result->status)->toBe('completed');
        expect($result->type)->toBe('image_edit');
        expect($result->text)->toBeNull();
        expect($result->audioContent)->toBeNull();
        expect($result->conversationId)->toBeNull();
        expect($result->images)->toHaveCount(2);
        expect($result->images[0]['url'])->toBe('https://example.com/edited1.png');
        expect($result->metadata['created'])->toBe(1234567890);
        expect($result->metadata['count'])->toBe(2);
        expect($result->raw)->toBe($apiResponse);
    });

    it('transforms response with b64_json format', function () {
        $apiResponse = [
            'created' => 1234567890,
            'data' => [
                ['b64_json' => 'base64editedimagedata=='],
            ],
        ];

        $result = $this->adapter->transformResponse($apiResponse);

        expect($result)->toBeInstanceOf(ResponseDto::class);
        expect($result->images)->toHaveCount(1);
        expect($result->images[0]['b64_json'])->toBe('base64editedimagedata==');
        expect($result->metadata['count'])->toBe(1);
    });

    it('transforms response with minimal fields', function () {
        $apiResponse = [
            'data' => [
                ['url' => 'https://example.com/edited.png'],
            ],
        ];

        $result = $this->adapter->transformResponse($apiResponse);

        expect($result)->toBeInstanceOf(ResponseDto::class);
        expect($result->status)->toBe('completed');
        expect($result->type)->toBe('image_edit');
        expect($result->images)->toHaveCount(1);
        expect($result->metadata['created'])->toBeNull();
        expect($result->metadata['count'])->toBe(1);
    });

    it('handles empty data array', function () {
        $apiResponse = ['data' => []];

        $result = $this->adapter->transformResponse($apiResponse);

        expect($result)->toBeInstanceOf(ResponseDto::class);
        expect($result->images)->toBeEmpty();
        expect($result->metadata['count'])->toBe(0);
    });

    it('handles missing data key', function () {
        $apiResponse = [];

        $result = $this->adapter->transformResponse($apiResponse);

        expect($result)->toBeInstanceOf(ResponseDto::class);
        expect($result->images)->toBeEmpty();
        expect($result->metadata['count'])->toBe(0);
    });

    it('generates unique id for each response', function () {
        $apiResponse = ['data' => [['url' => 'https://example.com/edited.png']]];

        $result1 = $this->adapter->transformResponse($apiResponse);
        $result2 = $this->adapter->transformResponse($apiResponse);

        expect($result1->id)->not->toBe($result2->id);
        expect($result1->id)->toContain('image_edit_');
        expect($result2->id)->toContain('image_edit_');
    });

    it('returns ResponseDto with correct helper method results', function () {
        $apiResponse = [
            'data' => [['url' => 'https://example.com/edited.png']],
        ];

        $result = $this->adapter->transformResponse($apiResponse);

        expect($result->isImage())->toBeTrue();
        expect($result->isText())->toBeFalse();
        expect($result->isAudio())->toBeFalse();
    });

    it('handles multiple edited images correctly', function () {
        $apiResponse = [
            'data' => [
                ['url' => 'https://example.com/edited1.png'],
                ['url' => 'https://example.com/edited2.png'],
                ['url' => 'https://example.com/edited3.png'],
            ],
        ];

        $result = $this->adapter->transformResponse($apiResponse);

        expect($result->images)->toHaveCount(3);
        expect($result->metadata['count'])->toBe(3);
        expect($result->images[0]['url'])->toBe('https://example.com/edited1.png');
        expect($result->images[2]['url'])->toBe('https://example.com/edited3.png');
    });
});
