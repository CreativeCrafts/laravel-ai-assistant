<?php

declare(strict_types=1);

it('applies testing overlay defaults and preserves base config keys', function () {
    expect(config('ai-assistant.temperature'))->toBe(0.1)
        ->and(config('ai-assistant.top_p'))->toBe(0.1)
        ->and(config('ai-assistant.stream'))->toBeFalse()
        ->and(config('ai-assistant.ai_role'))->toBe('assistant')
        ->and(config('ai-assistant.responses.retry.enabled'))->toBeTrue()
        ->and(config('ai-assistant.features.mock_responses'))->toBeTrue();
});
