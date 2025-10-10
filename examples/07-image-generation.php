<?php

declare(strict_types=1);

/**
 * Example 07: Image Generation
 *
 * This example demonstrates image operations using the unified Response API.
 * You'll learn:
 * - Generating images from text prompts
 * - Editing existing images with prompts
 * - Creating image variations
 * - Configuring size, quality, and style
 * - Saving image outputs
 *
 * Time: ~3 minutes
 */

require __DIR__ . '/../vendor/autoload.php';

use CreativeCrafts\LaravelAiAssistant\Facades\Ai;

echo "=== Laravel AI Assistant: Image Generation ===\n\n";

// Create output directory for generated images
$outputDir = __DIR__ . '/output';
if (!is_dir($outputDir)) {
    mkdir($outputDir, 0755, true);
    echo "📁 Created output directory: {$outputDir}\n\n";
}

try {
    // Example 1: Basic Image Generation
    echo "1. Basic Image Generation\n";
    echo str_repeat('-', 50) . "\n";

    $response = Ai::responses()
        ->input()
        ->image([
            'prompt' => 'A serene mountain landscape at sunset with a crystal-clear lake reflecting the orange and pink sky',
        ])
        ->send();

    echo "Prompt: A serene mountain landscape at sunset...\n";
    echo "Type: " . $response->type . "\n";

    if (!empty($response->imageUrls)) {
        echo "Generated image URL: " . $response->imageUrls[0] . "\n";

        $imageData = file_get_contents($response->imageUrls[0]);
        $outputFile = $outputDir . '/image-basic.png';
        file_put_contents($outputFile, $imageData);
        echo "Saved to: {$outputFile}\n";
    }
    echo "\n";

    // Example 2: High-Quality Image with DALL-E 3
    echo "2. High-Quality Image Generation (DALL-E 3)\n";
    echo str_repeat('-', 50) . "\n";

    $response = Ai::responses()
        ->model('dall-e-3')
        ->input()
        ->image([
            'prompt' => 'A futuristic cityscape with flying cars and neon lights, cyberpunk style',
            'size' => '1024x1024',
            'quality' => 'hd',
            'style' => 'vivid',
        ])
        ->send();

    echo "Model: dall-e-3\n";
    echo "Prompt: A futuristic cityscape...\n";
    echo "Size: 1024x1024\n";
    echo "Quality: hd\n";
    echo "Style: vivid\n";

    if (!empty($response->imageUrls)) {
        echo "Generated image URL: " . $response->imageUrls[0] . "\n";

        $imageData = file_get_contents($response->imageUrls[0]);
        $outputFile = $outputDir . '/image-hd-vivid.png';
        file_put_contents($outputFile, $imageData);
        echo "Saved to: {$outputFile}\n";
    }
    echo "\n";

    // Example 3: Natural Style Image
    echo "3. Natural Style Image Generation\n";
    echo str_repeat('-', 50) . "\n";

    $response = Ai::responses()
        ->model('dall-e-3')
        ->input()
        ->image([
            'prompt' => 'A cozy coffee shop interior with warm lighting, wooden furniture, and plants',
            'size' => '1024x1024',
            'quality' => 'standard',
            'style' => 'natural',
        ])
        ->send();

    echo "Prompt: A cozy coffee shop interior...\n";
    echo "Style: natural (more realistic)\n";

    if (!empty($response->imageUrls)) {
        echo "Generated image URL: " . $response->imageUrls[0] . "\n";

        $imageData = file_get_contents($response->imageUrls[0]);
        $outputFile = $outputDir . '/image-natural.png';
        file_put_contents($outputFile, $imageData);
        echo "Saved to: {$outputFile}\n";
    }
    echo "\n";

    // Example 4: Different Image Sizes
    echo "4. Different Image Sizes\n";
    echo str_repeat('-', 50) . "\n";

    $sizes = ['1024x1024', '1792x1024', '1024x1792'];
    $basePrompt = 'A minimalist geometric pattern in shades of blue';

    foreach ($sizes as $size) {
        $response = Ai::responses()
            ->model('dall-e-3')
            ->input()
            ->image([
                'prompt' => $basePrompt,
                'size' => $size,
            ])
            ->send();

        echo "Size: {$size}\n";

        if (!empty($response->imageUrls)) {
            $imageData = file_get_contents($response->imageUrls[0]);
            $outputFile = $outputDir . "/image-{$size}.png";
            file_put_contents($outputFile, $imageData);
            echo "  Saved to: {$outputFile}\n";
        }
    }
    echo "\n";

    // Example 5: Multiple Images with DALL-E 2
    echo "5. Multiple Image Variations (DALL-E 2)\n";
    echo str_repeat('-', 50) . "\n";

    $response = Ai::responses()
        ->model('dall-e-2')
        ->input()
        ->image([
            'prompt' => 'A cute robot mascot for a tech company, friendly and approachable',
            'n' => 3,
            'size' => '512x512',
        ])
        ->send();

    echo "Model: dall-e-2\n";
    echo "Prompt: A cute robot mascot...\n";
    echo "Number of images: 3\n";

    if (!empty($response->imageUrls)) {
        echo "Generated " . count($response->imageUrls) . " images:\n";
        foreach ($response->imageUrls as $index => $url) {
            $imageData = file_get_contents($url);
            $outputFile = $outputDir . "/image-variation-" . ($index + 1) . ".png";
            file_put_contents($outputFile, $imageData);
            echo "  Image " . ($index + 1) . ": {$outputFile}\n";
        }
    }
    echo "\n";

    // Example 6: Image Editing (if fixture exists)
    $fixtureImage = __DIR__ . '/fixtures/test-image.png';
    if (file_exists($fixtureImage)) {
        echo "6. Image Editing\n";
        echo str_repeat('-', 50) . "\n";

        $response = Ai::responses()
            ->input()
            ->image([
                'image' => $fixtureImage,
                'prompt' => 'Add a beautiful sunset sky with vibrant colors',
                'size' => '1024x1024',
            ])
            ->send();

        echo "Original image: " . basename($fixtureImage) . "\n";
        echo "Edit prompt: Add a beautiful sunset sky...\n";

        if (!empty($response->imageUrls)) {
            $imageData = file_get_contents($response->imageUrls[0]);
            $outputFile = $outputDir . '/image-edited.png';
            file_put_contents($outputFile, $imageData);
            echo "Edited image saved to: {$outputFile}\n";
        }
        echo "\n";

        // Example 7: Image Variation
        echo "7. Image Variation\n";
        echo str_repeat('-', 50) . "\n";

        $response = Ai::responses()
            ->input()
            ->image([
                'image' => $fixtureImage,
                'n' => 2,
                'size' => '512x512',
            ])
            ->send();

        echo "Original image: " . basename($fixtureImage) . "\n";
        echo "Creating 2 variations...\n";

        if (!empty($response->imageUrls)) {
            foreach ($response->imageUrls as $index => $url) {
                $imageData = file_get_contents($url);
                $outputFile = $outputDir . "/image-variant-" . ($index + 1) . ".png";
                file_put_contents($outputFile, $imageData);
                echo "  Variation " . ($index + 1) . ": {$outputFile}\n";
            }
        }
        echo "\n";
    }

    // Example 8: Base64 Response Format
    echo "8. Base64 Response Format\n";
    echo str_repeat('-', 50) . "\n";

    $response = Ai::responses()
        ->input()
        ->image([
            'prompt' => 'A simple icon of a star, flat design',
            'size' => '256x256',
            'response_format' => 'b64_json',
        ])
        ->send();

    echo "Prompt: A simple icon of a star...\n";
    echo "Response format: b64_json\n";

    if (!empty($response->imageData)) {
        echo "Received base64 encoded image data\n";
        $imageData = base64_decode($response->imageData[0]);
        $outputFile = $outputDir . '/image-base64.png';
        file_put_contents($outputFile, $imageData);
        echo "Decoded and saved to: {$outputFile}\n";
        echo "Image size: " . number_format(strlen($imageData)) . " bytes\n";
    }
    echo "\n";

    echo "✅ Image generation examples completed successfully!\n\n";

    echo "💡 Tips:\n";
    echo "  - DALL-E 3: Higher quality, single image per request\n";
    echo "  - DALL-E 2: Multiple images, lower cost\n";
    echo "  - DALL-E 3 sizes: 1024x1024, 1792x1024, 1024x1792\n";
    echo "  - DALL-E 2 sizes: 256x256, 512x512, 1024x1024\n";
    echo "  - Quality options: standard, hd (DALL-E 3 only)\n";
    echo "  - Style options: vivid, natural (DALL-E 3 only)\n";
    echo "  - Response formats: url (default), b64_json\n";
    echo "  - All generated images saved to: {$outputDir}/\n";

} catch (Exception $e) {
    echo "\n❌ Error: " . $e->getMessage() . "\n";
    echo "\nTroubleshooting:\n";
    echo "  - Ensure OPENAI_API_KEY is configured in .env\n";
    echo "  - Verify the output directory is writable\n";
    echo "  - Check that image file exists for editing/variation\n";
    echo "  - Ensure prompt is descriptive enough\n";
    echo "  - Image files must be PNG and under 4MB for editing\n";
    exit(1);
}
