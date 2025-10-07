<?php

declare(strict_types=1);

namespace CreativeCrafts\LaravelAiAssistant\Services;

use CreativeCrafts\LaravelAiAssistant\Support\PrefixedKeyIndexer;
use Exception;
use Illuminate\Cache\TaggableStore;
use Illuminate\Contracts\Cache\LockProvider;
use Illuminate\Contracts\Cache\Repository;
use Illuminate\Contracts\Encryption\Encrypter;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Redis;
use InvalidArgumentException;
use JsonException;
use Throwable;

/**
 * Service for caching API responses and configuration data.
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

    private ?string $runtimeStore = null;
    private ?PrefixedKeyIndexer $indexer = null;

    public function __construct(private readonly ?MetricsCollectionService $metrics = null)
    {
    }

    public function withStore(?string $store): self
    {
        $this->runtimeStore = $store;
        // Reset indexer so it binds to the new store lazily
        $this->indexer = null;
        return $this;
    }

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
        $repo = $this->repoForArea('config');
        $stored = $repo->put($cacheKey, $value, $ttl);
        if (!$this->supportsTags()) {
            $this->indexer()->add(self::CONFIG_PREFIX, $cacheKey);
        }
        return $stored;
    }

    /**
     * Retrieve a cached configuration value.
     *
     * @param string $key Configuration key
     * @param mixed $default Default value if not found
     * @return mixed Cached value or default
     * @throws InvalidArgumentException|\Psr\SimpleCache\InvalidArgumentException When a key is invalid
     */
    public function getConfig(string $key, mixed $default = null): mixed
    {
        $this->validateKey($key);

        $cacheKey = $this->buildCacheKey(self::CONFIG_PREFIX . $key);

        return $this->repoForArea('config')->get($cacheKey, $default);
    }

    /**
     * Clear cached configuration.
     *
     * @param string|null $key Specific key to clear or null to clear all config cache
     * @return bool True if cleared successfully
     */
    public function clearConfig(?string $key = null): bool
    {
        if ($key === null) {
            return $this->clearCacheByPrefix(self::CONFIG_PREFIX);
        }

        $this->validateKey($key);
        $cacheKey = $this->buildCacheKey(self::CONFIG_PREFIX . $key);

        $forgot = $this->repoForArea('config')->forget($cacheKey);
        if ($forgot) {
            $this->metric('ai.cache.delete', ['area' => 'config']);
        }
        if (!$this->supportsTags()) {
            $this->indexer()->remove(self::CONFIG_PREFIX, $cacheKey);
        }
        return $forgot;
    }

    /**
     * Purge keys by logical prefix using tags when supported, otherwise the indexer.
     *
     * @return int Number of keys deleted where determinable; 0 when unknown (tag flush)
     */
    public function purgeByPrefix(string $prefix): int
    {
        $deleted = 0;

        // Map constant prefix to area name
        $area = match ($prefix) {
            self::CONFIG_PREFIX => 'config',
            self::RESPONSE_PREFIX => 'response',
            self::COMPLETION_PREFIX => 'completion',
            default => null,
        };

        if ($this->supportsTags()) {
            if ($area === null) {
                // Purge all areas
                foreach (['config', 'response', 'completion'] as $a) {
                    $this->store()->tags($this->tagsFor($a))->flush();
                }
                return 0; // unknown count
            }

            $this->store()->tags($this->tagsFor($area))->flush();
            return 0; // unknown count
        }

        // Driver-specific purge strategies
        $fullPrefix = $this->cfgString('ai-assistant.cache.global_prefix', self::CACHE_PREFIX) . $prefix;
        if ($this->isRedisStore()) {
            $deleted = $this->redisPurge($fullPrefix, $this->cfgInt('ai-assistant.cache.prefix_clear_batch', 500));
            return $deleted;
        }
        if ($this->isDatabaseStore()) {
            return $this->dbPurgeLike($fullPrefix);
        }

        // Indexer-based purge
        if ($area === null) {
            foreach ([self::CONFIG_PREFIX, self::RESPONSE_PREFIX, self::COMPLETION_PREFIX] as $p) {
                $keys = $this->indexer()->list($p);
                foreach ($keys as $k) {
                    if ($this->store()->forget($k)) {
                        $deleted++;
                    }
                    $this->indexer()->remove($p, $k);
                }
            }
            return $deleted;
        }

        $keys = $this->indexer()->list($prefix);
        foreach ($keys as $k) {
            if ($this->store()->forget($k)) {
                $deleted++;
            }
            $this->indexer()->remove($prefix, $k);
        }
        return $deleted;
    }

    /**
     * Clear cached responses.
     *
     * @param string|null $key Specific key to clear or null to clear all response cache
     * @return bool True if cleared successfully
     */
    public function clearResponses(?string $key = null): bool
    {
        if ($key === null) {
            return $this->clearCacheByPrefix(self::RESPONSE_PREFIX);
        }

        $this->validateKey($key);
        $cacheKey = $this->buildCacheKey(self::RESPONSE_PREFIX . $key);

        $forgot = $this->repoForArea('response')->forget($cacheKey);
        if ($forgot) {
            $this->metric('ai.cache.delete', ['area' => 'response']);
        }
        if (!$this->supportsTags()) {
            $this->indexer()->remove(self::RESPONSE_PREFIX, $cacheKey);
        }
        return $forgot;
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
     * Delete a specific cached configuration value by key.
     */
    public function deleteConfig(string $key): bool
    {
        $this->validateKey($key);
        $cacheKey = $this->buildCacheKey(self::CONFIG_PREFIX . $key);
        $forgot = $this->repoForArea('config')->forget($cacheKey);
        if (!$this->supportsTags()) {
            $this->indexer()->remove(self::CONFIG_PREFIX, $cacheKey);
        }
        return $forgot;
    }

    /**
     * Delete a specific cached API response by key.
     */
    public function deleteResponse(string $key): bool
    {
        $this->validateKey($key);
        $cacheKey = $this->buildCacheKey(self::RESPONSE_PREFIX . $key);
        $forgot = $this->repoForArea('response')->forget($cacheKey);
        if (!$this->supportsTags()) {
            $this->indexer()->remove(self::RESPONSE_PREFIX, $cacheKey);
        }
        return $forgot;
    }

    /**
     * Read-through cache helper for API responses with stampede protection.
     *
     * @return array<string, mixed>
     * @throws \Psr\SimpleCache\InvalidArgumentException|JsonException
     */
    public function rememberResponse(string $key, callable $resolver, ?int $ttl = null): array
    {
        $this->validateKey($key);
        $cacheTtl = $ttl ?? $this->cfgInt('ai-assistant.cache.ttl.response', self::DEFAULT_TTL);

        $cached = $this->getResponse($key);
        if (is_array($cached)) {
            return $cached;
        }

        $lockKey = $this->buildCacheKey(self::RESPONSE_PREFIX . $key . ':lock');
        $useLock = $this->cfgBool('ai-assistant.cache.stampede.enabled', true) && $this->supportsLocks();
        $lockTtl = $this->cfgInt('ai-assistant.cache.stampede.lock_ttl', 10);
        $maxWaitMs = $this->cfgInt('ai-assistant.cache.stampede.max_wait_ms', 1000);

        if ($useLock) {
            /** @var \Illuminate\Cache\Repository $repo */
            $repo = $this->store();
            $store = $repo->getStore();
            $lock = $store instanceof LockProvider ? $store->lock($lockKey, $lockTtl) : null;
            try {
                $this->metric('ai.cache.lock.request', ['area' => 'response']);
                $lock?->block($maxWaitMs / 1000);
                $this->metric('ai.cache.lock.acquired', ['area' => 'response']);
                $cached = $this->getResponse($key);
                if (is_array($cached)) {
                    return $cached;
                }
                $result = $resolver();
                if (!is_array($result)) {
                    $result = ['data' => $result];
                }
                $this->cacheResponse($key, $result, $cacheTtl);
                return $result;
            } finally {
                if ($lock !== null) {
                    try {
                        $lock->release();
                        $this->metric('ai.cache.lock.released', ['area' => 'response']);
                    } catch (Throwable) {
                        // ignore
                    }
                }
            }
        }

        $result = $resolver();
        if (!is_array($result)) {
            $result = ['data' => $result];
        }
        $this->cacheResponse($key, $result, $cacheTtl);
        return $result;
    }

    /**
     * Retrieve a cached API response.
     *
     * @param string $key Response cache key
     * @return array|null Cached response data or null if not found
     * @throws InvalidArgumentException|\Psr\SimpleCache\InvalidArgumentException When a key is invalid
     */
    public function getResponse(string $key): ?array
    {
        $this->validateKey($key);

        $cacheKey = $this->buildCacheKey(self::RESPONSE_PREFIX . $key);
        $cacheData = $this->repoForArea('response')->get($cacheKey);

        if (!is_array($cacheData)) {
            $this->metric('ai.cache.miss', ['area' => 'response']);
            return null;
        }

        // Validate cache data structure
        if (!isset($cacheData['response'], $cacheData['cached_at'])) {
            $this->metric('ai.cache.miss', ['area' => 'response']);
            return null;
        }

        $response = $cacheData['response'];
        // Decode if encoded
        if (isset($cacheData['meta']['encoding']) && is_array($cacheData['meta']['encoding'])) {
            $meta = $cacheData['meta']['encoding'];
            if (isset($meta['compressed'], $meta['encrypted'], $meta['type']) && is_bool($meta['compressed']) && is_bool($meta['encrypted']) && is_string($meta['type'])) {
                $decoded = $this->decodePayload($response, [
                    'compressed' => $meta['compressed'],
                    'encrypted' => $meta['encrypted'],
                    'type' => $meta['type'],
                ]);
                if (is_array($decoded)) {
                    $this->metric('ai.cache.hit', ['area' => 'response']);
                    return $decoded;
                }
            }
            $this->metric('ai.cache.miss', ['area' => 'response']);
            return null;
        }

        $this->metric('ai.cache.hit', ['area' => 'response']);
        return is_array($response) ? $response : null;
    }

    /**
     * Cache an API response.
     *
     * @param string $key Response cache key
     * @param array $response API response data
     * @param int $ttl Time to live in seconds (default: 5 minutes)
     * @return bool True if cached successfully
     * @throws InvalidArgumentException When key, response, or TTL is invalid
     * @throws JsonException
     */
    public function cacheResponse(string $key, array $response, int $ttl = self::DEFAULT_TTL): bool
    {
        $this->validateKey($key);
        $this->validateResponse($response);
        $this->validateTtl($ttl);

        $cacheKey = $this->buildCacheKey(self::RESPONSE_PREFIX . $key);

        // Optionally encode the payload
        $encoding = $this->encodePayload($response);
        $cacheData = [
            'response' => $encoding['value'],
            'cached_at' => time(),
            'ttl' => $ttl,
            'meta' => [
                'encoding' => $encoding['meta'],
            ],
        ];

        $repo = $this->repoForArea('response');
        $stored = $repo->put($cacheKey, $cacheData, $ttl);
        if ($stored) {
            $this->metric('ai.cache.write', ['area' => 'response']);
        }
        if (!$this->supportsTags()) {
            $this->indexer()->add(self::RESPONSE_PREFIX, $cacheKey);
        }
        return $stored;
    }

    /**
     * Read-through cache helper for completion results with stampede protection.
     *
     * @throws JsonException
     * @throws \Psr\SimpleCache\InvalidArgumentException
     */
    public function rememberCompletion(string $prompt, string $model, array $parameters, callable $resolver, ?int $ttl = null): string
    {
        $cacheTtl = $ttl ?? $this->cfgInt('ai-assistant.cache.ttl.completion', self::DEFAULT_TTL);

        $cached = $this->getCompletion($prompt, $model, $parameters);
        if (is_string($cached)) {
            return $cached;
        }

        $lockKey = $this->buildCompletionCacheKey($prompt, $model, $parameters) . ':lock';
        $useLock = $this->cfgBool('ai-assistant.cache.stampede.enabled', true) && $this->supportsLocks();
        $lockTtl = $this->cfgInt('ai-assistant.cache.stampede.lock_ttl', 10);
        $maxWaitMs = $this->cfgInt('ai-assistant.cache.stampede.max_wait_ms', 1000);

        if ($useLock) {
            /** @var \Illuminate\Cache\Repository $repo */
            $repo = $this->store();
            $store = $repo->getStore();
            $lock = $store instanceof LockProvider ? $store->lock($lockKey, $lockTtl) : null;
            try {
                $this->metric('ai.cache.lock.request', ['area' => 'completion']);
                $lock?->block($maxWaitMs / 1000);
                $this->metric('ai.cache.lock.acquired', ['area' => 'completion']);
                $cached = $this->getCompletion($prompt, $model, $parameters);
                if (is_string($cached)) {
                    return $cached;
                }
                $result = (string)$resolver();
                $this->cacheCompletion($prompt, $model, $parameters, $result, $cacheTtl);
                return $result;
            } finally {
                if ($lock !== null) {
                    try {
                        $lock->release();
                        $this->metric('ai.cache.lock.released', ['area' => 'completion']);
                    } catch (Throwable) {
                        // ignore
                    }
                }
            }
        }

        $result = (string)$resolver();
        $this->cacheCompletion($prompt, $model, $parameters, $result, $cacheTtl);
        return $result;
    }

    /**
     * Retrieve a cached completion result.
     *
     * @param string $prompt The prompt used for completion
     * @param string $model The model used
     * @param array $parameters Additional parameters used
     * @return string|null Cached completion result or null if not found
     * @throws InvalidArgumentException When parameters are invalid
     * @throws JsonException|\Psr\SimpleCache\InvalidArgumentException
     */
    public function getCompletion(string $prompt, string $model, array $parameters): ?string
    {
        $this->validatePrompt($prompt);
        $this->validateModel($model);

        $cacheKey = $this->buildCompletionCacheKey($prompt, $model, $parameters);
        $cacheData = $this->repoForArea('completion')->get($cacheKey);

        if (!is_array($cacheData)) {
            return null;
        }

        // Validate cache data structure
        if (!isset($cacheData['result'], $cacheData['cached_at'])) {
            $this->metric('ai.cache.miss', ['area' => 'completion']);
            return null;
        }

        $result = $cacheData['result'];
        if (isset($cacheData['meta']['encoding']) && is_array($cacheData['meta']['encoding'])) {
            $meta = $cacheData['meta']['encoding'];
            if (isset($meta['compressed'], $meta['encrypted'], $meta['type']) && is_bool($meta['compressed']) && is_bool($meta['encrypted']) && is_string($meta['type'])) {
                $decoded = $this->decodePayload($result, [
                    'compressed' => $meta['compressed'],
                    'encrypted' => $meta['encrypted'],
                    'type' => $meta['type'],
                ]);
                if (is_string($decoded)) {
                    $this->metric('ai.cache.hit', ['area' => 'completion']);
                    return $decoded;
                }
            }
            $this->metric('ai.cache.miss', ['area' => 'completion']);
            return null;
        }
        $this->metric('ai.cache.hit', ['area' => 'completion']);
        return is_string($result) ? $result : null;
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
     * @throws JsonException
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

        $encoding = $this->encodePayload($result);
        $cacheData = [
            'prompt' => $prompt,
            'model' => $model,
            'parameters' => $parameters,
            'result' => $encoding['value'],
            'cached_at' => time(),
            'ttl' => $ttl,
            'meta' => [
                'encoding' => $encoding['meta'],
            ],
        ];

        $repo = $this->repoForArea('completion');
        $stored = $repo->put($cacheKey, $cacheData, $ttl);
        if ($stored) {
            $this->metric('ai.cache.write', ['area' => 'completion']);
        }
        if (!$this->supportsTags()) {
            $this->indexer()->add(self::COMPLETION_PREFIX, $cacheKey);
        }
        return $stored;
    }

    /**
     * Clear all cache related to the AI Assistant.
     *
     * @return bool True if cleared successfully
     */
    public function clearAll(): bool
    {
        try {
            $this->purgeByPrefix('');
            return true;
        } catch (Exception $e) {
            return false;
        }
    }

    /**
     * Get cache statistics.
     *
     * @return array Cache usage statistics
     */
    public function getStats(): array
    {
        $configuredStore = $this->cfgNullableString('ai-assistant.cache.store', null);
        $defaultStore = $this->cfgString('cache.default', 'file');
        $storeName = $this->runtimeStore ?? ($configuredStore ?: $defaultStore);
        $globalPrefix = $this->cfgString('ai-assistant.cache.global_prefix', self::CACHE_PREFIX);
        $hashAlgo = $this->cfgString('ai-assistant.cache.hash_algo', 'sha256');

        /** @var \Illuminate\Cache\Repository $repo */
        $repo = $this->store();
        $underlying = $repo->getStore();

        $supportsTags = $this->supportsTags();
        $supportsLocks = $this->supportsLocks();

        $ttl = [
            'default' => $this->cfgInt('ai-assistant.cache.ttl.default', self::DEFAULT_TTL),
            'config' => $this->cfgInt('ai-assistant.cache.ttl.config', 3600),
            'response' => $this->cfgInt('ai-assistant.cache.ttl.response', self::DEFAULT_TTL),
            'completion' => $this->cfgInt('ai-assistant.cache.ttl.completion', self::DEFAULT_TTL),
            'lock' => $this->cfgInt('ai-assistant.cache.ttl.lock', 10),
            'grace' => $this->cfgInt('ai-assistant.cache.ttl.grace', 30),
        ];

        $safety = [
            'prevent_flush' => $this->cfgBool('ai-assistant.cache.prevent_flush', true),
            'max_ttl' => $this->cfgInt('ai-assistant.cache.max_ttl', self::MAX_TTL),
        ];

        $features = [
            'compression' => [
                'enabled' => $this->cfgBool('ai-assistant.cache.compression.enabled', false),
                'threshold' => $this->cfgInt('ai-assistant.cache.compression.threshold', 1024),
            ],
            'encryption' => [
                'enabled' => $this->cfgBool('ai-assistant.cache.encryption.enabled', false),
            ],
            'stampede' => [
                'enabled' => $this->cfgBool('ai-assistant.cache.stampede.enabled', true),
                'lock_ttl' => $this->cfgInt('ai-assistant.cache.stampede.lock_ttl', 10),
                'retry_ms' => $this->cfgInt('ai-assistant.cache.stampede.retry_ms', 150),
                'max_wait_ms' => $this->cfgInt('ai-assistant.cache.stampede.max_wait_ms', 1000),
            ],
        ];

        // Key counts (available when tags are not used; based on indexer)
        $counts = [
            'config' => null,
            'response' => null,
            'completion' => null,
            'total' => null,
        ];

        if (!$supportsTags) {
            $configCount = count($this->indexer()->list(self::CONFIG_PREFIX));
            $responseCount = count($this->indexer()->list(self::RESPONSE_PREFIX));
            $completionCount = count($this->indexer()->list(self::COMPLETION_PREFIX));

            $counts = [
                'config' => $configCount,
                'response' => $responseCount,
                'completion' => $completionCount,
                'total' => $configCount + $responseCount + $completionCount,
            ];
        }

        return [
            'store' => [
                'name' => $storeName,
                'driver' => get_class($underlying),
                'supports_tags' => $supportsTags,
                'supports_locks' => $supportsLocks,
                'configured_tags' => [
                    'config' => $this->tagsFor('config'),
                    'response' => $this->tagsFor('response'),
                    'completion' => $this->tagsFor('completion'),
                ],
            ],
            'namespace' => [
                'prefix' => $globalPrefix,
                'hash_algo' => $hashAlgo,
            ],
            'ttl' => $ttl,
            'safety' => $safety,
            'features' => $features,
            'keys' => $counts,
            'timestamp' => now()->toDateTimeString(),
        ];
    }

    /**
     * Validate a cache key.
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

        $maxTtl = $this->cfgInt('ai-assistant.cache.max_ttl', self::MAX_TTL);
        if ($ttl > $maxTtl) {
            throw new InvalidArgumentException('TTL cannot exceed ' . $maxTtl . ' seconds.');
        }
    }

    private function cfgInt(string $key, int $default): int
    {
        $value = config($key);
        if (is_int($value)) {
            return $value;
        }
        if (is_string($value) && is_numeric($value)) {
            return (int)$value;
        }
        return $default;
    }

    /**
     * Build a cache key with a prefix.
     *
     * @param string $key Base cache key
     * @return string Full cache key with prefix
     */
    private function buildCacheKey(string $key): string
    {
        $prefix = $this->cfgString('ai-assistant.cache.global_prefix', self::CACHE_PREFIX);
        return $prefix . $key;
    }

    private function cfgString(string $key, string $default): string
    {
        $value = config($key);
        if (is_string($value) && $value !== '') {
            return $value;
        }
        return $default;
    }

    /**
     * Get a repository instance optionally scoped with tags for the given area.
     */
    private function repoForArea(string $area): Repository
    {
        /** @var \Illuminate\Cache\Repository $repo */
        $repo = $this->store();
        if ($this->supportsTags()) {
            $tags = $this->tagsFor($area);
            return $repo->tags($tags);
        }
        return $repo;
    }

    private function store(): Repository
    {
        $configured = $this->cfgNullableString('ai-assistant.cache.store', null);
        $default = $this->cfgString('cache.default', 'file');
        $name = $this->runtimeStore ?? ($configured ?: $default);
        return Cache::store($name);
    }

    private function cfgNullableString(string $key, ?string $default = null): ?string
    {
        $value = config($key);
        if (is_string($value) && $value !== '') {
            return $value;
        }
        return $default;
    }

    private function supportsTags(): bool
    {
        /** @var \Illuminate\Cache\Repository $repo */
        $repo = $this->store();
        $store = $repo->getStore();
        return $store instanceof TaggableStore && $this->cfgBool('ai-assistant.cache.tags.enabled', true);
    }

    private function cfgBool(string $key, bool $default): bool
    {
        $value = config($key);
        if (is_bool($value)) {
            return $value;
        }
        if (is_string($value)) {
            $lower = strtolower($value);
            if (in_array($lower, ['1', 'true', 'yes', 'on'], true)) {
                return true;
            }
            if (in_array($lower, ['0', 'false', 'no', 'off'], true)) {
                return false;
            }
        }
        if (is_int($value)) {
            return $value === 1;
        }
        return $default;
    }

    /**
     * Resolve a tag group for an area: config|response|completion
     *
     * @return array<int, string>
     */
    private function tagsFor(string $area): array
    {
        $groups = (array)config('ai-assistant.cache.tags.groups', []);
        $tags = $groups[$area] ?? [];
        return is_array($tags) ? array_values(array_filter($tags, fn ($t) => is_string($t) && $t !== '')) : [];
    }

    private function indexer(): PrefixedKeyIndexer
    {
        if ($this->indexer === null) {
            $maxTtl = $this->cfgInt('ai-assistant.cache.max_ttl', self::MAX_TTL);
            $this->indexer = new PrefixedKeyIndexer($this->store(), $this->cfgString('ai-assistant.cache.global_prefix', self::CACHE_PREFIX), $maxTtl);
        }
        return $this->indexer;
    }

    /**
     * Clear cache entries by prefix.
     *
     * @param string $prefix Cache key prefix
     * @return bool True if cleared successfully
     */
    private function clearCacheByPrefix(string $prefix): bool
    {
        try {
            if ($prefix === '' && $this->cfgBool('ai-assistant.cache.prevent_flush', true)) {
                return false;
            }

            $deleted = $this->purgeByPrefix($prefix);
            if ($deleted > 0) {
                $this->metric('ai.cache.purge', ['prefix' => $prefix, 'deleted' => $deleted]);
            }
            return true;
        } catch (Exception $e) {
            return false;
        }
    }

    private function isRedisStore(): bool
    {
        /** @var \Illuminate\Cache\Repository $repo */
        $repo = $this->store();
        $store = $repo->getStore();
        return str_contains(strtolower(get_class($store)), 'redis');
    }

    private function redisPurge(string $fullPrefix, int $batch): int
    {
        try {
            /** @var \Illuminate\Cache\Repository $repo */
            $repo = $this->store();
            $store = $repo->getStore();
            $ns = method_exists($store, 'getPrefix') ? (string)$store->getPrefix() : '';
            $pattern = $ns . $fullPrefix . '*';

            $deleted = 0;
            $cursor = '0';
            do {
                $res = Redis::command('scan', [$cursor, 'MATCH', $pattern, 'COUNT', $batch]);
                if (!is_array($res) || count($res) < 2) {
                    break;
                }
                $cursor = (string)($res[0] ?? '0');
                $keys = $res[1] ?? [];
                if (is_array($keys) && count($keys) > 0) {
                    // Chunk deletes to avoid DEL without args
                    $chunks = array_chunk($keys, 1000);
                    foreach ($chunks as $chunk) {
                        Redis::del($chunk);
                        $deleted += count($chunk);
                    }
                }
            } while ($cursor !== '0');

            return $deleted;
        } catch (Throwable) {
            // Fallback to indexer-based purge via caller
            return 0;
        }
    }

    private function isDatabaseStore(): bool
    {
        /** @var \Illuminate\Cache\Repository $repo */
        $repo = $this->store();
        $store = $repo->getStore();
        return str_contains(strtolower(get_class($store)), 'database');
    }

    private function dbPurgeLike(string $fullPrefix): int
    {
        try {
            // Determine the current cache store config
            $configured = $this->cfgNullableString('ai-assistant.cache.store', null);
            $default = $this->cfgString('cache.default', 'file');
            $storeName = $this->runtimeStore ?? ($configured ?: $default);
            $storeConfig = (array)config("cache.stores.$storeName", []);
            $table = is_string($storeConfig['table'] ?? null) ? $storeConfig['table'] : 'cache';
            $connection = $storeConfig['connection'] ?? null;
            $cachePrefixRaw = config('cache.prefix');
            $cachePrefix = is_string($cachePrefixRaw) ? $cachePrefixRaw : 'laravel';

            $pattern = $cachePrefix . $fullPrefix . '%';
            $builder = $connection ? DB::connection($connection)->table($table) : DB::table($table);
            return (int)$builder->where('key', 'like', $pattern)->delete();
        } catch (Throwable) {
            return 0;
        }
    }

    private function metric(string $name, array $tags = []): void
    {
        $metrics = $this->metrics();
        if ($metrics) {
            $metrics->recordCustomMetric($name, 1, $tags);
        }
    }

    private function metrics(): ?MetricsCollectionService
    {
        if ($this->metrics !== null) {
            return $this->metrics;
        }
        return app()->bound(MetricsCollectionService::class) ? app(MetricsCollectionService::class) : null;
    }

    /**
     * @param mixed $value
     * @param array{compressed:bool, encrypted:bool, type:string} $meta
     * @return array<string,mixed>|string|null
     */
    private function decodePayload(mixed $value, array $meta): array|string|null
    {
        $isJson = ($meta['type']) === 'json';
        $encrypted = (bool)$meta['encrypted'];
        $compressed = (bool)$meta['compressed'];

        $payload = $value;
        try {
            if ($encrypted) {
                /** @var Encrypter $encrypter */
                $encrypter = app(Encrypter::class);
                if (!is_string($payload)) {
                    return null;
                }
                $payload = $encrypter->decrypt($payload, false);
            }
            if ($compressed) {
                if ($encrypted) {
                    if (!is_string($payload)) {
                        return null;
                    }
                    $binary = $payload;
                } else {
                    if (!is_string($payload)) {
                        return null;
                    }
                    $binary = base64_decode($payload, true);
                    if ($binary === false) {
                        return null;
                    }
                }
                $decoded = gzdecode($binary);
                if ($decoded === false) {
                    return null;
                }
                $payload = $decoded;
            }

            if ($isJson) {
                if (is_array($payload)) {
                    return $payload;
                }
                if (!is_string($payload)) {
                    return null;
                }
                $decodedJson = json_decode($payload, true);
                return is_array($decodedJson) ? $decodedJson : null;
            }

            return is_string($payload) ? $payload : null;
        } catch (Throwable) {
            return null;
        }
    }

    private function supportsLocks(): bool
    {
        /** @var \Illuminate\Cache\Repository $repo */
        $repo = $this->store();
        if (method_exists($repo, 'lock')) {
            $underlying = $repo->getStore();
            return $underlying instanceof LockProvider;
        }
        return false;
    }

    /**
     * Validate response data.
     *
     * @param array $response Response data to validate
     * @throws InvalidArgumentException When a response is invalid
     */
    private function validateResponse(array $response): void
    {
        if (empty($response)) {
            throw new InvalidArgumentException('Response data cannot be empty.');
        }

        // Check if the response is serializable
        try {
            json_encode($response, JSON_THROW_ON_ERROR);
        } catch (JsonException $e) {
            throw new InvalidArgumentException('Response data must be JSON serializable: ' . $e->getMessage());
        }
    }

    /**
     * @param array<string, mixed>|string $data
     * @return array{value:mixed, meta:array{compressed:bool, encrypted:bool, type:string}}
     * @throws JsonException
     */
    private function encodePayload(array|string $data): array
    {
        $compress = $this->cfgBool('ai-assistant.cache.compression.enabled', false);
        $threshold = $this->cfgInt('ai-assistant.cache.compression.threshold', 1024);
        $encrypt = $this->cfgBool('ai-assistant.cache.encryption.enabled', false);

        $isArray = is_array($data);
        $raw = $isArray ? json_encode($data, JSON_THROW_ON_ERROR) : (string)$data;
        $compressed = false;
        $encrypted = false;
        $value = $raw;

        if ($compress && strlen((string)$raw) >= $threshold) {
            $value = gzencode((string)$value, 6);
            $compressed = true;
        }

        if ($encrypt) {
            try {
                /** @var Encrypter $encrypter */
                $encrypter = app(Encrypter::class);
                $value = $encrypter->encrypt($value, false);
                $encrypted = true;
            } catch (Throwable) {
                // If encryption fails, fallback to unencrypted
                $encrypted = false;
            }
        } elseif ($compressed) {
            // Ensure binary is safe to store
            $value = base64_encode((string)$value);
        }

        return [
            'value' => $this->determineEncodedValue($encrypted, $compressed, $value, $isArray, $data),
            'meta' => [
                'compressed' => $compressed,
                'encrypted' => $encrypted,
                'type' => $isArray ? 'json' : 'string',
            ],
        ];
    }

    /**
     * Determine the final value to store based on the encoding state.
     *
     * @param bool $encrypted Whether the value is encrypted
     * @param bool $compressed Whether the value is compressed
     * @param mixed $value The processed value
     * @param bool $isArray Whether the original data was an array
     * @param array<string, mixed>|string $data The original data
     * @return mixed The final value to store
     */
    private function determineEncodedValue(bool $encrypted, bool $compressed, mixed $value, bool $isArray, array|string $data): mixed
    {
        if ($encrypted || $compressed) {
            return $value;
        }
        return $data;
    }

    /**
     * Validate prompt.
     *
     * @param string $prompt Prompt to validate
     * @throws InvalidArgumentException When a prompt is invalid
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
     * @throws InvalidArgumentException When a model is invalid
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

    /**
     * Build a cache key for completion results.
     *
     * @param string $prompt The prompt
     * @param string $model The model
     * @param array $parameters Additional parameters
     * @return string Cache key
     * @throws JsonException
     */
    private function buildCompletionCacheKey(string $prompt, string $model, array $parameters): string
    {
        // Create a deterministic cache key based on input parameters
        $keyData = [
            'prompt' => $prompt,
            'model' => $model,
            'parameters' => $parameters
        ];

        $serialized = json_encode($keyData, JSON_THROW_ON_ERROR);
        $algo = $this->cfgString('ai-assistant.cache.hash_algo', 'sha256');
        $hash = hash($algo, $serialized);

        return $this->buildCacheKey(self::COMPLETION_PREFIX . $hash);
    }
}
