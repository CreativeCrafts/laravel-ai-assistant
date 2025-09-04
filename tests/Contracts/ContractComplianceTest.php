<?php

declare(strict_types=1);

use CreativeCrafts\LaravelAiAssistant\Contracts\AssistantManagementContract;
use CreativeCrafts\LaravelAiAssistant\Contracts\AudioProcessingContract;
use CreativeCrafts\LaravelAiAssistant\Contracts\OpenAiRepositoryContract;
use CreativeCrafts\LaravelAiAssistant\Contracts\TextCompletionContract;
use CreativeCrafts\LaravelAiAssistant\Contracts\ThreadOperationContract;
use CreativeCrafts\LaravelAiAssistant\Repositories\OpenAiRepository;
use CreativeCrafts\LaravelAiAssistant\Services\AssistantService;
use CreativeCrafts\LaravelAiAssistant\Services\CacheService;
use CreativeCrafts\LaravelAiAssistant\Services\HealthCheckService;
use CreativeCrafts\LaravelAiAssistant\Services\LoggingService;
use CreativeCrafts\LaravelAiAssistant\Services\SecurityService;

/**
 * Contract compliance tests to verify that all implementations correctly fulfill their contracts.
 *
 * These tests ensure that classes implement all methods defined in their interfaces
 * with correct signatures and behavior expectations.
 */

afterEach(function () {
    Mockery::close();
});

