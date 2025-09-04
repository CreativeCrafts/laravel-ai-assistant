<?php

declare(strict_types=1);

use CreativeCrafts\LaravelAiAssistant\Services\ResponsesSseParser;

it('marks failed and canceled events as final and flushes without trailing blank line', function () {
    $parser = new ResponsesSseParser();

    // No trailing blank line before stream end
    $lines = [
        'event: response.failed',
        'data: {"type":"response.failed","error":{"message":"boom"}}',
        // no blank terminator
    ];

    $events = iterator_to_array($parser->parse($lines));
    expect($events)->toHaveCount(1);
    expect($events[0]['type'] ?? '')->toBe('response.failed');
    expect($events[0]['isFinal'] ?? false)->toBeTrue();

    // Explicit canceled event with trailing blank
    $lines2 = [
        'event: response.canceled',
        'data: {"type":"response.canceled"}',
        '',
    ];
    $events2 = iterator_to_array($parser->parse($lines2));
    expect($events2)->toHaveCount(1);
    expect($events2[0]['type'] ?? '')->toBe('response.canceled');
    expect($events2[0]['isFinal'] ?? false)->toBeTrue();
});

it('handles fragmented multi-data payload by concatenating data lines', function () {
    $parser = new ResponsesSseParser();

    // Fragmented JSON across multiple data lines; concatenated with newline
    $lines = [
        'event: response.output_text.delta',
        'data: {"delta":',
        'data: "Hi"}',
        '',
    ];

    $events = iterator_to_array($parser->parse($lines));
    expect($events)->toHaveCount(1);
    expect($events[0]['type'] ?? '')->toBe('response.output_text.delta');
    expect($events[0]['data'])->toBeArray();
    expect($events[0]['data']['delta'] ?? null)->toBe('Hi');
});
