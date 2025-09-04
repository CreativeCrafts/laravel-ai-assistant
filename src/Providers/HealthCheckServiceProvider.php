<?php

declare(strict_types=1);

namespace CreativeCrafts\LaravelAiAssistant\Providers;

use CreativeCrafts\LaravelAiAssistant\Http\Controllers\HealthCheckController;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;

/**
 * Service Provider for Health Check endpoints.
 *
 * Registers HTTP health check endpoints for monitoring systems,
 * load balancers, and orchestration platforms like Kubernetes.
 */
class HealthCheckServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        // Register health check routes if enabled
        $healthChecksEnabled = config('ai-assistant.health_checks.enabled', true);
        if ($healthChecksEnabled) {
            $this->registerHealthCheckRoutes();
        }
    }

    private function registerHealthCheckRoutes(): void
    {
        // Get configuration for health check routes
        $prefixValue = config('ai-assistant.health_checks.route_prefix', '/ai-assistant/health');
        $prefix = is_string($prefixValue) ? $prefixValue : '/ai-assistant/health';
        $middleware = config('ai-assistant.health_checks.middleware', []);

        // Normalize middleware configuration
        if (is_string($middleware)) {
            $middleware = preg_split('/[|,]/', $middleware) ?: [];
            $middleware = array_values(array_filter(array_map('trim', $middleware), fn ($m) => $m !== ''));
        } elseif (!is_array($middleware)) {
            $middleware = [];
        }

        // Register the health check routes
        Route::prefix($prefix)->middleware($middleware)->group(function () {
            // Basic health check - minimal response for load balancers
            Route::get('/', [HealthCheckController::class, 'basic'])
                ->name('ai-assistant.health.basic');

            // Detailed health check - comprehensive system status
            Route::get('/detailed', [HealthCheckController::class, 'detailed'])
                ->name('ai-assistant.health.detailed');

            // Kubernetes-style readiness probe
            Route::get('/ready', [HealthCheckController::class, 'ready'])
                ->name('ai-assistant.health.ready');

            // Kubernetes-style liveness probe
            Route::get('/live', [HealthCheckController::class, 'live'])
                ->name('ai-assistant.health.live');
        });
    }
}
