<?php

declare(strict_types=1);

namespace CreativeCrafts\LaravelAiAssistant\Support;

use InvalidArgumentException;

final class ToolChoice
{
    public static function auto(): string
    {
        return 'auto';
    }

    public static function required(): string
    {
        return 'required';
    }

    public static function none(): string
    {
        return 'none';
    }

    /**
     * Restrict tool choice to a specific function name.
     * Shape compatible with OpenAI Responses API.
     *
     * @param string $name
     * @return array{type:string,function:array{name:string}}
     */
    public static function forFunction(string $name): array
    {
        $name = trim($name);
        if ($name === '') {
            throw new InvalidArgumentException('ToolChoice::forFunction() expects a non-empty function name.');
        }
        return [
            'type' => 'function',
            'function' => ['name' => $name],
        ];
    }
}
