<?php

declare(strict_types=1);

use CreativeCrafts\LaravelAiAssistant\Services\CacheService;
use Illuminate\Support\Facades\Config;

it('caches and retrieves config values and clears by key and prefix (array store)', function (): void {
    Config::set('cache.default', 'array');
    Config::set('ai-assistant.cache.store', 'array');
    Config::set('ai-assistant.cache.global_prefix', 'test_ai:');

    $service = app(CacheService::class);

    expect($service->getConfig('foo'))->toBeNull();

    $ok = $service->cacheConfig('foo', ['x' => 1], 60);
    expect($ok)->toBeTrue();

    $val = $service->getConfig('foo');
    expect($val)->toBe(['x' => 1]);

    // delete by key
    expect($service->deleteConfig('foo'))->toBeTrue();
    expect($service->getConfig('foo'))->toBeNull();

    // cache multiple and clear by prefix using indexer
    $service->cacheConfig('a', 'A', 60);
    $service->cacheConfig('b', 'B', 60);

    $cleared = $service->clearConfig();
    expect($cleared)->toBeTrue();
    expect($service->getConfig('a'))->toBeNull();
    expect($service->getConfig('b'))->toBeNull();
});

it('caches and retrieves API responses and supports rememberResponse', function (): void {
    Config::set('cache.default', 'array');
    Config::set('ai-assistant.cache.store', 'array');
    Config::set('ai-assistant.cache.global_prefix', 'test_ai:');
    Config::set('ai-assistant.cache.stampede.enabled', false); // disable locks for array store

    $service = app(CacheService::class);

    expect($service->getResponse('users:list'))->toBeNull();

    $payload = ['items' => [1, 2, 3]];
    expect($service->cacheResponse('users:list', $payload, 120))->toBeTrue();

    $fetched = $service->getResponse('users:list');
    expect($fetched)->toBe($payload);

    // remember should return cached value and not call resolver
    $called = 0;
    $remembered = $service->rememberResponse('users:list', function () use (&$called, $payload) {
        $called++;
        return $payload;
    });
    expect($remembered)->toBe($payload);
    expect($called)->toBe(0);

    // deleteResponse by key and ensure gone
    expect($service->deleteResponse('users:list'))->toBeTrue();
    expect($service->getResponse('users:list'))->toBeNull();
});

it('caches and retrieves completions and supports rememberCompletion', function (): void {
    Config::set('cache.default', 'array');
    Config::set('ai-assistant.cache.store', 'array');
    Config::set('ai-assistant.cache.global_prefix', 'test_ai:');
    Config::set('ai-assistant.cache.stampede.enabled', false); // disable locks for array store

    $service = app(CacheService::class);

    $prompt = 'Say hi';
    $model = 'gpt-5';
    $params = ['tone' => 'friendly'];

    expect($service->getCompletion($prompt, $model, $params))->toBeNull();

    $ok = $service->cacheCompletion($prompt, $model, $params, 'Hello there!', 60);
    expect($ok)->toBeTrue();

    $text = $service->getCompletion($prompt, $model, $params);
    expect($text)->toBe('Hello there!');

    // rememberCompletion should hit cache
    $called = 0;
    $out = $service->rememberCompletion($prompt, $model, $params, function () use (&$called) {
        $called++;
        return 'New';
    });
    expect($out)->toBe('Hello there!');
    expect($called)->toBe(0);

    // clear all completions by prefix
    expect($service->clearCompletions())->toBeTrue();
    expect($service->getCompletion($prompt, $model, $params))->toBeNull();
});

it('validates TTL and keys properly', function (): void {
    $service = app(CacheService::class);

    // invalid TTL
    expect(fn () => $service->cacheResponse('ok', ['a' => 1], 0))
        ->toThrow(InvalidArgumentException::class);

    // invalid key chars
    expect(fn () => $service->cacheResponse('not ok ✨', ['a' => 1], 60))
        ->toThrow(InvalidArgumentException::class);
});

it('purges by prefix using indexer when tags are unsupported', function (): void {
    Config::set('cache.default', 'array');
    Config::set('ai-assistant.cache.store', 'array');
    Config::set('ai-assistant.cache.global_prefix', 'test_ai:');

    $service = app(CacheService::class);

    $service->cacheConfig('one', 1, 60);
    $service->cacheResponse('two', ['x' => 2], 60);
    $service->cacheCompletion('p', 'm', [], 'r', 60);

    // Purge only response keys (count may be 0 on taggable stores)
    $deleted = $service->purgeByPrefix('response:');
    expect($deleted)->toBeInt();

    expect($service->getConfig('one'))->toBe(1);
    expect($service->getResponse('two'))->toBeNull();
    expect($service->getCompletion('p', 'm', []))->toBe('r');
});
