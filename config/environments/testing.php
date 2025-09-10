<?php

declare(strict_types=1);

/**
 * Laravel AI Assistant - Testing Environment Configuration
 * This configuration is optimized for automated testing environments with:
 * - Deterministic settings for consistent test results
 * - Mocking capabilities to avoid actual API calls
 * - Minimal timeouts for fast test execution
 * - Comprehensive logging for test debugging
 */

return [
    /*
    |--------------------------------------------------------------------------
    | Testing-Specific Settings
    |--------------------------------------------------------------------------
    */

    /**
     * Use consistent models for predictable testing
     */
    'chat_model' => env('OPENAI_CHAT_MODEL', 'gpt-4o-mini'),
    'edit_model' => env('OPENAI_EDIT_MODEL', 'gpt-4o-mini'),
    'audio_model' => env('OPENAI_AUDIO_MODEL', 'whisper-1'),

    /**
     * Deterministic settings for consistent test results
     * Low temperature for predictable outputs
     */
    'temperature' => 0.1,
    'top_p' => 0.1,

    /**
     * Limited tokens for fast test execution
     */
    'max_completion_tokens' => 50,

    /**
     * Disable streaming for simpler test assertions
     */
    'stream' => false,

    /**
     * Short timeouts for fast test execution
     */
    'timeout' => env('AI_ASSISTANT_TIMEOUT', 10),
    'max_retry_attempts' => 1,
    'retry_delay' => 100, // milliseconds

    /**
     * Caching - disabled for testing isolation
     */
    'cache' => [
        'enabled' => false,
        'ttl' => 0,
        'prefix' => 'ai_test_',
    ],

    /**
     * Rate limiting - disabled for testing
     */
    'rate_limiting' => [
        'enabled' => false,
        'max_requests_per_minute' => 1000,
        'max_requests_per_hour' => 10000,
    ],

    /**
     * Security settings - strict for security testing
     */
    'security' => [
        'validate_file_paths' => true,
        'allowed_audio_formats' => ['mp3', 'wav'],
        'max_file_size' => 1024 * 1024, // 1MB for test files
    ],

    /**
     * Logging - comprehensive for test debugging
     */
    'logging' => [
        'enabled' => true,
        'level' => 'debug',
        'log_requests' => true,
        'log_responses' => true,
        'log_performance' => true,
        'log_test_data' => true,
    ],

    /**
     * Testing-specific feature flags
     */
    'features' => [
        'debug_mode' => false,
        'mock_responses' => env('AI_ASSISTANT_MOCK', true),
        'response_validation' => true,
        'performance_tracking' => false,
        'test_mode' => true,
        'fail_on_api_errors' => true,
    ],

    /**
     * Mock response configurations for testing
     */
    'mock_responses' => [
        'completion' => 'This is a test completion response.',
        'chat' => [
            'role' => 'assistant',
            'content' => 'This is a test chat response.',
        ],
        'translation' => 'Esta es una respuesta de prueba.',
        'transcription' => 'This is a test transcription.',
        'grammar_correction' => 'This is a test grammar correction.',
        'writing_improvement' => 'This is an improved test response.',
    ],

    /**
     * Test data validation settings
     */
    'validation' => [
        'strict_types' => true,
        'validate_schemas' => true,
        'check_required_fields' => true,
        'sanitize_inputs' => true,
        'skip' => true,
    ],

    /**
     * Performance testing thresholds
     */
    'performance_thresholds' => [
        'max_response_time' => 5000, // 5 seconds
        'max_memory_usage' => 128 * 1024 * 1024, // 128MB
        'max_api_calls_per_test' => 10,
    ],
];
