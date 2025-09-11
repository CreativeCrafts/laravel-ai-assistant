# Providers

Service providers for bindings, publishes, routes/macros.

## Classes in this directory
- **CoreServiceProvider** (class) — *Package component.*  
  FQCN: `CreativeCrafts\LaravelAiAssistant\Providers\CoreServiceProvider`
  - **Key methods:**
    - `public register(): void`
- **HealthCheckServiceProvider** (class) — *Package component.*  
  FQCN: `CreativeCrafts\LaravelAiAssistant\Providers\HealthCheckServiceProvider`
  - **Key methods:**
    - `public boot(): void`
    - `private registerHealthCheckRoutes(): void`
- **MonitoringServiceProvider** (class) — *Package component.*  
  FQCN: `CreativeCrafts\LaravelAiAssistant\Providers\MonitoringServiceProvider`
  - **Key methods:**
    - `public register(): void`
- **StorageServiceProvider** (class) — *Package component.*  
  FQCN: `CreativeCrafts\LaravelAiAssistant\Providers\StorageServiceProvider`
  - **Key methods:**
    - `public register(): void`
    - `private registerEloquentStores(): void`
    - `private registerInMemoryStores(): void`
- **WebhookServiceProvider** (class) — *Package component.*  
  FQCN: `CreativeCrafts\LaravelAiAssistant\Providers\WebhookServiceProvider`
  - **Key methods:**
    - `public boot(): void`
    - `private registerWebhookRoute(): void`

## When to Use & Examples
### CoreServiceProvider
**Use it when:**
- You want to understand what bindings/macros are registered at boot.

**Example:**
```php
// Auto-discovered by Laravel; exposes Route::aiAssistant() macro
```

### HealthCheckServiceProvider
**Use it when:**
- You want to understand what bindings/macros are registered at boot.

**Example:**
```php
// Auto-discovered by Laravel; exposes Route::aiAssistant() macro
```

### MonitoringServiceProvider
**Use it when:**
- You want to understand what bindings/macros are registered at boot.

**Example:**
```php
// Auto-discovered by Laravel; exposes Route::aiAssistant() macro
```

### StorageServiceProvider
**Use it when:**
- You want to understand what bindings/macros are registered at boot.

**Example:**
```php
// Auto-discovered by Laravel; exposes Route::aiAssistant() macro
```

### WebhookServiceProvider
**Use it when:**
- You want to understand what bindings/macros are registered at boot.

**Example:**
```php
// Auto-discovered by Laravel; exposes Route::aiAssistant() macro
```
