<?php

declare(strict_types=1);

/**
 * Example 06: Audio Speech Generation
 *
 * This example demonstrates text-to-speech using the unified Response API.
 * You'll learn:
 * - Converting text to speech audio
 * - Using different voice options
 * - Configuring speech speed and format
 * - Saving audio output to files
 *
 * Time: ~2 minutes
 */

require __DIR__ . '/../vendor/autoload.php';

use CreativeCrafts\LaravelAiAssistant\Facades\Ai;

echo "=== Laravel AI Assistant: Audio Speech Generation ===\n\n";

// Create output directory for generated audio files
$outputDir = __DIR__ . '/output';
if (!is_dir($outputDir)) {
    mkdir($outputDir, 0755, true);
    echo "📁 Created output directory: {$outputDir}\n\n";
}

try {
    // Example 1: Basic Text-to-Speech
    echo "1. Basic Text-to-Speech\n";
    echo str_repeat('-', 50) . "\n";

    $response = Ai::responses()
        ->input()
        ->audio([
            'text' => 'Hello! Welcome to Laravel AI Assistant. This package makes it easy to integrate AI capabilities into your Laravel applications.',
            'action' => 'speech',
            'voice' => 'alloy',
        ])
        ->send();

    $outputFile = $outputDir . '/speech-basic.mp3';
    file_put_contents($outputFile, $response->audioContent);

    echo "Text: Hello! Welcome to Laravel AI Assistant...\n";
    echo "Voice: alloy\n";
    echo "Output: {$outputFile}\n";
    echo "File size: " . number_format(strlen($response->audioContent)) . " bytes\n";
    echo "Type: " . $response->type . "\n\n";

    // Example 2: Different Voice Options
    echo "2. Exploring Different Voices\n";
    echo str_repeat('-', 50) . "\n";

    $voices = ['alloy', 'echo', 'fable', 'onyx', 'nova', 'shimmer'];
    $text = 'Laravel makes building web applications elegant and enjoyable.';

    foreach ($voices as $voice) {
        $response = Ai::responses()
            ->input()
            ->audio([
                'text' => $text,
                'action' => 'speech',
                'voice' => $voice,
            ])
            ->send();

        $outputFile = $outputDir . "/speech-{$voice}.mp3";
        file_put_contents($outputFile, $response->audioContent);

        echo "Voice: {$voice} -> {$outputFile} (" . number_format(strlen($response->audioContent)) . " bytes)\n";
    }
    echo "\n";

    // Example 3: Adjusting Speech Speed
    echo "3. Speech Speed Variations\n";
    echo str_repeat('-', 50) . "\n";

    $speeds = [0.25, 0.5, 1.0, 1.5, 2.0];
    $text = 'This demonstrates different speech speeds in Laravel AI Assistant.';

    foreach ($speeds as $speed) {
        $response = Ai::responses()
            ->input()
            ->audio([
                'text' => $text,
                'action' => 'speech',
                'voice' => 'nova',
                'speed' => $speed,
            ])
            ->send();

        $outputFile = $outputDir . "/speech-speed-{$speed}.mp3";
        file_put_contents($outputFile, $response->audioContent);

        echo "Speed: {$speed}x -> {$outputFile} (" . number_format(strlen($response->audioContent)) . " bytes)\n";
    }
    echo "\n";

    // Example 4: Different Audio Formats
    echo "4. Different Audio Formats\n";
    echo str_repeat('-', 50) . "\n";

    $formats = ['mp3', 'opus', 'aac', 'flac'];
    $text = 'Laravel AI Assistant supports multiple audio output formats.';

    foreach ($formats as $format) {
        $response = Ai::responses()
            ->input()
            ->audio([
                'text' => $text,
                'action' => 'speech',
                'voice' => 'alloy',
                'format' => $format,
            ])
            ->send();

        $outputFile = $outputDir . "/speech-format.{$format}";
        file_put_contents($outputFile, $response->audioContent);

        echo "Format: {$format} -> {$outputFile} (" . number_format(strlen($response->audioContent)) . " bytes)\n";
    }
    echo "\n";

    // Example 5: Long-Form Content with Custom Model
    echo "5. Long-Form Content Generation\n";
    echo str_repeat('-', 50) . "\n";

    $longText = <<<TEXT
Laravel AI Assistant provides a unified interface for working with OpenAI's APIs.
The package includes support for chat completions, audio transcription and translation,
text-to-speech generation, and image operations. All through a single, consistent API
that follows Laravel conventions and best practices.
TEXT;

    $response = Ai::responses()
        ->model('tts-1')
        ->input()
        ->audio([
            'text' => $longText,
            'action' => 'speech',
            'voice' => 'shimmer',
            'speed' => 1.0,
        ])
        ->send();

    $outputFile = $outputDir . '/speech-long-form.mp3';
    file_put_contents($outputFile, $response->audioContent);

    echo "Model: tts-1\n";
    echo "Voice: shimmer\n";
    echo "Text length: " . strlen($longText) . " characters\n";
    echo "Output: {$outputFile}\n";
    echo "Audio size: " . number_format(strlen($response->audioContent)) . " bytes\n\n";

    // Example 6: HD Quality Speech
    echo "6. High-Quality Speech (TTS-1-HD)\n";
    echo str_repeat('-', 50) . "\n";

    $response = Ai::responses()
        ->model('tts-1-hd')
        ->input()
        ->audio([
            'text' => 'This audio uses the high-definition text-to-speech model for enhanced quality.',
            'action' => 'speech',
            'voice' => 'onyx',
        ])
        ->send();

    $outputFile = $outputDir . '/speech-hd-quality.mp3';
    file_put_contents($outputFile, $response->audioContent);

    echo "Model: tts-1-hd (High Definition)\n";
    echo "Voice: onyx\n";
    echo "Output: {$outputFile}\n";
    echo "File size: " . number_format(strlen($response->audioContent)) . " bytes\n\n";

    echo "✅ Audio speech generation examples completed successfully!\n\n";

    echo "💡 Tips:\n";
    echo "  - Available voices: alloy, echo, fable, onyx, nova, shimmer\n";
    echo "  - Speed range: 0.25 to 4.0 (default: 1.0)\n";
    echo "  - Supported formats: mp3, opus, aac, flac, wav, pcm\n";
    echo "  - Models: tts-1 (faster), tts-1-hd (higher quality)\n";
    echo "  - All generated audio files saved to: {$outputDir}/\n";
    echo "  - Default format is mp3 if not specified\n";

} catch (Exception $e) {
    echo "\n❌ Error: " . $e->getMessage() . "\n";
    echo "\nTroubleshooting:\n";
    echo "  - Ensure OPENAI_API_KEY is configured in .env\n";
    echo "  - Verify the output directory is writable\n";
    echo "  - Check that voice name is valid\n";
    echo "  - Ensure speed is between 0.25 and 4.0\n";
    exit(1);
}
