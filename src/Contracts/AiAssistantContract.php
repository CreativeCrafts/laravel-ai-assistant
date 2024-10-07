<?php

declare(strict_types=1);

namespace CreativeCrafts\LaravelAiAssistant\Contracts;

interface AiAssistantContract
{
    public static function acceptPrompt(string $prompt): self;
}
