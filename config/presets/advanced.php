<?php

declare(strict_types=1);

/**
 * Advanced AI Assistant Configuration Preset
 *
 * This preset enables a richer feature set and more robust defaults while
 * remaining cost-conscious. It aligns with the keys and structure defined in
 * config/ai-assistant.php so it can be copied over or used as guidance.
 */

return [
    // OpenAI credentials
    'api_key' => env('OPENAI_API_KEY', 'your-api-key-here'),
    'organization' => env('OPENAI_ORGANIZATION'),

    // Persistence
    'persistence' => [
        'driver' => env('AI_ASSISTANT_PERSISTENCE_DRIVER', 'eloquent'),
    ],

    // Disable mocks by default for advanced usage
    'mock_responses' => (bool)env('AI_ASSISTANT_MOCK', false),

    // Generation parameters
    'temperature' => 0.5,
    'top_p' => 0.9,
    'max_completion_tokens' => 1000,
    'stream' => true,

    // Models
    'model' => env('OPENAI_MODEL', env('OPENAI_CHAT_MODEL', 'gpt-5')),
    'default_model' => env('AI_RESPONSES_DEFAULT_MODEL', env('OPENAI_CHAT_MODEL', 'gpt-5')),
    'chat_model' => env('OPENAI_CHAT_MODEL', 'gpt-5'),
    'edit_model' => env('OPENAI_EDIT_MODEL', 'gpt-5'),
    'audio_model' => env('OPENAI_AUDIO_MODEL', 'whisper-1'),

    // Responses API controls
    'responses' => [
        'timeout' => env('AI_RESPONSES_TIMEOUT', 90),
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
        'idempotency_bucket' => env('AI_RESPONSES_IDEMPOTENCY_BUCKET', 120),
    ],

    // Roles
    'ai_role' => 'assistant',
    'user_role' => 'user',

    // Performance & monitoring
    'connection_pool' => [
        'enabled' => env('AI_CONNECTION_POOL_ENABLED', true),
        'max_connections' => env('AI_MAX_CONNECTIONS', 100),
        'max_connections_per_host' => env('AI_MAX_CONNECTIONS_PER_HOST', 10),
        'timeout' => env('AI_CONNECTION_TIMEOUT', 30),
        'idle_timeout' => env('AI_IDLE_TIMEOUT', 15),
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

    // Tool calling
    'tool_calling' => [
        'max_rounds' => env('AI_TOOL_CALLING_MAX_ROUNDS', 3),
        'executor' => env('AI_TOOL_CALLING_EXECUTOR', 'sync'),
        'parallel' => (bool)env('AI_TOOL_CALLING_PARALLEL', false),
    ],

    // Error reporting
    'error_reporting' => [
        'enabled' => env('AI_ERROR_REPORTING_ENABLED', true),
        'driver' => env('AI_ERROR_REPORTING_DRIVER', 'log'),
        'dsn' => env('AI_ERROR_REPORTING_DSN'),
        'environment' => env('AI_ERROR_REPORTING_ENV', env('APP_ENV', 'local')),
        'sample_rate' => env('AI_ERROR_SAMPLE_RATE', 1.0),
        'include_context' => env('AI_ERROR_INCLUDE_CONTEXT', true),
        'scrub_sensitive_data' => env('AI_SCRUB_SENSITIVE_DATA', true),
    ],

    // Health checks
    'health_checks' => [
        'enabled' => env('AI_HEALTH_CHECKS_ENABLED', true),
        'timeout' => env('AI_HEALTH_CHECK_TIMEOUT', 10),
        'api_connectivity' => [
            'enabled' => env('AI_HEALTH_CHECK_API_ENABLED', true),
            'test_model' => env('AI_HEALTH_CHECK_API_MODEL', 'gpt-5'),
            'timeout' => env('AI_HEALTH_CHECK_API_TIMEOUT', 5),
            'max_tokens' => env('AI_HEALTH_CHECK_API_MAX_TOKENS', 1),
        ],
    ],

    // Streaming controls
    'streaming' => [
        'enabled' => env('AI_STREAMING_ENABLED', true),
        'buffer_size' => env('AI_STREAMING_BUFFER_SIZE', 8192),
        'chunk_size' => env('AI_STREAMING_CHUNK_SIZE', 1024),
        'timeout' => env('AI_STREAMING_TIMEOUT', 120),
    ],

    // Lazy loading
    'lazy_loading' => [
        'enabled' => env('AI_LAZY_LOADING_ENABLED', true),
        'cache_duration' => env('AI_LAZY_CACHE_DURATION', 3600),
        'preload_common_models' => env('AI_PRELOAD_MODELS', false),
        'defer_client_creation' => env('AI_DEFER_CLIENT_CREATION', true),
    ],

    // Webhooks (disabled by default in advanced preset)
    'webhooks' => [
        'enabled' => env('AI_WEBHOOKS_ENABLED', false),
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
