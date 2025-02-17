<?php

declare(strict_types=1);

use CreativeCrafts\LaravelAiAssistant\DataTransferObjects\CustomFunctionData;

covers(CustomFunctionData::class);

describe('CustomFunctionData', function () {
    it('converts to array with provided parameters', function () {
        $name = 'myFunction';
        $description = 'A test function';
        $parameters = [
            'type'       => 'object',
            'properties' => [
                'param1' => ['type' => 'string'],
                'param2' => ['type' => 'integer'],
            ],
            'required'   => ['param1'],
        ];

        $customFunctionData = new CustomFunctionData($name, $description, $parameters);
        $result = $customFunctionData->toArray();

        expect($result)->toEqual([
            'name'        => $name,
            'description' => $description,
            'parameters'  => $parameters,
        ]);
    });

    it('uses default parameters when none are provided', function () {
        $name = 'defaultFunction';
        $description = 'Function with default parameters';

        $customFunctionData = new CustomFunctionData($name, $description);
        $result = $customFunctionData->toArray();

        expect($result)->toEqual([
            'name'        => $name,
            'description' => $description,
            'parameters'  => [
                'type'       => 'object',
                'properties' => [],
                'required'   => [],
            ],
        ]);
    });
});
