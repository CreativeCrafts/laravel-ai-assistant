# Services

Application‑level services orchestrating flows and domain logic.

## Classes in this directory
- **AiManager** (class) — *Package component.*  
  FQCN: `CreativeCrafts\LaravelAiAssistant\Services\AiManager`
  - **Key methods:**
    - `public chat(?string $prompt = ''): ChatSession`
    - `public assistant(): Assistant`
    - `public quick(string $prompt): ChatResponseDto`
    - `public stream(string $prompt, ?callable $onEvent = null, ?callable $shouldStop = null): Generator`
- **AppConfig** (class) — *Package component.*  
  FQCN: `CreativeCrafts\LaravelAiAssistant\Services\AppConfig`
  - **Key methods:**
    - `public static openAiClient(Client $client = null): Client`
    - `public static textGeneratorConfig(): array`
    - `public static chatTextGeneratorConfig(): array`
    - `public static editTextGeneratorConfig(): array`
    - `public static audioToTextGeneratorConfig(): array`
    - `private static normalizeStop(mixed $stop): array|string|null`
- **AssistantService** (class) — *Package component.*  
  FQCN: `CreativeCrafts\LaravelAiAssistant\Services\AssistantService`
  - **Key methods:**
    - `public __construct(OpenAiRepositoryContract $repository, CacheService $cacheService)`
    - `public getCorrelationId(): ?string`
    - `public createAssistant(array $parameters): AssistantResponse`
    - `public getAssistantViaId(string $assistantId): AssistantResponse`
    - `public createThread(array $parameters): ThreadResponse`
    - `public createConversation(array $metadata = []): string`
    - `public writeMessage(string $threadId, array $messageData): ThreadMessageResponse`
    - `public sendChatMessage(string $conversationId, string $message, array $options = []): array`
    - `public sendTurn(string $conversationId, ?string $instructions, ?string $model, array $tools, array $inputItems, array|string|null $re...): array`
    - `public continueWithToolResults(string $conversationId, array $toolResults, ?string $model = null, ?string $instructions = null, ?string $idempotency...): array`
    - `public runMessageThread(string $threadId, array $runThreadParameter, int $timeoutSeconds = 300, int $maxRetryAttempts = 60, float $initialDel...): bool`
    - `public listMessages(string $threadId): string`
- **BackgroundJobService** (class) — *Package component.*  
  FQCN: `CreativeCrafts\LaravelAiAssistant\Services\BackgroundJobService`
  - **Key methods:**
    - `public __construct(LoggingService $loggingService, MetricsCollectionService $metricsService, array $config = [])`
    - `public queueLongRunningOperation(string $operation, array $parameters, array $options = []): string`
    - `public queueBatchOperation(string $operation, array $items, int $batchSize = 10, array $options = []): array`
    - `public getJobStatus(string $jobId): ?array`
    - `public updateJobProgress(string $jobId, int $progress, array $metadata = []): void`
    - `public markJobStarted(string $jobId): void`
    - `public markJobCompleted(string $jobId, $result = null): void`
    - `public markJobFailed(string $jobId, string $error, array $context = []): void`
    - `public cancelJob(string $jobId): bool`
    - `public getQueueStatistics(): array`
    - `public cleanupOldJobs(int $retentionDays = 7): int`
    - `private isEnabled(): bool`
- **CacheService** (class) — *Package component.*  
  FQCN: `CreativeCrafts\LaravelAiAssistant\Services\CacheService`
  - **Key methods:**
    - `public cacheConfig(string $key, mixed $value, int $ttl = 3600): bool`
    - `public getConfig(string $key, mixed $default = null): mixed`
    - `public cacheResponse(string $key, array $response, int $ttl = self::DEFAULT_TTL): bool`
    - `public getResponse(string $key): ?array`
    - `public cacheCompletion(string $prompt, string $model, array $parameters, string $result, int $ttl = self::DEFAULT_TTL): bool`
    - `public getCompletion(string $prompt, string $model, array $parameters): ?string`
    - `public clearConfig(?string $key = null): bool`
    - `public clearResponses(?string $key = null): bool`
    - `public clearCompletions(): bool`
    - `public clearAll(): bool`
    - `public getStats(): array`
    - `private buildCacheKey(string $key): string`
