<?php

declare(strict_types=1);

$toolsAllowlist = env('AI_TOOLS_ALLOWLIST', []);
if (is_string($toolsAllowlist)) {
    $toolsAllowlist = preg_split('/[|,]/', $toolsAllowlist) ?: [];
    $toolsAllowlist = array_values(array_filter(array_map('trim', $toolsAllowlist), fn ($v) => $v !== ''));
} elseif (!is_array($toolsAllowlist)) {
    $toolsAllowlist = [];
}

$toolsSchemas = env('AI_TOOLS_SCHEMAS', []);
if (is_string($toolsSchemas)) {
    $decoded = json_decode($toolsSchemas, true);
    $toolsSchemas = is_array($decoded) ? $decoded : [];
} elseif (!is_array($toolsSchemas)) {
    $toolsSchemas = [];
}

return [
    'preset' => env('AI_ASSISTANT_PRESET', 'simple'),
    /*
    |--------------------------------------------------------------------------
    | OpenAI API Configuration
    |--------------------------------------------------------------------------
    |
    | Configure your OpenAI API credentials and organisation settings.
    | You can get these from your OpenAI dashboard at https://openai.com.
    |
    */

    /**
     * Your OpenAI API Key for authentication.
     * This is required to make API calls to OpenAI services.
     */
    'api_key' => env('OPENAI_API_KEY', 'your-api-key-here'),

    /**
     * Your OpenAI organization ID (optional).
     * Used to associate API usage with your organization.
     */
    'organization' => env('OPENAI_ORGANIZATION'),

    /*
    |--------------------------------------------------------------------------
    | Persistence Layer
    |--------------------------------------------------------------------------
    |
    | Choose where the SDK stores a local state (assistants, conversations,
    | conversation items, responses, tool invocations). The default is in-memory
    | for ephemeral storage. Set to 'eloquent' to persist using Eloquent models
    | and the provided migrations.
    | options: 'memory' | 'eloquent'
    */
    'persistence' => [
        'driver' => env('AI_ASSISTANT_PERSISTENCE_DRIVER', 'memory'),
    ],

    /**
     * When true, the package will avoid real OpenAI calls.
     * Useful for local development and tests. Can also be toggled via overlays.
     */
    'mock_responses' => (bool)env('AI_ASSISTANT_MOCK', false),

    /*
    |--------------------------------------------------------------------------
    | Response Generation Parameters
    |--------------------------------------------------------------------------
    |
    | Configure how the AI generates responses. These parameters control
    | the randomness, length, and style of generated content.
    |
    */

    /**
     * Sampling temperature to use, between 0 and 2.
     * Higher values like 0.8 will make the output more random,
     * while lower values like 0.2 will make it more focused and deterministic.
     * It is generally recommended to alter this or top_p but not both.
     */
    'temperature' => 0.3,

    /**
     * An alternative to sampling with temperature, called nucleus sampling.
     * The model considers the results of the tokens with top_p probability mass.
     * So 0.1 means only the tokens comprising the top 10% probability mass are considered.
     * It is generally recommended to alter this or temperature but not both.
     */
    'top_p' => 1,

    /**
     * The maximum number of tokens to generate in the completion.
     * The token count of your prompt plus max_tokens cannot exceed the model's context length.
     * Most modern models have much larger context lengths (e.g., 4096, 8192, or more tokens).
     */
    'max_completion_tokens' => 400,

    /**
     * Whether to stream tokens as they become available.
     * If true, tokens will be sent as data-only server-sent events as they become available,
     * with the stream terminated by a data: [DONE] message.
     */
    'stream' => false,

    /**
     * Up to 4 sequences where the API will stop generating further tokens.
     * The returned text will not contain the stop sequence.
     * Example: ["\n", "Human:", "AI:"] (optional)
     */
    'stop' => null,

    /**
     * Presence penalty between -2.0 and 2.0.
     * Positive values penalize new tokens based on whether they appear in the text so far,
     * increasing the model's likelihood to talk about new topics.
     */
    'presence_penalty' => 0,

    /**
     * Frequency penalty between -2.0 and 2.0.
     * Positive values penalize new tokens based on their existing frequency in the text so far,
     * decreasing the model's likelihood to repeat the same line verbatim.
     */
    'frequency_penalty' => 0,

    /*
    |--------------------------------------------------------------------------
    | Model Configuration
    |--------------------------------------------------------------------------
    |
    | Configure which OpenAI models to use for different operations.
    | You can find available models at: https://platform.openai.com/docs/models
    |
    | Recommended models:
    | - Chat: gpt-5, GPT-4o, gpt-4o-mini
    | - Text Editing: GPT-4o, gpt-4-turbo
    | - Audio: whisper-1
    |
    */

    /**
     * Default model to use for general AI operations.
     * This serves as a fallback when no specific model is configured.
     */
    'model' => env('OPENAI_MODEL', env('OPENAI_CHAT_MODEL', 'gpt-5')),

    /**
     * The default model to use for Responses API turns when not explicitly provided.
     */
    'default_model' => env('AI_RESPONSES_DEFAULT_MODEL', env('OPENAI_CHAT_MODEL', 'gpt-5')),

    /**
     * Default assistant persona/instructions to be sent with each turn unless overridden.
     */
    'default_instructions' => env('AI_DEFAULT_INSTRUCTIONS', ''),

    /**
     * Chat completion model to use for conversations.
     * This model will be used for chat-based interactions and responses.
     */
    'chat_model' => env('OPENAI_CHAT_MODEL', 'gpt-5'),

    /**
     * Model to use for text editing operations.
     * This model will be used for tasks like grammar correction and text improvement.
     */
    'edit_model' => env('OPENAI_EDIT_MODEL', 'gpt-5'),

    /**
     * Model to use for audio transcription and translation.
     * Currently, only 'whisper-1' is available for audio processing.
     */
    'audio_model' => env('OPENAI_AUDIO_MODEL', 'whisper-1'),

    /*
    |--------------------------------------------------------------------------
    | Audio Configuration
    |--------------------------------------------------------------------------
    |
    | Configure audio processing options including models, voices, file limits,
    | and endpoint-specific timeouts for transcription, translation, and speech.
    |
    */
    'audio' => [
        /**
         * Default models for different audio operations
         */
        'models' => [
            'transcription' => env('OPENAI_AUDIO_TRANSCRIPTION_MODEL', 'whisper-1'),
            'translation' => env('OPENAI_AUDIO_TRANSLATION_MODEL', 'whisper-1'),
            'speech' => env('OPENAI_AUDIO_SPEECH_MODEL', 'tts-1'),
        ],

        /**
         * Default voice options for text-to-speech
         * Available voices: alloy, echo, fable, onyx, nova, shimmer
         */
        'voices' => [
            'default' => env('OPENAI_AUDIO_DEFAULT_VOICE', 'alloy'),
            'available' => ['alloy', 'echo', 'fable', 'onyx', 'nova', 'shimmer'],
        ],

        /**
         * File size limits for audio uploads (in megabytes)
         * OpenAI limit: 25MB for transcription/translation
         */
        'file_size_limit_mb' => env('OPENAI_AUDIO_FILE_SIZE_LIMIT_MB', 25),

        /**
         * Supported audio file formats
         */
        'supported_formats' => ['mp3', 'mp4', 'mpeg', 'mpga', 'm4a', 'wav', 'webm'],

        /**
         * Timeout settings for audio endpoints (in seconds)
         */
        'timeouts' => [
            'transcription' => env('OPENAI_AUDIO_TRANSCRIPTION_TIMEOUT', 120),
            'translation' => env('OPENAI_AUDIO_TRANSLATION_TIMEOUT', 120),
            'speech' => env('OPENAI_AUDIO_SPEECH_TIMEOUT', 60),
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Image Configuration
    |--------------------------------------------------------------------------
    |
    | Configure image processing options including models, default sizes,
    | file limits, and endpoint-specific timeouts for generation, editing,
    | and variations.
    |
    */
    'image' => [
        /**
         * Default models for different image operations
         */
        'models' => [
            'generation' => env('OPENAI_IMAGE_GENERATION_MODEL', 'dall-e-3'),
            'edit' => env('OPENAI_IMAGE_EDIT_MODEL', 'dall-e-2'),
            'variation' => env('OPENAI_IMAGE_VARIATION_MODEL', 'dall-e-2'),
        ],

        /**
         * Default image sizes for generation
         * DALL-E 3: 1024x1024, 1792x1024, 1024x1792
         * DALL-E 2: 256x256, 512x512, 1024x1024
         */
        'sizes' => [
            'dall-e-3' => env('OPENAI_IMAGE_SIZE_DALLE3', '1024x1024'),
            'dall-e-2' => env('OPENAI_IMAGE_SIZE_DALLE2', '1024x1024'),
        ],

        /**
         * File size limits for image uploads (in megabytes)
         * OpenAI limit: 4MB for image edits and variations
         */
        'file_size_limit_mb' => env('OPENAI_IMAGE_FILE_SIZE_LIMIT_MB', 4),

        /**
         * Supported image file formats
         */
        'supported_formats' => ['png', 'jpg', 'jpeg', 'webp'],

        /**
         * Timeout settings for image endpoints (in seconds)
         */
        'timeouts' => [
            'generation' => env('OPENAI_IMAGE_GENERATION_TIMEOUT', 120),
            'edit' => env('OPENAI_IMAGE_EDIT_TIMEOUT', 120),
            'variation' => env('OPENAI_IMAGE_VARIATION_TIMEOUT', 120),
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Adapter Configuration
    |--------------------------------------------------------------------------
    |
    | Configure adapter-specific options for request/response transformation
    | and validation across different OpenAI endpoints.
    |
    */
    'adapters' => [
        /**
         * Enable or disable adapter caching for improved performance
         */
        'cache_enabled' => env('AI_ADAPTERS_CACHE_ENABLED', true),

        /**
         * Validate requests before transformation
         */
        'validate_requests' => env('AI_ADAPTERS_VALIDATE_REQUESTS', true),

        /**
         * Validate responses after transformation
         */
        'validate_responses' => env('AI_ADAPTERS_VALIDATE_RESPONSES', true),

        /**
         * Maximum file upload size across all adapters (in megabytes)
         * This serves as a global limit; endpoint-specific limits take precedence
         */
        'max_file_size_mb' => env('AI_ADAPTERS_MAX_FILE_SIZE_MB', 25),
    ],

    /*
    |--------------------------------------------------------------------------
    | Request Routing Configuration
    |--------------------------------------------------------------------------
    |
    | Configure how the RequestRouter determines which OpenAI endpoint should
    | handle incoming requests. This system allows you to customize endpoint
    | matching priorities and detect conflicting configurations.
    |
    | ## Routing Logic
    |
    | The router evaluates endpoint conditions in the order specified by
    | 'endpoint_priority'. The first matching condition determines which
    | endpoint handles the request. This implements a "first match wins"
    | strategy that can be customized through configuration.
    |
    | ## Conflict Detection
    |
    | When 'validate_conflicts' is enabled, the router validates that your
    | configuration doesn't have conflicting endpoint settings that could
    | lead to ambiguous routing decisions. For example, enabling both audio
    | transcription and audio translation with overlapping input conditions
    | would be flagged as a conflict.
    |
    | ## Example Scenarios
    |
    | Simple Configuration (No Conflicts):
    | - endpoint_priority: ['audio_transcription', 'image_generation', 'response_api']
    | - Request with audio file and transcribe action
    | - Reasoning: Audio transcription has highest priority and matches the request
    | - Conclusion: Routes to audio_transcription endpoint
    |
    | Conflicting Configuration:
    | - Both audio_transcription and audio_translation configured
    | - Request with audio file but ambiguous action
    | - Reasoning: Multiple audio endpoints match, creating ambiguity
    | - Conclusion: Error raised to resolve configuration conflict
    |
    */
    'routing' => [
        /**
         * Endpoint matching priority order.
         * Defines the order in which endpoint conditions are evaluated.
         * The router checks conditions in this order and uses the first match.
         * Available endpoints:
         * - audio_transcription: Audio file with transcribe action
         * - audio_translation: Audio file with translate action
         * - audio_speech: Text with speech generation action
         * - image_generation: Image prompt without existing image
         * - image_edit: Image file with prompt for editing
         * - image_variation: Image file without prompt for variations
         * - chat_completion: Audio input in chat context (Response API limitation workaround)
         * - response_api: Default for all text/chat operations (recommended by OpenAI)
         * Default order follows OpenAI best practices with Response API as default.
         */
        'endpoint_priority' => is_string($priority = env('AI_ROUTING_ENDPOINT_PRIORITY')) && !empty($priority)
            ? explode(',', $priority)
            : [
                'audio_transcription',
                'audio_translation',
                'audio_speech',
                'image_generation',
                'image_edit',
                'image_variation',
                'chat_completion',
                'response_api_image_input',
                'response_api',
            ],

        /**
         * Enable conflict detection validation.
         * When true, the router validates that endpoint configurations don't
         * conflict with each other. This helps catch configuration errors that
         * could lead to unexpected routing behavior.
         * Reasoning-first approach: The validator analyzes all active endpoints,
         * identifies potential conflicts, explains the reasoning, and then
         * raises an error with clear resolution steps.
         */
        'validate_conflicts' => env('AI_ROUTING_VALIDATE_CONFLICTS', true),

        /**
         * Conflict validation behavior.
         * Determines how the router handles detected conflicts:
         * - 'error': Throw exception and halt execution (recommended for production)
         * - 'warn': Log warning but continue with first match (useful for development)
         * - 'silent': Ignore conflicts and use first match (not recommended)
         */
        'conflict_behavior' => env('AI_ROUTING_CONFLICT_BEHAVIOR', 'error'),

        /**
         * Enable endpoint availability validation.
         * When true, validates that endpoints in the priority list are valid
         * and supported by the system. Invalid endpoint names will raise an error
         * with reasoning about which endpoints are invalid and why.
         */
        'validate_endpoint_names' => env('AI_ROUTING_VALIDATE_ENDPOINT_NAMES', true),
    ],

    /*
    |--------------------------------------------------------------------------
    | Responses API Configuration
    |--------------------------------------------------------------------------
    |
    | Controls for using the Responses API. Includes timeouts, retry/backoff,
    | and idempotency for safe retries. You may also specify a max_output_tokens
    | used when translating from legacy max_completion_tokens.
    */
    'responses' => [
        'timeout' => env('AI_RESPONSES_TIMEOUT', 120),
        'idempotency_enabled' => env('AI_RESPONSES_IDEMPOTENCY', true),
        'max_output_tokens' => env('AI_RESPONSES_MAX_OUTPUT_TOKENS', null),
        'retry' => [
            'enabled' => env('AI_RESPONSES_RETRY_ENABLED', true),
            'max_attempts' => env('AI_RESPONSES_RETRY_MAX_ATTEMPTS', 3),
            'initial_delay' => env('AI_RESPONSES_RETRY_INITIAL_DELAY', 0.5), // seconds
            'backoff_multiplier' => env('AI_RESPONSES_RETRY_BACKOFF_MULTIPLIER', 2.0),
            'max_delay' => env('AI_RESPONSES_RETRY_MAX_DELAY', 8.0),
            'jitter' => env('AI_RESPONSES_RETRY_JITTER', true),
        ],
        // Deterministic idempotency time bucket (seconds)
        'idempotency_bucket' => env('AI_RESPONSES_IDEMPOTENCY_BUCKET', 60),
    ],

    /*
    |--------------------------------------------------------------------------
    | Conversations API Configuration
    |--------------------------------------------------------------------------
    |
    | Controls for using the Conversations API. Includes timeouts and retry
    | configuration for conversation management operations.
    */
    'conversations' => [
        'timeout' => env('AI_CONVERSATIONS_TIMEOUT', 60),
        'retry' => [
            'enabled' => env('AI_CONVERSATIONS_RETRY_ENABLED', true),
            'max_attempts' => env('AI_CONVERSATIONS_RETRY_MAX_ATTEMPTS', 3),
            'initial_delay' => env('AI_CONVERSATIONS_RETRY_INITIAL_DELAY', 0.5), // seconds
            'backoff_multiplier' => env('AI_CONVERSATIONS_RETRY_BACKOFF_MULTIPLIER', 2.0),
            'max_delay' => env('AI_CONVERSATIONS_RETRY_MAX_DELAY', 8.0),
            'jitter' => env('AI_CONVERSATIONS_RETRY_JITTER', true),
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Role Configuration
    |--------------------------------------------------------------------------
    |
    | Define the roles used in chat conversations. These roles help structure
    | the conversation and provide context to the AI model about who is speaking.
    |
    */

    /**
     * The role identifier for the AI assistant in chat conversations.
     * This should typically remain as 'assistant' for standard OpenAI compatibility.
     */
    'ai_role' => 'assistant',

    /**
     * The role identifier for the user in chat conversations.
     * This should typically remain as 'user' for standard OpenAI compatibility.
     */
    'user_role' => 'user',

    /*
    |--------------------------------------------------------------------------
    | Performance & Monitoring Configuration
    |--------------------------------------------------------------------------
    |
    | Advanced performance optimization and monitoring settings for production
    | environments. These settings help optimize HTTP connections, memory usage,
    | and provide detailed metrics and error reporting capabilities.
    |
    */

    /**
     * HTTP connection pooling settings for improved performance.
     * Connection pooling reuses existing HTTP connections to reduce latency
     * and improve throughput for multiple API requests.
     */
    'connection_pool' => [
        'enabled' => env('AI_CONNECTION_POOL_ENABLED', true),
        'max_connections' => env('AI_MAX_CONNECTIONS', 100),
        'max_connections_per_host' => env('AI_MAX_CONNECTIONS_PER_HOST', 10),
        'timeout' => env('AI_CONNECTION_TIMEOUT', 30),
        'idle_timeout' => env('AI_IDLE_TIMEOUT', 10),
    ],

    /**
     * Memory usage monitoring configuration.
     * Tracks memory consumption during large file operations and API responses.
     */
    'memory_monitoring' => [
        'enabled' => env('AI_MEMORY_MONITORING_ENABLED', true),
        'threshold_mb' => env('AI_MEMORY_THRESHOLD_MB', 256),
        'log_usage' => env('AI_LOG_MEMORY_USAGE', true),
        'alert_on_high_usage' => env('AI_ALERT_HIGH_MEMORY', true),
    ],

    /**
     * Background job configuration for long-running operations.
     * Moves time-intensive operations to queue jobs for better performance.
     */
    'background_jobs' => [
        'enabled' => env('AI_BACKGROUND_JOBS_ENABLED', false),
        'queue' => env('AI_QUEUE_NAME', 'ai-assistant'),
        'connection' => env('AI_QUEUE_CONNECTION', 'default'),
        'timeout' => env('AI_JOB_TIMEOUT', 300),
        'retry_after' => env('AI_JOB_RETRY_AFTER', 90),
        'max_tries' => env('AI_JOB_MAX_TRIES', 3),
    ],

    /**
     * Metrics collection configuration.
     * Tracks API usage patterns, performance metrics, and system health.
     */
    'metrics' => [
        'enabled' => env('AI_METRICS_ENABLED', true),
        'driver' => env('AI_METRICS_DRIVER', 'log'), // log, redis, database
        'collection_interval' => env('AI_METRICS_INTERVAL', 60), // seconds
        'retention_days' => env('AI_METRICS_RETENTION_DAYS', 30),
        'track_response_times' => env('AI_TRACK_RESPONSE_TIMES', true),
        'track_token_usage' => env('AI_TRACK_TOKEN_USAGE', true),
        'track_error_rates' => env('AI_TRACK_ERROR_RATES', true),
    ],

    /*
    |--------------------------------------------------------------------------
    | Tools Configuration
    |--------------------------------------------------------------------------
    |
    | Define which callable tools are allowed to be invoked by the AI and their
    | optional JSON schemas. These can be used by your ToolRegistry to enforce
    | security and validate arguments.
    */
    'tools' => [
        'allowlist' => $toolsAllowlist, // array of tool names allowed
        'schemas' => $toolsSchemas, // map of tool name => schema array
    ],

    /*
    |--------------------------------------------------------------------------
    | Tool/function calling controls
    |--------------------------------------------------------------------------
    |
    | Configure defaults for the automatic tool/function calling loop used by
    | AssistantService when the Responses API emits tool calls.
    |
    */
    'tool_calling' => [
        'max_rounds' => env('AI_TOOL_CALLING_MAX_ROUNDS', 3),
        'executor' => env('AI_TOOL_CALLING_EXECUTOR', 'sync'), // 'sync' | 'queue'
        'parallel' => (bool)env('AI_TOOL_CALLING_PARALLEL', false),
    ],

    /**
     * Error reporting configuration (log-only).
     * Provides structured logging for comprehensive monitoring without external services.
     */
    'error_reporting' => [
        'enabled' => env('AI_ERROR_REPORTING_ENABLED', true),
        'driver' => env('AI_ERROR_REPORTING_DRIVER', 'log'), // log only
        'dsn' => env('AI_ERROR_REPORTING_DSN', null),
        'environment' => env('AI_ERROR_REPORTING_ENV', env('APP_ENV', 'production')),
        'sample_rate' => env('AI_ERROR_SAMPLE_RATE', 1.0),
        'include_context' => env('AI_ERROR_INCLUDE_CONTEXT', true),
        'scrub_sensitive_data' => env('AI_SCRUB_SENSITIVE_DATA', true),
    ],

    /**
     * Health check configuration for system monitoring.
     * Controls thresholds, timeouts, and behavior of health check operations.
     */
    'health_checks' => [
        'enabled' => env('AI_HEALTH_CHECKS_ENABLED', true),
        'timeout' => env('AI_HEALTH_CHECK_TIMEOUT', 10), // seconds per individual check
        'route_prefix' => env('AI_HEALTH_CHECK_ROUTE_PREFIX', '/ai-assistant/health'),
        'middleware' => env('AI_HEALTH_CHECK_MIDDLEWARE', []),
        'api_connectivity' => [
            'enabled' => env('AI_HEALTH_CHECK_API_ENABLED', true),
            'test_model' => env('AI_HEALTH_CHECK_API_MODEL', 'gpt-5'),
            'timeout' => env('AI_HEALTH_CHECK_API_TIMEOUT', 5), // seconds
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

    /**
     * Advanced streaming configuration for large responses.
     * Optimizes handling of large API responses through efficient streaming.
     */
    'streaming' => [
        'enabled' => env('AI_STREAMING_ENABLED', true),
        'buffer_size' => env('AI_STREAMING_BUFFER_SIZE', 8192),
        'chunk_size' => env('AI_STREAMING_CHUNK_SIZE', 1024),
        'timeout' => env('AI_STREAMING_TIMEOUT', 120),
        'sse_timeout' => env('AI_STREAMING_SSE_TIMEOUT', 120),
        'max_response_size' => env('AI_MAX_RESPONSE_SIZE_MB', 50),
    ],

    /**
     * Lazy loading configuration for expensive operations.
     * Defers initialization of expensive resources until actually needed.
     */
    'lazy_loading' => [
        'enabled' => env('AI_LAZY_LOADING_ENABLED', true),
        'cache_duration' => env('AI_LAZY_CACHE_DURATION', 3600), // seconds
        'preload_common_models' => env('AI_PRELOAD_MODELS', false),
        'defer_client_creation' => env('AI_DEFER_CLIENT_CREATION', true),
    ],

    // Webhook configuration for Responses API events
    'webhooks' => [
        'enabled' => env('AI_WEBHOOKS_ENABLED', false),
        'signing_secret' => env('AI_WEBHOOKS_SIGNING_SECRET', ''),
        'path' => env('AI_WEBHOOKS_PATH', '/ai-assistant/webhook'),
        // Signature and timestamp headers are configurable; defaults follow OpenAI style
        'signature_header' => env('AI_WEBHOOKS_SIGNATURE_HEADER', 'X-OpenAI-Signature'),
        'timestamp_header' => env('AI_WEBHOOKS_TIMESTAMP_HEADER', 'X-OpenAI-Timestamp'),
        // Enforce timestamp verification; when true, legacy signature fallback is disabled
        'require_timestamp' => env('AI_WEBHOOKS_REQUIRE_TIMESTAMP', false),
        // Maximum allowed clock skew (seconds) for replay protection
        'max_skew_seconds' => env('AI_WEBHOOKS_MAX_SKEW_SECONDS', 300), // 5 minutes
        'route' => [
            // Customize the registered route name (default preserved for BC)
            'name' => env('AI_WEBHOOKS_ROUTE_NAME', 'ai-assistant.webhook'),
            // Middleware can be an array or a string of names separated by comma or pipe
            'middleware' => env('AI_WEBHOOKS_MIDDLEWARE', []),
            // Optional Route::group options
            'group' => [
                'prefix' => env('AI_WEBHOOKS_ROUTE_PREFIX', null),
                // Can be an array or string separated by comma/pipe
                'middleware' => env('AI_WEBHOOKS_GROUP_MIDDLEWARE', []),
            ],
        ],
        // Response-specific webhook configuration
        'responses' => [
            'enabled' => env('AI_WEBHOOKS_RESPONSES_ENABLED', true),
            'events' => env('AI_WEBHOOKS_RESPONSES_EVENTS', ['response.created', 'response.completed', 'response.failed']),
            'queue' => env('AI_WEBHOOKS_RESPONSES_QUEUE', false),
            'retry_failed' => env('AI_WEBHOOKS_RESPONSES_RETRY_FAILED', true),
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Transport retry policy
    |--------------------------------------------------------------------------
    |
    | Controls retry behavior for transient errors (timeouts, 429/5xx, etc.).
    |
    */
    'transport' => [
        'max_retries' => env('AI_TRANSPORT_MAX_RETRIES', 2),
        'initial_delay_ms' => env('AI_TRANSPORT_INITIAL_DELAY_MS', 200),
        'max_delay_ms' => env('AI_TRANSPORT_MAX_DELAY_MS', 2000),
    ],

    /*
    |--------------------------------------------------------------------------
    | AI Assistant Cache
    |--------------------------------------------------------------------------
    |
    | Production-ready cache configuration for this package. Do not use
    | Cache::flush() anywhere. These settings control store selection,
    | key namespacing, TTLs, tags, stampede protection, and safety.
    */
    'cache' => [
        // Which cache store to use for the assistant specifically.
        // null => use cache.default
        'store' => env('AI_ASSISTANT_CACHE_STORE', null),

        // Global prefix used by this package (override the built-in default)
        'global_prefix' => env('AI_ASSISTANT_CACHE_PREFIX', 'laravel_ai_assistant:'),

        // Hash algorithm used to build completion keys from payloads
        'hash_algo' => env('AI_ASSISTANT_CACHE_HASH_ALGO', 'sha256'),

        // TTLs (seconds)
        'ttl' => [
            'default' => env('AI_ASSISTANT_CACHE_TTL_DEFAULT', 300),
            'config' => env('AI_ASSISTANT_CACHE_TTL_CONFIG', 3600),
            'response' => env('AI_ASSISTANT_CACHE_TTL_RESPONSE', 300),
            'completion' => env('AI_ASSISTANT_CACHE_TTL_COMPLETION', 300),
            'lock' => env('AI_ASSISTANT_CACHE_TTL_LOCK', 10),
            'grace' => env('AI_ASSISTANT_CACHE_TTL_GRACE', 30),
        ],

        // Safety & behavior
        'prevent_flush' => env('AI_ASSISTANT_CACHE_PREVENT_FLUSH', true),
        'prefix_clear_batch' => env('AI_ASSISTANT_CACHE_CLEAR_BATCH', 500),
        'max_ttl' => env('AI_ASSISTANT_CACHE_MAX_TTL', 86400),

        // Performance
        'compression' => [
            'enabled' => env('AI_ASSISTANT_CACHE_COMPRESSION', false),
            'threshold' => env('AI_ASSISTANT_CACHE_COMPRESSION_THRESHOLD', 1024),
        ],
        'encryption' => [
            'enabled' => env('AI_ASSISTANT_CACHE_ENCRYPTION', false),
        ],

        // Stampede protection
        'stampede' => [
            'enabled' => env('AI_ASSISTANT_CACHE_STAMPEDE', true),
            'lock_ttl' => env('AI_ASSISTANT_CACHE_LOCK_TTL', 10),
            'retry_ms' => env('AI_ASSISTANT_CACHE_RETRY_MS', 150),
            'max_wait_ms' => env('AI_ASSISTANT_CACHE_MAX_WAIT_MS', 1000),
        ],

        // Tagging (auto-disabled when unsupported by the store)
        'tags' => [
            'enabled' => env('AI_ASSISTANT_CACHE_TAGS', true),
            'groups' => [
                'config' => ['ai:config'],
                'response' => ['ai:response'],
                'completion' => ['ai:completion'],
            ],
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Deprecation warnings
    |--------------------------------------------------------------------------
    |
    | Controls emission of deprecation warnings for legacy features.
    |
    */

    'deprecations' => [
        // Set to true to emit E_USER_DEPRECATED notices at runtime for legacy surfaces.
        // Defaults are false to keep tests/CI noise-free.
        'emit' => env('AI_ASSISTANT_EMIT_DEPRECATIONS', false),
    ],
];
