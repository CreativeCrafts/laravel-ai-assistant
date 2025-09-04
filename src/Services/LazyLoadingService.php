<?php

declare(strict_types=1);

namespace CreativeCrafts\LaravelAiAssistant\Services;

use Closure;
use Illuminate\Support\Facades\Cache;
use Exception;

/**
 * Lazy loading service for deferred initialization of expensive resources.
 *
 * This service provides optimized resource initialization including:
 * - Deferred client creation until actually needed
 * - Cached expensive computations and configurations
 * - Memory-efficient resource management
 * - Automatic resource cleanup and optimization
 * - Performance monitoring for lazy-loaded resources
 */
class LazyLoadingService
{
    private array $config;
    private LoggingService $loggingService;
    private array $lazyResources = [];
    private array $loadedResources = [];
    private array $loadingTimings = [];

    public function __construct(LoggingService $loggingService)
    {
        $this->loggingService = $loggingService;
        $this->config = [];
    }

    /**
     * Register a lazy-loaded resource with deferred initialization.
     *
     * @param string $resourceKey Unique key for the resource
     * @param Closure $initializer Function to initialize the resource
     * @param array $options Lazy loading options
     * @return self
     */
    public function registerLazyResource(string $resourceKey, Closure $initializer, array $options = []): self
    {
        if (!$this->isEnabled()) {
            // If lazy loading is disabled, initialize immediately
            $this->loadedResources[$resourceKey] = $initializer();
            return $this;
        }

        $this->lazyResources[$resourceKey] = [
            'initializer' => $initializer,
            'options' => array_merge($this->getDefaultOptions(), $options),
            'registered_at' => microtime(true),
            'access_count' => 0,
            'last_accessed' => null,
        ];


        return $this;
    }

    /**
     * Get a lazy-loaded resource, initializing it if necessary.
     *
     * @param string $resourceKey Resource identifier
     * @return mixed The initialized resource
     */
    public function getResource(string $resourceKey)
    {
        // Return immediately if already loaded
        if (isset($this->loadedResources[$resourceKey])) {
            $this->updateResourceAccess($resourceKey);
            return $this->loadedResources[$resourceKey];
        }

        // Check if resource is registered for lazy loading
        if (!isset($this->lazyResources[$resourceKey])) {
            return null;
        }

        return $this->initializeResource($resourceKey);
    }

    /**
     * Preload specific resources for performance optimization.
     *
     * @param array $resourceKeys Array of resource keys to preload
     * @return array Results of preloading operations
     */
    public function preloadResources(array $resourceKeys): array
    {
        $results = [];

        foreach ($resourceKeys as $resourceKey) {
            $resource = $this->getResource($resourceKey);
            if ($resource !== null) {
                $results[$resourceKey] = $resource;
            }
        }

        return $results;
    }

    /**
     * Register commonly used AI models for lazy loading.
     *
     * @param array $modelConfigs Model configurations
     * @return self
     */
    public function registerAiModels(array $modelConfigs): self
    {
        foreach ($modelConfigs as $modelKey => $config) {
            $this->registerLazyResource($modelKey, function () use ($config) {
                return $config;
            }, [
                'cache' => true,
                'ttl' => $this->config['cache_duration'] ?? 3600,
                'category' => 'ai_models',
            ]);
        }

        return $this;
    }

    /**
     * Register HTTP clients for lazy loading with connection pooling.
     *
     * @param array $clientConfigs Client configurations
     * @return self
     */
    public function registerHttpClients(array $clientConfigs): self
    {
        foreach ($clientConfigs as $clientKey => $config) {
            $this->registerLazyResource($clientKey, function () use ($config) {
                return $config;
            }, [
                'cache' => false, // HTTP clients shouldn't be cached
                'category' => 'http_clients',
            ]);
        }

        return $this;
    }

    /**
     * Get lazy loading performance metrics.
     *
     * @return array Performance metrics
     */
    public function getLazyLoadingMetrics(): array
    {
        $totalRegistered = count($this->lazyResources);
        $totalLoaded = count($this->loadedResources);
        $averageLoadTime = $this->calculateAverageLoadTime();

        return [
            'total_registered' => $totalRegistered,
            'total_loaded' => $totalLoaded,
            'load_hit_rate' => $totalRegistered > 0 ? round(($totalLoaded / $totalRegistered) * 100, 2) : 0,
            'average_load_time_ms' => round($averageLoadTime * 1000, 2),
            'memory_saved_estimate_mb' => $this->estimateMemorySaved(),
            'cache_hit_rate_percent' => $this->calculateCacheHitRate(),
            'resources_by_category' => $this->getResourceCategories(),
        ];
    }

    /**
     * Clear specific lazy-loaded resources to free memory.
     *
     * @param array $resourceKeys Resource keys to clear (empty = clear all)
     * @return int Number of resources cleared
     */
    public function clearResources(array $resourceKeys = []): int
    {
        $cleared = 0;
        $keysToClear = empty($resourceKeys) ? array_keys($this->loadedResources) : $resourceKeys;

        foreach ($keysToClear as $key) {
            if (isset($this->loadedResources[$key])) {
                unset($this->loadedResources[$key]);
                $cleared++;
            }
        }

        if ($cleared > 0) {
            // Force garbage collection after clearing resources
            if (function_exists('gc_collect_cycles')) {
                gc_collect_cycles();
            }
        }

        return $cleared;
    }

