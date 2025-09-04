<?php

declare(strict_types=1);

use CreativeCrafts\LaravelAiAssistant\Services\LazyLoadingService;
use CreativeCrafts\LaravelAiAssistant\Services\LoggingService;
use Illuminate\Support\Facades\Cache;

beforeEach(function () {
    $this->loggingServiceMock = Mockery::mock(LoggingService::class);
    $this->service = new LazyLoadingService($this->loggingServiceMock);

    // Mock Cache facade
    Cache::shouldReceive('get')->andReturn(null)->byDefault();
    Cache::shouldReceive('put')->andReturn(true)->byDefault();
});

afterEach(function () {
    Mockery::close();
});

test('constructor initializes properly', function () {
    $service = new LazyLoadingService($this->loggingServiceMock);

    expect($service)->toBeInstanceOf(LazyLoadingService::class);
});

test('register lazy resource with basic options', function () {
    $initializer = fn () => 'test_resource';
    $options = ['priority' => 'high'];

    $result = $this->service->registerLazyResource('test_key', $initializer, $options);

    expect($result)->toBeInstanceOf(LazyLoadingService::class);
});

test('register lazy resource with all options', function () {
    $initializer = fn () => 'complex_resource';
    $options = [
        'priority' => 'high',
        'cache_ttl' => 3600,
        'dependencies' => ['dep1', 'dep2'],
        'category' => 'ai_models'
    ];

    $result = $this->service->registerLazyResource('complex_key', $initializer, $options);

    expect($result)->toBeInstanceOf(LazyLoadingService::class);
});

test('get resource initializes and returns resource', function () {
    $expectedResource = 'test_resource_data';
    $initializer = fn () => $expectedResource;

    $this->loggingServiceMock
        ->shouldReceive('logPerformanceEvent')
        ->with('lazy_loading', 'resource_loaded', Mockery::type('array'), 'lazy_loading_service')
        ->once();

    $this->service->registerLazyResource('test_key', $initializer, []);
    $result = $this->service->getResource('test_key');

    expect($result)->toBe($expectedResource);
});

test('get resource returns cached resource on second call', function () {
    $callCount = 0;
    $initializer = function () use (&$callCount) {
        $callCount++;
        return 'resource_' . $callCount;
    };

    $this->loggingServiceMock
        ->shouldReceive('logPerformanceEvent')
        ->once();

    $this->service->registerLazyResource('cached_key', $initializer, []);

    $firstCall = $this->service->getResource('cached_key');
    $secondCall = $this->service->getResource('cached_key');

    expect($firstCall)->toBe('resource_1')
        ->and($secondCall)->toBe('resource_1')
        ->and($callCount)->toBe(1);
});

test('get resource returns null for unregistered key', function () {
    $result = $this->service->getResource('non_existent_key');

    expect($result)->toBeNull();
});

test('preload resources loads multiple resources', function () {
    $this->loggingServiceMock
        ->shouldReceive('logPerformanceEvent')
        ->twice();

    $this->service->registerLazyResource('key1', fn () => 'resource1', []);
    $this->service->registerLazyResource('key2', fn () => 'resource2', []);

    $result = $this->service->preloadResources(['key1', 'key2']);

    expect($result)->toBeArray()
        ->and($result)->toHaveKey('key1')
        ->and($result)->toHaveKey('key2')
        ->and($result['key1'])->toBe('resource1')
        ->and($result['key2'])->toBe('resource2');
});

test('preload resources skips unregistered keys', function () {
    $this->service->registerLazyResource('existing_key', fn () => 'existing_resource', []);

    $this->loggingServiceMock
        ->shouldReceive('logPerformanceEvent')
        ->once();

    $result = $this->service->preloadResources(['existing_key', 'non_existent_key']);

    expect($result)->toBeArray()
        ->and($result)->toHaveKey('existing_key')
        ->and($result)->not->toHaveKey('non_existent_key')
        ->and($result['existing_key'])->toBe('existing_resource');
});

test('register ai models with configurations', function () {
    $modelConfig = ['model' => 'gpt-4', 'temperature' => 0.7];

    $result = $this->service->registerAiModels(['text_model' => $modelConfig]);

    expect($result)->toBeInstanceOf(LazyLoadingService::class);

    // Test that the resource can be retrieved
    $this->loggingServiceMock
        ->shouldReceive('logPerformanceEvent')
        ->once();

    $retrievedModel = $this->service->getResource('text_model');
    expect($retrievedModel)->toBe($modelConfig);
});

test('register http clients with configurations', function () {
    $clientConfig = ['base_uri' => 'https://api.openai.com', 'timeout' => 30];

    $result = $this->service->registerHttpClients(['openai_client' => $clientConfig]);

    expect($result)->toBeInstanceOf(LazyLoadingService::class);

    // Test that the resource can be retrieved
    $this->loggingServiceMock
        ->shouldReceive('logPerformanceEvent')
        ->once();

    $retrievedClient = $this->service->getResource('openai_client');
    expect($retrievedClient)->toBe($clientConfig);
});

