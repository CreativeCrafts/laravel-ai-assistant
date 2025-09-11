# ValueObjects

Components grouped by responsibility.

## Classes in this directory
- **TurnOptions** (class) — *Package component.*  
  FQCN: `CreativeCrafts\LaravelAiAssistant\ValueObjects\TurnOptions`
  - **Key methods:**
    - `private __construct(array $data)`
    - `public static new(): self`
    - `public static fromArray(array $data): self`
    - `public model(?string $model): self`
    - `public instructions(?string $instructions): self`
    - `public addTool(array $tool): self`
    - `public responseFormatText(): self`
    - `public responseFormatJsonSchema(array $schema, ?string $name = 'response'): self`
    - `public toolChoice(array|string|null $choice): self`
    - `public modalities(array|string|null $modalities): self`
    - `public metadata(array $metadata): self`
    - `public idempotencyKey(?string $key): self`

## When to Use & Examples
### TurnOptions
**Use it when:**
- General use for this area.

**Example:**
```php
use CreativeCrafts\LaravelAiAssistant\ValueObjects\TurnOptions;
```
