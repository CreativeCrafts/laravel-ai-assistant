<?php

declare(strict_types=1);

namespace CreativeCrafts\LaravelAiAssistant\Providers;

use CreativeCrafts\LaravelAiAssistant\Http\Controllers\WebhookController;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;

class WebhookServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        // Register webhook route if enabled
        $webhooksEnabled = config('ai-assistant.webhooks.enabled', false);
        if ($webhooksEnabled) {
            $this->registerWebhookRoute();
        }
    }

    private function registerWebhookRoute(): void
    {
        $path = config('ai-assistant.webhooks.path', '/ai-assistant/webhook');
        if (!is_string($path)) {
            $path = '/ai-assistant/webhook';
        }

        // Read individual route config keys to ensure runtime overrides are respected
        $routeName = config('ai-assistant.webhooks.route.name', 'ai-assistant.webhook');
        if (!is_string($routeName)) {
            $routeName = 'ai-assistant.webhook';
        }

        // Support two keys for BC: webhooks.middleware (new) and webhooks.route.middleware (old)
        $routeMiddleware = config('ai-assistant.webhooks.middleware', config('ai-assistant.webhooks.route.middleware', []));
        if (is_string($routeMiddleware)) {
            // Support comma or pipe separated middleware strings
            $routeMiddleware = preg_split('/[|,]/', $routeMiddleware) ?: [];
            $routeMiddleware = array_values(array_filter(array_map('trim', $routeMiddleware), fn ($m) => $m !== ''));
        } elseif (!is_array($routeMiddleware)) {
            $routeMiddleware = [];
        }

        $prefix = config('ai-assistant.webhooks.route.group.prefix');
        $groupMiddleware = config('ai-assistant.webhooks.route.group.middleware', []);
        if (is_string($groupMiddleware)) {
            $groupMiddleware = preg_split('/[|,]/', $groupMiddleware) ?: [];
            $groupMiddleware = array_values(array_filter(array_map('trim', $groupMiddleware), fn ($m) => $m !== ''));
        } elseif (!is_array($groupMiddleware)) {
            $groupMiddleware = [];
        }

        $register = function () use ($path, $routeName, $routeMiddleware) {
            $route = Route::post($path, [WebhookController::class, 'handle'])->name($routeName);
            if (!empty($routeMiddleware)) {
                $route->middleware($routeMiddleware);
            }
        };

        $groupOptions = [];
        if (!empty($prefix)) {
            $groupOptions['prefix'] = $prefix;
        }
        if (!empty($groupMiddleware)) {
            $groupOptions['middleware'] = $groupMiddleware;
        }

        if (!empty($groupOptions)) {
            Route::group($groupOptions, $register);
        } else {
            $register();
        }
    }
}
