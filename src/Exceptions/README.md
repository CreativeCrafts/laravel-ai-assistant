# Exceptions

Typed exceptions your code can catch for robust error handling.

## Classes in this directory
- **ApiResponseValidationException** (class) — *Package component.*  
  FQCN: `CreativeCrafts\LaravelAiAssistant\Exceptions\ApiResponseValidationException`
  - **Key methods:**
    - `public __construct(string $message = 'API response validation failed.', int $code = Response::HTTP_BAD_GATEWAY)`
- **ConfigurationValidationException** (class) — *Package component.*  
  FQCN: `CreativeCrafts\LaravelAiAssistant\Exceptions\ConfigurationValidationException`
  - **Key methods:**
    - `public __construct(string $message = 'Configuration validation failed.', int $code = Response::HTTP_BAD_REQUEST)`
- **CreateNewAssistantException** (class) — *Package component.*  
  FQCN: `CreativeCrafts\LaravelAiAssistant\Exceptions\CreateNewAssistantException`
  - **Key methods:**
    - `public __construct(string $message = 'Unable to create new assistant.', int $code = Response::HTTP_NOT_ACCEPTABLE)`
- **FileOperationException** (class) — *Package component.*  
  FQCN: `CreativeCrafts\LaravelAiAssistant\Exceptions\FileOperationException`
  - **Key methods:**
    - `public __construct(string $message = 'File operation failed.', int $code = Response::HTTP_UNPROCESSABLE_ENTITY)`
- **InvalidApiKeyException** (class) — *Package component.*  
  FQCN: `CreativeCrafts\LaravelAiAssistant\Exceptions\InvalidApiKeyException`
  - **Key methods:**
    - `public __construct(string $message = 'Missing API key. Set OPENAI_API_KEY or ai-assistant.api_key. See config/ai-assistant.php.', int $c...)`
- **MaxRetryAttemptsExceededException** (class) — *Package component.*  
  FQCN: `CreativeCrafts\LaravelAiAssistant\Exceptions\MaxRetryAttemptsExceededException`
  - **Key methods:**
    - `public __construct(string $message = 'Maximum retry attempts exceeded for operation.', int $code = Response::HTTP_TOO_MANY_REQUESTS)`
- **MissingRequiredParameterException** (class) — *Package component.*  
  FQCN: `CreativeCrafts\LaravelAiAssistant\Exceptions\MissingRequiredParameterException`
  - **Key methods:**
    - `public __construct(string $message = 'Missing required parameter.', int $code = Response::HTTP_NOT_ACCEPTABLE)`
- **OpenAiTransportException** (class) — *Package component.*  
  FQCN: `CreativeCrafts\LaravelAiAssistant\Exceptions\OpenAiTransportException`
  - **Key methods:**
    - `public __construct(string $message, ?int $httpCode = null, ?string $requestId = null, ?string $responseSnippet = null, ?Throwable $previ...)`
    - `public static from(Throwable $e): self`
    - `public getHttpCode(): ?int`
    - `public getRequestId(): ?string`
    - `public getResponseSnippet(): ?string`
- **ResponseCanceledException** (class) — *Package component.*  
  FQCN: `CreativeCrafts\LaravelAiAssistant\Exceptions\ResponseCanceledException`
  - **Key methods:**
    - `public __construct(string $message = 'Response was canceled by the client.', int $code = 499)`
- **ThreadExecutionTimeoutException** (class) — *Package component.*  
  FQCN: `CreativeCrafts\LaravelAiAssistant\Exceptions\ThreadExecutionTimeoutException`
  - **Key methods:**
    - `public __construct(string $message = 'Thread execution exceeded maximum timeout period.', int $code = Response::HTTP_REQUEST_TIMEOUT)`

## When to Use & Examples
### ApiResponseValidationException
**Use it when:**
- You want to catch specific failure modes and handle them explicitly.

**Example:**
```php
use CreativeCrafts\LaravelAiAssistant\Exceptions\ApiResponseValidationException;

try { /* ... */ } catch (ApiResponseValidationException $e) { report($e); }
```

### ConfigurationValidationException
**Use it when:**
- You want to catch specific failure modes and handle them explicitly.

**Example:**
```php
use CreativeCrafts\LaravelAiAssistant\Exceptions\ConfigurationValidationException;

try { /* ... */ } catch (ConfigurationValidationException $e) { report($e); }
```

### CreateNewAssistantException
**Use it when:**
- You want to catch specific failure modes and handle them explicitly.

**Example:**
```php
use CreativeCrafts\LaravelAiAssistant\Exceptions\CreateNewAssistantException;

try { /* ... */ } catch (CreateNewAssistantException $e) { report($e); }
```

### FileOperationException
**Use it when:**
- You want to catch specific failure modes and handle them explicitly.

**Example:**
```php
use CreativeCrafts\LaravelAiAssistant\Exceptions\FileOperationException;

try { /* ... */ } catch (FileOperationException $e) { report($e); }
```

### InvalidApiKeyException
**Use it when:**
- You want to catch specific failure modes and handle them explicitly.

**Example:**
```php
use CreativeCrafts\LaravelAiAssistant\Exceptions\InvalidApiKeyException;

try { /* ... */ } catch (InvalidApiKeyException $e) { report($e); }
```

### MaxRetryAttemptsExceededException
**Use it when:**
- You want to catch specific failure modes and handle them explicitly.

**Example:**
```php
use CreativeCrafts\LaravelAiAssistant\Exceptions\MaxRetryAttemptsExceededException;

try { /* ... */ } catch (MaxRetryAttemptsExceededException $e) { report($e); }
```

### MissingRequiredParameterException
**Use it when:**
- You want to catch specific failure modes and handle them explicitly.

**Example:**
```php
use CreativeCrafts\LaravelAiAssistant\Exceptions\MissingRequiredParameterException;

try { /* ... */ } catch (MissingRequiredParameterException $e) { report($e); }
```

### OpenAiTransportException
**Use it when:**
- You want to catch specific failure modes and handle them explicitly.

**Example:**
```php
use CreativeCrafts\LaravelAiAssistant\Exceptions\OpenAiTransportException;

try { /* ... */ } catch (OpenAiTransportException $e) { report($e); }
```

### ResponseCanceledException
**Use it when:**
- You want to catch specific failure modes and handle them explicitly.

**Example:**
```php
use CreativeCrafts\LaravelAiAssistant\Exceptions\ResponseCanceledException;

try { /* ... */ } catch (ResponseCanceledException $e) { report($e); }
```

### ThreadExecutionTimeoutException
**Use it when:**
- You want to catch specific failure modes and handle them explicitly.

**Example:**
```php
use CreativeCrafts\LaravelAiAssistant\Exceptions\ThreadExecutionTimeoutException;

try { /* ... */ } catch (ThreadExecutionTimeoutException $e) { report($e); }
```