- **ErrorReportingService** (class) — *Package component.*  
  FQCN: `CreativeCrafts\LaravelAiAssistant\Services\ErrorReportingService`
  - **Key methods:**
    - `public __construct(LoggingService $loggingService, array $config = [])`
    - `public reportException(Throwable $exception, array $context = [], array $tags = []): ?string`
    - `public reportError(string $message, array $context = [], string $level = 'error', array $tags = []): ?string`
    - `public reportApiError(string $operation, string $endpoint, int $statusCode, string $errorMessage, array $requestData = [], array $responseD...): ?string`
    - `public reportMemoryIssue(string $operation, float $memoryUsageMB, float $thresholdMB, array $additionalContext = []): ?string`
    - `public reportPerformanceIssue(string $operation, float $responseTime, float $threshold, array $metrics = []): ?string`
    - `public setUserContext($userId, array $userData = []): void`
    - `public addTags(array $tags): void`
    - `public testIntegration(): array`
    - `private isEnabled(): bool`
    - `private shouldIncludeContext(): bool`
    - `private shouldScrubSensitiveData(): bool`
- **HealthCheckService** (class) — *Package component.*  
  FQCN: `CreativeCrafts\LaravelAiAssistant\Services\HealthCheckService`
  - **Key methods:**
    - `public __construct(OpenAiRepositoryContract $repository, CacheService $cacheService, LoggingService $loggingService, SecurityService $se...)`
    - `public performHealthCheck(): array`
    - `public getHealthStatus(): array`
    - `private checkConfiguration(): array`
    - `private checkCacheHealth(): array`
    - `private cleanupTestKeys(array $testKeys, array $cacheConfig): void`
    - `private checkSecurityHealth(): array`
    - `private checkApiConnectivity(): array`
    - `private checkMemoryUsage(): array`
    - `private checkDiskSpace(): array`
    - `private getPackageVersion(): string`
    - `private getUptime(): array`
- **IdempotencyService** (class) — *Package component.*  
  FQCN: `CreativeCrafts\LaravelAiAssistant\Services\IdempotencyService`
  - **Key methods:**
    - `public buildKey(array $payload, ?int $bucketSeconds = null): string`
    - `private stableJson(array $payload): string`
    - `private ksortRecursive(array $array): array`
- **LazyLoadingService** (class) — *Package component.*  
  FQCN: `CreativeCrafts\LaravelAiAssistant\Services\LazyLoadingService`
  - **Key methods:**
    - `public __construct(LoggingService $loggingService)`
    - `public registerLazyResource(string $resourceKey, Closure $initializer, array $options = []): self`
    - `public getResource(string $resourceKey)`
    - `public preloadResources(array $resourceKeys): array`
    - `public registerAiModels(array $modelConfigs): self`
    - `public registerHttpClients(array $clientConfigs): self`
    - `public getLazyLoadingMetrics(): array`
    - `public clearResources(array $resourceKeys = []): int`
    - `private isEnabled(): bool`
    - `private getDefaultOptions(): array`
    - `private initializeResource(string $resourceKey)`
    - `private updateResourceAccess(string $resourceKey): void`
- **LoggingService** (class) — *Package component.*  
  FQCN: `CreativeCrafts\LaravelAiAssistant\Services\LoggingService`
  - **Key methods:**
    - `public setCorrelationId(?string $id): void`
    - `public getCorrelationId(): ?string`
    - `public logApiRequest(string $operation, array $payload, string $model, ?float $duration = null): void`
    - `public logApiResponse(string $operation, bool $success, mixed $response, ?float $duration = null): void`
    - `public logCacheOperation(string $operation, string $key, ?string $type = null, ?int $size = null): void`
    - `public logPerformanceMetrics(string $operation, float $duration, array $metrics = []): void`
    - `public logPerformanceEvent(string $category, string $event, array $data = [], ?string $source = null): void`
    - `public logError(string $operation, Throwable|string $error, array $context = []): void`
    - `public logSecurityEvent(string $event, string $description, array $context = []): void`
    - `public logConfigurationEvent(string $operation, string $key, mixed $value, ?string $source = null): void`
    - `private appendCorrelationContext(array $context): array`
    - `private calculatePayloadSize(array $payload): int`
