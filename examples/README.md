# Laravel AI Assistant - Examples

This directory contains runnable code examples demonstrating key features of the Laravel AI Assistant package.

## Prerequisites

Before running these examples, ensure you have:

1. **Installed the package**:
   ```bash
   composer require creativecrafts/laravel-ai-assistant
   php artisan ai:install
   ```

2. **Set your OpenAI API key** in `.env`:
   ```env
   OPENAI_API_KEY=your-openai-api-key-here
   ```

3. **Laravel application** with the package properly configured.

## Running Examples

### Quick Test

Run the smoke test to verify everything is set up correctly:

```bash
php examples/smoke-test.php
```

### Individual Examples

Each example is a standalone PHP script that can be run directly:

```bash
php examples/01-hello-world.php
php examples/02-streaming.php
php examples/03-cancellation.php
php examples/04-complete-api.php
php examples/05-observability.php
```

## Examples Overview

### 01. Hello World (`01-hello-world.php`)

**Purpose**: Get started quickly with basic chat completion.

**What you'll learn**:
- Using `Ai::quick()` for one-off requests
- Simple chat session with `Ai::chat()`
- Basic response handling

**Time**: ~1 minute

**Example output**:
```
=== Hello World Example ===
Quick response: Laravel queues are...
Chat response: Service providers are...
```

---

### 02. Streaming (`02-streaming.php`)

**Purpose**: Implement real-time streaming for better user experience.

**What you'll learn**:
- Basic streaming with `Ai::stream()`
- Streaming with callbacks for real-time processing
- HTTP streaming responses for web applications

**Time**: ~2 minutes

**Example output**:
```
=== Streaming Example ===
Streaming story: Once upon a time...
[text appears token by token]
```

---

### 03. Cancellation (`03-cancellation.php`)

**Purpose**: Control streaming operations and stop them mid-flight.

**What you'll learn**:
- Chunk count-based cancellation
- Time-based cancellation
- User-initiated cancellation patterns
- `shouldStop` callback usage

**Time**: ~2 minutes

**Example output**:
```
=== Cancellation Example ===
Limiting to 5 chunks...
Chunk 1: Once
Chunk 2: upon
...
Stopped after 5 chunks!
```

---

### 04. Unified Completion API (`04-complete-api.php`)

**Purpose**: Use the modern, recommended unified completion API.

**What you'll learn**:
- `AiManager::complete()` method
- `Mode::TEXT` and `Mode::CHAT` usage
- `Transport::SYNC` and `Transport::STREAM` differences
- `CompletionRequest` DTO usage
- Type-safe, explicit API design

**Time**: ~3 minutes

**Example output**:
```
=== Unified Completion API Example ===

TEXT + SYNC:
Result: Laravel is a web application framework...

CHAT + SYNC:
Result: {'role': 'assistant', 'content': '...'}

TEXT + STREAM:
Final result: Once upon a time in Laravel...
```

---

### 05. Observability (`05-observability.php`)

**Purpose**: Integrate comprehensive observability for production systems.

**What you'll learn**:
- Correlation ID tracking for request tracing
- Structured logging with context
- Performance metrics collection
- Memory monitoring and tracking
- Error reporting with context
- Complete observability integration pattern

**Time**: ~3 minutes

**Example output**:
```
=== Observability Example ===
Correlation ID: 550e8400-e29b-41d4-a716-446655440000

AI Request with Observability:
Response: Laravel queues are...
Duration: 1.25s
Memory used: 2.3MB
Metrics recorded: 5

Stream with Observability:
Chunks: 42
Duration: 3.45s
All metrics logged successfully!
```

---

## Smoke Test (`smoke-test.php`)

**Purpose**: Verify your installation and configuration.

**What it tests**:
- Package is installed correctly
- OpenAI API key is configured
- Basic AI request works
- Streaming works
- Observability integration works

**Usage**:
```bash
php examples/smoke-test.php
```

**Expected output**:
```
🚀 Laravel AI Assistant - Smoke Test
=====================================

✓ Package installed
✓ OpenAI API key configured
✓ Basic request works
✓ Streaming works
✓ Observability works

All tests passed! ✓
```

---

## Integration into Your Application

These examples use standalone scripts for simplicity. To integrate into your Laravel application:

### In Controllers

```php
namespace App\Http\Controllers;

use CreativeCrafts\LaravelAiAssistant\Facades\Ai;
use Illuminate\Http\Request;

class AiController extends Controller
{
    public function chat(Request $request)
    {
        $response = Ai::quick($request->input('message'));
        
        return response()->json([
            'response' => $response->text,
        ]);
    }
    
    public function stream(Request $request)
    {
        $generator = Ai::stream($request->input('message'));
        
        return StreamedAiResponse::fromGenerator($generator);
    }
}
```

### In Commands

```php
namespace App\Console\Commands;

use CreativeCrafts\LaravelAiAssistant\Facades\Ai;
use Illuminate\Console\Command;

class AiChatCommand extends Command
{
    protected $signature = 'ai:chat {message}';
    
    public function handle()
    {
        $response = Ai::quick($this->argument('message'));
        $this->info($response->text);
    }
}
```

### In Jobs

```php
namespace App\Jobs;

use CreativeCrafts\LaravelAiAssistant\Facades\{Ai, Observability};
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;

class ProcessAiRequest implements ShouldQueue
{
    use Queueable;
    
    public function __construct(
        private string $correlationId,
        private string $prompt
    ) {}
    
    public function handle()
    {
        Observability::setCorrelationId($this->correlationId);
        
        $response = Ai::quick($this->prompt);
        
        // Process response...
    }
}
```

---

## Best Practices Demonstrated

### 1. Error Handling
All examples include proper try-catch blocks and error reporting.

### 2. Resource Cleanup
Memory tracking and cleanup patterns are shown in observability examples.

### 3. Correlation IDs
Observability example shows request tracing across operations.

### 4. Graceful Degradation
Cancellation examples show how to handle timeouts and limits.

### 5. Type Safety
Unified API examples demonstrate strongly-typed interfaces.

---

## Troubleshooting

### "Class not found" errors

Make sure you've installed the package:
```bash
composer require creativecrafts/laravel-ai-assistant
```

### "API key not configured" errors

Set your OpenAI API key in `.env`:
```env
OPENAI_API_KEY=sk-...
```

### "Connection timeout" errors

Check your internet connection and OpenAI API status. Increase timeout in config:
```php
// config/ai-assistant.php
'timeout' => 60, // seconds
```

### Examples not running

These are standalone scripts. For Laravel integration, use controllers, commands, or jobs as shown above.

---

## Additional Resources

- **[Complete API Reference](../docs/API.md)** - All methods and options
- **[Observability Guide](../docs/OBSERVABILITY.md)** - Complete observability documentation
- **[Migration Guide](../UPGRADE.md)** - Upgrading from legacy APIs
- **[Main README](../README.md)** - Package overview and features

---

## Contributing Examples

Have a useful example? Submit a PR! Guidelines:

1. Keep examples focused on one concept
2. Include clear comments explaining key points
3. Follow existing naming convention (`##-name.php`)
4. Update this README with example description
5. Ensure code follows Laravel conventions
6. Test the example before submitting

---

## License

These examples are part of the Laravel AI Assistant package and are provided under the same MIT license.
