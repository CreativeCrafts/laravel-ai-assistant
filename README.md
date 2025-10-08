# Laravel AI Assistant

[![Latest Version on Packagist](https://img.shields.io/packagist/v/creativecrafts/laravel-ai-assistant.svg?style=flat-square)](https://packagist.org/packages/creativecrafts/laravel-ai-assistant)
[![GitHub Tests Action Status](https://img.shields.io/github/actions/workflow/status/creativecrafts/laravel-ai-assistant/run-tests.yml?branch=main&label=tests&style=flat-square)](https://github.com/creativecrafts/laravel-ai-assistant/actions?query=workflow%3Arun-tests+branch%3Amain)
[![GitHub Code Style Action Status](https://img.shields.io/github/actions/workflow/status/creativecrafts/laravel-ai-assistant/fix-php-code-style-issues.yml?branch=main&label=code%20style&style=flat-square)](https://github.com/creativecrafts/laravel-ai-assistant/actions?query=workflow%3A"Fix+PHP+code+style+issues"+branch%3Amain)
[![Total Downloads](https://img.shields.io/packagist/dt/creativecrafts/laravel-ai-assistant.svg?style=flat-square)](https://packagist.org/packages/creativecrafts/laravel-ai-assistant)

Laravel AI Assistant is an enterprise-grade, comprehensive package designed to seamlessly integrate OpenAI's powerful language models into your Laravel applications. It provides an easy-to-use, fluent
API with advanced features including streaming responses, background job processing, lazy loading optimization, comprehensive metrics collection, robust security controls, and sophisticated error
handling. Perfect for building production-ready AI-powered applications with enterprise-level reliability and performance.

---

## 🚀 Installation

```bash
composer require creativecrafts/laravel-ai-assistant
```

Publish the config:

```bash
php artisan vendor:publish --tag=laravel-ai-assistant-config
```

Run the installer (recommended):

```bash
php artisan ai:install
```

---

## 📖 Usage

### Quick one-off prompts (`Ai::quick()`)

Use when you need a single response with minimal setup (stateless).

```php
use CreativeCrafts\LaravelAiAssistant\Facades\Ai;

$response = Ai::quick("Explain Laravel queues in simple terms.");
echo $response->text;

// Force JSON output:
$response = Ai::quick([
    "messages" => [["role" => "user", "content" => "Give me a JSON object with 3 fun facts about cats."]],
    "response_format" => "json",
]);
echo $response->json;
```

### Stateful conversations & streaming (`Ai::chat()`)

Use when you need multi-turn interactions or streaming tokens.

```php
use CreativeCrafts\LaravelAiAssistant\Facades\Ai;

$chat = Ai::chat("You are a helpful assistant.");
$chat->message("Hello, who won the 2022 World Cup?");

// One-shot reply for this turn
$reply = $chat->send();
echo $reply->text;

// Stream token-by-token (SSE/WebSockets/Livewire friendly)
foreach ($chat->stream() as $chunk) {
    echo $chunk;
}
```

**When to use which?**

- **`quick()`** → one-off Q&A, summarization, translation; **stateless** and minimal ceremony.
- **`chat()`** → multi-turn conversations, memory/threads, **streaming** support.

Both are intentionally provided: they cover different developer ergonomics and runtime needs.

## 5‑Minute Sample App (Streaming + Webhook)

Quick sanity check you can paste into a fresh Laravel app that has this package installed.

### 1) Mount helper routes

```php
use Illuminate\Support\Facades\Route;

Route::aiAssistant([
    'prefix' => 'ai',
    'middleware' => ['web', 'auth:sanctum'],
]);
```

### 2) Add a streaming endpoint

```php
// routes/web.php
use App\Http\Controllers\StreamingController;

Route::get('/ai/stream', StreamingController::class);
```

```php
// app/Http/Controllers/StreamingController.php
namespace App\Http\Controllers;

use CreativeCrafts\LaravelAiAssistant\Http\Responses\StreamedAiResponse;
use CreativeCrafts\LaravelAiAssistant\Facades\Ai;
use Illuminate\Http\Request;

class StreamingController extends Controller
{
    public function __invoke(Request $request)
    {
        $prompt = (string) $request->input('q', 'Say hello and count to 10.');
        $gen = Ai::chat($prompt)->stream();
        return StreamedAiResponse::fromGenerator($gen);
    }
}
```

### 3) Secure your webhook (if used)

```php
Route::post('/ai/webhook', [\App\Http\Controllers\AiWebhookController::class, 'handle'])
    ->middleware('verify.ai.webhook');
```

### 4) Configure environment

```env
AI_ASSISTANT_WEBHOOK_SIGNING_SECRET=base64:GENERATE_A_STRONG_VALUE
AI_ASSISTANT_PRESET=simple
```

> Stubs available: `stubs/examples/routes.php`, `stubs/examples/StreamingController.php`

---

## 🔐 Webhook Security

Validate incoming webhook signatures using the middleware:

```php
Route::post("/ai/webhook", [\App\Http\Controllers\AiWebhookController::class, "handle"])
    ->middleware("verify.ai.webhook");
```

---

## 🔄 Migration Guide (Version 3.x is current in beta and not ready for production)

### Upgrading to Latest Version

#### From v2.x to v3.x

- **Breaking Change:** The default model has been updated from `gpt-4` to `gpt-5`
- **Breaking Change:** Streaming configuration has been moved from root level to `streaming` array
- **New Feature:** Responses API is now the default for new installations
- **Configuration Changes:**
  ```php
  // Old configuration
  'stream' => true,
  'buffer_size' => 8192,
  
  // New configuration
  'streaming' => [
      'enabled' => true,
      'buffer_size' => 8192,
      'chunk_size' => 1024,
      'timeout' => 120,
  ],
  ```

#### From v1.x to v2.x

- **Breaking Change:** Minimum PHP version increased to 8.2
- **Breaking Change:** Laravel 9 support dropped, minimum Laravel 10 required
- **New Feature:** Background job processing added
- **Configuration Changes:**
  ```php
  // Add to your .env file
  AI_BACKGROUND_JOBS_ENABLED=false  // Set to true if you want to enable
  AI_QUEUE_NAME=ai-assistant
  ```

#### Migration Steps

1. **Update Dependencies:**
   ```bash
   composer update creativecrafts/laravel-ai-assistant
   ```

2. **Update Configuration:**
   ```bash
   php artisan vendor:publish --tag="laravel-ai-assistant-config" --force
   ```

3. **Update Environment Variables:**
    - Review your `.env` file against the new configuration options
    - Update any deprecated environment variables

4. **Test Your Implementation:**
    - Run your test suite to ensure compatibility
    - Check for any deprecated method usage

5. **Update Database (if using Eloquent persistence):**
   ```bash
   php artisan vendor:publish --tag="laravel-ai-assistant-migrations" --force
   php artisan migrate
   ```

#### Deprecated Features

- ⚠️ `Assistant::sendMessage()` - Use `sendChatMessage()` instead
- ⚠️ Root-level streaming config - Use `streaming` array configuration
- ⚠️ Legacy thread management—Migrate to Responses API for better performance

For detailed migration instructions, see the [migration documentation](docs/Migrate_from_AssistantAPI_to_ResponseAPI.md).

---

## Table of Contents

- [Overview](#overview)
- [Features](#features)
- [Choosing the right class](#choosing-the-right-class)
- [Installation](#installation)
- [Configuration](#configuration)
- [Core Usage](#core-usage)
- [Advanced Features](#advanced-features)
    - [Streaming Service](#streaming-service)
    - [Background Jobs](#background-jobs)
    - [Lazy Loading](#lazy-loading)
    - [Metrics Collection](#metrics-collection)
    - [Security Features](#security-features)
    - [Performance Optimizations](#performance-optimizations)
- [Error Handling & Exceptions](#error-handling--exceptions)
- [Best Practices](#best-practices)
- [Cookbook](#cookbook)
    - [Chat with JSON output](#chat-with-json-output)
    - [Function calling with a Laravel job executor (queue)](#function-calling-with-a-laravel-job-executor-queue)
    - [Attach files and enable file search (vector stores)](#attach-files-and-enable-file-search-vector-stores)
- [Examples](#examples)
- [Testing](#testing)
- [Contributing](#contributing)
- [License](#license)

---

## Overview

Laravel AI Assistant simplifies the integration of AI models into your Laravel application with enterprise-grade features. Whether you're building a conversational chatbot, automating content
creation, transcribing audio files, or processing large-scale AI operations, this package provides a clean, expressive API to handle complex tasks with minimal effort.

The package now includes advanced architectural improvements:

- **Enterprise-grade streaming** with memory management and performance monitoring
- **Background job processing** for long-running AI operations
- **Lazy loading optimization** for better resource management
- **Comprehensive metrics collection** for monitoring and analytics
- **Advanced security features** including rate limiting and request validation
- **Sophisticated error handling** with custom exception types
- **Performance optimizations** including caching and connection pooling

---

## ⚡ Quick Start (5 Minutes)

Get up and running with Laravel AI Assistant in just 5 minutes:

### 1. Install the Package

```bash
composer require creativecrafts/laravel-ai-assistant
```

### 2. Set Your API Key

Add your OpenAI API key to your `.env` file:

```env
OPENAI_API_KEY=your-openai-api-key-here
```

### 3. Publish Configuration (Optional)

```bash
php artisan vendor:publish --tag="laravel-ai-assistant-config"
```

### 4. Run the Installer (Recommended)

```bash
php artisan ai:install
```

### 5. Start Using AI Assistant

```php
use CreativeCrafts\LaravelAiAssistant\Facades\Ai;

// Simple chat interaction
$response = Ai::chat('Explain Laravel in one sentence')->send();
echo $response->getContent();

// Or use the fluent builder (DEPRECATED)
// Ai::assistant() is deprecated. Prefer Ai::responses() or Ai::conversations().
// New API example:
// $dto = Ai::responses()->input()->appendUserText('Write a haiku about coding')->send();
// echo $dto->text ?? $dto->content;
$response = Ai::assistant()
    ->usingModel('gpt-4o')
    ->withTemperature(0.7)
    ->sendChatMessage('Write a haiku about coding');
```

### Mount built-in helper routes (optional)

Add the macro to your route file to mount a small set of helper endpoints:

```php
use Illuminate\Support\Facades\Route;

Route::aiAssistant([
    'prefix' => 'ai',
    'middleware' => ['web', 'auth:sanctum'],
]);
```

This registers:
• GET /ai/health – health probe
• POST /ai/webhooks – incoming webhooks (secured with verify.ai.webhook middleware)

### 6. Enable Advanced Features (Optional)

For production use, consider enabling these features in your `.env`:

```env
# Background job processing
AI_BACKGROUND_JOBS_ENABLED=true
AI_QUEUE_NAME=ai-assistant

# Streaming responses
AI_STREAMING_ENABLED=true

# Metrics collection
AI_METRICS_ENABLED=true
AI_METRICS_DRIVER=database

# Connection pooling for better performance
AI_CONNECTION_POOL_ENABLED=true
```

**That's it!** You now have a powerful AI assistant integrated into your Laravel application. Continue reading for advanced features and configuration options.

---

## Features

### Core Features

- **Fluent API:** Chain method calls for a clean and intuitive setup
- **Chat Messaging:** Easily manage user, developer, and tool messages
- **Audio Transcription:** Convert audio files to text with optional prompts
- **Tool Integration:** Extend functionality with file search, code interpreter, and custom function calls
- **Custom Configuration:** Configure model parameters, temperature, top_p, and more
- **DTOs & Factories:** Use data transfer objects to structure and validate your data

### Advanced Features

- **Streaming Service:** Real-time response streaming with memory management and buffering
- **Background Jobs:** Queue long-running AI operations with progress tracking
- **Lazy Loading:** Optimize resource usage with intelligent lazy initialization
- **Metrics Collection:** Comprehensive monitoring of API usage, performance, and costs
- **Security Service:** Advanced security with API key validation, rate limiting, and request signing
- **Error Handling:** Robust exception system with 8+ custom exception types
- **Performance Monitoring:** Memory usage tracking and performance optimization
- **Caching & Optimization:** Intelligent caching for improved performance

---

## Choosing the right class

Pick the entrypoint that best matches your use case:

- AiAssistant (Responses API)
    - Ephemeral, per-turn options. Best for one-off chat turns with modern Responses + Conversations APIs.
    - Strongly-typed DTOs are available via sendChatMessageDto() and ResponseEnvelope via sendChatMessageEnvelope().
    - Great when you want full control over tools, files, response formats, and streaming.

- Assistant (legacy Chat Completions style)
    - Persistent-style builder with backwards compatibility. Returns arrays by default, DTO via sendChatMessageDto().
    - Useful when migrating existing code; supports streaming and many configuration options.

- Facade Ai
    - Ai::chat() provides a discoverable, typed ChatSession over AiAssistant.
    - Ai::assistant() gives you the Assistant builder when you prefer that flow. [DEPRECATED] Use Ai::responses() or Ai::conversations() instead.

Quick examples

- One-off turn (modern Responses API)

```php
use CreativeCrafts\LaravelAiAssistant\Facades\Ai;

$response = Ai::chat('Summarize SOLID in 2 lines')
    ->setResponseFormatText()
    ->send();

echo $response->content; // normalized text content when available
```

- Streaming (text chunks)

```php
foreach (Ai::chat('Stream a limerick about Laravel')->streamText(fn($t) => print($t)) as $chunk) {
    // $chunk is a string delta
}
```

- Assistant (legacy-style)

```php
use CreativeCrafts\LaravelAiAssistant\Assistant;

$assistant = app(Assistant::class)
    ->setUserMessage('Compare Laravel and Symfony briefly');

$result = $assistant->sendChatMessageDto();
```

When to choose which

- Prefer Ai::chat() / AiAssistant for new work and modern Responses + Conversations features (tool calls, file_search, json_schema).
- Use Assistant when migrating older code or when its builder API matches your needs today.

See also: Migration Guide in docs/Migrate_from_AssistantAPI_to_ResponseAPI.md.

## Installation

You can install the package via composer:

```bash
composer require creativecrafts/laravel-ai-assistant
```

You can publish the config file with:

```bash
php artisan vendor:publish --tag="ai-assistant-config"
```

---

## Configuration

After publishing, you will find a configuration file at `config/ai-assistant.php`. The configuration includes:

### API Credentials

Set your OpenAI API key and organization:

```php
'api_key' => env('OPENAI_API_KEY', null),
'organization' => env('OPENAI_ORGANIZATION', null),
```

Note:

- This package can optionally use openai-php/client ^0.10. If you install the SDK, you can use the full client; otherwise the package provides internal Compat classes and class aliases so common
  OpenAI\… types resolve at runtime.
- For chat completions use $client->chat()->create([...]). For legacy text completions use $client->completions()->create([...]). Do not chain $client->chat()->completions()->create([...]).
- AppConfig::openAiClient requires only ai-assistant.api_key. The organization id is optional. If provided, it will be used; otherwise the client is created with API key only.
- Configuration uses the key max_completion_tokens. AppConfig maps this to the OpenAI API parameter max_tokens in request payloads.

### Model Settings

Configure your default models for different operations:

```php
'model' => env('OPENAI_CHAT_MODEL', 'gpt-3.5-turbo'),
'chat_model' => env('OPENAI_CHAT_MODEL', 'gpt-3.5-turbo'),
'edit_model' => 'gpt-4o',
'audio_model' => 'whisper-1',
```

### Advanced Configuration

Configure advanced features like streaming, caching, and security:

```php
// Streaming configuration
'streaming' => [
    'enabled' => true,
    'buffer_size' => 1024,
    'timeout' => 30,
    'memory_limit_mb' => 256,
],

// Background jobs configuration
'background_jobs' => [
    'enabled' => true,
    'queue' => 'ai-operations',
    'timeout' => 300,
    'max_tries' => 3,
],

// Security configuration
'security' => [
    'rate_limiting' => [
        'enabled' => true,
        'max_requests' => 100,
        'time_window' => 3600,
    ],
    'api_key_validation' => true,
    'request_signing' => false,
],

// Metrics collection
'metrics' => [
    'enabled' => true,
    'track_response_times' => true,
    'track_token_usage' => true,
    'track_error_rates' => true,
],
```

---

## Core Usage

### Initializing the Assistant

Create a new assistant instance using the fluent API:

```php
use CreativeCrafts\LaravelAiAssistant\AiAssistant;

$assistant = AiAssistant::init();
```

### Basic Chat Operations

Configure and send chat messages:

```php
$response = AiAssistant::init()
    ->setModelName('gpt-4')
    ->adjustTemperature(0.7)
    ->setDeveloperMessage('Please maintain a friendly tone.')
    ->setUserMessage('What is the weather like today?')
    ->sendChatMessage();
```

### Audio Transcription

Transcribe audio files with language specification:

```php
$transcription = AiAssistant::init()
    ->setFilePath('/path/to/audio.mp3')
    ->transcribeTo('en', 'Transcribe the following audio:');
```

### Creating AI Assistants

Create and configure AI assistants with custom tools:

```php
$assistant = AiAssistant::init()
    ->setModelName('gpt-4')
    ->adjustTemperature(0.5)
    ->setAssistantName('My Assistant')
    ->setAssistantDescription('An assistant for handling tasks')
    ->setInstructions('Be as helpful as possible.')
    ->includeFunctionCallTool(
        'calculateSum',
        'Calculates the sum of two numbers',
        ['num1' => 'number', 'num2' => 'number'],
        isStrict: true,
        requiredParameters: ['num1', 'num2']
    )
    ->create();
```

---

## Advanced Features

### Streaming Service

#### SSE streaming with `StreamedAiResponse`

Use the built-in SSE helper to stream tokens/events to the browser.

**Controller:**

```php
use CreativeCrafts\LaravelAiAssistant\Http\Responses\StreamedAiResponse;
use CreativeCrafts\LaravelAiAssistant\Facades\Ai;
use Illuminate\Http\Request;

class StreamingController
{
    public function __invoke(Request $request)
    {
        $prompt = (string) $request->input('q', 'Say hello and count to 10.');
        // Yields string deltas or arrays like ['type' => 'message', 'data' => '...']
        $gen = Ai::chat($prompt)->stream();

        return StreamedAiResponse::fromGenerator($gen);
    }
}
```

Frontend (Alpine.js example):

```html

<div x-data="{ out: '' }" x-init="
    const es = new EventSource('/ai/stream');
    es.addEventListener('message', e => { this.out += e.data; });
    es.addEventListener('done', () => es.close());
">
    <pre x-text="out"></pre>
</div>
```

The streaming service provides real-time response processing with advanced memory management and performance monitoring.

#### Basic Streaming Usage

```php
use CreativeCrafts\LaravelAiAssistant\Services\StreamingService;

$streamingService = app(StreamingService::class);

// Process a stream with automatic buffering
foreach ($streamingService->processStream($apiStream, 'chat_completion') as $chunk) {
    echo $chunk['content'];
    // Real-time processing of each chunk
}
```

#### Advanced Streaming Configuration

```php
// Configure streaming with custom buffer size and chunk processing
$streamingService = app(StreamingService::class);

$customProcessor = function($chunk) {
    // Custom processing logic
    return strtoupper($chunk['content']);
};

foreach ($streamingService->processStream($stream, 'text_completion', $customProcessor) as $chunk) {
    // Process transformed chunks
    broadcast(new StreamChunkEvent($chunk));
}
```

#### Streaming Metrics

```php
$metrics = $streamingService->getStreamingMetrics();
// Returns: active_streams, total_streams_processed, average_stream_size_mb, total_data_processed_mb, streaming_errors

$capabilities = $streamingService->validateStreamingCapabilities();
// Returns system capabilities and limits
```

### Background Jobs

Queue long-running AI operations with progress tracking and retry mechanisms.

#### Queuing Operations

```php
use CreativeCrafts\LaravelAiAssistant\Services\BackgroundJobService;

$jobService = app(BackgroundJobService::class);

// Queue a long-running operation
$jobId = $jobService->queueLongRunningOperation('large_text_processing', [
    'text' => $largeText,
    'model' => 'gpt-4',
    'temperature' => 0.7
], [
    'queue' => 'high-priority',
    'timeout' => 600,
    'max_tries' => 5
]);

echo "Job queued with ID: $jobId";
```

#### Batch Operations

```php
// Process multiple items in batches
$batchJobIds = $jobService->queueBatchOperation('translate_texts', [
    ['text' => 'Hello world', 'to' => 'es'],
    ['text' => 'How are you?', 'to' => 'fr'],
    ['text' => 'Good morning', 'to' => 'de']
], 10, ['queue' => 'translations']);
```

#### Job Monitoring

```php
// Check job status
$status = $jobService->getJobStatus($jobId);
/*
Returns:
[
    'job_id' => 'job_123',
    'operation' => 'large_text_processing',
    'status' => 'processing', // queued, processing, completed, failed, cancelled
    'progress' => 75,
    'created_at' => '2024-01-01T10:00:00Z',
    'started_at' => '2024-01-01T10:00:05Z',
    'completed_at' => null,
    'duration_seconds' => 42.1,
    'result' => null,
    'error' => null,
    'retry_count' => 1
]
*/

// Get queue statistics
$stats = $jobService->getQueueStatistics();
/*
Returns:
[
    'total_jobs' => 150,
    'queued_jobs' => 5,
    'processing_jobs' => 3,
    'completed_jobs' => 140,
    'failed_jobs' => 2,
    'cancelled_jobs' => 1,
    'average_processing_time' => 45.2,
    'success_rate_percent' => 98.6
]
*/
```

### Lazy Loading

Optimize resource usage with intelligent lazy initialization of AI models and HTTP clients.

#### Resource Registration

```php
use CreativeCrafts\LaravelAiAssistant\Services\LazyLoadingService;

$lazyService = app(LazyLoadingService::class);

// Register a lazy-loaded resource
$lazyService->registerLazyResource('heavy_model', function() {
    return new ExpensiveAiModel();
}, [
    'ttl' => 3600,  // Cache for 1 hour
    'preload' => false,
    'category' => 'ai_models'
]);

// Get resource (loads only when first accessed)
$model = $lazyService->getResource('heavy_model');
```

#### AI Model Lazy Loading

```php
// Register multiple AI models for lazy loading
$lazyService->registerAiModels([
    'gpt-4' => ['type' => 'chat', 'context_size' => 8192],
    'gpt-3.5-turbo' => ['type' => 'chat', 'context_size' => 4096],
    'whisper-1' => ['type' => 'audio', 'languages' => ['en', 'es', 'fr']]
]);

// Register HTTP clients with different configurations
$lazyService->registerHttpClients([
    'standard' => ['timeout' => 30, 'pool_size' => 10],
    'streaming' => ['timeout' => 300, 'stream' => true],
    'batch' => ['timeout' => 600, 'concurrent' => 5]
]);
```

#### Performance Metrics

```php
$metrics = $lazyService->getLazyLoadingMetrics();
/*
Returns:
[
    'total_resources' => 15,
    'loaded_resources' => 8,
    'cache_hits' => 45,
    'cache_misses' => 12,
    'average_load_time' => 2.3,
    'memory_saved_mb' => 124.5,
    'cache_hit_rate' => 78.9
]
*/
```

### Metrics Collection

Comprehensive monitoring and analytics for API usage, performance, and costs.

#### API Monitoring

```php
use CreativeCrafts\LaravelAiAssistant\Services\MetricsCollectionService;

$metricsService = app(MetricsCollectionService::class);

// Automatic recording (handled internally)
// Manual recording for custom operations
$metricsService->recordApiCall('/chat/completions', 1.5, 200, [
    'model' => 'gpt-4',
    'tokens' => 150
]);

$metricsService->recordTokenUsage('chat_completion', 100, 50, 'gpt-4');
```

#### Performance Analysis

```php
// Get API performance summary
$performance = $metricsService->getApiPerformanceSummary('/chat/completions', 24);
/*
Returns:
[
    'total_calls' => 1250,
    'average_response_time' => 1.8,
    'success_rate' => 99.2,
    'error_rate' => 0.8,
    'p95_response_time' => 3.2,
    'total_errors' => 10
]
*/

// Get token usage summary
$tokenUsage = $metricsService->getTokenUsageSummary(24);
/*
Returns:
[
    'total_tokens' => 125000,
    'prompt_tokens' => 75000,
    'completion_tokens' => 50000,
    'estimated_cost_usd' => 2.50,
    'by_model' => [
        'gpt-4' => ['tokens' => 50000, 'cost' => 1.00],
        'gpt-3.5-turbo' => ['tokens' => 75000, 'cost' => 0.15]
    ]
]
*/
```

#### System Health Monitoring

```php
// System health is recorded automatically
$health = $metricsService->getSystemHealthSummary(1);
/*
Returns:
[
    'status' => 'healthy', // healthy, warning, critical
    'uptime_seconds' => 86400,
    'memory_usage_mb' => 512,
    'cpu_usage_percent' => 15.3,
    'disk_usage_percent' => 68.2,
    'active_connections' => 25,
    'error_rate_percent' => 0.5
]
*/
```

### Security Features

Advanced security controls including API key validation, rate limiting, and request integrity.

#### API Key Validation

```php
use CreativeCrafts\LaravelAiAssistant\Services\SecurityService;

$securityService = app(SecurityService::class);

// Validate API key format and structure
if ($securityService->validateApiKey($apiKey)) {
    echo "API key is valid";
}

// Validate organization ID
if ($securityService->validateOrganizationId($orgId)) {
    echo "Organization ID is valid";
}
```

#### Webhook signature verification

Incoming webhooks to the package endpoint are protected by the `verify.ai.webhook` middleware. If you build your own controller/endpoint, apply it explicitly:

```php
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AiWebhookController;

Route::post('/ai/webhook', [AiWebhookController::class, 'handle'])
    ->middleware('verify.ai.webhook');
```

Environment/config:

```env
AI_ASSISTANT_WEBHOOK_SIGNING_SECRET=base64:GENERATE_A_STRONG_VALUE
```

```php
// config/ai-assistant.php
'webhooks' => [
    'signing_secret' => env('AI_ASSISTANT_WEBHOOK_SIGNING_SECRET', ''),
    'signature_header' => 'X-AI-Signature',
],
```

#### Rate Limiting

```php
// Check rate limit before making API call
$identifier = "user_" . auth()->id();
$canProceed = $securityService->checkRateLimit($identifier, 100, 3600); // 100 requests per hour

if ($canProceed) {
    // Apply rate limiting to operation
    $result = $securityService->applyRateLimit($identifier, function() {
        return AiAssistant::init()->sendChatMessage();
    }, 100, 3600);
} else {
    throw new RateLimitExceededException("Rate limit exceeded");
}
```

#### Request Integrity

```php
// Generate request signature
$payload = ['message' => 'Hello AI', 'model' => 'gpt-4'];
$secret = config('app.key');
$timestamp = time();

$signature = $securityService->generateRequestSignature($payload, $secret, $timestamp);

// Verify request signature
$isValid = $securityService->verifyRequestSignature(
    $payload, 
    $signature, 
    $secret, 
    $timestamp, 
    300 // 5 minute tolerance
);
```

#### Data Sanitization

```php
// Sanitize sensitive data before logging
$sensitiveData = [
    'api_key' => 'sk-secret123',
    'user_email' => 'user@example.com',
    'message' => 'Hello world',
    'organization' => 'org-secret456'
];

$sanitized = $securityService->sanitizeSensitiveData($sensitiveData, [
    'api_key', 'organization', 'password', 'secret'
]);
/*
Returns:
[
    'api_key' => '***REDACTED***',
    'user_email' => 'user@example.com',
    'message' => 'Hello world',
    'organization' => '***REDACTED***'
]
*/
```

### Performance Optimizations

#### Memory Management

```php
use CreativeCrafts\LaravelAiAssistant\Services\MemoryMonitoringService;

$memoryService = app(MemoryMonitoringService::class);

// Monitor memory usage during operations
$memoryReport = $memoryService->getMemoryReport();
/*
Returns:
[
    'current_usage_mb' => 45.2,
    'peak_usage_mb' => 67.8,
    'limit_mb' => 256,
    'usage_percent' => 17.7,
    'available_mb' => 210.8
]
*/

// Set memory alerts
$memoryService->setMemoryAlert(200, function($usage) {
    Log::warning("High memory usage detected: {$usage}MB");
});
```

#### Caching Strategies

```php
use CreativeCrafts\LaravelAiAssistant\Services\CacheService;

$cache = app(CacheService::class);

// Response caching (array payloads)
$data = $cache->rememberResponse('users:list', function () {
    // compute and return an array
    return ['items' => [1, 2, 3]];
}, 300);

// Completion caching (string payloads)
$text = $cache->rememberCompletion(
    'Explain queues in one sentence.',
    'gpt-5',
    ['tone' => 'concise'],
    fn () => 'Queues let you defer time-consuming tasks.',
    300
);

// Clearing safely (never use Cache::flush())
$cache->deleteResponse('users:list');
$cache->clearResponses();
$cache->clearCompletions();
```

For a complete guide, see docs/cache.md.

##### Advanced Caching Configuration

Configure caching for optimal performance in your environment variables:

```bash
# Enable lazy loading with caching
AI_LAZY_LOADING_ENABLED=true
AI_LAZY_CACHE_DURATION=3600

# Connection pooling for better performance
AI_CONNECTION_POOL_ENABLED=true
AI_MAX_CONNECTIONS=100
AI_MAX_CONNECTIONS_PER_HOST=10

# Memory monitoring
AI_MEMORY_MONITORING_ENABLED=true
AI_MEMORY_THRESHOLD_MB=256
AI_LOG_MEMORY_USAGE=true
```

##### Cache Best Practices

```php
// Use appropriate TTL based on content type
$cacheService->cacheResponse('static_content', $response, 86400); // 24 hours
$cacheService->cacheResponse('dynamic_content', $response, 300);  // 5 minutes

// Implement cache warming for frequently accessed data
$cacheService->cacheConfig('popular_prompts', $popularPrompts, 7200);

// Use hierarchical cache keys for better organization
$cacheKey = "ai_response:{$userId}:{$modelName}:" . md5($prompt);
```

#### Queue Configuration for Background Jobs

Configure background job processing for better scalability:

##### Environment Configuration

```bash
# Enable background job processing
AI_BACKGROUND_JOBS_ENABLED=true
AI_QUEUE_NAME=ai-assistant
AI_QUEUE_CONNECTION=redis

# Job configuration
AI_JOB_TIMEOUT=300
AI_JOB_RETRY_AFTER=90
AI_JOB_MAX_TRIES=3

# Tool calling execution via queue
AI_TOOL_CALLING_EXECUTOR=queue
AI_TOOL_CALLING_PARALLEL=true
```

##### Queue Setup Examples

```php
// Process large operations in background
use CreativeCrafts\LaravelAiAssistant\Jobs\ProcessAiRequestJob;

// Dispatch AI processing to queue
ProcessAiRequestJob::dispatch($requestData)
    ->onQueue(config('ai-assistant.background_jobs.queue'))
    ->delay(now()->addSeconds(5));

// Batch processing for multiple requests
$jobs = collect($requests)->map(function ($request) {
    return new ProcessAiRequestJob($request);
});

Bus::batch($jobs)
    ->then(function (Batch $batch) {
        // All jobs completed successfully
    })
    ->catch(function (Batch $batch, Throwable $e) {
        // First batch job failure
    })
    ->finally(function (Batch $batch) {
        // Batch has finished executing
    })
    ->dispatch();
```

##### Redis Queue Configuration

```php
// config/queue.php
'connections' => [
    'redis' => [
        'driver' => 'redis',
        'connection' => 'default',
        'queue' => env('REDIS_QUEUE', 'default'),
        'retry_after' => 90,
        'block_for' => null,
        'after_commit' => false,
    ],
],

// Dedicated connection for AI Assistant
'ai-assistant-redis' => [
    'driver' => 'redis',
    'connection' => 'ai-assistant',
    'queue' => 'ai-assistant',
    'retry_after' => 300,
    'block_for' => null,
],
```

#### Load Testing and Performance Verification

##### Performance Benchmarking

Run the included performance tests to verify package performance:

```bash
# Run performance tests
composer test -- --group=performance

# Run specific performance test
vendor/bin/pest tests/Performance/AssistantServicePerformanceTest.php

# Generate performance report
vendor/bin/pest tests/Performance/ --coverage
```

##### Load Testing Setup

Create load testing scenarios for your application:

```php
// tests/Performance/LoadTest.php
use CreativeCrafts\LaravelAiAssistant\AiAssistant;

public function test_concurrent_requests_performance()
{
    $concurrentRequests = 50;
    $promises = [];
    
    $startTime = microtime(true);
    
    for ($i = 0; $i < $concurrentRequests; $i++) {
        $promises[] = AiAssistant::init()
            ->setUserMessage("Test message {$i}")
            ->sendChatMessage();
    }
    
    // Wait for all requests to complete
    $results = collect($promises)->map(fn($promise) => $promise);
    
    $duration = microtime(true) - $startTime;
    $avgResponseTime = $duration / $concurrentRequests;
    
    $this->assertLessThan(5.0, $avgResponseTime, 'Average response time should be under 5 seconds');
}
```

##### Production Load Testing

Use tools like Apache Bench or Siege for production load testing:

```bash
# Test API endpoint with Apache Bench
ab -n 1000 -c 10 -H "Authorization: Bearer your-token" \
   -p post_data.json -T application/json \
   https://yourapp.com/api/ai-assistant/chat

# Test with Siege
siege -c 20 -t 60s -H "Authorization: Bearer your-token" \
      --content-type="application/json" \
      'https://yourapp.com/api/ai-assistant/chat POST < post_data.json'
```

##### Performance Monitoring

Set up comprehensive metrics collection:

```bash
# Enable metrics collection
AI_METRICS_ENABLED=true
AI_METRICS_DRIVER=database  # or redis, log
AI_TRACK_RESPONSE_TIMES=true
AI_TRACK_TOKEN_USAGE=true
AI_TRACK_ERROR_RATES=true

# Health checks
AI_HEALTH_CHECKS_ENABLED=true
AI_HEALTH_CHECK_ROUTE_PREFIX=/ai-assistant/health
```

```php
// Monitor performance metrics
use CreativeCrafts\LaravelAiAssistant\Services\MetricsService;

$metrics = app(MetricsService::class);
$performanceData = $metrics->getPerformanceMetrics();

/*
Returns:
[
    'avg_response_time' => 1.2,
    'total_requests' => 1500,
    'error_rate' => 0.02,
    'token_usage' => [
        'total' => 45000,
        'avg_per_request' => 30
    ],
    'cache_hit_rate' => 0.85
]
*/
```

#### Scaling Best Practices

1. **Queue Management**: Use dedicated queue workers for AI processing
2. **Connection Pooling**: Enable HTTP connection pooling for better throughput
3. **Caching Strategy**: Implement multi-layer caching (Redis + Application)
4. **Resource Monitoring**: Set up alerts for memory and performance thresholds
5. **Load Balancing**: Distribute requests across multiple application instances
6. **Rate Limiting**: Implement appropriate rate limiting for API endpoints

---

## Error Handling & Exceptions

The package includes 8+ custom exception types for precise error handling:

### Exception Types

```php
use CreativeCrafts\LaravelAiAssistant\Exceptions\{
    ApiResponseValidationException,
    ConfigurationValidationException,
    CreateNewAssistantException,
    FileOperationException,
    InvalidApiKeyException,
    MaxRetryAttemptsExceededException,
    MissingRequiredParameterException,
    ThreadExecutionTimeoutException
};
```

### Exception Handling Examples

```php
try {
    $assistant = AiAssistant::init()
        ->setModelName('gpt-4')
        ->sendChatMessage();
} catch (InvalidApiKeyException $e) {
    Log::error('Invalid API key provided', ['error' => $e->getMessage()]);
    return response()->json(['error' => 'Authentication failed'], 401);
    
} catch (MaxRetryAttemptsExceededException $e) {
    Log::error('Maximum retry attempts exceeded', ['error' => $e->getMessage()]);
    return response()->json(['error' => 'Service temporarily unavailable'], 503);
    
} catch (ThreadExecutionTimeoutException $e) {
    Log::error('Thread execution timeout', ['error' => $e->getMessage()]);
    return response()->json(['error' => 'Request timeout'], 408);
    
} catch (ApiResponseValidationException $e) {
    Log::error('API response validation failed', ['error' => $e->getMessage()]);
    return response()->json(['error' => 'Invalid response from AI service'], 502);
    
} catch (FileOperationException $e) {
    Log::error('File operation failed', ['error' => $e->getMessage()]);
    return response()->json(['error' => 'File processing error'], 422);
}
```

### Configuration Validation

```php
try {
    // Configuration is validated automatically during service provider boot
    $config = config('ai-assistant');
} catch (ConfigurationValidationException $e) {
    // Handle configuration errors
    Log::critical('AI Assistant configuration invalid', [
        'error' => $e->getMessage(),
        'config' => $e->getConfigurationErrors()
    ]);
}
```

---

## Best Practices

### Performance Best Practices

1. **Use Streaming for Real-time Applications**
   ```php
   // Enable streaming for real-time chat interfaces
   $streamingService = app(StreamingService::class);
   foreach ($streamingService->processStream($stream, 'chat_completion') as $chunk) {
       broadcast(new MessageChunkEvent($chunk));
   }
   ```

2. **Queue Long-running Operations**
   ```php
   // Queue operations that take more than 30 seconds
   $jobService = app(BackgroundJobService::class);
   $jobId = $jobService->queueLongRunningOperation('bulk_processing', $data);
   ```

3. **Implement Lazy Loading for Resources**
   ```php
   // Register expensive resources for lazy loading
   $lazyService = app(LazyLoadingService::class);
   $lazyService->registerLazyResource('expensive_model', $initializer, ['ttl' => 3600]);
   ```

### Security Best Practices

1. **Always Validate API Keys**
   ```php
   $securityService = app(SecurityService::class);
   if (!$securityService->validateApiKey($apiKey)) {
       throw new InvalidApiKeyException('Invalid API key format');
   }
   ```

2. **Implement Rate Limiting**
   ```php
   // Implement per-user rate limiting
   $canProceed = $securityService->checkRateLimit("user_{$userId}", 100, 3600);
   ```

3. **Sanitize Sensitive Data in Logs**
   ```php
   $sanitizedData = $securityService->sanitizeSensitiveData($data, [
       'api_key', 'organization', 'password'
   ]);
   Log::info('API call made', $sanitizedData);
   ```

### Monitoring Best Practices

1. **Enable Comprehensive Metrics**
   ```php
   // Configure in config/ai-assistant.php
   'metrics' => [
       'enabled' => true,
       'track_response_times' => true,
       'track_token_usage' => true,
       'track_error_rates' => true,
   ]
   ```

2. **Set Up Health Monitoring**
   ```php
   $metricsService = app(MetricsCollectionService::class);
   $health = $metricsService->getSystemHealthSummary(1);
   
   if ($health['status'] === 'critical') {
       // Alert administrators
       Mail::to('admin@example.com')->send(new SystemHealthAlert($health));
   }
   ```

3. **Monitor Token Usage and Costs**
   ```php
   $tokenUsage = $metricsService->getTokenUsageSummary(24);
   if ($tokenUsage['estimated_cost_usd'] > $dailyBudget) {
       // Implement cost controls
       Log::warning('Daily AI budget exceeded', $tokenUsage);
   }
   ```

---

### Cookbook

#### Chat with JSON output

```php
use CreativeCrafts\LaravelAiAssistant\Facades\Ai;

$schema = [
    '$schema' => 'https://json-schema.org/draft/2020-12/schema',
    'type' => 'object',
    'properties' => [
        'title' => ['type' => 'string'],
        'bullets' => ['type' => 'array', 'items' => ['type' => 'string']],
    ],
    'required' => ['title', 'bullets'],
    'additionalProperties' => false,
];

$response = Ai::chat('Summarize SOLID principles as JSON')
    ->setResponseFormatJsonSchema($schema, 'answer')
    ->send();

// Normalized text if the model returns textual content
$textOutput = $response->content;

// Or inspect the raw normalized envelope data as an array
$raw = $response->toArray();
```

Tips:

- Prefer Ai::chat() for modern Responses + Conversations flows.
- setToolChoice('none') if you want pure JSON without tool calls.

#### Function calling with a Laravel job executor (queue)

This recipe demonstrates enabling queued function (tool) execution. The executor dispatches tool calls as jobs and returns an inline result for determinism while the job runs in your queue.

1) Configure the executor to queue

- In .env:

```
AI_TOOL_CALLING_EXECUTOR=queue
AI_QUEUE_CONNECTION=redis   # or database, sqs, etc.
AI_QUEUE_NAME=ai-tools
```

- Or dynamically in code (e.g., for tests or one-off scripts):

```php
config([
    'ai-assistant.tool_calling.executor' => 'queue',  // 'sync' | 'queue'
    'ai-assistant.background_jobs.queue' => 'ai-tools',
    'queue.default' => env('AI_QUEUE_CONNECTION', 'sync'),
]);
```

2) Define a callable tool and let the assistant invoke it

```php
use CreativeCrafts\LaravelAiAssistant\AiAssistant;

$assistant = AiAssistant::init()
    ->startConversation()
    ->includeFunctionCallTool(
        'getWeather',
        'Gets current weather for a city',
        [
            'type' => 'object',
            'properties' => [ 'city' => ['type' => 'string'] ],
            'required' => ['city'],
            'additionalProperties' => false,
        ],
        isStrict: true
    )
    ->setToolChoice('auto')
    ->setUserMessage('What is the weather in Paris?');

$result = $assistant->sendChatMessage();

// If the model requested a tool call, you can continue the turn with tool results
$toolCalls = $result['toolCalls'] ?? [];
if ($toolCalls !== []) {
    $call = $toolCalls[0];

    // Execute your domain logic (this can also be in a Job handler for full control)
    $weather = [ 'temp_c' => 21, 'condition' => 'Cloudy' ];

    $followUp = AiAssistant::init()
        ->useConversation($result['conversationId'])
        ->continueWithToolResults([
            [
                'tool_call_id' => $call['id'],
                'output' => json_encode($weather),
            ],
        ]);
}
```

Notes:

- With AI_TOOL_CALLING_EXECUTOR=queue, the package dispatches ExecuteToolCallJob internally (requires a working queue).
- For local development, set executor to sync to avoid queue setup: AI_TOOL_CALLING_EXECUTOR=sync.
- Related config: AI_TOOL_CALLING_MAX_ROUNDS, AI_TOOL_CALLING_PARALLEL.

Troubleshooting:

- If jobs don’t run, verify your queue worker and connection settings.
- Check logs: the executor logs dispatch events with correlation IDs.

#### Attach files and enable file search (vector stores)

```php
use CreativeCrafts\LaravelAiAssistant\AiAssistant;

$result = AiAssistant::init()
    ->startConversation()
    ->includeFileSearchTool(['vector_store_789'])  // pass vector store IDs
    ->attachFilesToTurn(['file_abc123'])           // optional file IDs already uploaded
    ->setUserMessage('Answer using the attached policy file and related knowledge base.')
    ->sendChatMessage();
```

Variants:

- Disable automatic file_search while still attaching files:

```php
$result = AiAssistant::init()
    ->startConversation()
    ->useFileSearch(false)
    ->attachFilesToTurn(['file_abc123'])
    ->setUserMessage('Do not use RAG; answer generally.')
    ->sendChatMessage();
```

---

## 📊 Performance Benchmarks

Laravel AI Assistant is optimized for enterprise-scale applications. Below are performance metrics and optimization recommendations:

### Benchmark Results

#### Response Times (Average)

- **Simple Chat Message:** ~800ms-1.2s (depends on model and complexity)
- **Streaming Response:** First token ~400ms, subsequent tokens ~50-100ms
- **File Processing:** ~2-5s per MB (varies by file type)
- **Background Jobs:** Queue dispatch ~50ms, processing time varies

#### Throughput (With Connection Pooling)

- **Concurrent Requests:** Up to 100 simultaneous connections
- **Requests per Second:** 50-150 RPS (depends on response complexity)
- **Memory Usage:** ~10-50MB per active request
- **Connection Reuse:** 80-90% efficiency with pooling enabled

### Performance Optimization Tips

#### 1. Enable Connection Pooling

```env
AI_CONNECTION_POOL_ENABLED=true
AI_MAX_CONNECTIONS=100
AI_MAX_CONNECTIONS_PER_HOST=10
```

**Impact:** 30-50% improvement in response times for multiple requests

#### 2. Use Background Jobs for Long Operations

```env
AI_BACKGROUND_JOBS_ENABLED=true
AI_QUEUE_NAME=ai-assistant
```

**Impact:** Prevents blocking the main thread, improves user experience

#### 3. Optimize Streaming Configuration

```env
AI_STREAMING_ENABLED=true
AI_STREAMING_BUFFER_SIZE=8192
AI_STREAMING_CHUNK_SIZE=1024
```

**Impact:** 60-80% faster perceived response times for long responses

#### 4. Configure Memory Monitoring

```env
AI_MEMORY_MONITORING_ENABLED=true
AI_MEMORY_THRESHOLD_MB=256
```

**Impact:** Prevents memory exhaustion, maintains system stability

#### 5. Enable Metrics Collection

```env
AI_METRICS_ENABLED=true
AI_METRICS_DRIVER=redis  # or database
```

**Impact:** Provides insights for further optimization

### Model-Specific Performance

| Model         | Avg Response Time | Tokens/Sec | Best Use Case                       |
|---------------|-------------------|------------|-------------------------------------|
| `gpt-5`       | 1.2s              | 80-120     | Complex reasoning, latest features  |
| `gpt-4o`      | 0.8s              | 120-180    | Balanced performance and capability |
| `gpt-4o-mini` | 0.4s              | 200-300    | Simple tasks, high throughput       |

### Scaling Recommendations

#### Small Applications (< 1,000 requests/day)

```env
AI_CONNECTION_POOL_ENABLED=false
AI_BACKGROUND_JOBS_ENABLED=false
AI_METRICS_ENABLED=false
```

#### Medium Applications (1,000 - 50,000 requests/day)

```env
AI_CONNECTION_POOL_ENABLED=true
AI_MAX_CONNECTIONS=50
AI_BACKGROUND_JOBS_ENABLED=true
AI_METRICS_ENABLED=true
AI_METRICS_DRIVER=database
```

#### Large Applications (> 50,000 requests/day)

```env
AI_CONNECTION_POOL_ENABLED=true
AI_MAX_CONNECTIONS=100
AI_BACKGROUND_JOBS_ENABLED=true
AI_QUEUE_CONNECTION=redis
AI_METRICS_ENABLED=true
AI_METRICS_DRIVER=redis
AI_LAZY_LOADING_ENABLED=true
```

### Monitoring & Profiling

The package provides built-in performance monitoring:

```php
use CreativeCrafts\LaravelAiAssistant\Services\Metrics;

// Get performance metrics
$metrics = app(Metrics::class);
$stats = $metrics->getStats();

echo "Average Response Time: " . $stats['avg_response_time'] . "ms\n";
echo "Total Requests: " . $stats['total_requests'] . "\n";
echo "Error Rate: " . ($stats['error_rate'] * 100) . "%\n";
```

---

## Examples

### Example 1: Building a Chat Interface with Streaming

```php
use CreativeCrafts\LaravelAiAssistant\AiAssistant;
use CreativeCrafts\LaravelAiAssistant\Services\StreamingService;

class ChatController extends Controller
{
    public function streamChat(Request $request)
    {
        $streamingService = app(StreamingService::class);
        
        // Validate and rate limit
        $securityService = app(SecurityService::class);
        $userId = auth()->id();
        
        if (!$securityService->checkRateLimit("chat_user_{$userId}", 50, 3600)) {
            return response()->json(['error' => 'Rate limit exceeded'], 429);
        }
        
        // Create streaming response
        return response()->stream(function() use ($request, $streamingService) {
            $assistant = AiAssistant::init()
                ->setModelName('gpt-4')
                ->adjustTemperature(0.7)
                ->setUserMessage($request->input('message'));
            
            $stream = $assistant->getStreamingResponse();
            
            foreach ($streamingService->processStream($stream, 'chat_completion') as $chunk) {
                echo "data: " . json_encode(['content' => $chunk['content']]) . "\n\n";
                ob_flush();
                flush();
            }
        }, 200, [
            'Content-Type' => 'text/event-stream',
            'Cache-Control' => 'no-cache',
        ]);
    }
}
```

### Example 2: Background Processing with Progress Tracking

```php
use CreativeCrafts\LaravelAiAssistant\Services\BackgroundJobService;

class DocumentProcessor
{
    public function processBulkDocuments(array $documents)
    {
        $jobService = app(BackgroundJobService::class);
        
        // Queue batch processing
        $jobIds = $jobService->queueBatchOperation('process_document', 
            array_map(fn($doc) => ['path' => $doc['path'], 'format' => $doc['format']], $documents),
            5, // Process 5 at a time
            [
                'queue' => 'document-processing',
                'timeout' => 600,
                'max_tries' => 3
            ]
        );
        
        return [
            'message' => 'Documents queued for processing',
            'job_ids' => $jobIds,
            'total_documents' => count($documents)
        ];
    }
    
    public function getProcessingStatus(array $jobIds)
    {
        $jobService = app(BackgroundJobService::class);
        $statuses = [];
        
        foreach ($jobIds as $jobId) {
            $statuses[$jobId] = $jobService->getJobStatus($jobId);
        }
        
        $completed = collect($statuses)->where('status', 'completed')->count();
        $total = count($statuses);
        
        return [
            'overall_progress' => ($completed / $total) * 100,
            'job_statuses' => $statuses,
            'queue_stats' => $jobService->getQueueStatistics()
        ];
    }
}
```

### Example 3: Advanced AI Assistant with Custom Tools

```php
use CreativeCrafts\LaravelAiAssistant\AiAssistant;

class AdvancedAiService
{
    public function createSmartAssistant()
    {
        return AiAssistant::init()
            ->setModelName('gpt-4')
            ->adjustTemperature(0.3)
            ->setAssistantName('Smart Business Assistant')
            ->setAssistantDescription('An AI assistant specialized in business operations')
            ->setInstructions('You are a business expert. Provide clear, actionable advice.')
            
            // Add code interpreter for data analysis
            ->includeCodeInterpreterTool(['file_12345'])
            
            // Add file search for document retrieval
            ->includeFileSearchTool(['vector_store_789'])
            
            // Add custom business calculation functions
            ->includeFunctionCallTool(
                'calculate_roi',
                'Calculate return on investment',
                [
                    'initial_investment' => 'number',
                    'final_value' => 'number',
                    'time_period_years' => 'number'
                ],
                isStrict: true,
                requiredParameters: ['initial_investment', 'final_value', 'time_period_years']
            )
            
            // Add inventory management function
            ->includeFunctionCallTool(
                'check_inventory',
                'Check product inventory levels',
                [
                    'product_id' => 'string',
                    'location' => 'string'
                ],
                isStrict: true,
                requiredParameters: ['product_id']
            )
            
            ->create();
    }
    
    public function processBusinessQuery($assistantId, $query)
    {
        // Use lazy loading for the assistant interaction
        $lazyService = app(LazyLoadingService::class);
        
        $result = $lazyService->getResource('business_assistant_' . $assistantId, function() use ($assistantId, $query) {
            return AiAssistant::init()
                ->setAssistantId($assistantId)
                ->createTask()
                ->askQuestion($query)
                ->process()
                ->response();
        });
        
        return $result;
    }
}
```

### Example 4: Comprehensive Monitoring Dashboard

```php
use CreativeCrafts\LaravelAiAssistant\Services\MetricsCollectionService;

class AiDashboardController extends Controller
{
    public function getDashboardData()
    {
        $metricsService = app(MetricsCollectionService::class);
        $hours = 24; // Last 24 hours
        
        return [
            'api_performance' => [
                'chat_completions' => $metricsService->getApiPerformanceSummary('/chat/completions', $hours),
                'audio_transcriptions' => $metricsService->getApiPerformanceSummary('/audio/transcriptions', $hours),
                'assistants' => $metricsService->getApiPerformanceSummary('/assistants', $hours),
            ],
            
            'token_usage' => $metricsService->getTokenUsageSummary($hours),
            'system_health' => $metricsService->getSystemHealthSummary($hours),
            
            'streaming_metrics' => app(StreamingService::class)->getStreamingMetrics(),
            'lazy_loading_metrics' => app(LazyLoadingService::class)->getLazyLoadingMetrics(),
            'job_queue_stats' => app(BackgroundJobService::class)->getQueueStatistics(),
            
            'cost_analysis' => [
                'estimated_daily_cost' => $metricsService->calculateEstimatedCost($hours),
                'cost_by_model' => $metricsService->getTokenUsageByModel($hours),
                'cost_by_operation' => $metricsService->getTokenUsageByOperation($hours),
            ]
        ];
    }
}
```

---

## Environment Variables

Add the following to your `.env` file:

```bash
# Required
OPENAI_API_KEY=your_openai_api_key
OPENAI_ORGANIZATION=your_organization_id
OPENAI_CHAT_MODEL=gpt-3.5-turbo

# Persistence
AI_ASSISTANT_PERSISTENCE_DRIVER=memory # or 'eloquent'

# Optional advanced configuration
AI_ASSISTANT_STREAMING_ENABLED=true
AI_ASSISTANT_JOBS_ENABLED=true
AI_ASSISTANT_METRICS_ENABLED=true
AI_ASSISTANT_SECURITY_RATE_LIMITING=true
AI_ASSISTANT_LAZY_LOADING_ENABLED=true
```

Notes:

- If you set `AI_ASSISTANT_PERSISTENCE_DRIVER=eloquent`, publish and run the package migrations:

```bash
php artisan vendor:publish --tag="ai-assistant-migrations"
php artisan migrate
```

- You can optionally publish Eloquent model stubs for customization:

```bash
php artisan vendor:publish --tag="ai-assistant-models"
```

- With `hasMigrations([...])`, the package can also load migrations without publishing; publish only if you need to customize.

---

## 🔧 Troubleshooting

Common issues and their solutions when working with Laravel AI Assistant:

### Installation & Configuration Issues

#### Issue: "Class 'OpenAI\Client' not found"

**Solution:**

```bash
composer require openai-php/client
composer dump-autoload
```

#### Issue: "OPENAI_API_KEY environment variable is required"

**Solution:**

1. Add your API key to `.env`:
   ```env
   OPENAI_API_KEY=sk-your-actual-api-key-here
   ```
2. Clear config cache:
   ```bash
   php artisan config:clear
   ```

#### Issue: Service provider not loading

**Solution:**

```bash
# Clear all caches
php artisan config:clear
php artisan cache:clear
php artisan route:clear

# Re-discover packages
composer dump-autoload
```

### Runtime Issues

#### Issue: "Connection timeout" errors

**Symptoms:** Requests taking too long or timing out
**Solutions:**

1. Increase timeout values:
   ```env
   AI_STREAMING_TIMEOUT=300
   AI_CONNECTION_TIMEOUT=60
   ```
2. Enable connection pooling:
   ```env
   AI_CONNECTION_POOL_ENABLED=true
   ```

#### Issue: High memory usage

**Symptoms:** Memory exhaustion during large operations
**Solutions:**

1. Enable memory monitoring:
   ```env
   AI_MEMORY_MONITORING_ENABLED=true
   AI_MEMORY_THRESHOLD_MB=256
   ```
2. Use background jobs for large operations:
   ```env
   AI_BACKGROUND_JOBS_ENABLED=true
   ```

#### Issue: "Rate limit exceeded" errors

**Symptoms:** 429 HTTP status codes from OpenAI
**Solutions:**

1. Enable retry mechanism:
   ```env
   AI_RESPONSES_RETRY_ENABLED=true
   AI_RESPONSES_RETRY_MAX_ATTEMPTS=3
   ```
2. Implement request queuing:
   ```env
   AI_BACKGROUND_JOBS_ENABLED=true
   AI_QUEUE_NAME=ai-assistant
   ```

#### Issue: Webhook requests rejected (400/401)

**Solutions:**

1. Set `AI_ASSISTANT_WEBHOOK_SIGNING_SECRET` in `.env` and deploy the value across all app instances.
2. Ensure your webhook sender includes an `X-AI-Signature` header with `HMAC-SHA256` over the raw body using the secret.
3. If you implemented a custom endpoint, apply the `verify.ai.webhook` middleware.

### Database & Persistence Issues

#### Issue: "Table doesn't exist" when using Eloquent persistence

**Solution:**

```bash
php artisan vendor:publish --tag="laravel-ai-assistant-migrations"
php artisan migrate
```

#### Issue: Eloquent models not found

**Solution:**

```bash
php artisan vendor:publish --tag="ai-assistant-models"
```

### Performance Issues

#### Issue: Slow response times

**Diagnostic Steps:**

1. Check your model selection:
   ```env
   OPENAI_CHAT_MODEL=gpt-4o-mini  # Faster than gpt-5
   ```
2. Enable streaming for better perceived performance:
   ```env
   AI_STREAMING_ENABLED=true
   ```
3. Use connection pooling:
   ```env
   AI_CONNECTION_POOL_ENABLED=true
   AI_MAX_CONNECTIONS=50
   ```

#### Issue: Queue jobs not processing

**Solution:**

1. Ensure queue worker is running:
   ```bash
   php artisan queue:work --queue=ai-assistant
   ```
2. Check queue configuration:
   ```env
   AI_QUEUE_CONNECTION=redis  # or database
   AI_QUEUE_NAME=ai-assistant
   ```

### API & Authentication Issues

#### Issue: "Invalid API key" errors

**Solutions:**

1. Verify your API key format (starts with `sk-`):
   ```env
   OPENAI_API_KEY=sk-your-48-character-key-here
   ```
2. Check API key permissions in OpenAI dashboard
3. Ensure no extra spaces in `.env` file

#### Issue: Organization access errors

**Solution:**

```env
OPENAI_ORGANIZATION=org-your-organization-id
```

### Streaming Issues

#### Issue: Streaming responses not working

**Solutions:**

1. Check if streaming is enabled:
   ```env
   AI_STREAMING_ENABLED=true
   ```
2. Verify buffer settings:
   ```env
   AI_STREAMING_BUFFER_SIZE=8192
   AI_STREAMING_CHUNK_SIZE=1024
   ```
3. Ensure proper headers in your frontend:
   ```js
   const headers = {
     "Accept": "text/event-stream",
     "Cache-Control": "no-cache"
   };
   ```

### Development & Testing Issues

#### Issue: Tests failing with API key errors

**Solution:**
Use the test configuration override:

```php
// In your test
config(['ai-assistant.api_key' => 'test_key_123']);
config(['ai-assistant.mock_responses' => true]);
```

#### Issue: Mock responses not working

**Solution:**

```env
AI_ASSISTANT_MOCK=true
```

### Debug Mode

Enable debug logging for troubleshooting:

```env
LOG_LEVEL=debug
AI_METRICS_ENABLED=true
AI_LOG_MEMORY_USAGE=true
```

Then check your Laravel logs:

```bash
tail -f storage/logs/laravel.log
```

### Getting Help

If you're still experiencing issues:

1. **Check the logs:** Laravel logs often contain detailed error information
2. **Review configuration:** Compare your config with the defaults
3. **Test with minimal setup:** Try with basic configuration first
4. **Check GitHub issues:** Search existing issues for similar problems
5. **Create an issue:** Provide detailed error messages and configuration

### Useful Commands for Debugging

```bash
# Check package installation
composer show creativecrafts/laravel-ai-assistant

# Verify configuration
php artisan config:show ai-assistant

# Test API connectivity
php artisan tinker
>>> app(\CreativeCrafts\LaravelAiAssistant\Services\AssistantService::class);

# Clear all caches
php artisan optimize:clear
```

---

## Testing

Bootstrap: tests/Pest.php applies the shared TestCase across the test suite. The shared tests/TestCase.php configures:

- database.default = testing
- ai-assistant.api_key = 'test_key_123' (bypasses ServiceProvider OPENAI_API_KEY validation in tests)

If you author tests that boot the app outside the shared TestCase, set this config key manually inside the test or provide OPENAI_API_KEY in env to avoid ConfigurationValidationException during
package boot.

The package has 100% code and mutation test coverage. Run tests using:

```bash
# Run all tests
composer test

# Run tests with coverage
composer test-coverage

# Run mutation tests
composer test-mutation

# Run static analysis
composer analyse

# Run code style checks
composer format
```

---

## Compatibility

- **PHP:** 8.1, 8.2, 8.3+
- **Laravel:** 9.x, 10.x, 11.x
- **OpenAI API:** Compatible with latest API versions as of December 2024

---

## Changelog

Please see [CHANGELOG](CHANGELOG.md) for more information on what has changed recently.

---

## Contributing

Please see [CONTRIBUTING](CONTRIBUTING.md) for details on how to contribute to this package.

---

## Security Vulnerabilities

Please review [our security policy](../../security/policy) on how to report security vulnerabilities.

---


---

## 🔄 Transport: Retries & Idempotency

- All outbound calls use a centralized `Support\Retry::execute()` wrapper with **exponential backoff + jitter**, controlled by `ai-assistant.transport` config.
- Mutating calls (create/update/delete/run/submit) automatically include an **Idempotency-Key** header generated via `Support\Idempotency`, preventing duplicate side effects on retries.

Config example (`config/ai-assistant.php`):

```php
transport => [
    max_retries => env(AI_TRANSPORT_MAX_RETRIES, 2),
    initial_delay_ms => env(AI_TRANSPORT_INITIAL_DELAY_MS, 200),
    max_delay_ms => env(AI_TRANSPORT_MAX_DELAY_MS, 2000),
    retry_http_codes => [408, 425, 429, 500, 502, 503, 504],
    retry_exception_classes => [
        // \GuzzleHttp\Exception\ConnectException::class,
        // \Illuminate\Http\Client\ConnectionException::class,
    ],
    idempotency => [
        enabled => env(AI_IDEMPOTENCY_ENABLED, true),
        header => env(AI_IDEMPOTENCY_HEADER, Idempotency-Key),
        strategy => env(AI_IDEMPOTENCY_STRATEGY, hash),
        hash_algo => env(AI_IDEMPOTENCY_HASH_ALGO, sha256),
        prefix => env(AI_IDEMPOTENCY_PREFIX, aiasst:),
    ],
],
```

## Credits

- [Godspower Oduose](https://github.com/rockblings)
- [All Contributors](../../contributors)

---

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.

---

## Responses + Conversations (New API)

This package now uses OpenAI's Responses + Conversations APIs under the hood. Below are copy‑paste examples that match the current fluent methods and payload shapes.

### Quickstart: Start and use a conversation

```php
use CreativeCrafts\LaravelAiAssistant\AiAssistant;

$assistant = AiAssistant::init()
    ->startConversation(['tenant_id' => 'acme-1'])
    ->instructions('You are a helpful assistant. Keep answers concise.')
    ->setModelName('gpt-4o')
    ->setUserMessage('What are three benefits of unit testing?');

$result = $assistant->sendChatMessage();

// Result fields
$responseId = $result['responseId'];
$conversationId = $result['conversationId'];
$text = $result['messages'];
```

To continue the same conversation:

```php
$assistant = AiAssistant::init()
    ->useConversation($conversationId)
    ->setUserMessage('Give me a short code example in PHP.');

$next = $assistant->sendChatMessage();
```

### Instructions (system/developer persona)

```php
$assistant = AiAssistant::init()
    ->startConversation()
    ->instructions('You are a senior PHP engineer. Always explain the why.');
```

### Tool choice and response format (json_schema)

```php
$schema = [
    '$schema' => 'https://json-schema.org/draft/2020-12/schema',
    'type' => 'object',
    'properties' => [
        'title' => ['type' => 'string'],
        'bullets' => ['type' => 'array', 'items' => ['type' => 'string']],
    ],
    'required' => ['title','bullets'],
    'additionalProperties' => false,
];

$assistant = AiAssistant::init()
    ->startConversation()
    ->setToolChoice('auto') // 'auto' | 'required' | 'none' | ['type' => 'function', 'name' => '...']
    ->setResponseFormatJsonSchema($schema, 'answer')
    ->setUserMessage('Summarize SOLID principles as JSON.');

$result = $assistant->sendChatMessage();
```

### File search and attachments

```php
$assistant = AiAssistant::init()
    ->startConversation()
    ->includeFileSearchTool() // explicitly include file_search tool
    ->attachFilesToTurn(['file_abc123']) // file IDs previously uploaded
    ->setUserMessage('Answer using the attached policy file.');

$result = $assistant->sendChatMessage();
```

Disable auto file_search insertion if you want to pass file_ids but avoid the tool:

```php
$assistant = AiAssistant::init()
    ->startConversation()
    ->useFileSearch(false)
    ->attachFilesToTurn(['file_abc123'])
    ->setUserMessage('Do not use RAG; answer generally.');

$result = $assistant->sendChatMessage();
```

### Images: from file and by URL

```php
// Local file
$assistant = AiAssistant::init()
    ->startConversation()
    ->addImageFromFile(storage_path('app/public/photo.jpg'))
    ->setUserMessage('Describe this image in one sentence.');

$res1 = $assistant->sendChatMessage();

// Remote URL
$res2 = AiAssistant::init()
    ->startConversation()
    ->addImageFromUrl('https://example.com/cat.jpg')
    ->setUserMessage('What breed is this cat?')
    ->sendChatMessage();
```

### Streaming with onEvent and shouldStop

```php
$assistant = AiAssistant::init()
    ->startConversation()
    ->setUserMessage('Stream a limerick about Laravel.');

$onEvent = function(array $evt) {
    // evt example: ['type' => 'response.output_text.delta', 'data' => [...], 'isFinal' => false]
    if (($evt['type'] ?? '') === 'response.output_text.delta') {
        echo $evt['data']['delta'] ?? '';
    }
};

$shouldStop = function() {
    // Return true to stop early (e.g., client disconnected)
    return false;
};

foreach ($assistant->streamChatMessage($onEvent, $shouldStop) as $event) {
    if (!empty($event['isFinal'])) {
        // completed/failed
    }
}
```

### Cancel an in‑flight response

```php
// If you captured a response ID to cancel later:
AiAssistant::init()->cancelResponse($responseId);
```

### Continue a turn with tool results

```php
$first = AiAssistant::init()
    ->startConversation()
    ->includeFunctionCallTool(
        'getWeather',
        'Gets weather by city',
        [
            'type' => 'object',
            'properties' => ['city' => ['type' => 'string']],
            'required' => ['city']
        ],
        true
    )
    ->setToolChoice('auto')
    ->setUserMessage('What is the weather in Paris?')
    ->sendChatMessage();

$toolCalls = $first['toolCalls'] ?? [];
if ($toolCalls !== []) {
    $call = $toolCalls[0];
    $resultPayload = [
        [
            'tool_call_id' => $call['id'],
            'output' => json_encode(['temp_c' => 21, 'condition' => 'Cloudy']),
        ],
    ];

    $followUp = AiAssistant::init()
        ->useConversation($first['conversationId'])
        ->continueWithToolResults($resultPayload);
}
```

### Deprecation notes (legacy Assistants/Threads/Runs)

The following legacy methods are deprecated. Use the new replacements:

- createThread(), writeMessage(), runMessageThread(), listMessages() → startConversation(), sendChatMessage()/streamChatMessage(), and AssistantService::listConversationItems().
- createAssistant/getAssistantViaId → Prefer local config + per‑turn instructions via instructions().

See docs/Migrate_from_AssistantAPI_to_ResponseAPI.md for the complete migration guide.

### QA checklist for examples

- Configured OPENAI_API_KEY and default model in config/ai-assistant.php
- Confirmed conversationId is returned and reused across turns
- Verified streaming events print deltas and respect early stop
- Validated tool_call flow with continueWithToolResults()
- Confirmed cancelResponse() works with an in‑flight response ID
- Verified addImageFromFile/addImageFromUrl block shapes in payload
- Checked file_search toggles based on includeFileSearchTool()/useFileSearch(false)

---

## Additional Documentation

- Architecture Overview: docs/ARCHITECTURE.md
- Code Map: docs/CODEMAP.md
- Migration Guide: docs/Migrate_from_AssistantAPI_to_ResponseAPI.md
- Tests Guide: tests/README.md
- Contributing: CONTRIBUTING.md

---

## Migration Guide (2.0)

This release introduces a unified, typed, Laravel-native API while preserving backwards compatibility. Here’s how to migrate gradually.

Key goals

- Prefer Ai::chat() entrypoint and the ChatSession for discoverability and strong typing.
- Keep existing code working, but note deprecations on array-returning methods.

Recommended new entrypoint

```php
use CreativeCrafts\LaravelAiAssistant\Facades\Ai;

// Simple, typed chat turn
$response = Ai::chat('Help me compare X and Y')
    ->instructions('You are a product assistant')
    ->setResponseFormatJsonSchema([
        'type' => 'object',
        'properties' => [ 'verdict' => ['type' => 'string'] ],
    ])
    ->send(); // returns ChatResponseDto

$text = $response->content;           // normalized content when available
$raw  = $response->toArray();         // raw normalized envelope array if needed

// Streaming (typed events via ChatSession::stream or text chunks via streamText)
foreach (Ai::chat('stream me')->stream() as $event) {
    // $event is StreamingEventDto
}
```

Files and Tools helpers

```php
// Files helper mirrors AiAssistant capabilities
Ai::chat('Explain these docs')
    ->files()->attachFileReference('file_123', useFileSearch: true)
    ->tools()->includeFileSearchTool()
    ->send();
```

Backwards compatibility and deprecations

- AiAssistant::sendChatMessage(): array is deprecated. Use one of:
    - Ai::chat()->send() which returns ChatResponseDto, or
    - AiAssistant::sendChatMessageDto(): ChatResponseDto, or
    - AiAssistant::sendChatMessageEnvelope(): ResponseEnvelope
- AiAssistant::continueWithToolResults(array): array is deprecated. Use:
    - Ai::chat()->continueWithToolResults($results) → ChatResponseDto, or
    - AiAssistant::continueWithToolResultsDto()/continueWithToolResultsEnvelope()
- AiAssistant::reply(?string): array is deprecated. Use:
    - Ai::chat($message)->send() or Ai::chat()->setUserMessage($message)->send()
- Assistant::sendChatMessage(): array is deprecated. Use:
    - Assistant::sendChatMessageDto(): ChatResponseDto, or
    - Ai::chat() for the new fluent chat entrypoint

Why DTOs?

- ChatResponseDto provides a stable, typed surface while preserving access to the underlying normalized array via toArray().
- StreamingEventDto provides a consistent event shape for streaming.
- ResponseEnvelope represents the normalized Responses API envelope when you need full fidelity.

Minimal migration examples

```php
// Old
$assistant = new \CreativeCrafts\LaravelAiAssistant\AiAssistant('Help me');
$result = $assistant->sendChatMessage(); // array

// New (preferred)
use CreativeCrafts\LaravelAiAssistant\Facades\Ai;
$dto = Ai::chat('Help me')->send();      // ChatResponseDto

// Or typed via AiAssistant (incremental migration)
$assistant = new \CreativeCrafts\LaravelAiAssistant\AiAssistant('Help me');
$dto = $assistant->sendChatMessageDto(); // ChatResponseDto
```

Notes

- All deprecated methods remain functional in 2.x for BC but will be removed in a future major release. Update at your convenience.
- The new API follows Laravel conventions (facades + fluent builders) and improves discoverability (tools(), files()).
- Tests and fakes continue to work. For unit tests, prefer asserting on DTOs.


---

## New: Responses & Conversations Facades

This release introduces two new, fluent entrypoints designed around OpenAI's modern APIs:

- Ai::responses() – Send a turn (response) with minimal setup
- Ai::conversations() – Manage conversations and send turns within a specific conversation

Example: one-off response

```php
use CreativeCrafts\LaravelAiAssistant\Facades\Ai;

$builder = Ai::responses()->model('gpt-4o-mini');
$builder->input()->appendUserText('Give me two fun facts about Laravel.');
$dto = $builder->send();

echo $dto->text ?? $dto->content;
```

Example: conversation lifecycle

```php
use CreativeCrafts\LaravelAiAssistant\Facades\Ai;

$conv = Ai::conversations();
$conversationId = $conv->start();
$conv->input()->appendUserText('Remember me as Alex.');
$first = $conv->send();

// Send a follow-up in the same conversation
$secondBuilder = $conv->responses()->model('gpt-4o-mini');
$secondBuilder->input()->appendUserText('What did I ask you to remember?');
$second = $secondBuilder->send();

echo $second->text ?? $second->content;
```

Deprecation notice

- Ai::assistant() and the legacy Assistant builder are now deprecated and will be removed in a future major version. Prefer Ai::chat(), Ai::responses(), or Ai::conversations().
- You can enable one-time deprecation warnings by setting `AI_ASSISTANT_EMIT_DEPRECATIONS=true` or `config(['ai-assistant.deprecations.emit' => true])` in your environment.

Demo controller

You can copy the stub controller to your app for a quick try:

```php
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\DemoAiController; // see stubs/examples/DemoAiController.php

Route::get('/ai/demo/responses', [DemoAiController::class, 'responsesExample']);
Route::get('/ai/demo/conversations', [DemoAiController::class, 'conversationsExample']);
```
