<?php

declare(strict_types=1);

/**
 * Laravel AI Assistant - Production Environment Configuration
 *
 * This configuration is optimized for production environments with:
 * - Cost-effective models with reliable performance
 * - Conservative resource limits and timeouts
 * - Robust error handling and monitoring
 * - Security-focused settings and minimal logging
 * - Performance optimizations for scale
 */

return [
    /*
    |--------------------------------------------------------------------------
    | OpenAI API Configuration
    |--------------------------------------------------------------------------
    */

    'api_key' => env('OPENAI_API_KEY'),
    'organization' => env('OPENAI_ORGANIZATION'),

    /*
    |--------------------------------------------------------------------------
    | Persistence Layer
    |--------------------------------------------------------------------------
    */

    'persistence' => [
        'driver' => env('AI_ASSISTANT_PERSISTENCE_DRIVER', 'eloquent'),
    ],

    'mock_responses' => false, // Never mock in production
    'repository' => env('AI_ASSISTANT_REPOSITORY', null),

    /*
    |--------------------------------------------------------------------------
    | Response Generation Parameters (Production Optimized)
    |--------------------------------------------------------------------------
    */

    'temperature' => 0.3, // More deterministic for production
    'top_p' => 0.9,
    'max_completion_tokens' => 1000, // Conservative token limit
    'stream' => false, // Disabled for reliability
    'stop' => null,
    'presence_penalty' => 0,
    'frequency_penalty' => 0.1, // Slight penalty to reduce repetition

    /*
    |--------------------------------------------------------------------------
    | Model Configuration
    |--------------------------------------------------------------------------
    */

    'model' => env('OPENAI_MODEL', env('OPENAI_CHAT_MODEL', 'gpt-4o-mini')),
    'default_model' => env('AI_RESPONSES_DEFAULT_MODEL', env('OPENAI_CHAT_MODEL', 'gpt-4o-mini')),
    'default_instructions' => env('AI_DEFAULT_INSTRUCTIONS', ''),
    'chat_model' => env('OPENAI_CHAT_MODEL', 'gpt-4o-mini'),
    'edit_model' => env('OPENAI_EDIT_MODEL', 'gpt-4o-mini'),
    'audio_model' => env('OPENAI_AUDIO_MODEL', 'whisper-1'),

    /*
    |--------------------------------------------------------------------------
    | Responses API Configuration (Production Optimized)
    |--------------------------------------------------------------------------
    */

    'responses' => [
        'timeout' => env('AI_RESPONSES_TIMEOUT', 90), // Balanced timeout
        'idempotency_enabled' => env('AI_RESPONSES_IDEMPOTENCY', true),
        'max_output_tokens' => env('AI_RESPONSES_MAX_OUTPUT_TOKENS', 1000),
        'retry' => [
            'enabled' => env('AI_RESPONSES_RETRY_ENABLED', true),
            'max_attempts' => env('AI_RESPONSES_RETRY_MAX_ATTEMPTS', 3),
            'initial_delay' => env('AI_RESPONSES_RETRY_INITIAL_DELAY', 1.0),
            'backoff_multiplier' => env('AI_RESPONSES_RETRY_BACKOFF_MULTIPLIER', 2.0),
            'max_delay' => env('AI_RESPONSES_RETRY_MAX_DELAY', 16.0),
            'jitter' => env('AI_RESPONSES_RETRY_JITTER', true),
        ],
        'idempotency_bucket' => env('AI_RESPONSES_IDEMPOTENCY_BUCKET', 300), // 5 minutes
    ],

    /*
    |--------------------------------------------------------------------------
    | Role Configuration
    |--------------------------------------------------------------------------
    */

    'ai_role' => 'assistant',
    'user_role' => 'user',

    /*
    |--------------------------------------------------------------------------
    | Performance & Monitoring (Production Optimized)
    |--------------------------------------------------------------------------
    */

    'connection_pool' => [
        'enabled' => env('AI_CONNECTION_POOL_ENABLED', true),
        'max_connections' => env('AI_MAX_CONNECTIONS', 50), // Conservative limit
        'max_connections_per_host' => env('AI_MAX_CONNECTIONS_PER_HOST', 5),
        'timeout' => env('AI_CONNECTION_TIMEOUT', 30),
        'idle_timeout' => env('AI_IDLE_TIMEOUT', 30), // Longer idle timeout
    ],

    'memory_monitoring' => [
        'enabled' => env('AI_MEMORY_MONITORING_ENABLED', true),
        'threshold_mb' => env('AI_MEMORY_THRESHOLD_MB', 128), // Aggressive monitoring
        'log_usage' => env('AI_LOG_MEMORY_USAGE', true),
        'alert_on_high_usage' => env('AI_ALERT_HIGH_MEMORY', true),
    ],

    'background_jobs' => [
        'enabled' => env('AI_BACKGROUND_JOBS_ENABLED', true),
        'queue' => env('AI_QUEUE_NAME', 'ai-assistant'),
        'connection' => env('AI_QUEUE_CONNECTION', 'redis'), // Recommended for production
        'timeout' => env('AI_JOB_TIMEOUT', 180), // Balanced timeout
        'retry_after' => env('AI_JOB_RETRY_AFTER', 120),
        'max_tries' => env('AI_JOB_MAX_TRIES', 2), // Fewer retries
    ],

    'metrics' => [
        'enabled' => env('AI_METRICS_ENABLED', true),
        'driver' => env('AI_METRICS_DRIVER', 'redis'), // Better for production
        'collection_interval' => env('AI_METRICS_INTERVAL', 30), // More frequent
        'retention_days' => env('AI_METRICS_RETENTION_DAYS', 90), // Longer retention
        'track_response_times' => env('AI_TRACK_RESPONSE_TIMES', true),
        'track_token_usage' => env('AI_TRACK_TOKEN_USAGE', true),
        'track_error_rates' => env('AI_TRACK_ERROR_RATES', true),
    ],

    /*
    |--------------------------------------------------------------------------
    | Tools Configuration (Secure)
    |--------------------------------------------------------------------------
    */

    'tools' => [
        'allowlist' => env('AI_TOOLS_ALLOWLIST', []), // Explicit allowlist required
        'schemas' => env('AI_TOOLS_SCHEMAS', []),
    ],

    'tool_calling' => [
        'max_rounds' => env('AI_TOOL_CALLING_MAX_ROUNDS', 3), // Conservative limit
        'executor' => env('AI_TOOL_CALLING_EXECUTOR', 'queue'), // Async for production
        'parallel' => (bool)env('AI_TOOL_CALLING_PARALLEL', false), // Disabled for reliability
    ],

    /*
    |--------------------------------------------------------------------------
    | Error Reporting & Health Checks (Production Ready)
    |--------------------------------------------------------------------------
    */

    'error_reporting' => [
        'enabled' => env('AI_ERROR_REPORTING_ENABLED', true),
        'driver' => env('AI_ERROR_REPORTING_DRIVER', 'log'), // log only
        'dsn' => env('AI_ERROR_REPORTING_DSN'),
        'environment' => env('AI_ERROR_REPORTING_ENV', env('APP_ENV', 'production')),
        'sample_rate' => env('AI_ERROR_SAMPLE_RATE', 0.1), // Sample errors to reduce noise
        'include_context' => env('AI_ERROR_INCLUDE_CONTEXT', true),
        'scrub_sensitive_data' => env('AI_SCRUB_SENSITIVE_DATA', true), // Always scrub
    ],

    'health_checks' => [
        'enabled' => env('AI_HEALTH_CHECKS_ENABLED', true),
        'timeout' => env('AI_HEALTH_CHECK_TIMEOUT', 5), // Shorter timeout
        'api_connectivity' => [
            'enabled' => env('AI_HEALTH_CHECK_API_ENABLED', true),
            'test_model' => env('AI_HEALTH_CHECK_API_MODEL', 'gpt-4o-mini'),
            'timeout' => env('AI_HEALTH_CHECK_API_TIMEOUT', 3),
            'max_tokens' => env('AI_HEALTH_CHECK_API_MAX_TOKENS', 1),
        ],
        'memory' => [
            'warning_threshold_percent' => env('AI_HEALTH_CHECK_MEMORY_WARNING', 70),
            'critical_threshold_percent' => env('AI_HEALTH_CHECK_MEMORY_CRITICAL', 85),
        ],
        'disk' => [
            'warning_threshold_percent' => env('AI_HEALTH_CHECK_DISK_WARNING', 15),
            'critical_threshold_percent' => env('AI_HEALTH_CHECK_DISK_CRITICAL', 5),
            'hide_paths' => env('AI_HEALTH_CHECK_HIDE_PATHS', true),
        ],
        'cache' => [
            'cleanup_test_keys' => env('AI_HEALTH_CHECK_CACHE_CLEANUP', true),
            'verify_delete' => env('AI_HEALTH_CHECK_CACHE_VERIFY_DELETE', true),
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Advanced Features (Production Optimized)
    |--------------------------------------------------------------------------
    */

    'streaming' => [
        'enabled' => env('AI_STREAMING_ENABLED', false), // Disabled for reliability
        'buffer_size' => env('AI_STREAMING_BUFFER_SIZE', 4096), // Smaller buffer
        'chunk_size' => env('AI_STREAMING_CHUNK_SIZE', 512),
        'timeout' => env('AI_STREAMING_TIMEOUT', 60),
        'sse_timeout' => env('AI_STREAMING_SSE_TIMEOUT', 60),
        'max_response_size' => env('AI_MAX_RESPONSE_SIZE_MB', 10), // Smaller limit
    ],

    'lazy_loading' => [
        'enabled' => env('AI_LAZY_LOADING_ENABLED', true),
        'cache_duration' => env('AI_LAZY_CACHE_DURATION', 7200), // 2 hours
        'preload_common_models' => env('AI_PRELOAD_MODELS', true), // Preload in production
        'defer_client_creation' => env('AI_DEFER_CLIENT_CREATION', false),
    ],

    'webhooks' => [
        'enabled' => env('AI_WEBHOOKS_ENABLED', false), // Disabled by default
        'signing_secret' => env('AI_WEBHOOKS_SIGNING_SECRET'),
        'path' => env('AI_WEBHOOKS_PATH', '/ai-assistant/webhook'),
        'signature_header' => env('AI_WEBHOOKS_SIGNATURE_HEADER', 'X-OpenAI-Signature'),
        'timestamp_header' => env('AI_WEBHOOKS_TIMESTAMP_HEADER', 'X-OpenAI-Timestamp'),
        'max_skew_seconds' => env('AI_WEBHOOKS_MAX_SKEW_SECONDS', 180), // Stricter timing
        'route' => [
            'name' => env('AI_WEBHOOKS_ROUTE_NAME', 'ai-assistant.webhook'),
            'middleware' => env('AI_WEBHOOKS_MIDDLEWARE', ['auth', 'throttle:60,1']), // Security middleware
            'group' => [
                'prefix' => env('AI_WEBHOOKS_ROUTE_PREFIX', 'api'),
                'middleware' => env('AI_WEBHOOKS_GROUP_MIDDLEWARE', ['api']),
            ],
        ],
    ],
];
