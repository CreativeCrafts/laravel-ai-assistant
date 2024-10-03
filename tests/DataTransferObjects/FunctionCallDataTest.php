<?php

declare(strict_types=1);

use CreativeCrafts\LaravelAiAssistant\Contracts\FunctionCallParameterContract;
use CreativeCrafts\LaravelAiAssistant\DataTransferObjects\FunctionCallData;

covers(FunctionCallData::class);

it('can instantiate with default values', function () {
    $functionCallData = new FunctionCallData('myFunction');

    expect($functionCallData->toArray())->toBe([
        'name' => 'myFunction',
        'description' => '',
        'parameters' => [],
        'strict' => false,
        'required' => [],
        'additionalProperties' => false,
    ]);
});

it('can instantiate with custom values', function () {
    $parameterMock = Mockery::mock(FunctionCallParameterContract::class);
    $parameterMock->shouldReceive('toArray')->andReturn(['param1' => 'value1']);

    $functionCallData = new FunctionCallData(
        'myFunction',
        'A description',
        $parameterMock,
        true,
        ['param1', 'param2'],
        true
    );

    expect($functionCallData->toArray())->toBe([
        'name' => 'myFunction',
        'description' => 'A description',
        'parameters' => ['param1' => 'value1'],
        'strict' => true,
        'required' => ['param1', 'param2'],
        'additionalProperties' => true,
    ]);
});

it('can handle array parameters', function () {
    $parameters = [
        'param1' => 'value1',
        'param2' => 'value2'
    ];

    $functionCallData = new FunctionCallData(
        'myFunction',
        'A description',
        $parameters,
        false,
        ['param1'],
        true
    );

    expect($functionCallData->toArray())->toBe([
        'name' => 'myFunction',
        'description' => 'A description',
        'parameters' => $parameters,
        'strict' => false,
        'required' => ['param1'],
        'additionalProperties' => true,
    ]);
});

it('can handle no additional properties', function () {
    $functionCallData = new FunctionCallData(
        'myFunction',
        'A description',
        [],
        true,
        ['param1'],
        false
    );

    expect($functionCallData->toArray())->toBe([
        'name' => 'myFunction',
        'description' => 'A description',
        'parameters' => [],
        'strict' => true,
        'required' => ['param1'],
        'additionalProperties' => false,
    ]);
});
