<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Config;

it('applies testing environment overlay defaults with correct values', function () {
    // The application environment for Testbench is "testing"
    // The ServiceProvider applies overlays during packageBooted()

    // Assert values coming from config/environments/testing.php are present
    expect(Config::get('ai-assistant.temperature'))->toBe(0.1);
    expect(Config::get('ai-assistant.top_p'))->toBe(0.1);
    expect(Config::get('ai-assistant.max_completion_tokens'))->toBe(50);

    // Nested overlay keys should exist
    expect(Config::get('ai-assistant.features.mock_responses'))->toBeTrue();
    expect(Config::get('ai-assistant.logging.level'))->toBe('debug');

    // Ensure base config keys still exist alongside overlay additions
    expect(Config::get('ai-assistant.connection_pool.enabled'))->toBeBool();
});
