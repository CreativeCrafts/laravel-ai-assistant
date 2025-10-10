<?php

declare(strict_types=1);

use CreativeCrafts\LaravelAiAssistant\Adapters\ImageVariationAdapter;
use CreativeCrafts\LaravelAiAssistant\DataTransferObjects\ResponseDto;

beforeEach(function () {
    $this->adapter = new ImageVariationAdapter();
});

describe('transformRequest', function () {
    it('transforms unified request with all parameters', function () {
        $imageFile = tempnam(sys_get_temp_dir(), 'test_image_') . '.png';
        file_put_contents($imageFile, 'fake png content');

        $unifiedRequest = [
            'image' => [
                'image' => $imageFile,
                'model' => 'dall-e-2',
                'n' => 3,
                'size' => '512x512',
                'response_format' => 'b64_json',
            ],
        ];

        $result = $this->adapter->transformRequest($unifiedRequest);

        expect($result)->toBe([
            'image' => $imageFile,
            'model' => 'dall-e-2',
            'n' => 3,
            'size' => '512x512',
            'response_format' => 'b64_json',
        ]);

        unlink($imageFile);
    });

    it('applies default values for missing parameters', function () {
        $imageFile = tempnam(sys_get_temp_dir(), 'test_image_') . '.png';
        file_put_contents($imageFile, 'fake png content');

        $unifiedRequest = [
            'image' => [
                'image' => $imageFile,
            ],
        ];

        $result = $this->adapter->transformRequest($unifiedRequest);

        expect($result)->toBe([
            'image' => $imageFile,
            'model' => 'dall-e-2',
            'n' => 1,
            'size' => '1024x1024',
            'response_format' => 'url',
        ]);

        unlink($imageFile);
    });

    it('uses dall-e-2 as default model', function () {
        $imageFile = tempnam(sys_get_temp_dir(), 'test_image_') . '.png';
        file_put_contents($imageFile, 'fake png content');

        $unifiedRequest = [
            'image' => [
                'image' => $imageFile,
            ],
        ];

        $result = $this->adapter->transformRequest($unifiedRequest);

        expect($result['model'])->toBe('dall-e-2');

        unlink($imageFile);
    });

    it('handles empty image array', function () {
        $unifiedRequest = ['image' => []];

        $result = $this->adapter->transformRequest($unifiedRequest);

        expect($result)->toBe([
            'image' => null,
            'model' => 'dall-e-2',
            'n' => 1,
            'size' => '1024x1024',
            'response_format' => 'url',
        ]);
    });

    it('handles missing image key', function () {
        $unifiedRequest = [];

        $result = $this->adapter->transformRequest($unifiedRequest);

        expect($result)->toBe([
            'image' => null,
            'model' => 'dall-e-2',
            'n' => 1,
            'size' => '1024x1024',
            'response_format' => 'url',
        ]);
    });

    it('throws exception when image file path is not a string', function () {
        $unifiedRequest = [
            'image' => [
                'image' => 123,
            ],
        ];

        expect(fn () => $this->adapter->transformRequest($unifiedRequest))
            ->toThrow(InvalidArgumentException::class, 'Image file path must be a string.');
    });

    it('throws exception when image file does not exist', function () {
        $unifiedRequest = [
            'image' => [
                'image' => '/non/existent/path/image.png',
            ],
        ];

        expect(fn () => $this->adapter->transformRequest($unifiedRequest))
            ->toThrow(InvalidArgumentException::class, 'Image file does not exist');
    });

    it('throws exception when image file is not readable', function () {
        $tempFile = tempnam(sys_get_temp_dir(), 'test_image_') . '.png';
        file_put_contents($tempFile, 'test');
        chmod($tempFile, 0000);

        $unifiedRequest = [
            'image' => [
                'image' => $tempFile,
            ],
        ];

        try {
            expect(fn () => $this->adapter->transformRequest($unifiedRequest))
                ->toThrow(InvalidArgumentException::class, 'Image file is not readable');
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
            ],
        ];

        try {
            expect(fn () => $this->adapter->transformRequest($unifiedRequest))
                ->toThrow(InvalidArgumentException::class, 'Unsupported image format');
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
            ],
        ];

        try {
            expect(fn () => $this->adapter->transformRequest($unifiedRequest))
                ->toThrow(InvalidArgumentException::class, 'Image file size must be less than 4MB');
        } finally {
            unlink($tempFile);
        }
    });

    it('accepts various size options', function () {
        $sizes = ['256x256', '512x512', '1024x1024'];

        foreach ($sizes as $size) {
            $tempFile = tempnam(sys_get_temp_dir(), 'test_image_') . '.png';
            file_put_contents($tempFile, 'fake png content');

            $unifiedRequest = [
                'image' => [
                    'image' => $tempFile,
                    'size' => $size,
                ],
            ];

            $result = $this->adapter->transformRequest($unifiedRequest);

            expect($result['size'])->toBe($size);

            unlink($tempFile);
        }
    });

    it('accepts multiple variations parameter', function () {
        $imageFile = tempnam(sys_get_temp_dir(), 'test_image_') . '.png';
        file_put_contents($imageFile, 'fake png content');

        $unifiedRequest = [
            'image' => [
                'image' => $imageFile,
                'n' => 4,
            ],
        ];

        $result = $this->adapter->transformRequest($unifiedRequest);
        expect($result['n'])->toBe(4);

        unlink($imageFile);
    });
});

