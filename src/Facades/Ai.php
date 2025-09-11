<?php

declare(strict_types=1);

namespace CreativeCrafts\LaravelAiAssistant\Facades;

use CreativeCrafts\LaravelAiAssistant\Chat\ChatSession;
use Illuminate\Support\Facades\Facade;

/**
 * @method static ChatSession chat(?string $prompt = '')
 * @method static \CreativeCrafts\LaravelAiAssistant\DataTransferObjects\ChatResponseDto quick(string $prompt)
 * @method static \Generator stream(string $prompt, ?callable $onEvent = null, ?callable $shouldStop = null)
 * @method static \CreativeCrafts\LaravelAiAssistant\Assistant assistant()
 *
 * @see \CreativeCrafts\LaravelAiAssistant\Services\AiManager
 */
class Ai extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return \CreativeCrafts\LaravelAiAssistant\Services\AiManager::class;
    }
}
