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
php examples/05-audio-transcription.php
php examples/06-audio-speech.php
php examples/07-image-generation.php
php examples/08-unified-api.php
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

### 05. Audio Transcription & Translation (`05-audio-transcription.php`)

**Purpose**: Transcribe and translate audio files using the unified Response API.

**What you'll learn**:
- Transcribing audio files with `audio(['file' => ..., 'action' => 'transcribe'])`
- Translating audio to English with `audio(['file' => ..., 'action' => 'translate'])`
- Using language hints to improve transcription accuracy
- Providing context prompts for better transcription results
- Different response formats (json, text, srt, verbose_json, vtt)
- Configuring temperature for deterministic output
- Using the unified API for both transcription and translation

**Time**: ~3 minutes

**Example output**:
```
=== Audio Transcription & Translation Example ===
Audio File: test-audio.mp3
Transcription: [transcribed text]
Type: audio_transcription

Audio File: test-audio.mp3
Translation: [translated text in English]
Type: audio_translation
```

**Note**: Supports mp3, mp4, mpeg, mpga, m4a, wav, webm formats up to 25MB. Translation automatically converts any language to English.

---

### 06. Audio Speech (`06-audio-speech.php`)

**Purpose**: Generate speech from text using text-to-speech (TTS).

**What you'll learn**:
- Converting text to speech with `audio(['text' => ..., 'action' => 'speech'])`
- Using different voice options (alloy, echo, fable, onyx, nova, shimmer)
- Adjusting speech speed (0.25 to 4.0)
- Different audio formats (mp3, opus, aac, flac, wav, pcm)
- Saving audio output to files
- Using tts-1 vs tts-1-hd models

**Time**: ~2 minutes

**Example output**:
```
=== Audio Speech Generation Example ===
Voice: alloy -> output/speech-alloy.mp3
Voice: echo -> output/speech-echo.mp3
...
All audio files saved successfully!
```

**Note**: Generated audio files are saved to `examples/output/` directory.

---

### 07. Image Generation (`07-image-generation.php`)

**Purpose**: Generate, edit, and create variations of images.

**What you'll learn**:
- Generating images from prompts with `image(['prompt' => ...])`
- Editing images with `image(['image' => ..., 'prompt' => ...])`
- Creating image variations with `image(['image' => ...])`
- Using DALL-E 3 for high-quality images
- Using DALL-E 2 for multiple images
- Configuring size, quality, and style
- Base64 vs URL response formats

**Time**: ~3 minutes

**Example output**:
```
=== Image Generation Example ===
Prompt: A serene mountain landscape...
Generated image saved to: output/image-basic.png

Size: 1024x1024
Quality: hd
Style: vivid
Generated image saved to: output/image-hd-vivid.png
```

**Note**: Generated images are saved to `examples/output/` directory.

---

### 08. Unified API (`08-unified-api.php`)

**Purpose**: Demonstrate the Single Source of Truth (SSOT) unified API approach.

**What you'll learn**:
- Using one interface for all operations (text, audio, image)
- How the API automatically routes to appropriate endpoints
- Building multi-step AI workflows
- Combining different AI capabilities seamlessly
- Benefits of the unified API design
- Consistent error handling across all operations

**Time**: ~3 minutes

**Example output**:
```
=== Unified API Example ===

1. Text Conversation -> Routes to Response API
2. Audio Transcription -> Routes to Audio API
3. Text-to-Speech -> Routes to Audio Speech API
4. Image Generation -> Routes to Image API

Multi-Step Workflow:
  Step 1: Generated tagline
  Step 2: Audio saved to output/
  Step 3: Image saved to output/
```

**Note**: This example showcases the SSOT architecture where a single API intelligently delegates to different OpenAI endpoints based on input type.

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
