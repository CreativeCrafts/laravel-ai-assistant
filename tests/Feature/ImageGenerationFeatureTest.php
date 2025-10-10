<?php

declare(strict_types=1);

use CreativeCrafts\LaravelAiAssistant\Adapters\ImageGenerationAdapter;
use CreativeCrafts\LaravelAiAssistant\DataTransferObjects\ResponseDto;

beforeEach(function () {
    $this->adapter = new ImageGenerationAdapter();
});

describe('End-to-end image generation flow', function () {
    it('processes complete image generation request with all parameters', function () {
        // Arrange: Create request with all parameters
        $unifiedRequest = [
            'image' => [
                'prompt' => 'A beautiful sunset over the mountains',
                'model' => 'dall-e-3',
                'n' => 1,
                'size' => '1024x1024',
                'quality' => 'hd',
                'style' => 'vivid',
                'response_format' => 'url',
            ],
        ];

        // Act: Transform request
        $transformedRequest = $this->adapter->transformRequest($unifiedRequest);

        // Assert: Verify request transformation
        expect($transformedRequest)->toBeArray()
            ->and($transformedRequest['prompt'])->toBe('A beautiful sunset over the mountains')
            ->and($transformedRequest['model'])->toBe('dall-e-3')
            ->and($transformedRequest['n'])->toBe(1)
            ->and($transformedRequest['size'])->toBe('1024x1024')
            ->and($transformedRequest['quality'])->toBe('hd')
            ->and($transformedRequest['style'])->toBe('vivid')
            ->and($transformedRequest['response_format'])->toBe('url');

        // Simulate API response
        $apiResponse = [
            'created' => 1234567890,
            'data' => [
                [
                    'url' => 'https://example.com/image1.png',
                    'revised_prompt' => 'A beautiful sunset over the mountains with vibrant colors',
                ],
            ],
        ];

        // Act: Transform response
        $responseDto = $this->adapter->transformResponse($apiResponse);

        // Assert: Verify response transformation
        expect($responseDto)->toBeInstanceOf(ResponseDto::class)
            ->and($responseDto->status)->toBe('completed')
            ->and($responseDto->text)->toBeNull()
            ->and($responseDto->type)->toBe('image_generation')
            ->and($responseDto->audioContent)->toBeNull()
            ->and($responseDto->images)->toBeArray()
            ->and($responseDto->images)->toHaveCount(1)
            ->and($responseDto->images[0]['url'])->toBe('https://example.com/image1.png')
            ->and($responseDto->metadata['created'])->toBe(1234567890)
            ->and($responseDto->metadata['count'])->toBe(1)
            ->and($responseDto->isText())->toBeFalse()
            ->and($responseDto->isAudio())->toBeFalse()
            ->and($responseDto->isImage())->toBeTrue();
    });

    it('handles minimal image generation request with defaults', function () {
        // Arrange: Create request with minimal parameters
        $unifiedRequest = [
            'image' => [
                'prompt' => 'A simple drawing',
            ],
        ];

        // Act: Transform request
        $transformedRequest = $this->adapter->transformRequest($unifiedRequest);

        // Assert: Verify defaults are applied
        expect($transformedRequest['prompt'])->toBe('A simple drawing')
            ->and($transformedRequest['model'])->toBe('dall-e-2')
            ->and($transformedRequest['n'])->toBe(1)
            ->and($transformedRequest['size'])->toBe('1024x1024')
            ->and($transformedRequest['quality'])->toBeNull()
            ->and($transformedRequest['style'])->toBeNull()
            ->and($transformedRequest['response_format'])->toBe('url');
    });

    it('processes multiple images in response', function () {
        // Arrange: Simulate API response with multiple images
        $apiResponse = [
            'created' => 1234567890,
            'data' => [
                ['url' => 'https://example.com/image1.png'],
                ['url' => 'https://example.com/image2.png'],
                ['url' => 'https://example.com/image3.png'],
            ],
        ];

        // Act: Transform response
        $responseDto = $this->adapter->transformResponse($apiResponse);

        // Assert: Verify multiple images are handled
        expect($responseDto->images)->toHaveCount(3)
            ->and($responseDto->metadata['count'])->toBe(3)
            ->and($responseDto->images[0]['url'])->toBe('https://example.com/image1.png')
            ->and($responseDto->images[1]['url'])->toBe('https://example.com/image2.png')
            ->and($responseDto->images[2]['url'])->toBe('https://example.com/image3.png');
    });

    it('handles base64 response format', function () {
        // Arrange: Request with base64 response format
        $unifiedRequest = [
            'image' => [
                'prompt' => 'A cat',
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
                ['url' => 'https://example.com/image.png'],
            ],
        ];

        // Act: Transform response
        $responseDto = $this->adapter->transformResponse($apiResponse);

        // Assert: Should handle missing created field gracefully
        expect($responseDto)->toBeInstanceOf(ResponseDto::class)
            ->and($responseDto->metadata['created'])->toBeNull()
            ->and($responseDto->metadata['count'])->toBe(1);
    });

    it('preserves raw API response in ResponseDto', function () {
        // Arrange: Complete API response
        $apiResponse = [
            'created' => 1234567890,
            'data' => [
                [
                    'url' => 'https://example.com/image.png',
                    'revised_prompt' => 'A revised prompt',
                ],
            ],
            'custom_field' => 'custom_value',
        ];

        // Act: Transform response
        $responseDto = $this->adapter->transformResponse($apiResponse);

        // Assert: Raw response should be preserved
        expect($responseDto->raw)->toBe($apiResponse)
            ->and($responseDto->raw['custom_field'])->toBe('custom_value')
            ->and($responseDto->raw['data'][0]['revised_prompt'])->toBe('A revised prompt');
    });

    it('handles different DALL-E models', function () {
        $models = ['dall-e-2', 'dall-e-3'];

        foreach ($models as $model) {
            // Arrange: Request with specific model
            $unifiedRequest = [
                'image' => [
                    'prompt' => 'A test image',
                    'model' => $model,
                ],
            ];

            // Act: Transform request
            $transformedRequest = $this->adapter->transformRequest($unifiedRequest);

            // Assert: Model should be preserved
            expect($transformedRequest['model'])->toBe($model);
        }
    });

    it('handles different image sizes', function () {
        $sizes = ['256x256', '512x512', '1024x1024', '1792x1024', '1024x1792'];

        foreach ($sizes as $size) {
            // Arrange: Request with specific size
            $unifiedRequest = [
                'image' => [
                    'prompt' => 'A test image',
                    'size' => $size,
                ],
            ];

            // Act: Transform request
            $transformedRequest = $this->adapter->transformRequest($unifiedRequest);

            // Assert: Size should be preserved
            expect($transformedRequest['size'])->toBe($size);
        }
    });

    it('handles DALL-E 3 specific parameters', function () {
        // Arrange: Request with DALL-E 3 specific parameters
        $unifiedRequest = [
            'image' => [
                'prompt' => 'A futuristic city',
                'model' => 'dall-e-3',
                'quality' => 'hd',
                'style' => 'natural',
            ],
        ];

        // Act: Transform request
        $transformedRequest = $this->adapter->transformRequest($unifiedRequest);

        // Assert: DALL-E 3 parameters should be preserved
        expect($transformedRequest['quality'])->toBe('hd')
            ->and($transformedRequest['style'])->toBe('natural');
    });
});
