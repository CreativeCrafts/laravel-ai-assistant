<?php

declare(strict_types=1);

/**
 * Example 05: Audio Transcription & Translation
 *
 * This example demonstrates audio transcription and translation using the unified Response API.
 * You'll learn:
 * - Transcribing audio files to text
 * - Translating audio to English
 * - Using different audio models (Whisper)
 * - Configuring language, prompt, and response format
 * - Handling transcription and translation errors
 *
 * Time: ~3 minutes
 */

require __DIR__ . '/../vendor/autoload.php';

use CreativeCrafts\LaravelAiAssistant\Facades\Ai;

echo "=== Laravel AI Assistant: Audio Transcription & Translation ===\n\n";

// Check if sample audio file exists
$audioFile = __DIR__ . '/fixtures/test-audio.mp3';
if (!file_exists($audioFile)) {
    echo "❌ Error: Sample audio file not found at {$audioFile}\n";
    echo "Please ensure the fixtures directory contains test-audio.mp3\n";
    exit(1);
}

try {
    // Example 1: Basic Audio Transcription
    echo "1. Basic Audio Transcription\n";
    echo str_repeat('-', 50) . "\n";

    $response = Ai::responses()
        ->input()
        ->audio([
            'file' => $audioFile,
            'action' => 'transcribe',
        ])
        ->send();

    echo "Audio File: " . basename($audioFile) . "\n";
    echo "Transcription: " . $response->text . "\n";
    echo "Type: " . $response->type . "\n\n";

    // Example 2: Transcription with Language Hint
    echo "2. Transcription with Language Hint\n";
    echo str_repeat('-', 50) . "\n";

    $response = Ai::responses()
        ->input()
        ->audio([
            'file' => $audioFile,
            'action' => 'transcribe',
            'language' => 'en', // ISO-639-1 language code
            'model' => 'whisper-1',
        ])
        ->send();

    echo "Audio File: " . basename($audioFile) . "\n";
    echo "Language: en (English)\n";
    echo "Transcription: " . $response->text . "\n\n";

    // Example 3: Transcription with Context Prompt
    echo "3. Transcription with Context Prompt\n";
    echo str_repeat('-', 50) . "\n";

    $response = Ai::responses()
        ->input()
        ->audio([
            'file' => $audioFile,
            'action' => 'transcribe',
            'prompt' => 'This is a technical discussion about Laravel and PHP.',
            'temperature' => 0.0, // Lower temperature for more deterministic output
        ])
        ->send();

    echo "Audio File: " . basename($audioFile) . "\n";
    echo "Context: Technical discussion about Laravel\n";
    echo "Transcription: " . $response->text . "\n\n";

    // Example 4: Different Response Formats
    echo "4. Transcription with Verbose JSON Format\n";
    echo str_repeat('-', 50) . "\n";

    $response = Ai::responses()
        ->input()
        ->audio([
            'file' => $audioFile,
            'action' => 'transcribe',
            'response_format' => 'verbose_json',
        ])
        ->send();

    echo "Audio File: " . basename($audioFile) . "\n";
    echo "Response Format: verbose_json\n";
    echo "Transcription: " . $response->text . "\n";

    // Check if metadata is available
    if (!empty($response->metadata)) {
        echo "Metadata:\n";
        foreach ($response->metadata as $key => $value) {
            if (is_array($value)) {
                echo "  {$key}: " . json_encode($value) . "\n";
            } else {
                echo "  {$key}: {$value}\n";
            }
        }
    }
    echo "\n";

    // Example 5: Using the Unified API with Model Configuration
    echo "5. Custom Model Configuration\n";
    echo str_repeat('-', 50) . "\n";

    $response = Ai::responses()
        ->model('whisper-1')
        ->input()
        ->audio([
            'file' => $audioFile,
            'action' => 'transcribe',
            'response_format' => 'text',
        ])
        ->send();

    echo "Audio File: " . basename($audioFile) . "\n";
    echo "Model: whisper-1\n";
    echo "Transcription: " . $response->text . "\n\n";

    // Example 6: Audio Translation (Translates to English)
    echo "6. Audio Translation (Any Language to English)\n";
    echo str_repeat('-', 50) . "\n";

    $response = Ai::responses()
        ->input()
        ->audio([
            'file' => $audioFile,
            'action' => 'translate',
        ])
        ->send();

    echo "Audio File: " . basename($audioFile) . "\n";
    echo "Action: translate (converts any language to English)\n";
    echo "Translation: " . $response->text . "\n";
    echo "Type: " . $response->type . "\n\n";

    // Example 7: Translation with Custom Model
    echo "7. Translation with Model Configuration\n";
    echo str_repeat('-', 50) . "\n";

    $response = Ai::responses()
        ->model('whisper-1')
        ->input()
        ->audio([
            'file' => $audioFile,
            'action' => 'translate',
            'response_format' => 'text',
        ])
        ->send();

    echo "Audio File: " . basename($audioFile) . "\n";
    echo "Model: whisper-1\n";
    echo "Response Format: text\n";
    echo "Translation: " . $response->text . "\n\n";

    // Example 8: Translation with Verbose JSON
    echo "8. Translation with Verbose JSON Format\n";
    echo str_repeat('-', 50) . "\n";

    $response = Ai::responses()
        ->input()
        ->audio([
            'file' => $audioFile,
            'action' => 'translate',
            'response_format' => 'verbose_json',
        ])
        ->send();

    echo "Audio File: " . basename($audioFile) . "\n";
    echo "Action: translate\n";
    echo "Response Format: verbose_json\n";
    echo "Translation: " . $response->text . "\n";

    if (!empty($response->metadata)) {
        echo "Metadata:\n";
        foreach ($response->metadata as $key => $value) {
            if (is_array($value)) {
                echo "  {$key}: " . json_encode($value) . "\n";
            } else {
                echo "  {$key}: {$value}\n";
            }
        }
    }
    echo "\n";

    echo "✅ Audio transcription and translation examples completed successfully!\n\n";

    echo "💡 Tips:\n";
    echo "  - Supported formats: mp3, mp4, mpeg, mpga, m4a, wav, webm\n";
    echo "  - Maximum file size: 25 MB\n";
    echo "  - Transcription: Use 'language' parameter to improve accuracy\n";
    echo "  - Transcription: Use 'prompt' to provide context and improve accuracy\n";
    echo "  - Translation: Automatically translates any language to English\n";
    echo "  - Translation: Does not support language or prompt parameters\n";
    echo "  - Temperature range: 0.0 to 1.0 (lower = more deterministic)\n";
    echo "  - Response formats: json, text, srt, verbose_json, vtt\n";
    echo "  - Both actions use the unified Ai::responses() API\n";

} catch (Exception $e) {
    echo "\n❌ Error: " . $e->getMessage() . "\n";
    echo "\nTroubleshooting:\n";
    echo "  - Ensure OPENAI_API_KEY is configured in .env\n";
    echo "  - Verify the audio file exists and is readable\n";
    echo "  - Check that the audio file format is supported\n";
    echo "  - Ensure the audio file is under 25 MB\n";
    exit(1);
}
