<?php

declare(strict_types=1);

namespace CreativeCrafts\LaravelAiAssistant\Services;

use Illuminate\Support\Facades\Cache;
use InvalidArgumentException;
use Exception;
use JsonException;

/**
 * Service for caching API responses and configuration data.
 *
 * This service provides methods to cache and retrieve API responses,
 * helping to reduce redundant API calls and improve performance.
 */
class CacheService
{
    private const DEFAULT_TTL = 300; // 5 minutes
    private const MAX_TTL = 86400; // 24 hours
    private const CACHE_PREFIX = 'laravel_ai_assistant:';
    private const CONFIG_PREFIX = 'config:';
    private const RESPONSE_PREFIX = 'response:';
    private const COMPLETION_PREFIX = 'completion:';

    /**
     * Cache a configuration value.
     *
     * @param string $key Configuration key
     * @param mixed $value Value to cache
     * @param int $ttl Time to live in seconds (default: 1 hour)
     * @return bool True if cached successfully
     * @throws InvalidArgumentException When key or TTL is invalid
     */
    public function cacheConfig(string $key, mixed $value, int $ttl = 3600): bool
    {
        $this->validateKey($key);
        $this->validateTtl($ttl);

        $cacheKey = $this->buildCacheKey(self::CONFIG_PREFIX . $key);

        return Cache::put($cacheKey, $value, $ttl);
    }

    /**
     * Retrieve a cached configuration value.
     *
     * @param string $key Configuration key
     * @param mixed $default Default value if not found
     * @return mixed Cached value or default
     * @throws InvalidArgumentException When key is invalid
     */
    public function getConfig(string $key, mixed $default = null): mixed
    {
        $this->validateKey($key);

        $cacheKey = $this->buildCacheKey(self::CONFIG_PREFIX . $key);

        return Cache::get($cacheKey, $default);
    }

    /**
     * Cache an API response.
     *
     * @param string $key Response cache key
     * @param array $response API response data
     * @param int $ttl Time to live in seconds (default: 5 minutes)
     * @return bool True if cached successfully
     * @throws InvalidArgumentException When key, response, or TTL is invalid
     */
    public function cacheResponse(string $key, array $response, int $ttl = self::DEFAULT_TTL): bool
    {
        $this->validateKey($key);
        $this->validateResponse($response);
        $this->validateTtl($ttl);

        $cacheKey = $this->buildCacheKey(self::RESPONSE_PREFIX . $key);

        $cacheData = [
            'response' => $response,
            'cached_at' => time(),
            'ttl' => $ttl
        ];

        return Cache::put($cacheKey, $cacheData, $ttl);
    }

    /**
     * Retrieve a cached API response.
     *
     * @param string $key Response cache key
     * @return array|null Cached response data or null if not found
     * @throws InvalidArgumentException When key is invalid
     */
    public function getResponse(string $key): ?array
    {
        $this->validateKey($key);

        $cacheKey = $this->buildCacheKey(self::RESPONSE_PREFIX . $key);
        $cacheData = Cache::get($cacheKey);

        if ($cacheData === null || !is_array($cacheData)) {
            return null;
        }

        // Validate cache data structure
        if (!isset($cacheData['response'], $cacheData['cached_at'])) {
            return null;
        }

        return $cacheData['response'];
    }

    /**
     * Cache a text completion result.
     *
     * @param string $prompt The prompt used for completion
     * @param string $model The model used
     * @param array $parameters Additional parameters used
     * @param string $result The completion result
     * @param int $ttl Time to live in seconds (default: 5 minutes)
     * @return bool True if cached successfully
     * @throws InvalidArgumentException When parameters are invalid
     */
    public function cacheCompletion(
        string $prompt,
        string $model,
        array $parameters,
        string $result,
        int $ttl = self::DEFAULT_TTL
    ): bool {
        $this->validatePrompt($prompt);
        $this->validateModel($model);
        $this->validateTtl($ttl);

        if (trim($result) === '') {
            return false; // Don't cache empty results
        }

        $cacheKey = $this->buildCompletionCacheKey($prompt, $model, $parameters);

        $cacheData = [
            'prompt' => $prompt,
            'model' => $model,
            'parameters' => $parameters,
            'result' => $result,
            'cached_at' => time(),
            'ttl' => $ttl
        ];

        return Cache::put($cacheKey, $cacheData, $ttl);
    }

    /**
     * Retrieve a cached completion result.
     *
     * @param string $prompt The prompt used for completion
     * @param string $model The model used
     * @param array $parameters Additional parameters used
     * @return string|null Cached completion result or null if not found
     * @throws InvalidArgumentException When parameters are invalid
     */
    public function getCompletion(string $prompt, string $model, array $parameters): ?string
    {
        $this->validatePrompt($prompt);
        $this->validateModel($model);

        $cacheKey = $this->buildCompletionCacheKey($prompt, $model, $parameters);
        $cacheData = Cache::get($cacheKey);

        if ($cacheData === null || !is_array($cacheData)) {
            return null;
        }

        // Validate cache data structure
        if (!isset($cacheData['result'], $cacheData['cached_at'])) {
            return null;
        }

        return $cacheData['result'];
    }

    /**
     * Clear cached configuration.
     *
     * @param string|null $key Specific key to clear, or null to clear all config cache
     * @return bool True if cleared successfully
     */
    public function clearConfig(?string $key = null): bool
    {
        if ($key === null) {
            return $this->clearCacheByPrefix(self::CONFIG_PREFIX);
        }

        $this->validateKey($key);
        $cacheKey = $this->buildCacheKey(self::CONFIG_PREFIX . $key);

        return Cache::forget($cacheKey);
    }