test('get lazy loading metrics returns metrics array', function () {
    // Register and load some resources to generate metrics
    $this->loggingServiceMock
        ->shouldReceive('logPerformanceEvent')
        ->twice();

    $this->service->registerLazyResource('metric_key1', fn () => 'resource1', []);
    $this->service->registerLazyResource('metric_key2', fn () => 'resource2', []);

    $this->service->getResource('metric_key1');
    $this->service->getResource('metric_key2');

    $metrics = $this->service->getLazyLoadingMetrics();

    expect($metrics)->toBeArray()
        ->and($metrics)->toHaveKeys(['total_registered', 'total_loaded', 'load_hit_rate', 'resources_by_category'])
        ->and($metrics['total_registered'])->toBeGreaterThanOrEqual(2)
        ->and($metrics['total_loaded'])->toBeGreaterThanOrEqual(2)
        ->and($metrics['load_hit_rate'])->toBeGreaterThanOrEqual(0)
        ->and($metrics['resources_by_category'])->toBeArray();
});

test('clear resources removes specified resources', function () {
    $this->loggingServiceMock
        ->shouldReceive('logPerformanceEvent')
        ->once();

    $this->service->registerLazyResource('clear_key', fn () => 'resource_to_clear', []);
    $this->service->getResource('clear_key'); // Load the resource

    $clearedCount = $this->service->clearResources(['clear_key']);

    expect($clearedCount)->toBe(1);

    // Verify resource is cleared by checking it gets initialized again
    $this->loggingServiceMock
        ->shouldReceive('logPerformanceEvent')
        ->once();

    $result = $this->service->getResource('clear_key');
    expect($result)->toBe('resource_to_clear');
});

test('clear resources returns zero for non existent keys', function () {
    $clearedCount = $this->service->clearResources(['non_existent_key1', 'non_existent_key2']);

    expect($clearedCount)->toBe(0);
});

test('service handles resource initialization errors gracefully', function () {
    $initializer = function () {
        throw new Exception('Resource initialization failed');
    };

    $this->loggingServiceMock
        ->shouldReceive('logPerformanceEvent')
        ->once();

    $this->service->registerLazyResource('error_key', $initializer, []);

    $result = $this->service->getResource('error_key');

    expect($result)->toBeNull();
});

test('resource with dependencies waits for dependencies', function () {
    $this->loggingServiceMock
        ->shouldReceive('logPerformanceEvent')
        ->times(3);

    // Register dependencies first
    $this->service->registerLazyResource('dep1', fn () => 'dependency1', []);
    $this->service->registerLazyResource('dep2', fn () => 'dependency2', []);

    // Register resource with dependencies
    $initializer = fn () => 'main_resource';
    $this->service->registerLazyResource('main_key', $initializer, [
        'dependencies' => ['dep1', 'dep2']
    ]);

    $result = $this->service->getResource('main_key');

    expect($result)->toBe('main_resource');
});

test('resource categories are tracked', function () {
    $this->service->registerLazyResource('ai_key', fn () => 'ai_resource', ['category' => 'ai_models']);
    $this->service->registerLazyResource('http_key', fn () => 'http_resource', ['category' => 'http_clients']);

    $metrics = $this->service->getLazyLoadingMetrics();

    expect($metrics['resources_by_category'])->toBeArray()
        ->and($metrics['resources_by_category'])->toHaveKey('ai_models')
        ->and($metrics['resources_by_category'])->toHaveKey('http_clients')
        ->and($metrics['resources_by_category']['ai_models'])->toBeGreaterThanOrEqual(1)
        ->and($metrics['resources_by_category']['http_clients'])->toBeGreaterThanOrEqual(1);
});

test('edge case empty resource keys array', function () {
    $result = $this->service->preloadResources([]);

    expect($result)->toBeArray()
        ->and($result)->toBeEmpty();
});

test('edge case null initializer result', function () {
    $initializer = fn () => null;

    $this->loggingServiceMock
        ->shouldReceive('logPerformanceEvent')
        ->once();

    $this->service->registerLazyResource('null_key', $initializer, []);
    $result = $this->service->getResource('null_key');

    expect($result)->toBeNull();
});

test('boundary condition large number of resources', function () {
    $resourceCount = 100;

    $this->loggingServiceMock
        ->shouldReceive('logPerformanceEvent')
        ->times($resourceCount);

    // Register many resources
    for ($i = 0; $i < $resourceCount; $i++) {
        $this->service->registerLazyResource("resource_$i", fn () => "value_$i", []);
    }

    // Load all resources
    $keys = array_map(fn ($i) => "resource_$i", range(0, $resourceCount - 1));
    $results = $this->service->preloadResources($keys);

    expect($results)->toBeArray()
        ->and(count($results))->toBe($resourceCount);

    $metrics = $this->service->getLazyLoadingMetrics();
    expect($metrics['total_registered'])->toBeGreaterThanOrEqual($resourceCount)
        ->and($metrics['total_loaded'])->toBeGreaterThanOrEqual($resourceCount);
});
