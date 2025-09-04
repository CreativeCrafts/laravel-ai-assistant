<?php

declare(strict_types=1);

namespace CreativeCrafts\LaravelAiAssistant\Tests;

class OverlayPrecedenceTestCase extends TestCase
{
    public function getEnvironmentSetUp($app)
    {
        parent::getEnvironmentSetUp($app);

        // Set a runtime override that should take precedence over environment overlays
        config()->set('ai-assistant.chat_model', 'override-model');
        // Also set a nested override to ensure deep precedence
        config()->set('ai-assistant.features.mock_responses', false);
    }
}
