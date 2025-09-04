<?php

declare(strict_types=1);

use CreativeCrafts\LaravelAiAssistant\Services\ResponsesSseParser;

it('marks response.failed as final and stops accumulation', function () {
    $parser = new ResponsesSseParser();

    $lines = [
        'event: response.output_text.delta',
        'data: {"delta":"Part"}',
        '',
        'event: response.failed',
        'data: {"type":"response.failed","error":{"message":"oops"}}',
        '',
        // After failure, a new delta should start fresh accumulation
        'event: response.output_text.delta',
        'data: {"delta":"New"}',
        '',
    ];

    $events = iterator_to_array($parser->parseWithAccumulation($lines));

    $failed = array_values(array_filter($events, fn ($e) => ($e['type'] ?? '') === 'response.failed'))[0] ?? null;
    expect($failed)->not->toBeNull();
    expect($failed['isFinal'] ?? false)->toBeTrue();

    $last = end($events);
    expect($last['type'] ?? '')->toBe('response.output_text.delta');
    expect($last['data']['accumulated'] ?? '')->toBe('New');
});
