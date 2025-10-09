<?php

declare(strict_types=1);

use CreativeCrafts\LaravelAiAssistant\Contracts\AudioProcessingContract;
use CreativeCrafts\LaravelAiAssistant\Contracts\OpenAiRepositoryContract;
use CreativeCrafts\LaravelAiAssistant\Contracts\TextCompletionContract;
use CreativeCrafts\LaravelAiAssistant\Repositories\OpenAiRepository;
use CreativeCrafts\LaravelAiAssistant\Services\AssistantService;
use CreativeCrafts\LaravelAiAssistant\Services\CacheService;
use CreativeCrafts\LaravelAiAssistant\Services\LoggingService;
use CreativeCrafts\LaravelAiAssistant\Services\SecurityService;
use CreativeCrafts\LaravelAiAssistant\Services\HealthCheckService;

/**
 * Contract compliance tests (modern API surface).
 */

afterEach(function () {
    Mockery::close();
});

 test('assistant service implements core contracts', function () {
    $contracts = [
        AudioProcessingContract::class,
        TextCompletionContract::class,
    ];

    foreach ($contracts as $contract) {
        expect(is_subclass_of(AssistantService::class, $contract))
            ->toBeTrue("AssistantService must implement {$contract}");
    }
});

 test('openai repository implements contract', function () {
    expect(is_subclass_of(OpenAiRepository::class, OpenAiRepositoryContract::class))
        ->toBeTrue('OpenAiRepository must implement OpenAiRepositoryContract');
});

 test('assistant service exposes modern methods', function () {
    $repositoryMock = Mockery::mock(OpenAiRepositoryContract::class);
    $cacheServiceMock = Mockery::mock(CacheService::class);
    $service = new AssistantService($repositoryMock, $cacheServiceMock);

    expect(method_exists($service, 'createConversation'))->toBeTrue();
    expect(method_exists($service, 'sendChatMessage'))->toBeTrue();

    $ref = new ReflectionMethod(AssistantService::class, 'sendChatMessage');
    expect($ref->getNumberOfParameters())->toBeGreaterThanOrEqual(2);
    expect($ref->getParameters()[0]->getType()?->getName())->toBe('string');
    expect($ref->getParameters()[1]->getType()?->getName())->toBe('string');
});

 test('audio processing contract methods', function () {
    $repositoryMock = Mockery::mock(OpenAiRepositoryContract::class);
    $cacheServiceMock = Mockery::mock(CacheService::class);
    $service = new AssistantService($repositoryMock, $cacheServiceMock);

    // Test transcribeTo method
    expect(method_exists($service, 'transcribeTo'))
        ->toBeTrue('AssistantService must have transcribeTo method');

    $reflection = new ReflectionMethod(AssistantService::class, 'transcribeTo');
    expect($reflection->getNumberOfParameters())
        ->toBe(1, 'transcribeTo must accept exactly 1 parameter');
    expect($reflection->getParameters()[0]->getType()?->getName())
        ->toBe('array', 'First parameter must be array');

    // Test translateTo method
    expect(method_exists($service, 'translateTo'))
        ->toBeTrue('AssistantService must have translateTo method');

    $reflection = new ReflectionMethod(AssistantService::class, 'translateTo');
    expect($reflection->getNumberOfParameters())
        ->toBe(1, 'translateTo must accept exactly 1 parameter');
    expect($reflection->getParameters()[0]->getType()?->getName())
        ->toBe('array', 'First parameter must be array');
});

