<?php

namespace CreativeCrafts\LaravelAiAssistant\Contract;

interface AiAssistantContract
{
    public static function acceptPrompt(string $prompt): self;
}