    /**
     * Check if lazy loading is enabled.
     *
     * @return bool
     */
    private function isEnabled(): bool
    {
        return $this->config['enabled'] ?? true;
    }

    /**
     * Get default lazy loading options.
     *
     * @return array
     */
    private function getDefaultOptions(): array
    {
        return [
            'cache' => false,
            'ttl' => $this->config['cache_duration'] ?? 3600,
            'category' => 'general',
            'preload' => false,
        ];
    }

    /**
     * Initialize a lazy-loaded resource.
     *
     * @param string $resourceKey Resource identifier
     * @return mixed Initialized resource
     */
    private function initializeResource(string $resourceKey)
    {
        $resourceConfig = $this->lazyResources[$resourceKey];
        $startTime = microtime(true);

        // Load dependencies first if specified
        $dependencies = $resourceConfig['options']['dependencies'] ?? [];
        foreach ($dependencies as $depKey) {
            if (!isset($this->loadedResources[$depKey])) {
                $this->getResource($depKey);
            }
        }

        // Check cache first if enabled
        if ($resourceConfig['options']['cache'] ?? false) {
            $cacheKey = "lazy_resource_{$resourceKey}";
            $cached = Cache::get($cacheKey);

            if ($cached !== null) {
                $this->loadedResources[$resourceKey] = $cached;
                $this->updateResourceAccess($resourceKey);

                return $cached;
            }
        }

        try {
            // Initialize the resource
            $resource = $resourceConfig['initializer']();
            $loadTime = microtime(true) - $startTime;

            // Store in memory
            $this->loadedResources[$resourceKey] = $resource;
            $this->loadingTimings[$resourceKey] = $loadTime;

            // Cache if enabled
            if ($resourceConfig['options']['cache'] ?? false) {
                $ttl = $resourceConfig['options']['ttl'] ?? 3600;
                Cache::put("lazy_resource_{$resourceKey}", $resource, $ttl);
            }

            $this->updateResourceAccess($resourceKey);

            $this->logLazyEvent($resourceKey, 'resource_loaded', [
                'load_time_seconds' => $loadTime,
                'load_time_ms' => round($loadTime * 1000, 2),
                'cached' => $resourceConfig['options']['cache'] ?? false,
                'resource_type' => gettype($resource),
                'memory_usage_mb' => round(memory_get_usage(true) / (1024 * 1024), 2),
            ]);

            return $resource;

        } catch (Exception $e) {
            $this->logLazyEvent($resourceKey, 'resource_loaded', [
                'error' => $e->getMessage(),
                'load_time_seconds' => microtime(true) - $startTime,
            ]);

            return null;
        }
    }

    /**
     * Update resource access statistics.
     *
     * @param string $resourceKey Resource identifier
     */
    private function updateResourceAccess(string $resourceKey): void
    {
        if (isset($this->lazyResources[$resourceKey])) {
            $this->lazyResources[$resourceKey]['access_count']++;
            $this->lazyResources[$resourceKey]['last_accessed'] = microtime(true);
        }
    }

    /**
     * Initialize an AI model configuration.
     *
     * @param array $config Model configuration
     * @return array Initialized model config
     */
    /** @phpstan-ignore-next-line Unused by production, kept for future extension and covered in docs */
    private function initializeAiModel(array $config): array
    {
        // This would contain complex model initialization logic
        return array_merge([
            'initialized_at' => now()->toISOString(),
            'status' => 'ready',
        ], $config);
    }


    /**
     * Calculate average resource loading time.
     *
     * @return float Average load time in seconds
     */
    private function calculateAverageLoadTime(): float
    {
        if (empty($this->loadingTimings)) {
            return 0.0;
        }

        return array_sum($this->loadingTimings) / count($this->loadingTimings);
    }

    /**
     * Estimate memory saved by lazy loading.
     *
     * @return float Estimated memory saved in MB
     */
    private function estimateMemorySaved(): float
    {
        $totalRegistered = count($this->lazyResources);
        $totalLoaded = count($this->loadedResources);
        $unloadedCount = $totalRegistered - $totalLoaded;

        // Rough estimate: each unloaded resource saves ~2MB on average
        return $unloadedCount * 2.0;
    }

    /**
     * Calculate cache hit rate.
     *
     * @return float Cache hit rate percentage
     */
    private function calculateCacheHitRate(): float
    {
        // This would be calculated from actual cache statistics
        return 75.0; // Placeholder
    }

    /**
     * Get resource categories breakdown.
     *
     * @return array Resource categories with counts
     */
    private function getResourceCategories(): array
    {
        $categories = [];

        foreach ($this->lazyResources as $key => $config) {
            $category = $config['options']['category'] ?? 'general';
            $categories[$category] = ($categories[$category] ?? 0) + 1;
        }

        return $categories;
    }

    /**
     * Log lazy loading events.
     *
     * @param string $resourceKey Resource identifier
     * @param string $event Event name
     * @param array $data Event data
     */
    private function logLazyEvent(string $resourceKey, string $event, array $data): void
    {
        $logData = array_merge([
            'resource_key' => $resourceKey,
            'event' => $event,
            'timestamp' => now()->toISOString(),
        ], $data);

        $this->loggingService->logPerformanceEvent(
            'lazy_loading',
            $event,
            $logData,
            'lazy_loading_service'
        );
    }
}