test('text completion contract methods', function () {
    $repositoryMock = Mockery::mock(OpenAiRepositoryContract::class);
    $cacheServiceMock = Mockery::mock(CacheService::class);
    $service = new AssistantService($repositoryMock, $cacheServiceMock);

    // Test textCompletion method
    expect(method_exists($service, 'textCompletion'))
        ->toBeTrue('AssistantService must have textCompletion method');

    $reflection = new ReflectionMethod(AssistantService::class, 'textCompletion');
    expect($reflection->getNumberOfParameters())
        ->toBe(1, 'textCompletion must accept exactly 1 parameter');
    expect($reflection->getParameters()[0]->getType()?->getName())
        ->toBe('array', 'First parameter must be array');

    // Test streamedCompletion method
    expect(method_exists($service, 'streamedCompletion'))
        ->toBeTrue('AssistantService must have streamedCompletion method');

    // Test chatTextCompletion method
    expect(method_exists($service, 'chatTextCompletion'))
        ->toBeTrue('AssistantService must have chatTextCompletion method');

    // Test streamedChat method
    expect(method_exists($service, 'streamedChat'))
        ->toBeTrue('AssistantService must have streamedChat method');
});

test('openai repository contract methods', function () {
    $clientMock = Mockery::mock(CreativeCrafts\LaravelAiAssistant\Compat\OpenAI\Client::class);
    $repository = new OpenAiRepository($clientMock);

    $contractMethods = [
        'createCompletion' => ['array'],
        'createStreamedCompletion' => ['array'],
        'createChatCompletion' => ['array'],
        'createStreamedChatCompletion' => ['array'],
        'transcribeAudio' => ['array'],
        'translateAudio' => ['array']
    ];

    foreach ($contractMethods as $methodName => $expectedParamTypes) {
        expect(method_exists($repository, $methodName))
            ->toBeTrue("OpenAiRepository must have {$methodName} method");

        $reflection = new ReflectionMethod(OpenAiRepository::class, $methodName);
        expect($reflection->getNumberOfParameters())
            ->toBe(count($expectedParamTypes), "{$methodName} must accept exactly " . count($expectedParamTypes) . " parameters");

        // Verify parameter types
        $params = $reflection->getParameters();
        foreach ($expectedParamTypes as $index => $expectedType) {
            $actualType = $params[$index]->getType()?->getName();
            expect($actualType)
                ->toBe($expectedType, "Parameter {$index} of {$methodName} must be {$expectedType}, got {$actualType}");
        }
    }
});

// Skipped: legacy Assistant/Threads return type checks removed in favor of modern API surface.

test('contract behavioral compliance', function () {
    $repositoryMock = Mockery::mock(OpenAiRepositoryContract::class);
    $cacheServiceMock = Mockery::mock(CacheService::class);

    // Set up default cache behaviors
    $cacheServiceMock->shouldReceive('getCompletion')->andReturn(null)->byDefault();
    $cacheServiceMock->shouldReceive('cacheCompletion')->andReturn(true)->byDefault();
    $cacheServiceMock->shouldReceive('getResponse')->andReturn(null)->byDefault();
    $cacheServiceMock->shouldReceive('cacheResponse')->andReturn(true)->byDefault();

    $service = new AssistantService($repositoryMock, $cacheServiceMock);

    // Test that validation exceptions are thrown for invalid inputs as per contracts
    expect(fn () => $service->transcribeTo([]))
        ->toThrow(InvalidArgumentException::class);

    expect(fn () => $service->textCompletion([]))
        ->toThrow(InvalidArgumentException::class);
});