describe('transformResponse', function () {
    it('transforms OpenAI API response with all fields', function () {
        $apiResponse = [
            'created' => 1234567890,
            'data' => [
                ['url' => 'https://example.com/variation1.png'],
                ['url' => 'https://example.com/variation2.png'],
            ],
        ];

        $result = $this->adapter->transformResponse($apiResponse);

        expect($result)->toBeInstanceOf(ResponseDto::class);
        expect($result->status)->toBe('completed');
        expect($result->type)->toBe('image_variation');
        expect($result->text)->toBeNull();
        expect($result->audioContent)->toBeNull();
        expect($result->conversationId)->toBeNull();
        expect($result->images)->toHaveCount(2);
        expect($result->images[0]['url'])->toBe('https://example.com/variation1.png');
        expect($result->metadata['created'])->toBe(1234567890);
        expect($result->metadata['count'])->toBe(2);
        expect($result->raw)->toBe($apiResponse);
    });

    it('transforms response with b64_json format', function () {
        $apiResponse = [
            'created' => 1234567890,
            'data' => [
                ['b64_json' => 'base64variationimagedata=='],
            ],
        ];

        $result = $this->adapter->transformResponse($apiResponse);

        expect($result)->toBeInstanceOf(ResponseDto::class);
        expect($result->images)->toHaveCount(1);
        expect($result->images[0]['b64_json'])->toBe('base64variationimagedata==');
        expect($result->metadata['count'])->toBe(1);
    });

    it('transforms response with minimal fields', function () {
        $apiResponse = [
            'data' => [
                ['url' => 'https://example.com/variation.png'],
            ],
        ];

        $result = $this->adapter->transformResponse($apiResponse);

        expect($result)->toBeInstanceOf(ResponseDto::class);
        expect($result->status)->toBe('completed');
        expect($result->type)->toBe('image_variation');
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
        $apiResponse = ['data' => [['url' => 'https://example.com/variation.png']]];

        $result1 = $this->adapter->transformResponse($apiResponse);
        $result2 = $this->adapter->transformResponse($apiResponse);

        expect($result1->id)->not->toBe($result2->id);
        expect($result1->id)->toContain('image_variation_');
        expect($result2->id)->toContain('image_variation_');
    });

    it('returns ResponseDto with correct helper method results', function () {
        $apiResponse = [
            'data' => [['url' => 'https://example.com/variation.png']],
        ];

        $result = $this->adapter->transformResponse($apiResponse);

        expect($result->isImage())->toBeTrue();
        expect($result->isText())->toBeFalse();
        expect($result->isAudio())->toBeFalse();
    });

    it('handles multiple image variations correctly', function () {
        $apiResponse = [
            'data' => [
                ['url' => 'https://example.com/variation1.png'],
                ['url' => 'https://example.com/variation2.png'],
                ['url' => 'https://example.com/variation3.png'],
                ['url' => 'https://example.com/variation4.png'],
            ],
        ];

        $result = $this->adapter->transformResponse($apiResponse);

        expect($result->images)->toHaveCount(4);
        expect($result->metadata['count'])->toBe(4);
        expect($result->images[0]['url'])->toBe('https://example.com/variation1.png');
        expect($result->images[3]['url'])->toBe('https://example.com/variation4.png');
    });

    it('handles mixed url and b64_json responses', function () {
        $apiResponse = [
            'data' => [
                ['url' => 'https://example.com/variation1.png'],
                ['b64_json' => 'base64data=='],
            ],
        ];

        $result = $this->adapter->transformResponse($apiResponse);

        expect($result->images)->toHaveCount(2);
        expect($result->images[0]['url'])->toBe('https://example.com/variation1.png');
        expect($result->images[1]['b64_json'])->toBe('base64data==');
    });
});
