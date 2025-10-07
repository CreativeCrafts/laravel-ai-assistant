<?php

declare(strict_types=1);

use CreativeCrafts\LaravelAiAssistant\Services\CacheService;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Str;

it('encodes and decodes response payloads with compression and encryption', function (): void {
    Config::set('cache.default', 'array');
    Config::set('ai-assistant.cache.store', 'array');
    Config::set('ai-assistant.cache.global_prefix', 'enc_ai:');

    // Force compression by using very low threshold
    Config::set('ai-assistant.cache.compression.enabled', true);
    Config::set('ai-assistant.cache.compression.threshold', 1);

    // Enable encryption; set a valid app key
    $key = base64_encode(random_bytes(32));
    Config::set('app.key', 'base64:' . $key);
    Config::set('ai-assistant.cache.encryption.enabled', true);

    $service = app(CacheService::class);

    $payload = ['items' => range(1, 10), 'text' => Str::random(128)];
    expect($service->cacheResponse('encode:test', $payload, 120))->toBeTrue();

    $decoded = $service->getResponse('encode:test');
    expect($decoded)->toBe($payload);
});

it('encodes and decodes completion payloads with compression and encryption', function (): void {
    Config::set('cache.default', 'array');
    Config::set('ai-assistant.cache.store', 'array');
    Config::set('ai-assistant.cache.global_prefix', 'enc_ai:');

    // Force compression & encryption
    Config::set('ai-assistant.cache.compression.enabled', true);
    Config::set('ai-assistant.cache.compression.threshold', 1);
    $key = base64_encode(random_bytes(32));
    Config::set('app.key', 'base64:' . $key);
    Config::set('ai-assistant.cache.encryption.enabled', true);

    $service = app(CacheService::class);

    $prompt = 'Encode me';
    $model = 'gpt-5';
    $params = ['a' => 1];
    $result = 'The quick brown fox jumps over the lazy dog.';

    expect($service->cacheCompletion($prompt, $model, $params, $result, 60))->toBeTrue();

    $text = $service->getCompletion($prompt, $model, $params);
    expect($text)->toBe($result);
});
