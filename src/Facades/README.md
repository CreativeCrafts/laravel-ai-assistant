# Facades

Convenient static proxies to services bound in the container.

## Classes in this directory
- **Ai** (class) — *Package component.*  
  FQCN: `CreativeCrafts\LaravelAiAssistant\Facades\Ai`
  - **Key methods:**
    - `protected static getFacadeAccessor(): string`
- **AiAssistant** (class) — *Package component.*  
  FQCN: `CreativeCrafts\LaravelAiAssistant\Facades\AiAssistant`
  - **Key methods:**
    - `protected static getFacadeAccessor(): string`
- **Assistant** (class) — *Package component.*  
  FQCN: `CreativeCrafts\LaravelAiAssistant\Facades\Assistant`
  - **Key methods:**
    - `protected static getFacadeAccessor(): string`

## When to Use & Examples
### Ai
**Use it when:**
- You want the quickest entrypoint (no DI) for common operations, including streaming.

**Example:**
```php
use CreativeCrafts\LaravelAiAssistant\Facades\Ai;

$response = Ai::quick('Summarize this text.');
foreach (Ai::stream('Count to 3') as $chunk) { echo $chunk; }
```

### AiAssistant
**Use it when:**
- You want the quickest entrypoint (no DI) for common operations, including streaming.

**Example:**
```php
use CreativeCrafts\LaravelAiAssistant\Facades\AiAssistant;

$response = AiAssistant::quick('Summarize this text.');
foreach (AiAssistant::stream('Count to 3') as $chunk) { echo $chunk; }
```

### Assistant
**Use it when:**
- You want the quickest entrypoint (no DI) for common operations, including streaming.

**Example:**
```php
use CreativeCrafts\LaravelAiAssistant\Facades\Assistant;

$response = Assistant::quick('Summarize this text.');
foreach (Assistant::stream('Count to 3') as $chunk) { echo $chunk; }
```
