<?php

declare(strict_types=1);

namespace CreativeCrafts\LaravelAiAssistant\Tests\Feature;

function skipIfNoRealKey(): void
{
    $key = env('OPENAI_API_KEY') ?? config('ai-assistant.api_key');
    if (empty($key) || $key === 'test_key_123') {
        test()->markTestSkipped('Skipping AI integration test without a real OPENAI_API_KEY.');
    }
}

use CreativeCrafts\LaravelAiAssistant\DataTransferObjects\ChatResponseDto;
use CreativeCrafts\LaravelAiAssistant\Facades\Ai;
use Generator;

test('quick returns chat response dto', function () {
    skipIfNoRealKey();
    $dto = Ai::quick('Hello world');
    expect($dto)->toBeInstanceOf(ChatResponseDto::class)
        ->and($dto->text ?? '')->not->toBe('');
});

test('stream returns generator', function () {
    skipIfNoRealKey();
    $gen = Ai::stream('Hello');
    expect($gen)->toBeInstanceOf(Generator::class);
});
