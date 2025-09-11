<?php

declare(strict_types=1);

namespace CreativeCrafts\LaravelAiAssistant\Services;

use CreativeCrafts\LaravelAiAssistant\Assistant;
use CreativeCrafts\LaravelAiAssistant\Chat\ChatSession;
use CreativeCrafts\LaravelAiAssistant\DataTransferObjects\ChatResponseDto;
use Generator;

final class AiManager
{
    public function chat(?string $prompt = ''): ChatSession
    {
        return ChatSession::make($prompt ?? '');
    }

    public function assistant(): Assistant
    {
        // Ensure Assistant is wired with the resolved AssistantService
        return Assistant::new()->client(resolve(AssistantService::class));
    }


    /**
     * Quickly send a one-off prompt and receive a response DTO.
     */
    public function quick(string $prompt): ChatResponseDto
    {
        return ChatSession::make($prompt)->send();
    }

    /**
     * Stream events from a prompt. Yields string chunks or typed events.
     *
     * @param callable(array|string):void|null $onEvent
     * @param callable():bool|null $shouldStop
     * @return Generator
     */
    public function stream(string $prompt, ?callable $onEvent = null, ?callable $shouldStop = null): Generator
    {
        return ChatSession::make($prompt)->stream($onEvent, $shouldStop);
    }

}
