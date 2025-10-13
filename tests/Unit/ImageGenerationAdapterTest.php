<?php

declare(strict_types=1);

use CreativeCrafts\LaravelAiAssistant\Adapters\ImageGenerationAdapter;
use CreativeCrafts\LaravelAiAssistant\DataTransferObjects\ResponseDto;
use CreativeCrafts\LaravelAiAssistant\Exceptions\ImageGenerationException;

beforeEach(function () {
    $this->adapter = new ImageGenerationAdapter();
});

describe('transformRequest', function () {
    it('transforms unified request with all parameters', function () {
        $unifiedRequest = [
            'image' => [
                'prompt' => 'A futuristic city at sunset',
                'model' => 'dall-e-3',
                'n' => 1,
                'size' => '1792x1024',
                'quality' => 'hd',
                'style' => 'natural',
                'response_format' => 'b64_json',
            ],
        ];

        $result = $this->adapter->transformRequest($unifiedRequest);

        expect($result)->toBe([
            'prompt' => 'A futuristic city at sunset',
            'model' => 'dall-e-3',
            'n' => 1,
            'size' => '1792x1024',
            'quality' => 'hd',
            'style' => 'natural',
            'response_format' => 'b64_json',
        ]);
    });

    it('applies default values for missing parameters', function () {
        $unifiedRequest = [
            'image' => [
                'prompt' => 'A simple test prompt',
            ],
        ];

        $result = $this->adapter->transformRequest($unifiedRequest);

        expect($result)->toBe([
            'prompt' => 'A simple test prompt',
            'model' => 'dall-e-2',
            'n' => 1,
            'size' => '1024x1024',
            'quality' => null,
            'style' => null,
            'response_format' => 'url',
        ]);
    });

    it('uses dall-e-2 as default model', function () {
        $unifiedRequest = [
            'image' => [
                'prompt' => 'Test prompt',
            ],
        ];

        $result = $this->adapter->transformRequest($unifiedRequest);

        expect($result['model'])->toBe('dall-e-2');
    });

    it('uses url as default response format', function () {
        $unifiedRequest = [
            'image' => [
                'prompt' => 'Test prompt',
            ],
        ];

        $result = $this->adapter->transformRequest($unifiedRequest);

        expect($result['response_format'])->toBe('url');
    });

    it('throws exception when prompt is missing (empty image array)', function () {
        $unifiedRequest = ['image' => []];

        expect(fn () => $this->adapter->transformRequest($unifiedRequest))
            ->toThrow(ImageGenerationException::class, 'Image prompt is required for generation');
    });

    it('throws exception when prompt is missing (missing image key)', function () {
        $unifiedRequest = [];

        expect(fn () => $this->adapter->transformRequest($unifiedRequest))
            ->toThrow(ImageGenerationException::class, 'Image prompt is required for generation');
    });

    it('throws exception when prompt is missing (non-array image value)', function () {
        $unifiedRequest = ['image' => 'not an array'];

        expect(fn () => $this->adapter->transformRequest($unifiedRequest))
            ->toThrow(ImageGenerationException::class, 'Image prompt is required for generation');
    });

    it('accepts dall-e-3 specific parameters', function () {
        $unifiedRequest = [
            'image' => [
                'prompt' => 'Test',
                'model' => 'dall-e-3',
                'quality' => 'hd',
                'style' => 'vivid',
            ],
        ];

        $result = $this->adapter->transformRequest($unifiedRequest);

        expect($result['model'])->toBe('dall-e-3');
        expect($result['quality'])->toBe('hd');
        expect($result['style'])->toBe('vivid');
    });

    it('accepts various size options for dall-e-2', function () {
        $sizes = ['256x256', '512x512', '1024x1024'];

        foreach ($sizes as $size) {
            $unifiedRequest = [
                'image' => [
                    'prompt' => 'Test',
                    'model' => 'dall-e-2',
                    'size' => $size,
                ],
            ];

            $result = $this->adapter->transformRequest($unifiedRequest);

            expect($result['size'])->toBe($size);
        }
    });

    it('accepts various size options for dall-e-3', function () {
        $sizes = ['1024x1024', '1792x1024', '1024x1792'];

        foreach ($sizes as $size) {
            $unifiedRequest = [
                'image' => [
                    'prompt' => 'Test',
                    'model' => 'dall-e-3',
                    'size' => $size,
                ],
            ];

            $result = $this->adapter->transformRequest($unifiedRequest);

            expect($result['size'])->toBe($size);
        }
    });
});

