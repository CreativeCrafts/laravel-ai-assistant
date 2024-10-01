<?php

declare(strict_types=1);

namespace CreativeCrafts\LaravelAiAssistant\Contract;

interface AiAssistantContract
{
    public static function acceptPrompt(string $prompt): self;
}
