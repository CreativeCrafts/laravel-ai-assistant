# Http/Middleware

Middleware for securing or transforming requests and responses.

## Classes & What They Do
- `VerifyAiWebhookSignature.php` — class **VerifyAiWebhookSignature**
  Namespace: `CreativeCrafts\LaravelAiAssistant\Http\Middleware`

## When to Use & Examples
### VerifyAiWebhookSignature
_Kind: class_

**Use it when** …

**Example:**
```php
Route::post('/ai/webhook', fn() => 'ok')->middleware('verify.ai.webhook');
```
