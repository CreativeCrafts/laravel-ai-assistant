<?php

declare(strict_types=1);

/**
 * Laravel AI Assistant - Development Environment Configuration
 *
 * This configuration is optimized for development environments with:
 * - Faster, cheaper models for quicker iteration
 * - More verbose logging and debugging
 * - Relaxed rate limiting for testing
 * - Shorter cache times for rapid development
 */

return [
    /*
    |--------------------------------------------------------------------------
    | Development-Specific Settings
    |--------------------------------------------------------------------------
    */

    /**
     * Use faster, cheaper models for development to reduce costs and latency
     */
    'chat_model' => env('OPENAI_CHAT_MODEL', 'gpt-4o-mini'),
    'edit_model' => env('OPENAI_EDIT_MODEL', 'gpt-4o-mini'),
    'audio_model' => env('OPENAI_AUDIO_MODEL', 'whisper-1'),

    /**
     * More creative/random responses for testing various outputs
     */
    'temperature' => 0.7,
    'top_p' => 0.9,

    /**
     * Lower token limits to reduce development costs
     */
    'max_completion_tokens' => 150,

    /**
     * Enable streaming for real-time development feedback
     */
    'stream' => env('AI_ASSISTANT_STREAM', true),

    /**
     * Development timeout settings - longer for debugging
     */
    'timeout' => env('AI_ASSISTANT_TIMEOUT', 60),
    'max_retry_attempts' => 2,
    'retry_delay' => 500, // milliseconds

    /**
     * Caching - disabled or very short for rapid development
     */
    'cache' => [
        'enabled' => env('AI_ASSISTANT_CACHE_ENABLED', false),
        'ttl' => 300, // 5 minutes
        'prefix' => 'ai_dev_',
    ],

    /**
     * Rate limiting - relaxed for development testing
     */
    'rate_limiting' => [
        'enabled' => env('AI_ASSISTANT_RATE_LIMIT_ENABLED', false),
        'max_requests_per_minute' => 100,
        'max_requests_per_hour' => 2000,
    ],

    /**
     * Security settings - relaxed for development
     */
    'security' => [
        'validate_file_paths' => true,
        'allowed_audio_formats' => ['mp3', 'wav', 'mp4', 'm4a', 'webm', 'ogg'],
        'max_file_size' => 50 * 1024 * 1024, // 50MB for dev testing
    ],

    /**
     * Logging - verbose for development
     */
    'logging' => [
        'enabled' => true,
        'level' => 'debug',
        'log_requests' => true,
        'log_responses' => true,
        'log_performance' => true,
    ],

    /**
     * Development-specific feature flags
     */
    'features' => [
        'debug_mode' => true,
        'mock_responses' => env('AI_ASSISTANT_MOCK', false),
        'response_validation' => true,
        'performance_tracking' => true,
    ],
];
