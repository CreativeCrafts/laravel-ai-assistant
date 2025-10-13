<?php

declare(strict_types=1);

namespace CreativeCrafts\LaravelAiAssistant\Tests\Feature;

use CreativeCrafts\LaravelAiAssistant\Chat\ChatSession;
use CreativeCrafts\LaravelAiAssistant\DataTransferObjects\ChatResponseDto;
use CreativeCrafts\LaravelAiAssistant\Facades\Ai;
use CreativeCrafts\LaravelAiAssistant\Services\AiManager;
use CreativeCrafts\LaravelAiAssistant\Support\ConversationsBuilder;
use CreativeCrafts\LaravelAiAssistant\Support\ResponsesBuilder;
use Generator;
use ReflectionClass;
use ReflectionMethod;

test('backward compatibility: Ai::chat() method exists and returns ChatSession', function () {
    $session = Ai::chat('Hello');

    expect($session)->toBeInstanceOf(ChatSession::class);
});

test('backward compatibility: Ai::chat() accepts null/empty prompt', function () {
    $session = Ai::chat();

    expect($session)->toBeInstanceOf(ChatSession::class);
});

test('backward compatibility: Ai::stream() method exists and returns Generator', function () {
    $generator = Ai::stream('Hello world');

    expect($generator)->toBeInstanceOf(Generator::class);
});

test('backward compatibility: Ai::responses() method exists and returns ResponsesBuilder', function () {
    $builder = Ai::responses();

    expect($builder)->toBeInstanceOf(ResponsesBuilder::class);
});

test('backward compatibility: Ai::conversations() method exists and returns ConversationsBuilder', function () {
    $builder = Ai::conversations();

    expect($builder)->toBeInstanceOf(ConversationsBuilder::class);
});

test('backward compatibility: all existing API methods are available', function () {
    $reflection = new ReflectionClass(AiManager::class);
    $publicMethods = array_map(
        fn ($method) => $method->getName(),
        $reflection->getMethods(ReflectionMethod::IS_PUBLIC)
    );

    expect($publicMethods)
        ->toContain('quick')
        ->toContain('chat')
        ->toContain('stream')
        ->toContain('responses')
        ->toContain('conversations');
});

test('backward compatibility: Ai::quick() method signature unchanged', function () {
    $reflection = new ReflectionMethod(AiManager::class, 'quick');
    $parameters = $reflection->getParameters();

    expect($parameters)
        ->toHaveCount(1)
        ->and($parameters[0]->getName())->toBe('input')
        ->and($reflection->getReturnType()?->getName())->toBe(ChatResponseDto::class);
});

test('backward compatibility: Ai::chat() method signature unchanged', function () {
    $reflection = new ReflectionMethod(AiManager::class, 'chat');
    $parameters = $reflection->getParameters();

    expect($parameters)
        ->toHaveCount(1)
        ->and($parameters[0]->getName())->toBe('prompt')
        ->and($parameters[0]->isOptional())->toBeTrue()
        ->and($parameters[0]->allowsNull())->toBeTrue()
        ->and($reflection->getReturnType()?->getName())->toBe(ChatSession::class);
});

test('backward compatibility: Ai::stream() method signature unchanged', function () {
    $reflection = new ReflectionMethod(AiManager::class, 'stream');
    $parameters = $reflection->getParameters();

    expect($parameters)
        ->toHaveCount(3)
        ->and($parameters[0]->getName())->toBe('prompt')
        ->and($parameters[1]->getName())->toBe('onEvent')
        ->and($parameters[1]->isOptional())->toBeTrue()
        ->and($parameters[1]->allowsNull())->toBeTrue()
        ->and($parameters[2]->getName())->toBe('shouldStop')
        ->and($parameters[2]->isOptional())->toBeTrue()
        ->and($parameters[2]->allowsNull())->toBeTrue()
        ->and($reflection->getReturnType()?->getName())->toBe(Generator::class);
});

test('backward compatibility: Ai::responses() method signature unchanged', function () {
    $reflection = new ReflectionMethod(AiManager::class, 'responses');
    $parameters = $reflection->getParameters();

    expect($parameters)
        ->toHaveCount(0)
        ->and($reflection->getReturnType()?->getName())->toBe(ResponsesBuilder::class);
});

test('backward compatibility: Ai::conversations() method signature unchanged', function () {
    $reflection = new ReflectionMethod(AiManager::class, 'conversations');
    $parameters = $reflection->getParameters();

    expect($parameters)
        ->toHaveCount(0)
        ->and($reflection->getReturnType()?->getName())->toBe(ConversationsBuilder::class);
});

test('backward compatibility: ResponsesBuilder has expected fluent methods', function () {
    $builder = Ai::responses();
    $reflection = new ReflectionClass($builder);

    $methodNames = array_map(
        fn ($method) => $method->getName(),
        $reflection->getMethods(ReflectionMethod::IS_PUBLIC)
    );

    expect($methodNames)
        ->toContain('input')
        ->toContain('model')
        ->toContain('send');
});

test('backward compatibility: ConversationsBuilder has expected fluent methods', function () {
    $builder = Ai::conversations();
    $reflection = new ReflectionClass($builder);

    $methodNames = array_map(
        fn ($method) => $method->getName(),
        $reflection->getMethods(ReflectionMethod::IS_PUBLIC)
    );

    expect($methodNames)
        ->toContain('start')
        ->toContain('use')
        ->toContain('items')
        ->toContain('input')
        ->toContain('send')
        ->toContain('responses');
});

test('backward compatibility: ChatSession has expected fluent methods', function () {
    $session = Ai::chat('test');
    $reflection = new ReflectionClass($session);

    $methodNames = array_map(
        fn ($method) => $method->getName(),
        $reflection->getMethods(ReflectionMethod::IS_PUBLIC)
    );

    expect($methodNames)
        ->toContain('send')
        ->toContain('stream')
        ->toContain('setModelName')
        ->toContain('setTemperature');
});

test('backward compatibility: Ai facade resolves to AiManager', function () {
    $facadeRoot = Ai::getFacadeRoot();

    expect($facadeRoot)->toBeInstanceOf(AiManager::class);
});

test('backward compatibility: no breaking changes to public API surface', function () {
    $expectedMethods = ['quick', 'chat', 'stream', 'responses', 'conversations', 'complete'];
    $reflection = new ReflectionClass(AiManager::class);

    $actualMethods = array_map(
        fn ($method) => $method->getName(),
        array_filter(
            $reflection->getMethods(ReflectionMethod::IS_PUBLIC),
            fn ($method) => !$method->isConstructor() && !$method->isDestructor()
        )
    );

    foreach ($expectedMethods as $method) {
        expect($actualMethods)->toContain($method);
    }
});
