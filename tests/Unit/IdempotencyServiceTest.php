<?php

declare(strict_types=1);

use CreativeCrafts\LaravelAiAssistant\Services\IdempotencyService;

covers(IdempotencyService::class);

it('generates deterministic keys for same payload within same bucket and different keys for different payloads', function () {
    $svc = new IdempotencyService();

    $payloadA = [
        'model' => 'gpt-4o',
        'conversation' => 'conv_123',
        'input' => [
            ['role' => 'user', 'content' => [['type' => 'text', 'text' => 'Hello']]]
        ],
    ];
    $payloadB = [
        'model' => 'gpt-4o',
        'conversation' => 'conv_456',
        'input' => [
            ['role' => 'user', 'content' => [['type' => 'text', 'text' => 'Hello']]]
        ],
    ];

    // Use a very large bucket to ensure same bucket during the test execution
    $hugeBucket = 86400 * 365; // one year seconds

    $keyA1 = $svc->buildKey($payloadA, $hugeBucket);
    $keyA2 = $svc->buildKey($payloadA, $hugeBucket);
    $keyB1 = $svc->buildKey($payloadB, $hugeBucket);

    expect($keyA1)->toBeString()
        ->and($keyA1)->toStartWith('resp_')
        ->and($keyA1)->toEqual($keyA2)
        ->and($keyB1)->not->toEqual($keyA1);
});
