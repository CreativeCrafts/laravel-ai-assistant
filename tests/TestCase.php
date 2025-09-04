<?php

declare(strict_types=1);

namespace CreativeCrafts\LaravelAiAssistant\Tests;

use CreativeCrafts\LaravelAiAssistant\LaravelAiAssistantServiceProvider;
use Illuminate\Database\Eloquent\Factories\Factory;
use Orchestra\Testbench\TestCase as Orchestra;

class TestCase extends Orchestra
{
    protected function setUp(): void
    {
        parent::setUp();

        Factory::guessFactoryNamesUsing(
            fn (string $modelName) => 'CreativeCrafts\\LaravelAiAssistant\\Database\\Factories\\'.class_basename($modelName).'Factory'
        );
    }

    public function getEnvironmentSetUp($app)
    {
        config()->set('database.default', 'testing');
        // Ensure required API key is present for ServiceProvider OpenAI client binding
        config()->set('ai-assistant.api_key', 'test_key_123');

        // Enable webhook route registration with predictable defaults for tests
        config()->set('ai-assistant.webhooks.enabled', true);
        config()->set('ai-assistant.webhooks.signing_secret', 'testsecret');
        // Provide route customization to validate ServiceProvider registration
        config()->set('ai-assistant.webhooks.route.name', 'custom.webhook');
        config()->set('ai-assistant.webhooks.path', '/custom/path');
        config()->set('ai-assistant.webhooks.route.group.prefix', 'hooks');
        config()->set('ai-assistant.webhooks.route.middleware', 'api');

        /*
        $migration = include __DIR__.'/../database/migrations/create_laravel-ai-assistant_table.php.stub';
        $migration->up();
        */
    }

    protected function getPackageProviders($app)
    {
        return [
            LaravelAiAssistantServiceProvider::class,
        ];
    }
}