describe('validation', function () {
    it('throws exception when prompt is empty string', function () {
        $unifiedRequest = [
            'image' => [
                'prompt' => '',
            ],
        ];

        expect(fn () => $this->adapter->transformRequest($unifiedRequest))
            ->toThrow(ImageGenerationException::class, 'Image prompt cannot be empty');
    });

    it('throws exception when prompt is whitespace only', function () {
        $unifiedRequest = [
            'image' => [
                'prompt' => '   ',
            ],
        ];

        expect(fn () => $this->adapter->transformRequest($unifiedRequest))
            ->toThrow(ImageGenerationException::class, 'Image prompt cannot be empty');
    });

    it('throws exception when prompt exceeds 4000 characters', function () {
        $longPrompt = str_repeat('a', 4001);
        $unifiedRequest = [
            'image' => [
                'prompt' => $longPrompt,
            ],
        ];

        expect(fn () => $this->adapter->transformRequest($unifiedRequest))
            ->toThrow(ImageGenerationException::class, 'exceeds maximum allowed length');
    });

    it('accepts prompt with exactly 4000 characters', function () {
        $maxPrompt = str_repeat('a', 4000);
        $unifiedRequest = [
            'image' => [
                'prompt' => $maxPrompt,
            ],
        ];

        $result = $this->adapter->transformRequest($unifiedRequest);

        expect($result['prompt'])->toBe($maxPrompt);
    });

    it('throws exception for invalid size with dall-e-2', function () {
        $unifiedRequest = [
            'image' => [
                'prompt' => 'Test',
                'model' => 'dall-e-2',
                'size' => '1792x1024',
            ],
        ];

        expect(fn () => $this->adapter->transformRequest($unifiedRequest))
            ->toThrow(ImageGenerationException::class, 'Invalid size');
    });

    it('throws exception for invalid size with dall-e-3', function () {
        $unifiedRequest = [
            'image' => [
                'prompt' => 'Test',
                'model' => 'dall-e-3',
                'size' => '256x256',
            ],
        ];

        expect(fn () => $this->adapter->transformRequest($unifiedRequest))
            ->toThrow(ImageGenerationException::class, 'Invalid size');
    });

    it('throws exception for invalid image count below minimum', function () {
        $unifiedRequest = [
            'image' => [
                'prompt' => 'Test',
                'n' => 0,
            ],
        ];

        expect(fn () => $this->adapter->transformRequest($unifiedRequest))
            ->toThrow(ImageGenerationException::class, 'Invalid number of images');
    });

    it('throws exception for invalid image count above maximum for dall-e-2', function () {
        $unifiedRequest = [
            'image' => [
                'prompt' => 'Test',
                'model' => 'dall-e-2',
                'n' => 11,
            ],
        ];

        expect(fn () => $this->adapter->transformRequest($unifiedRequest))
            ->toThrow(ImageGenerationException::class, 'Invalid number of images');
    });

    it('throws exception for invalid image count for dall-e-3', function () {
        $unifiedRequest = [
            'image' => [
                'prompt' => 'Test',
                'model' => 'dall-e-3',
                'n' => 2,
            ],
        ];

        expect(fn () => $this->adapter->transformRequest($unifiedRequest))
            ->toThrow(ImageGenerationException::class, 'Invalid number of images');
    });

    it('accepts valid image count range for dall-e-2', function () {
        foreach (range(1, 10) as $count) {
            $unifiedRequest = [
                'image' => [
                    'prompt' => 'Test',
                    'model' => 'dall-e-2',
                    'n' => $count,
                ],
            ];

            $result = $this->adapter->transformRequest($unifiedRequest);

            expect($result['n'])->toBe($count);
        }
    });

    it('throws exception for invalid quality', function () {
        $unifiedRequest = [
            'image' => [
                'prompt' => 'Test',
                'quality' => 'ultra',
            ],
        ];

        expect(fn () => $this->adapter->transformRequest($unifiedRequest))
            ->toThrow(ImageGenerationException::class, 'Invalid quality');
    });

    it('accepts valid quality values', function () {
        foreach (['standard', 'hd'] as $quality) {
            $unifiedRequest = [
                'image' => [
                    'prompt' => 'Test',
                    'quality' => $quality,
                ],
            ];

            $result = $this->adapter->transformRequest($unifiedRequest);

            expect($result['quality'])->toBe($quality);
        }
    });

    it('throws exception for invalid style', function () {
        $unifiedRequest = [
            'image' => [
                'prompt' => 'Test',
                'style' => 'abstract',
            ],
        ];

        expect(fn () => $this->adapter->transformRequest($unifiedRequest))
            ->toThrow(ImageGenerationException::class, 'Invalid style');
    });

    it('accepts valid style values', function () {
        foreach (['vivid', 'natural'] as $style) {
            $unifiedRequest = [
                'image' => [
                    'prompt' => 'Test',
                    'style' => $style,
                ],
            ];

            $result = $this->adapter->transformRequest($unifiedRequest);

            expect($result['style'])->toBe($style);
        }
    });

    it('converts non-string prompt to string', function () {
        $unifiedRequest = [
            'image' => [
                'prompt' => 123,
            ],
        ];

        $result = $this->adapter->transformRequest($unifiedRequest);

        expect($result['prompt'])->toBe('123');
    });
});

