<?php

declare(strict_types=1);

/**
 * Example 08: Unified API
 *
 * This example demonstrates the power of the unified Response API.
 * You'll learn:
 * - Using a single interface for multiple operations
 * - Combining text, audio, and image in one request
 * - How the API automatically routes to appropriate endpoints
 * - Building complex AI workflows with the SSOT approach
 *
 * Time: ~3 minutes
 */

require __DIR__ . '/../vendor/autoload.php';

use CreativeCrafts\LaravelAiAssistant\Facades\Ai;

echo "=== Laravel AI Assistant: Unified API ===\n\n";

// Create output directory
$outputDir = __DIR__ . '/output';
if (!is_dir($outputDir)) {
    mkdir($outputDir, 0755, true);
    echo "📁 Created output directory: {$outputDir}\n\n";
}

try {
    // Example 1: Pure Text Conversation
    echo "1. Text Conversation (Routes to Response API)\n";
    echo str_repeat('-', 50) . "\n";

    $response = Ai::responses()
        ->instructions('You are a helpful Laravel expert')
        ->input()
        ->message('What are the benefits of using service providers?')
        ->send();

    echo "Input: Text message\n";
    echo "Routes to: Response API / Chat Completion\n";
    echo "Response: " . substr($response->text, 0, 150) . "...\n";
    echo "Type: " . $response->type . "\n\n";

    // Example 2: Audio Transcription (Routes to Audio API)
    $audioFile = __DIR__ . '/fixtures/test-audio.mp3';
    if (file_exists($audioFile)) {
        echo "2. Audio Transcription (Routes to Audio API)\n";
        echo str_repeat('-', 50) . "\n";

        $response = Ai::responses()
            ->input()
            ->audio([
                'file' => $audioFile,
                'action' => 'transcribe',
            ])
            ->send();

        echo "Input: Audio file for transcription\n";
        echo "Routes to: Audio Transcription endpoint\n";
        echo "Transcription: " . $response->text . "\n";
        echo "Type: " . $response->type . "\n\n";
    }

    // Example 3: Text-to-Speech (Routes to Audio Speech API)
    echo "3. Text-to-Speech (Routes to Audio Speech API)\n";
    echo str_repeat('-', 50) . "\n";

    $response = Ai::responses()
        ->input()
        ->audio([
            'text' => 'The unified API makes it easy to work with different AI capabilities through a single interface.',
            'action' => 'speech',
            'voice' => 'nova',
        ])
        ->send();

    $outputFile = $outputDir . '/unified-speech.mp3';
    file_put_contents($outputFile, $response->audioContent);

    echo "Input: Text for speech generation\n";
    echo "Routes to: Audio Speech endpoint\n";
    echo "Output: {$outputFile}\n";
    echo "Type: " . $response->type . "\n\n";

    // Example 4: Image Generation (Routes to Image API)
    echo "4. Image Generation (Routes to Image API)\n";
    echo str_repeat('-', 50) . "\n";

    $response = Ai::responses()
        ->input()
        ->image([
            'prompt' => 'A modern Laravel application dashboard with clean UI design',
            'size' => '1024x1024',
        ])
        ->send();

    echo "Input: Image generation prompt\n";
    echo "Routes to: Image Generation endpoint\n";
    echo "Type: " . $response->type . "\n";

    if (!empty($response->imageUrls)) {
        $imageData = file_get_contents($response->imageUrls[0]);
        $outputFile = $outputDir . '/unified-image.png';
        file_put_contents($outputFile, $imageData);
        echo "Output: {$outputFile}\n";
    }
    echo "\n";

    // Example 5: Complex Workflow - Multi-Step Processing
    echo "5. Complex Workflow: Multi-Step AI Processing\n";
    echo str_repeat('-', 50) . "\n";

    echo "Step 1: Generate creative content with AI\n";
    $textResponse = Ai::responses()
        ->instructions('You are a creative marketing expert')
        ->input()
        ->message('Create a short tagline for a Laravel AI package')
        ->send();

    $tagline = trim($textResponse->text);
    echo "  Generated tagline: {$tagline}\n\n";

    echo "Step 2: Convert tagline to speech\n";
    $speechResponse = Ai::responses()
        ->input()
        ->audio([
            'text' => $tagline,
            'action' => 'speech',
            'voice' => 'shimmer',
        ])
        ->send();

    $audioOutputFile = $outputDir . '/tagline-speech.mp3';
    file_put_contents($audioOutputFile, $speechResponse->audioContent);
    echo "  Audio saved: {$audioOutputFile}\n\n";

    echo "Step 3: Generate visual for the tagline\n";
    $imageResponse = Ai::responses()
        ->input()
        ->image([
            'prompt' => "Create a modern tech illustration representing: {$tagline}",
            'size' => '1024x1024',
            'style' => 'vivid',
        ])
        ->send();

    if (!empty($imageResponse->imageUrls)) {
        $imageData = file_get_contents($imageResponse->imageUrls[0]);
        $imageOutputFile = $outputDir . '/tagline-visual.png';
        file_put_contents($imageOutputFile, $imageData);
        echo "  Image saved: {$imageOutputFile}\n";
    }
    echo "\n";

    // Example 6: Using Builder Pattern with Method Chaining
    echo "6. Builder Pattern with Method Chaining\n";
    echo str_repeat('-', 50) . "\n";

    $response = Ai::responses()
        ->model('gpt-4o')
        ->instructions('You are a Laravel package documentation expert')
        ->input()
        ->message('Explain the benefits of a unified API in 2 sentences')
        ->send();

    echo "Using fluent builder pattern for configuration\n";
    echo "Model: gpt-4o\n";
    echo "Instructions: Custom system prompt\n";
    echo "Response: " . $response->text . "\n";
    echo "Type: " . $response->type . "\n\n";

    // Example 7: Error Handling with Different Input Types
    echo "7. Consistent Error Handling Across All Operations\n";
    echo str_repeat('-', 50) . "\n";

    echo "The unified API provides consistent error handling:\n";
    echo "  - Invalid audio files throw clear exceptions\n";
    echo "  - Invalid image prompts return helpful messages\n";
    echo "  - All errors use the same exception hierarchy\n";
    echo "  - Easy to catch and handle in your application\n\n";

    // Example 8: Demonstrating Automatic Endpoint Detection
    echo "8. Automatic Endpoint Detection\n";
    echo str_repeat('-', 50) . "\n";

    echo "The API automatically detects the right endpoint based on input:\n\n";

    $examples = [
        ['input' => 'message("text")', 'routes_to' => 'Response API'],
        ['input' => 'audio([file, action=transcribe])', 'routes_to' => 'Audio Transcription'],
        ['input' => 'audio([text, action=speech])', 'routes_to' => 'Audio Speech'],
        ['input' => 'image([prompt])', 'routes_to' => 'Image Generation'],
        ['input' => 'image([image, prompt])', 'routes_to' => 'Image Edit'],
        ['input' => 'image([image])', 'routes_to' => 'Image Variation'],
    ];

    foreach ($examples as $example) {
        echo "  {$example['input']}\n";
        echo "    → {$example['routes_to']}\n\n";
    }

    echo "✅ Unified API examples completed successfully!\n\n";

    echo "💡 Key Benefits of the Unified API:\n";
    echo "  - Single Source of Truth (SSOT): One interface for all operations\n";
    echo "  - Automatic Routing: No need to know which endpoint to call\n";
    echo "  - Consistent API: Same builder pattern across all features\n";
    echo "  - Type Safety: Full IDE autocomplete and type hints\n";
    echo "  - Error Handling: Unified exception handling\n";
    echo "  - Fluent Interface: Clean, readable code\n";
    echo "  - Laravel Conventions: Follows Laravel best practices\n\n";

    echo "📚 Next Steps:\n";
    echo "  - Check examples/05-audio-transcription.php for audio details\n";
    echo "  - Check examples/06-audio-speech.php for speech generation\n";
    echo "  - Check examples/07-image-generation.php for image operations\n";
    echo "  - Read the documentation for advanced features\n";
    echo "  - All output files saved to: {$outputDir}/\n";

} catch (Exception $e) {
    echo "\n❌ Error: " . $e->getMessage() . "\n";
    echo "\nTroubleshooting:\n";
    echo "  - Ensure OPENAI_API_KEY is configured in .env\n";
    echo "  - Verify fixture files exist (audio/image)\n";
    echo "  - Check that output directory is writable\n";
    echo "  - Review the specific example that failed\n";
    exit(1);
}