test('repository delegates to client for core methods', function () {
    $clientMock = Mockery::mock(CreativeCrafts\LaravelAiAssistant\Compat\OpenAI\Client::class);
    $repository = new OpenAiRepository($clientMock);

    $completionsMock = Mockery::mock(CreativeCrafts\LaravelAiAssistant\Compat\OpenAI\CompletionsResource::class);
    $completionsMock->shouldReceive('create')->once()->with(['prompt' => 'hi'])->andReturn(new CreativeCrafts\LaravelAiAssistant\Compat\OpenAI\Responses\Completions\CreateResponse());
    $completionsMock->shouldReceive('createStreamed')->once()->andReturn([]);
    $clientMock->shouldReceive('completions')->twice()->andReturn($completionsMock);

    $chatMock = Mockery::mock(CreativeCrafts\LaravelAiAssistant\Compat\OpenAI\ChatResource::class);
    $chatMock->shouldReceive('create')->once()->with(['messages' => []])->andReturn(new CreativeCrafts\LaravelAiAssistant\Compat\OpenAI\Responses\Chat\CreateResponse());
    $chatMock->shouldReceive('createStreamed')->once()->andReturn([]);
    $clientMock->shouldReceive('chat')->twice()->andReturn($chatMock);

    $audioMock = Mockery::mock(CreativeCrafts\LaravelAiAssistant\Compat\OpenAI\AudioResource::class);
    $audioMock->shouldReceive('transcribe')->once()->with(['file' => 'file'])->andReturn(new CreativeCrafts\LaravelAiAssistant\Compat\OpenAI\Responses\Audio\TranscriptionResponse());
    $audioMock->shouldReceive('translate')->once()->with(['file' => 'file'])->andReturn(new CreativeCrafts\LaravelAiAssistant\Compat\OpenAI\Responses\Audio\TranslationResponse());
    $clientMock->shouldReceive('audio')->twice()->andReturn($audioMock);

    $repository->createCompletion(['prompt' => 'hi']);
    $repository->createStreamedCompletion(['prompt' => 'hi']);
    $repository->createChatCompletion(['messages' => []]);
    $repository->createStreamedChatCompletion(['messages' => []]);
    $repository->transcribeAudio(['file' => 'file']);
    $repository->translateAudio(['file' => 'file']);

    expect(true)->toBeTrue();
});

test('interface completeness', function () {
    // Ensure modern contract interfaces have method definitions
    $contracts = [
        AudioProcessingContract::class,
        TextCompletionContract::class,
        OpenAiRepositoryContract::class,
    ];

    foreach ($contracts as $contract) {
        $reflection = new ReflectionClass($contract);
        $methods = $reflection->getMethods();

        expect(count($methods))->toBeGreaterThan(0, "{$contract} must define at least one method");

        foreach ($methods as $method) {
            expect($method->isAbstract())
                ->toBeTrue("All methods in {$contract} must be abstract");
        }
    }
});

test('exception contracts', function () {
    // Test that custom exceptions exist and extend appropriate base classes
    $exceptions = [
        'ThreadExecutionTimeoutException',
        'MaxRetryAttemptsExceededException',
        'ApiResponseValidationException',
        'FileOperationException',
        'ConfigurationValidationException'
    ];

    foreach ($exceptions as $exceptionName) {
        $fullClassName = "CreativeCrafts\\LaravelAiAssistant\\Exceptions\\{$exceptionName}";

        expect(class_exists($fullClassName))
            ->toBeTrue("Exception class {$fullClassName} must exist");

        expect(is_subclass_of($fullClassName, Exception::class))
            ->toBeTrue("{$fullClassName} must extend Exception");

        // Test that exception can be instantiated
        $exception = new $fullClassName();
        expect($exception)->toBeInstanceOf(Exception::class);
    }
});

test('service dependency injection contracts', function () {
    // Test that services properly accept their dependencies through constructors
    $repositoryMock = Mockery::mock(OpenAiRepositoryContract::class);
    $cacheServiceMock = Mockery::mock(CacheService::class);

    // Test AssistantService accepts proper dependencies
    $assistantService = new AssistantService($repositoryMock, $cacheServiceMock);
    expect($assistantService)->toBeInstanceOf(AssistantService::class);

    // Test OpenAiRepository accepts Client dependency
    $clientMock = Mockery::mock(CreativeCrafts\LaravelAiAssistant\Compat\OpenAI\Client::class);
    $repository = new OpenAiRepository($clientMock);
    expect($repository)->toBeInstanceOf(OpenAiRepository::class);

    // Test other services
    $loggingService = new LoggingService();
    expect($loggingService)->toBeInstanceOf(LoggingService::class);

    $cacheService = new CacheService();
    expect($cacheService)->toBeInstanceOf(CacheService::class);

    $securityService = new SecurityService($cacheService, $loggingService);
    expect($securityService)->toBeInstanceOf(SecurityService::class);

    $healthCheckService = new HealthCheckService(
        $repositoryMock,
        $cacheService,
        $loggingService,
        $securityService
    );
    expect($healthCheckService)->toBeInstanceOf(HealthCheckService::class);
});
