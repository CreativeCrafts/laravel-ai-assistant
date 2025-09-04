<?php

declare(strict_types=1);

namespace CreativeCrafts\LaravelAiAssistant\Providers;

use CreativeCrafts\LaravelAiAssistant\Services\ErrorReportingService;
use CreativeCrafts\LaravelAiAssistant\Services\LoggingService;
use CreativeCrafts\LaravelAiAssistant\Services\MemoryMonitoringService;
use CreativeCrafts\LaravelAiAssistant\Services\MetricsCollectionService;
use Illuminate\Support\ServiceProvider;

class MonitoringServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        // Register the Logging Service as a singleton
        $this->app->singleton(LoggingService::class);

        // Register performance optimization and monitoring services as singletons
        $this->app->singleton(MemoryMonitoringService::class, function ($app) {
            return new MemoryMonitoringService(
                $app->make(LoggingService::class),
                (array)(config('ai-assistant.memory_monitoring') ?? [])
            );
        });

        $this->app->singleton(MetricsCollectionService::class, function ($app) {
            return new MetricsCollectionService(
                $app->make(LoggingService::class),
                (array)(config('ai-assistant.metrics') ?? [])
            );
        });

        $this->app->singleton(ErrorReportingService::class, function ($app) {
            return new ErrorReportingService(
                $app->make(LoggingService::class),
                (array)(config('ai-assistant.error_reporting') ?? [])
            );
        });
    }
}