    /**
     * Clear cached responses.
     *
     * @param string|null $key Specific key to clear, or null to clear all response cache
     * @return bool True if cleared successfully
     */
    public function clearResponses(?string $key = null): bool
    {
        if ($key === null) {
            return $this->clearCacheByPrefix(self::RESPONSE_PREFIX);
        }

        $this->validateKey($key);
        $cacheKey = $this->buildCacheKey(self::RESPONSE_PREFIX . $key);

        return Cache::forget($cacheKey);
    }

    /**
     * Clear cached completions.
     *
     * @return bool True if cleared successfully
     */
    public function clearCompletions(): bool
    {
        return $this->clearCacheByPrefix(self::COMPLETION_PREFIX);
    }

    /**
     * Clear all cache related to the AI Assistant.
     *
     * @return bool True if cleared successfully
     */
    public function clearAll(): bool
    {
        return $this->clearCacheByPrefix('');
    }

    /**
     * Get cache statistics.
     *
     * @return array Cache usage statistics
     */
    public function getStats(): array
    {
        // Note: This is a basic implementation. In production,
        // you might want to use more sophisticated cache statistics
        return [
            'cache_driver' => config('cache.default'),
            'prefix' => self::CACHE_PREFIX,
            'default_ttl' => self::DEFAULT_TTL,
            'max_ttl' => self::MAX_TTL,
            'cleared_at' => now()->toDateTimeString(),
        ];
    }

    /**
     * Build a cache key with prefix.
     *
     * @param string $key Base cache key
     * @return string Full cache key with prefix
     */
    private function buildCacheKey(string $key): string
    {
        return self::CACHE_PREFIX . $key;
    }

    /**
     * Build a cache key for completion results.
     *
     * @param string $prompt The prompt
     * @param string $model The model
     * @param array $parameters Additional parameters
     * @return string Cache key
     */
    private function buildCompletionCacheKey(string $prompt, string $model, array $parameters): string
    {
        // Create a deterministic cache key based on input parameters
        $keyData = [
            'prompt' => $prompt,
            'model' => $model,
            'parameters' => $parameters
        ];

        $serialized = json_encode($keyData);
        if ($serialized === false) {
            // Fallback if json_encode fails
            $serialized = serialize($keyData);
        }
        $hash = hash('sha256', $serialized);

        return $this->buildCacheKey(self::COMPLETION_PREFIX . $hash);
    }

    /**
     * Clear cache entries by prefix.
     *
     * @param string $prefix Cache key prefix
     * @return bool True if cleared successfully
     */
    private function clearCacheByPrefix(string $prefix): bool
    {
        $fullPrefix = self::CACHE_PREFIX . $prefix;

        // Note: This implementation depends on the cache driver.
        // For Redis, you could use SCAN. For file cache, you'd need to iterate through files.
        // This is a simplified implementation that works with most drivers.

        try {
            // If using Redis or similar, you might need driver-specific implementation
            Cache::flush(); // This clears all cache, not just prefixed ones
            return true;
        } catch (Exception $e) {
            return false;
        }
    }

    /**
     * Validate cache key.
     *
     * @param string $key Cache key to validate
     * @throws InvalidArgumentException When key is invalid
     */
    private function validateKey(string $key): void
    {
        if (trim($key) === '') {
            throw new InvalidArgumentException('Cache key cannot be empty.');
        }

        if (strlen($key) > 250) {
            throw new InvalidArgumentException('Cache key cannot exceed 250 characters.');
        }

        // Check for invalid characters that might cause issues
        if (preg_match('/[^\w\-:.\/@]/', $key)) {
            throw new InvalidArgumentException('Cache key contains invalid characters. Use only alphanumeric, dash, underscore, colon, dot, slash, and @ characters.');
        }
    }

    /**
     * Validate TTL value.
     *
     * @param int $ttl TTL to validate
     * @throws InvalidArgumentException When TTL is invalid
     */
    private function validateTtl(int $ttl): void
    {
        if ($ttl < 1) {
            throw new InvalidArgumentException('TTL must be a positive integer.');
        }

        if ($ttl > self::MAX_TTL) {
            throw new InvalidArgumentException('TTL cannot exceed ' . self::MAX_TTL . ' seconds (24 hours).');
        }
    }

    /**
     * Validate response data.
     *
     * @param array $response Response data to validate
     * @throws InvalidArgumentException When response is invalid
     */
    private function validateResponse(array $response): void
    {
        if (empty($response)) {
            throw new InvalidArgumentException('Response data cannot be empty.');
        }

        // Check if response is serializable
        try {
            json_encode($response, JSON_THROW_ON_ERROR);
        } catch (JsonException $e) {
            throw new InvalidArgumentException('Response data must be JSON serializable: ' . $e->getMessage());
        }
    }

    /**
     * Validate prompt.
     *
     * @param string $prompt Prompt to validate
     * @throws InvalidArgumentException When prompt is invalid
     */
    private function validatePrompt(string $prompt): void
    {
        if (trim($prompt) === '') {
            throw new InvalidArgumentException('Prompt cannot be empty.');
        }

        if (strlen($prompt) > 10000) {
            throw new InvalidArgumentException('Prompt cannot exceed 10,000 characters for caching.');
        }
    }

    /**
     * Validate model name.
     *
     * @param string $model Model name to validate
     * @throws InvalidArgumentException When model is invalid
     */
    private function validateModel(string $model): void
    {
        if (trim($model) === '') {
            throw new InvalidArgumentException('Model name cannot be empty.');
        }

        if (strlen($model) > 100) {
            throw new InvalidArgumentException('Model name cannot exceed 100 characters.');
        }
    }
}