- **MemoryMonitoringService** (class) — *Package component.*  
  FQCN: `CreativeCrafts\LaravelAiAssistant\Services\MemoryMonitoringService`
  - **Key methods:**
    - `public __construct(LoggingService $loggingService, array $config = [])`
    - `public startMonitoring(string $operationName): string`
    - `public updateMonitoring(string $checkpointId, string $stage): void`
    - `public endMonitoring(string $checkpointId): array`
    - `public getCurrentMemoryUsage(): float`
    - `public getPeakMemoryUsage(): float`
    - `public getMemoryUsagePercentage(): float`
    - `public isThresholdExceeded(float $thresholdPercentage): bool`
    - `public forceGarbageCollection(): array`
    - `private isEnabled(): bool`
    - `private shouldLogUsage(): bool`
    - `private shouldAlertOnHighUsage(): bool`
- **MetricsCollectionService** (class) — *Package component.*  
  FQCN: `CreativeCrafts\LaravelAiAssistant\Services\MetricsCollectionService`
  - **Key methods:**
    - `public __construct(LoggingService $loggingService, array $config = [])`
    - `public recordApiCall(string $endpoint, float $responseTime, int $statusCode, array $additionalData = []): void`
    - `public recordTokenUsage(string $operation, int $promptTokens, int $completionTokens, string $model): void`
    - `public recordError(string $operation, string $errorType, string $errorMessage, array $context = []): void`
    - `public recordSystemHealth(array $healthData): void`
    - `public recordCustomMetric(string $metricName, $value, array $tags = []): void`
    - `public getApiPerformanceSummary(string $endpoint, int $hours = 24): array`
    - `public getTokenUsageSummary(int $hours = 24): array`
    - `public getSystemHealthSummary(int $hours = 1): array`
    - `public flushAndCleanup(): void`
    - `private isEnabled(): bool`
    - `private shouldTrackResponseTimes(): bool`
- **ResponseStatusStore** (class) — *Package component.*  
  FQCN: `CreativeCrafts\LaravelAiAssistant\Services\ResponseStatusStore`
  - **Key methods:**
    - `public __construct(private CacheService $cache)`
    - `public setStatus(string $responseId, string $status, array $payload = [], int $ttl = 86400): void`
    - `public getStatus(string $responseId): ?array`
    - `public getLastStatus(string $responseId): ?string`
    - `public getByConversationId(string $conversationId): ?array`
    - `public getLastStatusByConversation(string $conversationId): ?string`
- **ResponsesSseParser** (class) — *Package component.*  
  FQCN: `CreativeCrafts\LaravelAiAssistant\Services\ResponsesSseParser`
  - **Key methods:**
    - `public parse(iterable $lines): Generator`
    - `public parseWithAccumulation(iterable $lines): Generator`
    - `private extractDeltaText(array $data): string`
    - `private extractCompletedText(array $data): string`
- **SecurityService** (class) — *Package component.*  
  FQCN: `CreativeCrafts\LaravelAiAssistant\Services\SecurityService`
  - **Key methods:**
    - `public __construct(CacheService $cacheService, LoggingService $loggingService)`
    - `public applyRateLimit(string $identifier, callable $operation, int $maxRequests = 100, int $timeWindow = 3600)`
    - `public checkRateLimit(string $identifier, int $maxRequests = 100, int $timeWindow = 3600): bool`
    - `public verifyRequestSignature(array $payload, string $signature, string $secret): bool`
    - `public generateRequestSignature(array $payload, string $secret): string`
    - `public sanitizeSensitiveData(array $data, array $sensitiveKeys = []): array`
    - `public validateRequestSize($data, int $maxSize = self::MAX_REQUEST_SIZE): bool`
    - `public generateSecureToken(int $length = 32): string`
    - `public validateConfigurationSecurity(array $config): array`
    - `public validateApiKey(string $apiKey): bool`
    - `public validateOrganizationId(string $organizationId): bool`
    - `private isObviouslyInvalidApiKey(string $apiKey): bool`