test('assistant service implements all contracts', function () {
    $contracts = [
        AssistantManagementContract::class,
        ThreadOperationContract::class,
        AudioProcessingContract::class,
        TextCompletionContract::class
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

test('assistant management contract methods', function () {
    $repositoryMock = Mockery::mock(OpenAiRepositoryContract::class);
    $cacheServiceMock = Mockery::mock(CacheService::class);
    $service = new AssistantService($repositoryMock, $cacheServiceMock);

    // Test createAssistant method exists and has correct signature
    expect(method_exists($service, 'createAssistant'))
        ->toBeTrue('AssistantService must have createAssistant method');

    $reflection = new ReflectionMethod(AssistantService::class, 'createAssistant');
    expect($reflection->getNumberOfParameters())
        ->toBe(1, 'createAssistant must accept exactly 1 parameter');
    expect($reflection->getParameters()[0]->getType()?->getName())
        ->toBe('array', 'First parameter must be array');

    // Test getAssistantViaId method
    expect(method_exists($service, 'getAssistantViaId'))
        ->toBeTrue('AssistantService must have getAssistantViaId method');

    $reflection = new ReflectionMethod(AssistantService::class, 'getAssistantViaId');
    expect($reflection->getNumberOfParameters())
        ->toBe(1, 'getAssistantViaId must accept exactly 1 parameter');
    expect($reflection->getParameters()[0]->getType()?->getName())
        ->toBe('string', 'First parameter must be string');
});

test('thread operation contract methods', function () {
    $repositoryMock = Mockery::mock(OpenAiRepositoryContract::class);
    $cacheServiceMock = Mockery::mock(CacheService::class);
    $service = new AssistantService($repositoryMock, $cacheServiceMock);

    // Test createThread method
    expect(method_exists($service, 'createThread'))
        ->toBeTrue('AssistantService must have createThread method');

    // Test writeMessage method
    expect(method_exists($service, 'writeMessage'))
        ->toBeTrue('AssistantService must have writeMessage method');

    $reflection = new ReflectionMethod(AssistantService::class, 'writeMessage');
    expect($reflection->getNumberOfParameters())
        ->toBe(2, 'writeMessage must accept exactly 2 parameters');

    // Test runMessageThread method
    expect(method_exists($service, 'runMessageThread'))
        ->toBeTrue('AssistantService must have runMessageThread method');

    $reflection = new ReflectionMethod(AssistantService::class, 'runMessageThread');
    expect($reflection->getNumberOfParameters())
        ->toBeGreaterThanOrEqual(2, 'runMessageThread must accept at least 2 parameters');

    // Test listMessages method
    expect(method_exists($service, 'listMessages'))
        ->toBeTrue('AssistantService must have listMessages method');

    $reflection = new ReflectionMethod(AssistantService::class, 'listMessages');
    expect($reflection->getNumberOfParameters())
        ->toBe(1, 'listMessages must accept exactly 1 parameter');
    expect($reflection->getParameters()[0]->getType()?->getName())
        ->toBe('string', 'First parameter must be string');
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
        'createAssistant' => ['array'],
        'retrieveAssistant' => ['string'],
        'createThread' => ['array'],
        'createThreadMessage' => ['string', 'array'],
        'createThreadRun' => ['string', 'array'],
        'retrieveThreadRun' => ['string', 'string'],
        'listThreadMessages' => ['string'],
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

test('contract method return types', function () {
    // Test AssistantService return types match contract expectations
    $reflection = new ReflectionClass(AssistantService::class);

    // Check methods that should return specific types
    $methodReturnTypes = [
        'createAssistant' => 'CreativeCrafts\LaravelAiAssistant\Compat\OpenAI\Responses\Assistants\AssistantResponse',
        'getAssistantViaId' => 'CreativeCrafts\LaravelAiAssistant\Compat\OpenAI\Responses\Assistants\AssistantResponse',
        'createThread' => 'CreativeCrafts\LaravelAiAssistant\Compat\OpenAI\Responses\Threads\ThreadResponse',
        'writeMessage' => 'CreativeCrafts\LaravelAiAssistant\Compat\OpenAI\Responses\Threads\Messages\ThreadMessageResponse',
        'runMessageThread' => 'bool',
        'listMessages' => 'string',
        'transcribeTo' => 'string',
        'translateTo' => 'string',
        'textCompletion' => 'string',
        'streamedCompletion' => 'string',
        'chatTextCompletion' => 'array',
        'streamedChat' => 'array'
    ];

    foreach ($methodReturnTypes as $methodName => $expectedReturnType) {
        $method = $reflection->getMethod($methodName);
        $returnType = $method->getReturnType();

        if ($returnType) {
            $actualReturnType = $returnType->getName();
            expect($actualReturnType)
                ->toBe($expectedReturnType, "{$methodName} must return {$expectedReturnType}, declared {$actualReturnType}");
        } else {
            expect($returnType)->not->toBeNull("{$methodName} must have a return type declaration");
        }
    }
});

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
    expect(fn () => $service->createAssistant([]))
        ->toThrow(InvalidArgumentException::class, 'Assistant parameters cannot be empty');

    expect(fn () => $service->getAssistantViaId(''))
        ->toThrow(InvalidArgumentException::class, 'Assistant ID cannot be empty');

    expect(fn () => $service->writeMessage('', []))
        ->toThrow(InvalidArgumentException::class, 'Thread ID cannot be empty');

    expect(fn () => $service->transcribeTo([]))
        ->toThrow(InvalidArgumentException::class, 'Audio payload cannot be empty');

    expect(fn () => $service->textCompletion([]))
        ->toThrow(InvalidArgumentException::class, 'Text completion payload cannot be empty');
});

test('repository contract behavioral compliance', function () {
    $clientMock = Mockery::mock(CreativeCrafts\LaravelAiAssistant\Compat\OpenAI\Client::class);
    $repository = new OpenAiRepository($clientMock);

    // Test that repository properly delegates to client
    $assistantsMock = Mockery::mock(CreativeCrafts\LaravelAiAssistant\Compat\OpenAI\AssistantsResource::class);
    $assistantsMock->shouldReceive('create')
        ->once()
        ->with(['test' => 'data'])
        ->andReturn(Mockery::mock(CreativeCrafts\LaravelAiAssistant\Compat\OpenAI\Responses\Assistants\AssistantResponse::class));

    $clientMock->shouldReceive('assistants')
        ->once()
        ->andReturn($assistantsMock);

    $result = $repository->createAssistant(['test' => 'data']);
    expect($result)->toBeInstanceOf(CreativeCrafts\LaravelAiAssistant\Compat\OpenAI\Responses\Assistants\AssistantResponse::class);

    // Test thread creation
    $threadsMock = Mockery::mock(CreativeCrafts\LaravelAiAssistant\Compat\OpenAI\ThreadsResource::class);
    $threadsMock->shouldReceive('create')
        ->once()
        ->with(['test' => 'thread'])
        ->andReturn(Mockery::mock(CreativeCrafts\LaravelAiAssistant\Compat\OpenAI\Responses\Threads\ThreadResponse::class));

    $clientMock->shouldReceive('threads')
        ->once()
        ->andReturn($threadsMock);

    $result = $repository->createThread(['test' => 'thread']);
    expect($result)->toBeInstanceOf(CreativeCrafts\LaravelAiAssistant\Compat\OpenAI\Responses\Threads\ThreadResponse::class);
});

test('interface completeness', function () {
    // Ensure all contract interfaces have complete method definitions
    $contracts = [
        AssistantManagementContract::class,
        ThreadOperationContract::class,
        AudioProcessingContract::class,
        TextCompletionContract::class,
        OpenAiRepositoryContract::class
    ];

    foreach ($contracts as $contract) {
        $reflection = new ReflectionClass($contract);
        $methods = $reflection->getMethods();

        expect(count($methods))->toBeGreaterThan(0, "{$contract} must define at least one method");

        foreach ($methods as $method) {
            expect($method->isAbstract())
                ->toBeTrue("All methods in {$contract} must be abstract");

            // Ensure methods have proper documentation
            $docComment = $method->getDocComment();
            expect($docComment)->not->toBeFalse("Method {$method->getName()} in {$contract} must have documentation");
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
