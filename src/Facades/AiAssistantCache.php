<?php

declare(strict_types=1);

namespace CreativeCrafts\LaravelAiAssistant\Facades;

use CreativeCrafts\LaravelAiAssistant\Services\CacheService;
use Illuminate\Support\Facades\Facade;

/**
 * @method static bool cacheConfig(string $key, mixed $value, int $ttl = 3600)
 * @method static mixed getConfig(string $key, mixed $default = null)
 * @method static bool clearConfig(?string $key = null)
 * @method static bool deleteConfig(string $key)
 * @method static array|null getResponse(string $key)
 * @method static bool cacheResponse(string $key, array $response, int $ttl = 300)
 * @method static array rememberResponse(string $key, callable $resolver, ?int $ttl = null)
 * @method static string|null getCompletion(string $prompt, string $model, array $parameters)
 * @method static bool cacheCompletion(string $prompt, string $model, array $parameters, string $result, int $ttl = 300)
 * @method static string rememberCompletion(string $prompt, string $model, array $parameters, callable $resolver, ?int $ttl = null)
 * @method static bool clearResponses(?string $key = null)
 * @method static bool clearCompletions()
 * @method static int purgeByPrefix(string $prefix)
 * @method static array getStats()
 */
final class AiAssistantCache extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return CacheService::class;
    }
}
