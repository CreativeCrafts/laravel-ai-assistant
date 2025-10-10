<?php

declare(strict_types=1);

it('has default audio configuration with all required keys', function () {
    $audioConfig = config('ai-assistant.audio');

    expect($audioConfig)->toBeArray()
        ->and($audioConfig)->toHaveKeys(['models', 'voices', 'file_size_limit_mb', 'supported_formats', 'timeouts']);
});

it('has correct default audio models', function () {
    $models = config('ai-assistant.audio.models');

    expect($models)->toBeArray()
        ->and($models['transcription'])->toBe('whisper-1')
        ->and($models['translation'])->toBe('whisper-1')
        ->and($models['speech'])->toBe('tts-1');
});

it('has correct default audio voices', function () {
    $voices = config('ai-assistant.audio.voices');

    expect($voices)->toBeArray()
        ->and($voices['default'])->toBe('alloy')
        ->and($voices['available'])->toBeArray()
        ->and($voices['available'])->toContain('alloy', 'echo', 'fable', 'onyx', 'nova', 'shimmer');
});

it('has correct audio file size limit', function () {
    $limit = config('ai-assistant.audio.file_size_limit_mb');

    expect($limit)->toBe(25);
});

it('has correct audio supported formats', function () {
    $formats = config('ai-assistant.audio.supported_formats');

    expect($formats)->toBeArray()
        ->and($formats)->toContain('mp3', 'mp4', 'mpeg', 'mpga', 'm4a', 'wav', 'webm');
});

it('has correct audio timeout settings', function () {
    $timeouts = config('ai-assistant.audio.timeouts');

    expect($timeouts)->toBeArray()
        ->and($timeouts['transcription'])->toBe(120)
        ->and($timeouts['translation'])->toBe(120)
        ->and($timeouts['speech'])->toBe(60);
});

it('has default image configuration with all required keys', function () {
    $imageConfig = config('ai-assistant.image');

    expect($imageConfig)->toBeArray()
        ->and($imageConfig)->toHaveKeys(['models', 'sizes', 'file_size_limit_mb', 'supported_formats', 'timeouts']);
});

it('has correct default image models', function () {
    $models = config('ai-assistant.image.models');

    expect($models)->toBeArray()
        ->and($models['generation'])->toBe('dall-e-3')
        ->and($models['edit'])->toBe('dall-e-2')
        ->and($models['variation'])->toBe('dall-e-2');
});

it('has correct default image sizes', function () {
    $sizes = config('ai-assistant.image.sizes');

    expect($sizes)->toBeArray()
        ->and($sizes['dall-e-3'])->toBe('1024x1024')
        ->and($sizes['dall-e-2'])->toBe('1024x1024');
});

it('has correct image file size limit', function () {
    $limit = config('ai-assistant.image.file_size_limit_mb');

    expect($limit)->toBe(4);
});

it('has correct image supported formats', function () {
    $formats = config('ai-assistant.image.supported_formats');

    expect($formats)->toBeArray()
        ->and($formats)->toContain('png', 'jpg', 'jpeg', 'webp');
});

it('has correct image timeout settings', function () {
    $timeouts = config('ai-assistant.image.timeouts');

    expect($timeouts)->toBeArray()
        ->and($timeouts['generation'])->toBe(120)
        ->and($timeouts['edit'])->toBe(120)
        ->and($timeouts['variation'])->toBe(120);
});

it('has default adapter configuration with all required keys', function () {
    $adapterConfig = config('ai-assistant.adapters');

    expect($adapterConfig)->toBeArray()
        ->and($adapterConfig)->toHaveKeys(['cache_enabled', 'validate_requests', 'validate_responses', 'max_file_size_mb']);
});

it('has correct adapter cache setting', function () {
    $cacheEnabled = config('ai-assistant.adapters.cache_enabled');

    expect($cacheEnabled)->toBe(true);
});

it('has correct adapter validation settings', function () {
    $validateRequests = config('ai-assistant.adapters.validate_requests');
    $validateResponses = config('ai-assistant.adapters.validate_responses');

    expect($validateRequests)->toBe(true)
        ->and($validateResponses)->toBe(true);
});

it('has correct adapter max file size', function () {
    $maxFileSize = config('ai-assistant.adapters.max_file_size_mb');

    expect($maxFileSize)->toBe(25);
});

it('allows overriding audio transcription model via config', function () {
    config(['ai-assistant.audio.models.transcription' => 'whisper-2']);

    $model = config('ai-assistant.audio.models.transcription');

    expect($model)->toBe('whisper-2');
});

it('allows overriding audio default voice via config', function () {
    config(['ai-assistant.audio.voices.default' => 'nova']);

    $voice = config('ai-assistant.audio.voices.default');

    expect($voice)->toBe('nova');
});

it('allows overriding image generation model via config', function () {
    config(['ai-assistant.image.models.generation' => 'dall-e-2']);

    $model = config('ai-assistant.image.models.generation');

    expect($model)->toBe('dall-e-2');
});

it('allows overriding adapter cache setting via config', function () {
    config(['ai-assistant.adapters.cache_enabled' => false]);

    $cacheEnabled = config('ai-assistant.adapters.cache_enabled');

    expect($cacheEnabled)->toBe(false);
});

it('allows overriding audio file size limit via config', function () {
    config(['ai-assistant.audio.file_size_limit_mb' => 20]);

    $limit = config('ai-assistant.audio.file_size_limit_mb');

    expect($limit)->toBe(20);
});

it('allows overriding image file size limit via config', function () {
    config(['ai-assistant.image.file_size_limit_mb' => 3]);

    $limit = config('ai-assistant.image.file_size_limit_mb');

    expect($limit)->toBe(3);
});

it('allows overriding audio timeout settings via config', function () {
    config(['ai-assistant.audio.timeouts.transcription' => 180]);

    $timeout = config('ai-assistant.audio.timeouts.transcription');

    expect($timeout)->toBe(180);
});

it('allows overriding image timeout settings via config', function () {
    config(['ai-assistant.image.timeouts.generation' => 150]);

    $timeout = config('ai-assistant.image.timeouts.generation');

    expect($timeout)->toBe(150);
});
