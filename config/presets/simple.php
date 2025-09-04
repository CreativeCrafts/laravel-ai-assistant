<?php

declare(strict_types=1);

/**
 * Simple AI Assistant Configuration Preset
 *
 * This preset contains only the essential settings needed for basic chat functionality.
 * Perfect for getting started quickly with minimal configuration.
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
    | Basic Settings
    |--------------------------------------------------------------------------
    */

    'persistence' => [
        'driver' => env('AI_ASSISTANT_PERSISTENCE_DRIVER', 'memory'),
    ],

    'mock_responses' => (bool)env('AI_ASSISTANT_MOCK', false),

    /*
    |--------------------------------------------------------------------------
    | Response Generation
    |--------------------------------------------------------------------------
    */

    'temperature' => 0.7,
    'max_completion_tokens' => 800,
    'stream' => false,

    /*
    |--------------------------------------------------------------------------
    | Model Configuration
    |--------------------------------------------------------------------------
    */

    'model' => env('OPENAI_CHAT_MODEL', 'gpt-4o'),
    'chat_model' => env('OPENAI_CHAT_MODEL', 'gpt-4o'),

    /*
    |--------------------------------------------------------------------------
    | Basic Response Settings
    |--------------------------------------------------------------------------
    */

    'responses' => [
        'timeout' => 60,
        'retry' => [
            'enabled' => true,
            'max_attempts' => 2,
        ],
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
    | Tool Configuration
    |--------------------------------------------------------------------------
    */

    'tool_calling' => [
        'max_rounds' => 2,
        'executor' => 'sync',
        'parallel' => false,
    ],

    /*
    |--------------------------------------------------------------------------
    | Minimal Monitoring
    |--------------------------------------------------------------------------
    */

    'metrics' => [
        'enabled' => false,
    ],

    'error_reporting' => [
        'enabled' => true,
        'driver' => 'log',
    ],

    'health_checks' => [
        'enabled' => false,
    ],

    'streaming' => [
        'enabled' => false,
    ],

    'lazy_loading' => [
        'enabled' => true,
    ],

    'webhooks' => [
        'enabled' => false,
    ],
];
