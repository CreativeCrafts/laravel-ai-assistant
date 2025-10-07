<?php

declare(strict_types=1);

use CreativeCrafts\LaravelAiAssistant\Services\CacheService;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Config;

it('shows cache stats via command', function (): void {
    Config::set('cache.default', 'array');
    Config::set('ai-assistant.cache.store', 'array');

    $service = app(CacheService::class);
    $service->cacheConfig('cmd.one', 1, 60);

    $exit = Artisan::call('ai-cache:stats');
    expect($exit)->toBe(0);
});

it('clears cache areas via command', function (): void {
    Config::set('cache.default', 'array');
    Config::set('ai-assistant.cache.store', 'array');
    Config::set('ai-assistant.cache.global_prefix', 'cmd_ai:');

    $service = app(CacheService::class);
    $service->cacheConfig('one', 1, 60);
    $service->cacheResponse('two', ['x' => 2], 60);

    // Delete by key
    $exit1 = Artisan::call('ai-cache:clear', ['--area' => 'config', '--key' => 'one']);
    expect($exit1)->toBe(0);
    expect($service->getConfig('one'))->toBeNull();

    // Clear responses by area
    $exit2 = Artisan::call('ai-cache:clear', ['--area' => 'response']);
    expect($exit2)->toBe(0);
    expect($service->getResponse('two'))->toBeNull();
});
