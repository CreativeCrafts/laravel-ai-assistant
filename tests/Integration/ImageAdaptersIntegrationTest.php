<?php

declare(strict_types=1);

use CreativeCrafts\LaravelAiAssistant\Adapters\AdapterFactory;
use CreativeCrafts\LaravelAiAssistant\Adapters\ImageEditAdapter;
use CreativeCrafts\LaravelAiAssistant\Adapters\ImageGenerationAdapter;
use CreativeCrafts\LaravelAiAssistant\Adapters\ImageVariationAdapter;
use CreativeCrafts\LaravelAiAssistant\DataTransferObjects\ResponseDto;
use CreativeCrafts\LaravelAiAssistant\Enums\OpenAiEndpoint;
use CreativeCrafts\LaravelAiAssistant\Exceptions\FileValidationException;

/**
 * Integration tests for Image Adapters with the new adapter architecture.
 *
 * These tests verify that the adapters correctly transform requests to OpenAI API format
 * and transform responses back to unified ResponseDto format.
 *
 * @group integration
 */
beforeEach(function () {
    $this->factory = new AdapterFactory();
    $this->testImagePath = __DIR__ . '/../fixtures/test-image.png';
});

afterEach(function () {
    // Clean up any temporary files
    if (isset($this->tempFile) && file_exists($this->tempFile)) {
        unlink($this->tempFile);
    }
});

describe('ImageGenerationAdapter Integration', function () {
    it('transforms request to correct OpenAI DALL-E generation format', function () {
        // Arrange: Create realistic image generation request
        $adapter = $this->factory->make(OpenAiEndpoint::ImageGeneration);

        $unifiedRequest = [
            'image' => [
                'prompt' => 'A futuristic cityscape at sunset with flying cars',
                'model' => 'dall-e-3',
                'n' => 1,
                'size' => '1024x1024',
                'quality' => 'hd',
                'style' => 'vivid',
                'response_format' => 'url',
            ],
        ];

        // Act: Transform to OpenAI format
        $openAiRequest = $adapter->transformRequest($unifiedRequest);

        // Assert: Verify OpenAI API format
        expect($openAiRequest)->toMatchArray([
            'prompt' => 'A futuristic cityscape at sunset with flying cars',
            'model' => 'dall-e-3',
            'n' => 1,
            'size' => '1024x1024',
            'quality' => 'hd',
            'style' => 'vivid',
            'response_format' => 'url',
        ]);
    });

    it('transforms OpenAI DALL-E generation response to unified ResponseDto', function () {
        // Arrange: Realistic OpenAI API response
        $adapter = $this->factory->make(OpenAiEndpoint::ImageGeneration);

        $openAiResponse = [
            'created' => 1699564800,
            'data' => [
                [
                    'url' => 'https://oaidalleapiprodscus.blob.core.windows.net/private/test-image.png',
                    'revised_prompt' => 'A stunning futuristic cityscape during golden hour with advanced flying vehicles',
                ],
            ],
        ];

        // Act: Transform to unified format
        $responseDto = $adapter->transformResponse($openAiResponse);

        // Assert: Verify unified ResponseDto structure
        expect($responseDto)->toBeInstanceOf(ResponseDto::class)
            ->and($responseDto->type)->toBe('image_generation')
            ->and($responseDto->status)->toBe('completed')
            ->and($responseDto->text)->toBeNull()
            ->and($responseDto->audioContent)->toBeNull()
            ->and($responseDto->images)->toBeArray()
            ->and($responseDto->images)->toHaveCount(1)
            ->and($responseDto->images[0]['url'])->toContain('blob.core.windows.net')
            ->and($responseDto->images[0]['revised_prompt'])->toContain('futuristic cityscape')
            ->and($responseDto->metadata['created'])->toBe(1699564800)
            ->and($responseDto->metadata['count'])->toBe(1);
    });

    it('uses defaults for missing optional parameters', function () {
        // Arrange: Minimal request with only required fields
        $adapter = $this->factory->make(OpenAiEndpoint::ImageGeneration);

        $unifiedRequest = [
            'image' => [
                'prompt' => 'A simple landscape',
            ],
        ];

        // Act: Transform to OpenAI format
        $openAiRequest = $adapter->transformRequest($unifiedRequest);

        // Assert: Verify defaults are applied
        expect($openAiRequest['model'])->toBe('dall-e-2')
            ->and($openAiRequest['n'])->toBe(1)
            ->and($openAiRequest['size'])->toBe('1024x1024')
            ->and($openAiRequest['response_format'])->toBe('url');
    });
});

