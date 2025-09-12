<?php

declare(strict_types=1);

namespace CreativeCrafts\LaravelAiAssistant\Facades;

use CreativeCrafts\LaravelAiAssistant\Chat\ChatSession;
use CreativeCrafts\LaravelAiAssistant\DataTransferObjects\ChatResponseDto;
use CreativeCrafts\LaravelAiAssistant\Services\AiManager;
use Generator;
use Illuminate\Support\Facades\Facade;

/**
 * * Primary entrypoint:
 *  - chat(string $message = ''): ChatSession (modern Responses and Conversations)
 * Convenience:
 *  - quick(string|array $input): ChatSession (one-shot sugar; internally calls chat())
 * Legacy (deprecated):
 *  - assistant(): Assistant (backward compatibility)
 * @method static ChatSession chat(?string $prompt = '')
 * @method static ChatResponseDto quick(string $prompt)
 * @method static Generator stream(string $prompt, ?callable $onEvent = null, ?callable $shouldStop = null)
 * @method static \CreativeCrafts\LaravelAiAssistant\Assistant assistant()
 *
 * @see AiManager
 */
class Ai extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return AiManager::class;
    }
}
