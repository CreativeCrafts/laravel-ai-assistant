<?php

namespace CreativeCrafts\LaravelAiAssistant\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * @see \CreativeCrafts\LaravelAiAssistant\LaravelAiAssistant
 */
class LaravelAiAssistant extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return \CreativeCrafts\LaravelAiAssistant\LaravelAiAssistant::class;
    }
}