describe('ImageEditAdapter Integration', function () {
    it('transforms request to correct OpenAI DALL-E edit format', function () {
        // Arrange: Create realistic image edit request
        $adapter = $this->factory->make(OpenAiEndpoint::ImageEdit);

        $unifiedRequest = [
            'image' => [
                'image' => $this->testImagePath,
                'prompt' => 'Add a red hat to the subject',
                'mask' => null,
                'model' => 'dall-e-2',
                'n' => 1,
                'size' => '512x512',
                'response_format' => 'url',
            ],
        ];

        // Act: Transform to OpenAI format
        $openAiRequest = $adapter->transformRequest($unifiedRequest);

        // Assert: Verify OpenAI API format
        expect($openAiRequest)->toMatchArray([
            'image' => $this->testImagePath,
            'prompt' => 'Add a red hat to the subject',
            'mask' => null,
            'model' => 'dall-e-2',
            'n' => 1,
            'size' => '512x512',
            'response_format' => 'url',
        ]);
    });

    it('transforms OpenAI DALL-E edit response to unified ResponseDto', function () {
        // Arrange: Realistic OpenAI API response for image edit
        $adapter = $this->factory->make(OpenAiEndpoint::ImageEdit);

        $openAiResponse = [
            'created' => 1699564900,
            'data' => [
                [
                    'url' => 'https://oaidalleapiprodscus.blob.core.windows.net/private/edited-image.png',
                ],
                [
                    'url' => 'https://oaidalleapiprodscus.blob.core.windows.net/private/edited-image-2.png',
                ],
            ],
        ];

        // Act: Transform to unified format
        $responseDto = $adapter->transformResponse($openAiResponse);

        // Assert: Verify unified ResponseDto structure
        expect($responseDto)->toBeInstanceOf(ResponseDto::class)
            ->and($responseDto->type)->toBe('image_edit')
            ->and($responseDto->status)->toBe('completed')
            ->and($responseDto->images)->toHaveCount(2)
            ->and($responseDto->images[0]['url'])->toContain('edited-image.png')
            ->and($responseDto->metadata['created'])->toBe(1699564900)
            ->and($responseDto->metadata['count'])->toBe(2);
    });

    it('validates image file exists before making API request', function () {
        // Arrange: Invalid image file path
        $adapter = $this->factory->make(OpenAiEndpoint::ImageEdit);

        $unifiedRequest = [
            'image' => [
                'image' => '/non/existent/image.png',
                'prompt' => 'Edit this image',
            ],
        ];

        // Act & Assert: Should throw validation exception before API call
        expect(fn () => $adapter->transformRequest($unifiedRequest))
            ->toThrow(FileValidationException::class, 'File not found');
    });
});

describe('ImageVariationAdapter Integration', function () {
    it('transforms request to correct OpenAI DALL-E variation format', function () {
        // Arrange: Create realistic image variation request
        $adapter = $this->factory->make(OpenAiEndpoint::ImageVariation);

        $unifiedRequest = [
            'image' => [
                'image' => $this->testImagePath,
                'model' => 'dall-e-2',
                'n' => 3,
                'size' => '256x256',
                'response_format' => 'b64_json',
            ],
        ];

        // Act: Transform to OpenAI format
        $openAiRequest = $adapter->transformRequest($unifiedRequest);

        // Assert: Verify OpenAI API format (no prompt for variations)
        expect($openAiRequest)->toMatchArray([
            'image' => $this->testImagePath,
            'model' => 'dall-e-2',
            'n' => 3,
            'size' => '256x256',
            'response_format' => 'b64_json',
        ])
        ->and($openAiRequest)->not->toHaveKey('prompt');
    });

    it('transforms OpenAI DALL-E variation response to unified ResponseDto', function () {
        // Arrange: Realistic OpenAI API response for image variation
        $adapter = $this->factory->make(OpenAiEndpoint::ImageVariation);

        $openAiResponse = [
            'created' => 1699565000,
            'data' => [
                [
                    'b64_json' => base64_encode('fake_image_data_1'),
                ],
                [
                    'b64_json' => base64_encode('fake_image_data_2'),
                ],
                [
                    'b64_json' => base64_encode('fake_image_data_3'),
                ],
            ],
        ];

        // Act: Transform to unified format
        $responseDto = $adapter->transformResponse($openAiResponse);

        // Assert: Verify unified ResponseDto structure
        expect($responseDto)->toBeInstanceOf(ResponseDto::class)
            ->and($responseDto->type)->toBe('image_variation')
            ->and($responseDto->status)->toBe('completed')
            ->and($responseDto->images)->toHaveCount(3)
            ->and($responseDto->images[0]['b64_json'])->not->toBeNull()
            ->and($responseDto->images[1]['b64_json'])->not->toBeNull()
            ->and($responseDto->images[2]['b64_json'])->not->toBeNull()
            ->and($responseDto->metadata['count'])->toBe(3);
    });

    it('validates image file exists for variations', function () {
        // Arrange: Invalid image file path
        $adapter = $this->factory->make(OpenAiEndpoint::ImageVariation);

        $unifiedRequest = [
            'image' => [
                'image' => '/path/to/missing/image.png',
            ],
        ];

        // Act & Assert: Should throw validation exception
        expect(fn () => $adapter->transformRequest($unifiedRequest))
            ->toThrow(FileValidationException::class, 'File not found');
    });
});