- **StreamReader** (class) — *Package component.*  
  FQCN: `CreativeCrafts\LaravelAiAssistant\Services\StreamReader`
  - **Key methods:**
    - `public normalize(iterable $events): Generator`
    - `public onTextChunks(iterable $events, callable $onTextChunk): Generator`
    - `private extractDelta(array $data): string`
    - `private extractCompleted(array $data): string`
    - `private extractArgsDelta(array $data): string`
- **StreamingService** (class) — *Package component.*  
  FQCN: `CreativeCrafts\LaravelAiAssistant\Services\StreamingService`
  - **Key methods:**
    - `public __construct(LoggingService $loggingService, MemoryMonitoringService $memoryMonitor, array $config = [])`
    - `public streamResponses(iterable $sse, ?callable $onEvent = null, ?callable $shouldStop = null): Generator`
    - `public processStream(iterable $stream, string $operation, ?callable $chunkProcessor = null): Generator`
    - `public streamTextCompletion(iterable $stream, string $operation = 'text_completion'): Generator`
    - `public streamChatCompletion(iterable $stream, string $operation = 'chat_completion'): Generator`
    - `public bufferStream(iterable $stream, int $bufferSize = null): Generator`
    - `public validateStreamingCapabilities(): array`
    - `public getStreamingMetrics(): array`
    - `private isEnabled(): bool`
    - `private getBufferSize(): int`
    - `private getChunkSize($chunk): int`
    - `private getMaxResponseSize(): int`
- **ThreadsToConversationsMapper** (class) — *Package component.*  
  FQCN: `CreativeCrafts\LaravelAiAssistant\Services\ThreadsToConversationsMapper`
  - **Key methods:**
    - `public __construct(private readonly CacheService $cache)`
    - `public map(string $threadId, string $conversationId, int $ttl = 86400): void`
    - `public get(string $threadId): ?string`
    - `public getOrMap(string $threadId, callable $createConversation, int $ttl = 86400): string`
- **ToolRegistry** (class) — *Package component.*  
  FQCN: `CreativeCrafts\LaravelAiAssistant\Services\ToolRegistry`
  - **Key methods:**
    - `public register(string $name, callable $callable, array $schema = []): void`
    - `public has(string $name): bool`
    - `public call(string $name, array $args = []): mixed`
    - `public getSchema(string $name): ?array`
    - `public setExecutor(callable $executor): void`
    - `public executeAll(array $calls, bool $parallel = false): array`

## When to Use & Examples
### AiManager
**Use it when:**
- You need orchestration/business logic on top of chat and repository layers.

**Example:**
```php
use CreativeCrafts\LaravelAiAssistant\Services\AiManager;

$svc = app(AiManager::class);
$dto = $svc->quick('Explain queues.');
```

### AppConfig
**Use it when:**
- You need orchestration/business logic on top of chat and repository layers.

**Example:**
```php
use CreativeCrafts\LaravelAiAssistant\Services\AppConfig;

$svc = app(AppConfig::class);
$dto = $svc->quick('Explain queues.');
```

### AssistantService
**Use it when:**
- You need orchestration/business logic on top of chat and repository layers.

**Example:**
```php
use CreativeCrafts\LaravelAiAssistant\Services\AssistantService;

$svc = app(AssistantService::class);
$dto = $svc->quick('Explain queues.');
```

### BackgroundJobService
**Use it when:**
- You need orchestration/business logic on top of chat and repository layers.

**Example:**
```php
use CreativeCrafts\LaravelAiAssistant\Services\BackgroundJobService;

$svc = app(BackgroundJobService::class);
$dto = $svc->quick('Explain queues.');
```

### CacheService
**Use it when:**
- You need orchestration/business logic on top of chat and repository layers.

**Example:**
```php
use CreativeCrafts\LaravelAiAssistant\Services\CacheService;

$svc = app(CacheService::class);
$dto = $svc->quick('Explain queues.');
```

### ErrorReportingService
**Use it when:**
- You need orchestration/business logic on top of chat and repository layers.

