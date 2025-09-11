# Transport

Low‑level HTTP/SDK transport adapters.

## Classes in this directory
- **OpenAITransport** (interface) — *Package component.*  
  FQCN: `CreativeCrafts\LaravelAiAssistant\Transport\OpenAITransport`
  - **Key methods:**
    - `public postJson(string $path, array $payload, array $headers = [], ?float $timeout = null, bool $idempotent = false): array`
    - `public postMultipart(string $path, array $fields, array $headers = [], ?float $timeout = null, bool $idempotent = false): array`
    - `public streamSse(string $path, array $payload, array $headers = [], ?float $timeout = null, bool $idempotent = false): iterable`
    - `public getJson(string $path, array $headers = [], ?float $timeout = null): array`
    - `public delete(string $path, array $headers = [], ?float $timeout = null): bool`

## When to Use & Examples
### OpenAITransport
**Use it when:**
- You need to customize HTTP/SDK transport or headers.

**Example:**
```php
use CreativeCrafts\LaravelAiAssistant\Transport\OpenAITransport;

$http = app(OpenAITransport::class);
$res = $http->postJson('/v1/runs', ['input' => 'Hi']);
```