describe('AdapterFactory Integration', function () {
    it('creates correct adapter for each image endpoint', function () {
        $testCases = [
            [OpenAiEndpoint::ImageGeneration, ImageGenerationAdapter::class],
            [OpenAiEndpoint::ImageEdit, ImageEditAdapter::class],
            [OpenAiEndpoint::ImageVariation, ImageVariationAdapter::class],
        ];

        foreach ($testCases as [$endpoint, $expectedClass]) {
            // Act: Create adapter
            $adapter = $this->factory->make($endpoint);

            // Assert: Correct adapter type
            expect($adapter)->toBeInstanceOf($expectedClass);
        }
    });

    it('all image adapters implement EndpointAdapter interface', function () {
        $imageEndpoints = [
            OpenAiEndpoint::ImageGeneration,
            OpenAiEndpoint::ImageEdit,
            OpenAiEndpoint::ImageVariation,
        ];

        foreach ($imageEndpoints as $endpoint) {
            // Act: Create adapter
            $adapter = $this->factory->make($endpoint);

            // Assert: Has required methods
            expect(method_exists($adapter, 'transformRequest'))->toBeTrue()
                ->and(method_exists($adapter, 'transformResponse'))->toBeTrue();
        }
    });
});

describe('Round-trip transformation', function () {
    it('maintains data integrity through complete image generation flow', function () {
        // Arrange: Complete flow simulation
        $adapter = new ImageGenerationAdapter();

        $originalData = [
            'image' => [
                'prompt' => 'A serene mountain lake with reflection',
                'model' => 'dall-e-3',
                'quality' => 'hd',
                'size' => '1792x1024',
            ],
        ];

        // Act: Transform request -> simulate API -> transform response
        $apiRequest = $adapter->transformRequest($originalData);

        expect($apiRequest['prompt'])->toBe('A serene mountain lake with reflection');

        $simulatedApiResponse = [
            'created' => 1699565100,
            'data' => [
                [
                    'url' => 'https://example.com/generated-image.png',
                    'revised_prompt' => 'A peaceful mountain lake with mirror-like reflections at dawn',
                ],
            ],
        ];

        $responseDto = $adapter->transformResponse($simulatedApiResponse);

        // Assert: Data integrity maintained
        expect($responseDto->images[0]['url'])->toContain('generated-image.png')
            ->and($responseDto->images[0]['revised_prompt'])->toContain('mountain lake')
            ->and($responseDto->type)->toBe('image_generation')
            ->and($responseDto->metadata['count'])->toBe(1);
    });

    it('maintains data integrity through complete image edit flow', function () {
        // Arrange: Complete flow simulation
        $adapter = new ImageEditAdapter();

        $originalData = [
            'image' => [
                'image' => $this->testImagePath,
                'prompt' => 'Change the color to blue',
                'n' => 2,
            ],
        ];

        // Act: Transform request -> simulate API -> transform response
        $apiRequest = $adapter->transformRequest($originalData);

        expect($apiRequest['prompt'])->toBe('Change the color to blue');

        $simulatedApiResponse = [
            'created' => 1699565200,
            'data' => [
                ['url' => 'https://example.com/edit-1.png'],
                ['url' => 'https://example.com/edit-2.png'],
            ],
        ];

        $responseDto = $adapter->transformResponse($simulatedApiResponse);

        // Assert: Data integrity maintained
        expect($responseDto->images)->toHaveCount(2)
            ->and($responseDto->type)->toBe('image_edit')
            ->and($responseDto->metadata['count'])->toBe(2);
    });

    it('maintains data integrity through complete image variation flow', function () {
        // Arrange: Complete flow simulation
        $adapter = new ImageVariationAdapter();

        $originalData = [
            'image' => [
                'image' => $this->testImagePath,
                'n' => 2,
                'size' => '512x512',
            ],
        ];

        // Act: Transform request -> simulate API -> transform response
        $apiRequest = $adapter->transformRequest($originalData);

        $simulatedApiResponse = [
            'created' => 1699565300,
            'data' => [
                ['url' => 'https://example.com/variation-1.png'],
                ['url' => 'https://example.com/variation-2.png'],
            ],
        ];

        $responseDto = $adapter->transformResponse($simulatedApiResponse);

        // Assert: Data integrity maintained
        expect($responseDto->images)->toHaveCount(2)
            ->and($responseDto->type)->toBe('image_variation')
            ->and($responseDto->metadata['count'])->toBe(2);
    });
});