describe('transformResponse', function () {
    it('transforms OpenAI API response with all fields', function () {
        $apiResponse = [
            'created' => 1234567890,
            'data' => [
                [
                    'url' => 'https://example.com/image1.png',
                    'revised_prompt' => 'Revised prompt text',
                ],
                [
                    'url' => 'https://example.com/image2.png',
                    'revised_prompt' => 'Another revised prompt',
                ],
            ],
        ];

        $result = $this->adapter->transformResponse($apiResponse);

        expect($result)->toBeInstanceOf(ResponseDto::class);
        expect($result->status)->toBe('completed');
        expect($result->type)->toBe('image_generation');
        expect($result->text)->toBeNull();
        expect($result->audioContent)->toBeNull();
        expect($result->conversationId)->toBeNull();
        expect($result->images)->toHaveCount(2);
        expect($result->images[0]['url'])->toBe('https://example.com/image1.png');
        expect($result->images[0]['revised_prompt'])->toBe('Revised prompt text');
        expect($result->metadata['created'])->toBe(1234567890);
        expect($result->metadata['count'])->toBe(2);
        expect($result->raw)->toBe($apiResponse);
    });

    it('transforms response with b64_json format', function () {
        $apiResponse = [
            'created' => 1234567890,
            'data' => [
                [
                    'b64_json' => 'base64encodedimagedata==',
                ],
            ],
        ];

        $result = $this->adapter->transformResponse($apiResponse);

        expect($result)->toBeInstanceOf(ResponseDto::class);
        expect($result->images)->toHaveCount(1);
        expect($result->images[0]['b64_json'])->toBe('base64encodedimagedata==');
        expect($result->metadata['count'])->toBe(1);
    });

    it('transforms response with minimal fields', function () {
        $apiResponse = [
            'data' => [
                ['url' => 'https://example.com/image.png'],
            ],
        ];

        $result = $this->adapter->transformResponse($apiResponse);

        expect($result)->toBeInstanceOf(ResponseDto::class);
        expect($result->status)->toBe('completed');
        expect($result->type)->toBe('image_generation');
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
        $apiResponse = ['data' => [['url' => 'https://example.com/image.png']]];

        $result1 = $this->adapter->transformResponse($apiResponse);
        $result2 = $this->adapter->transformResponse($apiResponse);

        expect($result1->id)->not->toBe($result2->id);
        expect($result1->id)->toContain('image_generation_');
        expect($result2->id)->toContain('image_generation_');
    });

    it('returns ResponseDto with correct helper method results', function () {
        $apiResponse = [
            'data' => [['url' => 'https://example.com/image.png']],
        ];

        $result = $this->adapter->transformResponse($apiResponse);

        expect($result->isImage())->toBeTrue();
        expect($result->isText())->toBeFalse();
        expect($result->isAudio())->toBeFalse();
    });

    it('preserves all image data fields', function () {
        $apiResponse = [
            'data' => [
                [
                    'url' => 'https://example.com/image.png',
                    'revised_prompt' => 'Detailed prompt',
                    'b64_json' => 'base64data',
                ],
            ],
        ];

        $result = $this->adapter->transformResponse($apiResponse);

        expect($result->images[0])->toHaveKeys(['url', 'revised_prompt', 'b64_json']);
        expect($result->images[0]['url'])->toBe('https://example.com/image.png');
        expect($result->images[0]['revised_prompt'])->toBe('Detailed prompt');
        expect($result->images[0]['b64_json'])->toBe('base64data');
    });

    it('handles multiple images correctly', function () {
        $apiResponse = [
            'data' => [
                ['url' => 'https://example.com/image1.png'],
                ['url' => 'https://example.com/image2.png'],
                ['url' => 'https://example.com/image3.png'],
                ['url' => 'https://example.com/image4.png'],
            ],
        ];

        $result = $this->adapter->transformResponse($apiResponse);

        expect($result->images)->toHaveCount(4);
        expect($result->metadata['count'])->toBe(4);
        expect($result->images[0]['url'])->toBe('https://example.com/image1.png');
        expect($result->images[3]['url'])->toBe('https://example.com/image4.png');
    });
});
