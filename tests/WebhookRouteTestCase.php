<?php

declare(strict_types=1);

namespace CreativeCrafts\LaravelAiAssistant\Tests;

class WebhookRouteTestCase extends TestCase
{
    public function getEnvironmentSetUp($app)
    {
        parent::getEnvironmentSetUp($app);

        // Enable and configure the webhook before the package boots so routes are registered accordingly
        config()->set('ai-assistant.webhooks.enabled', true);
        config()->set('ai-assistant.webhooks.signing_secret', 'testsecret');
        config()->set('ai-assistant.webhooks.path', '/custom/path');
        config()->set('ai-assistant.webhooks.route.name', 'custom.webhook');
        config()->set('ai-assistant.webhooks.route.group.prefix', 'hooks');
        config()->set('ai-assistant.webhooks.route.middleware', 'api');
    }
}
