<?php

declare(strict_types=1);

namespace CreativeCrafts\LaravelAiAssistant\Tests;

use Illuminate\Support\Facades\Config;

class EnvironmentOverlayPrecedenceTest extends OverlayPrecedenceTestCase
{
    public function test_prefers_runtime_overrides_over_overlay(): void
    {
        // chat_model should come from the runtime override set in OverlayPrecedenceTestCase
        $this->assertSame('override-model', Config::get('ai-assistant.chat_model'));

        // features.mock_responses is set to false at runtime and should override testing overlay (true)
        $this->assertFalse((bool) Config::get('ai-assistant.features.mock_responses'));

        // Unrelated overlay values should still apply from the overlay
        $this->assertSame('debug', Config::get('ai-assistant.logging.level'));
    }
}