**Example:**
```php
use CreativeCrafts\LaravelAiAssistant\Services\ErrorReportingService;

$svc = app(ErrorReportingService::class);
$dto = $svc->quick('Explain queues.');
```

### HealthCheckService
**Use it when:**
- You need orchestration/business logic on top of chat and repository layers.

**Example:**
```php
use CreativeCrafts\LaravelAiAssistant\Services\HealthCheckService;

$svc = app(HealthCheckService::class);
$dto = $svc->quick('Explain queues.');
```

### IdempotencyService
**Use it when:**
- You need orchestration/business logic on top of chat and repository layers.

**Example:**
```php
use CreativeCrafts\LaravelAiAssistant\Services\IdempotencyService;

$svc = app(IdempotencyService::class);
$dto = $svc->quick('Explain queues.');
```

### LazyLoadingService
**Use it when:**
- You need orchestration/business logic on top of chat and repository layers.

**Example:**
```php
use CreativeCrafts\LaravelAiAssistant\Services\LazyLoadingService;

$svc = app(LazyLoadingService::class);
$dto = $svc->quick('Explain queues.');
```

### LoggingService
**Use it when:**
- You need orchestration/business logic on top of chat and repository layers.

**Example:**
```php
use CreativeCrafts\LaravelAiAssistant\Services\LoggingService;

$svc = app(LoggingService::class);
$dto = $svc->quick('Explain queues.');
```

### MemoryMonitoringService
**Use it when:**
- You need orchestration/business logic on top of chat and repository layers.

**Example:**
```php
use CreativeCrafts\LaravelAiAssistant\Services\MemoryMonitoringService;

$svc = app(MemoryMonitoringService::class);
$dto = $svc->quick('Explain queues.');
```

### MetricsCollectionService
**Use it when:**
- You need orchestration/business logic on top of chat and repository layers.

**Example:**
```php
use CreativeCrafts\LaravelAiAssistant\Services\MetricsCollectionService;

$svc = app(MetricsCollectionService::class);
$dto = $svc->quick('Explain queues.');
```

### ResponseStatusStore
**Use it when:**
- You need orchestration/business logic on top of chat and repository layers.

**Example:**
```php
use CreativeCrafts\LaravelAiAssistant\Services\ResponseStatusStore;

$svc = app(ResponseStatusStore::class);
$dto = $svc->quick('Explain queues.');
```

### ResponsesSseParser
**Use it when:**
- You need orchestration/business logic on top of chat and repository layers.

**Example:**
```php
use CreativeCrafts\LaravelAiAssistant\Services\ResponsesSseParser;

$svc = app(ResponsesSseParser::class);
$dto = $svc->quick('Explain queues.');
```

### SecurityService
**Use it when:**
- You need orchestration/business logic on top of chat and repository layers.

**Example:**
```php
use CreativeCrafts\LaravelAiAssistant\Services\SecurityService;

$svc = app(SecurityService::class);
$dto = $svc->quick('Explain queues.');
```

### StreamReader
**Use it when:**
- You need orchestration/business logic on top of chat and repository layers.

**Example:**
```php
use CreativeCrafts\LaravelAiAssistant\Services\StreamReader;

$svc = app(StreamReader::class);
$dto = $svc->quick('Explain queues.');
```

### StreamingService
**Use it when:**
- You need orchestration/business logic on top of chat and repository layers.

**Example:**
```php
use CreativeCrafts\LaravelAiAssistant\Services\StreamingService;

$svc = app(StreamingService::class);
$dto = $svc->quick('Explain queues.');
```

### ThreadsToConversationsMapper
**Use it when:**
- You need orchestration/business logic on top of chat and repository layers.

**Example:**
```php
use CreativeCrafts\LaravelAiAssistant\Services\ThreadsToConversationsMapper;

$svc = app(ThreadsToConversationsMapper::class);
$dto = $svc->quick('Explain queues.');
```

### ToolRegistry
**Use it when:**
- You need orchestration/business logic on top of chat and repository layers.

**Example:**
```php
use CreativeCrafts\LaravelAiAssistant\Services\ToolRegistry;

$svc = app(ToolRegistry::class);
$dto = $svc->quick('Explain queues.');
```
