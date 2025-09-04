# Upgrade Guide

This document provides comprehensive upgrade instructions for major version changes of the Laravel AI Assistant package.

## Upgrading from 1.x to 2.0

Version 2.0 represents a significant architectural overhaul of the Laravel AI Assistant package. This upgrade introduces breaking changes that require code modifications.

### Breaking Changes Overview

#### 1. Service Architecture Refactor

- **Old**: Direct service method calls on legacy classes
- **New**: Unified AssistantService with improved method signatures
- **Impact**: All service interactions require updating

#### 2. Data Transfer Objects (DTOs)

- **Old**: Basic arrays and legacy data structures
- **New**: Strongly typed DTOs (AssistantMessageData, NewAssistantResponseData, FunctionCalData)
- **Impact**: Data handling code needs restructuring

#### 3. Method Signatures

- **Old**: Inconsistent method signatures across different services
- **New**: Standardized, fluent API with method chaining
- **Impact**: Method calls require syntax updates

### Step-by-Step Migration

#### Step 1: Update Dependencies

```bash
composer update creativecrafts/laravel-ai-assistant
```

#### Step 2: Update Service Instantiation

**Before (1.x):**

```php
use CreativeCrafts\LaravelAiAssistant\AiAssistant;

$assistant = app(AiAssistant::class);
```

**After (2.0+):**

```php
use CreativeCrafts\LaravelAiAssistant\Assistant;

$assistant = Assistant::new();
```

#### Step 3: Update Assistant Creation

**Before (1.x):**

```php
$assistant = new AiAssistant();
$response = $assistant->createAssistant([
    'name' => 'My Assistant',
    'model' => 'gpt-4',
    'instructions' => 'You are a helpful assistant'
]);
```

**After (2.0+):**

```php
$assistant = Assistant::new()
    ->setAssistantName('My Assistant')
    ->setModelName('gpt-4')
    ->setInstructions('You are a helpful assistant')
    ->create();
```

#### Step 4: Update Chat Interactions

**Before (1.x):**

```php
$response = $assistant->chat([
    'model' => 'gpt-4',
    'messages' => [
        ['role' => 'user', 'content' => 'Hello']
    ]
]);
```

**After (2.0+):**

```php
$assistant = Assistant::new()
    ->setModelName('gpt-4')
    ->createTask()
    ->askQuestion('Hello')
    ->process();
    
$response = $assistant->response();
```

#### Step 5: Update Thread Management

**Before (1.x):**

```php
$thread = $assistant->createThread(['title' => 'My Thread']);
$message = $assistant->addMessage($thread['id'], ['content' => 'Hello']);
```

**After (2.0+):**

```php
$assistantService = app(AssistantService::class);
$thread = $assistantService->createThread(['title' => 'My Thread']);
$message = $assistantService->writeMessage($thread->id, ['content' => 'Hello']);
```

#### Step 6: Update Function Calls

**Before (1.x):**

```php
$assistant->addFunction([
    'name' => 'get_weather',
    'description' => 'Get weather information',
    'parameters' => [...]
]);
```

**After (2.0+):**

```php
$assistant = Assistant::new()
    ->includeFunctionCallTool([
        'type' => 'function',
        'function' => [
            'name' => 'get_weather',
            'description' => 'Get weather information',
            'parameters' => [...]
        ]
    ]);
```

### Configuration Changes

#### Environment Variables

No changes to environment variables are required. All existing configuration remains compatible.

#### Published Assets

If you have published the package assets, you may want to republish them:

```bash
# Republish configuration
php artisan vendor:publish --tag="ai-assistant-config" --force

# Republish migrations
php artisan vendor:publish --tag="ai-assistant-migrations" --force

# Republish model stubs (new in 2.0)
php artisan vendor:publish --tag="ai-assistant-models" --force
```

### Data Structure Changes

#### Response Formats

Responses now use standardized DTOs instead of raw arrays:

**Before (1.x):**

```php
$response = $assistant->createAssistant([...]);
// $response is a raw array
$assistantId = $response['id'];
```

**After (2.0+):**

```php
$response = $assistant->create();
// $response is a NewAssistantResponseData DTO
$assistantId = $response->id;
```

### Testing Updates

#### Mock Updates

Update your test mocks to use the new service architecture:

**Before (1.x):**

```php
$mock = Mockery::mock(AiAssistant::class);
$mock->shouldReceive('createAssistant')->andReturn([...]);
```

**After (2.0+):**

```php
$mock = Mockery::mock(AssistantService::class);
$mock->shouldReceive('createAssistant')->andReturn(new AssistantResponse(...));
```

### Common Migration Issues

#### Issue 1: Method Not Found

**Error**: `Call to undefined method`
**Solution**: Check the new method names in the AssistantService class

#### Issue 2: Array Access on Object

**Error**: `Cannot access offset on object`
**Solution**: Update array access to use DTO properties

#### Issue 3: Type Errors

**Error**: Type mismatch in method parameters
**Solution**: Use the new DTO classes instead of raw arrays

### Rollback Strategy

If you need to rollback to 1.x:

1. **Revert composer.json**:
   ```bash
   composer require creativecrafts/laravel-ai-assistant:^1.3
   ```

2. **Restore old code**: Revert your application code changes

3. **Clear caches**:
   ```bash
   php artisan config:clear
   php artisan cache:clear
   ```

### Getting Help

If you encounter issues during migration:

1. Check the [updated documentation](README.md)
2. Review the [test files](tests/) for usage examples
3. Open an issue on [GitHub](https://github.com/creativecrafts/laravel-ai-assistant/issues)

## Future Upgrades

### Best Practices for Smooth Upgrades

1. **Keep dependencies updated**: Regularly update to minor versions
2. **Read changelogs**: Review CHANGELOG.md before upgrading
3. **Test thoroughly**: Use comprehensive tests to catch breaking changes
4. **Use semantic versioning**: Pin major versions in production

### Deprecation Policy

- Deprecated methods include clear deprecation notices
- Deprecated features are supported for at least one major version
- Migration paths are provided for all deprecated features

## Version Support Matrix

| Package Version | Laravel Version  | PHP Version | Support Status |
|-----------------|------------------|-------------|----------------|
| 2.1.x           | 10.x, 11.x, 12.x | 8.2, 8.3    | ✅ Active       |
| 2.0.x           | 10.x, 11.x, 12.x | 8.2, 8.3    | ✅ Security     |
| 1.3.x           | 10.x, 11.x       | 8.2         | 🔶 EOL         |
| 1.2.x           | 10.x, 11.x       | 8.2         | ❌ EOL          |

**Legend:**

- ✅ Active: Regular updates and new features
- ✅ Security: Security fixes only
- 🔶 EOL: End of life, critical fixes only
- ❌ EOL: No longer supported