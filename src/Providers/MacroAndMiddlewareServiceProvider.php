<?php

declare(strict_types=1);

namespace CreativeCrafts\LaravelAiAssistant\Providers;

use CreativeCrafts\LaravelAiAssistant\Http\Controllers\HealthCheckController;
use CreativeCrafts\LaravelAiAssistant\Http\Controllers\WebhookController;
use CreativeCrafts\LaravelAiAssistant\Http\Middleware\VerifyAiWebhookSignature;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;

/**
 * Registers:
 * - Middleware alias: verify.ai.webhook
 * - Route macro: Route::aiAssistant([...]) to mount health + webhook endpoints
 *
 * Safe to include alongside any auto-registered routes; the macro is opt-in.
 */
final class MacroAndMiddlewareServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        // no-op
    }

    public function boot(): void
    {
        // Middleware alias for webhook signature verification
        $this->app['router']->aliasMiddleware('verify.ai.webhook', VerifyAiWebhookSignature::class);

        // Route macro to quickly mount helper endpoints (opt-in)
        if (!Route::hasMacro('aiAssistant')) {
            Route::macro('aiAssistant', function (array $options = []): void {
                $prefix = $options['prefix'] ?? 'ai';
                $middleware = $options['middleware'] ?? ['web'];
                $namePrefix = $options['name'] ?? 'ai.';

                Route::prefix($prefix)
                    ->as($namePrefix)
                    ->middleware($middleware)
                    ->group(function (): void {
                        Route::get('/health/ready', [HealthCheckController::class, 'ready'])->name('health.ready');
                        Route::get('/health/live', [HealthCheckController::class, 'live'])->name('health.live');
                        Route::get('/health/detailed', [HealthCheckController::class, 'detailed'])->name('health.detailed');

                        // Webhook endpoint – secured via alias
                        Route::post('/webhook', [WebhookController::class, 'handle'])
                            ->middleware('verify.ai.webhook')
                            ->name('webhook');
                    });
            });
        }
    }
}
