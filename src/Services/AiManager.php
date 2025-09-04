<?php

declare(strict_types=1);

namespace CreativeCrafts\LaravelAiAssistant\Services;

use CreativeCrafts\LaravelAiAssistant\Assistant;
use CreativeCrafts\LaravelAiAssistant\Chat\ChatSession;

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
}
