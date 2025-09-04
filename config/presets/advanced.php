<?php

declare(strict_types=1);

/**
 * Advanced AI Assistant Configuration Preset
 *
 * This preset includes all available features and advanced configuration options.
 * Perfect for users who want access to the full functionality of the AI Assistant package.
 *
 * To use this preset, copy the values below to your config/ai-assistant.php file
 * or publish and customize as needed.
 */

return [
    /*
    |--------------------------------------------------------------------------
    | OpenAI API Configuration
    |--------------------------------------------------------------------------
    */

    'api_key' => env('OPENAI_API_KEY', 'your-api-key-here'),
    'organization' => env('OPENAI_ORGANIZATION'),

    /*
    |--------------------------------------------------------------------------
    | Persistence Layer
    |--------------------------------------------------------------------------
    */

    'persistence' => [
        'driver' => env('AI_ASSISTANT_PERSISTENCE_DRIVER', 'eloquent'),
    ],

    'mock_responses' => (bool)env('AI_ASSISTANT_MOCK', false),
    'repository' => env('AI_ASSISTANT_REPOSITORY', null),

    /*
    |--------------------------------------------------------------------------
    | Response Generation Parameters
    |--------------------------------------------------------------------------
    */

    'temperature' => 0.7,
    'top_p' => 1,
    'max_completion_tokens' => 1500,
    'stream' => true,
    'stop' => null,
    'presence_penalty' => 0,
    'frequency_penalty' => 0,

    /*
    |--------------------------------------------------------------------------
    | Model Configuration
    |--------------------------------------------------------------------------
    */

    'model' => env('OPENAI_MODEL', env('OPENAI_CHAT_MODEL', 'gpt-4o')),
    'default_model' => env('AI_RESPONSES_DEFAULT_MODEL', env('OPENAI_CHAT_MODEL', 'gpt-4o')),
    'default_instructions' => env('AI_DEFAULT_INSTRUCTIONS', ''),
    'chat_model' => env('OPENAI_CHAT_MODEL', 'gpt-4o'),
    'edit_model' => env('OPENAI_EDIT_MODEL', 'gpt-4o'),
    'audio_model' => env('OPENAI_AUDIO_MODEL', 'whisper-1'),

    /*
    |--------------------------------------------------------------------------
    | Responses API Configuration
    |--------------------------------------------------------------------------
    */

    'responses' => [
        'timeout' => env('AI_RESPONSES_TIMEOUT', 120),
        'idempotency_enabled' => env('AI_RESPONSES_IDEMPOTENCY', true),
        'max_output_tokens' => env('AI_RESPONSES_MAX_OUTPUT_TOKENS', null),
        'retry' => [
            'enabled' => env('AI_RESPONSES_RETRY_ENABLED', true),
            'max_attempts' => env('AI_RESPONSES_RETRY_MAX_ATTEMPTS', 3),
            'initial_delay' => env('AI_RESPONSES_RETRY_INITIAL_DELAY', 0.5),
            'backoff_multiplier' => env('AI_RESPONSES_RETRY_BACKOFF_MULTIPLIER', 2.0),
            'max_delay' => env('AI_RESPONSES_RETRY_MAX_DELAY', 8.0),
            'jitter' => env('AI_RESPONSES_RETRY_JITTER', true),
        ],
        'idempotency_bucket' => env('AI_RESPONSES_IDEMPOTENCY_BUCKET', 60),
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
    | Performance & Monitoring Configuration
    |--------------------------------------------------------------------------
    */

    'connection_pool' => [
        'enabled' => env('AI_CONNECTION_POOL_ENABLED', true),
        'max_connections' => env('AI_MAX_CONNECTIONS', 100),
        'max_connections_per_host' => env('AI_MAX_CONNECTIONS_PER_HOST', 10),
        'timeout' => env('AI_CONNECTION_TIMEOUT', 30),
        'idle_timeout' => env('AI_IDLE_TIMEOUT', 10),
    ],

    'memory_monitoring' => [
        'enabled' => env('AI_MEMORY_MONITORING_ENABLED', true),
        'threshold_mb' => env('AI_MEMORY_THRESHOLD_MB', 256),
        'log_usage' => env('AI_LOG_MEMORY_USAGE', true),
        'alert_on_high_usage' => env('AI_ALERT_HIGH_MEMORY', true),
    ],

    'background_jobs' => [
        'enabled' => env('AI_BACKGROUND_JOBS_ENABLED', true),
        'queue' => env('AI_QUEUE_NAME', 'ai-assistant'),
        'connection' => env('AI_QUEUE_CONNECTION', 'default'),
        'timeout' => env('AI_JOB_TIMEOUT', 300),
        'retry_after' => env('AI_JOB_RETRY_AFTER', 90),
        'max_tries' => env('AI_JOB_MAX_TRIES', 3),
    ],

    'metrics' => [
        'enabled' => env('AI_METRICS_ENABLED', true),
        'driver' => env('AI_METRICS_DRIVER', 'log'),
        'collection_interval' => env('AI_METRICS_INTERVAL', 60),
        'retention_days' => env('AI_METRICS_RETENTION_DAYS', 30),
        'track_response_times' => env('AI_TRACK_RESPONSE_TIMES', true),
        'track_token_usage' => env('AI_TRACK_TOKEN_USAGE', true),
        'track_error_rates' => env('AI_TRACK_ERROR_RATES', true),
    ],

    /*
    |--------------------------------------------------------------------------
    | Tools Configuration
    |--------------------------------------------------------------------------
    */

    'tools' => [
        'allowlist' => env('AI_TOOLS_ALLOWLIST', []),
        'schemas' => env('AI_TOOLS_SCHEMAS', []),
    ],

    'tool_calling' => [
        'max_rounds' => env('AI_TOOL_CALLING_MAX_ROUNDS', 5),
        'executor' => env('AI_TOOL_CALLING_EXECUTOR', 'queue'),
        'parallel' => (bool)env('AI_TOOL_CALLING_PARALLEL', true),
    ],

    /*
    |--------------------------------------------------------------------------
    | Error Reporting & Health Checks
    |--------------------------------------------------------------------------
    */

    'error_reporting' => [
        'enabled' => env('AI_ERROR_REPORTING_ENABLED', true),
        'driver' => env('AI_ERROR_REPORTING_DRIVER', 'log'),
        'dsn' => env('AI_ERROR_REPORTING_DSN', null),
        'environment' => env('AI_ERROR_REPORTING_ENV', env('APP_ENV', 'production')),
        'sample_rate' => env('AI_ERROR_SAMPLE_RATE', 1.0),
        'include_context' => env('AI_ERROR_INCLUDE_CONTEXT', true),
        'scrub_sensitive_data' => env('AI_SCRUB_SENSITIVE_DATA', true),
    ],

    'health_checks' => [
        'enabled' => env('AI_HEALTH_CHECKS_ENABLED', true),
        'timeout' => env('AI_HEALTH_CHECK_TIMEOUT', 10),
        'api_connectivity' => [
            'enabled' => env('AI_HEALTH_CHECK_API_ENABLED', true),
            'test_model' => env('AI_HEALTH_CHECK_API_MODEL', 'gpt-4o'),
            'timeout' => env('AI_HEALTH_CHECK_API_TIMEOUT', 5),
            'max_tokens' => env('AI_HEALTH_CHECK_API_MAX_TOKENS', 1),
        ],
        'memory' => [
            'warning_threshold_percent' => env('AI_HEALTH_CHECK_MEMORY_WARNING', 60),
            'critical_threshold_percent' => env('AI_HEALTH_CHECK_MEMORY_CRITICAL', 80),
        ],
        'disk' => [
            'warning_threshold_percent' => env('AI_HEALTH_CHECK_DISK_WARNING', 20),
            'critical_threshold_percent' => env('AI_HEALTH_CHECK_DISK_CRITICAL', 10),
            'hide_paths' => env('AI_HEALTH_CHECK_HIDE_PATHS', true),
        ],
        'cache' => [
            'cleanup_test_keys' => env('AI_HEALTH_CHECK_CACHE_CLEANUP', true),
            'verify_delete' => env('AI_HEALTH_CHECK_CACHE_VERIFY_DELETE', true),
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Advanced Features
    |--------------------------------------------------------------------------
    */

    'streaming' => [
        'enabled' => env('AI_STREAMING_ENABLED', true),
        'buffer_size' => env('AI_STREAMING_BUFFER_SIZE', 8192),
        'chunk_size' => env('AI_STREAMING_CHUNK_SIZE', 1024),
        'timeout' => env('AI_STREAMING_TIMEOUT', 120),
        'sse_timeout' => env('AI_STREAMING_SSE_TIMEOUT', 120),
        'max_response_size' => env('AI_MAX_RESPONSE_SIZE_MB', 50),
    ],

    'lazy_loading' => [
        'enabled' => env('AI_LAZY_LOADING_ENABLED', true),
        'cache_duration' => env('AI_LAZY_CACHE_DURATION', 3600),
        'preload_common_models' => env('AI_PRELOAD_MODELS', true),
        'defer_client_creation' => env('AI_DEFER_CLIENT_CREATION', false),
    ],

    'webhooks' => [
        'enabled' => env('AI_WEBHOOKS_ENABLED', true),
        'signing_secret' => env('AI_WEBHOOKS_SIGNING_SECRET', ''),
        'path' => env('AI_WEBHOOKS_PATH', '/ai-assistant/webhook'),
        'signature_header' => env('AI_WEBHOOKS_SIGNATURE_HEADER', 'X-OpenAI-Signature'),
        'timestamp_header' => env('AI_WEBHOOKS_TIMESTAMP_HEADER', 'X-OpenAI-Timestamp'),
        'max_skew_seconds' => env('AI_WEBHOOKS_MAX_SKEW_SECONDS', 300),
        'route' => [
            'name' => env('AI_WEBHOOKS_ROUTE_NAME', 'ai-assistant.webhook'),
            'middleware' => env('AI_WEBHOOKS_MIDDLEWARE', []),
            'group' => [
                'prefix' => env('AI_WEBHOOKS_ROUTE_PREFIX', null),
                'middleware' => env('AI_WEBHOOKS_GROUP_MIDDLEWARE', []),
            ],
        ],
    ],
];
