<?php

declare(strict_types=1);

/**
 * Example 09: Voice Analysis — Speaker Diarization
 *
 * This example identifies the different voices in a conversation recording using OpenAI's
 * gpt-4o-transcribe-diarize model. You'll learn:
 * - Splitting a conversation into speaker-labelled segments (A, B, ...)
 * - Naming speakers from short reference samples (known speakers)
 * - Speaking time, talk share and the dominant speaker
 * - Rendering a readable transcript or WebVTT captions with speaker voice tags
 * - Streaming speaker segments as soon as they are recognised
 * - Using the unified Ai::responses() builder for diarization
 *
 * Time: ~3 minutes
 */

require __DIR__ . '/../vendor/autoload.php';

use CreativeCrafts\LaravelAiAssistant\DataTransferObjects\DiarizedSegment;
use CreativeCrafts\LaravelAiAssistant\Facades\Ai;

echo "=== Laravel AI Assistant: Speaker Diarization ===\n\n";

$recording = __DIR__ . '/fixtures/test-audio.mp3';
if (!file_exists($recording)) {
    echo "❌ Error: Sample audio file not found at {$recording}\n";
    exit(1);
}

// Optional 2-10 second voice samples used to name speakers (see example 2)
$agentSample = __DIR__ . '/fixtures/agent-sample.wav';
$customerSample = __DIR__ . '/fixtures/customer-sample.wav';
$hasSamples = file_exists($agentSample) && file_exists($customerSample);

// Create output directory for generated captions
$outputDir = __DIR__ . '/output';
if (!is_dir($outputDir)) {
    mkdir($outputDir, 0755, true);
}

try {
    // Example 1: Who spoke when?
    echo "1. Identify the speakers in a conversation\n";
    echo str_repeat('-', 50) . "\n";

    $result = Ai::diarize($recording)
        ->language('en')
        ->send();

    echo 'Speakers: ' . implode(', ', $result->speakers()) . "\n";
    foreach ($result->segments as $segment) {
        printf("[%6.2fs - %6.2fs] %s: %s\n", $segment->start, $segment->end, $segment->speaker, $segment->text);
    }
    echo "\n";

    // Example 2: Name the speakers with 2-10 second reference samples (max 4)
    echo "2. Known speakers\n";
    echo str_repeat('-', 50) . "\n";

    if ($hasSamples) {
        $result = Ai::diarize($recording)
            ->knownSpeaker('agent', $agentSample)
            ->knownSpeaker('customer', $customerSample)
            ->send();

        echo "What the customer said: " . $result->textFor('customer') . "\n\n";
    } else {
        echo "Skipped: add fixtures/agent-sample.wav and fixtures/customer-sample.wav to name speakers.\n\n";
    }

    // Example 3: Conversation analytics
    echo "3. Speaking time and talk share\n";
    echo str_repeat('-', 50) . "\n";

    foreach ($result->speakingTime() as $speaker => $seconds) {
        printf("%s: %.1fs (%.1f%%)\n", $speaker, $seconds, $result->speakingShare()[$speaker]);
    }
    echo 'Dominant speaker: ' . ($result->dominantSpeaker() ?? 'n/a') . "\n";
    echo 'Turns: ' . count($result->turns()) . "\n\n";

    // Example 4: Transcript and captions
    echo "4. Transcript and WebVTT captions\n";
    echo str_repeat('-', 50) . "\n";

    // Relabel the automatic A/B labels when you know who is who
    $named = Ai::diarize($recording)->send()->renameSpeakers(['A' => 'Agent', 'B' => 'Customer']);
    echo $named->toTranscript(withTimestamps: true) . "\n\n";
    file_put_contents($outputDir . '/conversation.vtt', $named->toWebVtt());
    echo "Captions saved to {$outputDir}/conversation.vtt\n\n";

    // Example 5: Stream segments as they are recognised (e.g. broadcast with Laravel Reverb)
    echo "5. Streaming\n";
    echo str_repeat('-', 50) . "\n";

    $stream = Ai::diarize($recording)->stream();
    foreach ($stream as $segment) {
        /** @var DiarizedSegment $segment */
        echo "{$segment->speaker}: {$segment->text}\n";
    }
    echo 'Total speakers: ' . $stream->getReturn()->speakerCount() . "\n\n";

    // Example 6: The unified Responses builder
    echo "6. Unified API\n";
    echo str_repeat('-', 50) . "\n";

    $response = Ai::responses()
        ->input()
        ->audio([
            'file' => $recording,
            'action' => 'diarize',
            'known_speakers' => $hasSamples ? ['agent' => $agentSample] : [],
        ])
        ->send();

    echo 'Speakers: ' . implode(', ', $response->metadata['speakers'] ?? []) . "\n";
    echo $response->diarization()?->toTranscript() . "\n";
} catch (Throwable $e) {
    echo '❌ Error: ' . $e->getMessage() . "\n";
    exit(1);
}
