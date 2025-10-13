<?php

declare(strict_types=1);

namespace CreativeCrafts\LaravelAiAssistant\Facades;

use CreativeCrafts\LaravelAiAssistant\Chat\ChatSession;
use CreativeCrafts\LaravelAiAssistant\DataTransferObjects\ChatResponseDto;
use CreativeCrafts\LaravelAiAssistant\DataTransferObjects\CompletionRequest;
use CreativeCrafts\LaravelAiAssistant\DataTransferObjects\CompletionResult;
use CreativeCrafts\LaravelAiAssistant\Enums\Mode;
use CreativeCrafts\LaravelAiAssistant\Enums\Transport;
use CreativeCrafts\LaravelAiAssistant\Services\AiManager;
use CreativeCrafts\LaravelAiAssistant\Support\ConversationsBuilder;
use CreativeCrafts\LaravelAiAssistant\Support\ResponsesBuilder;
use Generator;
use Illuminate\Support\Facades\Facade;

/**
 * @method static ChatSession chat(?string $prompt = '')
 * @method static ChatResponseDto quick(string $prompt)
 * @method static Generator stream(string $prompt, ?callable $onEvent = null, ?callable $shouldStop = null)
 * @method static ResponsesBuilder responses()
 * @method static ConversationsBuilder conversations()
 * @method static CompletionResult complete(Mode $mode, Transport $transport, CompletionRequest $request)
 * @see AiManager
 */
class Ai extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return AiManager::class;
    }
}
