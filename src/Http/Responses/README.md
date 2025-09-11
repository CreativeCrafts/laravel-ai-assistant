# Http/Responses

Custom response helpers (e.g., SSE streaming).

## Classes & What They Do
- `StreamedAiResponse.php` — class **StreamedAiResponse**
  Namespace: `CreativeCrafts\LaravelAiAssistant\Http\Responses`

## When to Use & Examples
### StreamedAiResponse
_Kind: class_

**Use it when** …

**Example:**
```php
use CreativeCrafts\LaravelAiAssistant\Http\Responses\StreamedAiResponse;
use CreativeCrafts\LaravelAiAssistant\Facades\Ai;

Route::get('/ai/stream', function() {
    $gen = Ai::stream('Count to 10.');
    return StreamedAiResponse::fromGenerator($gen);
});
```
