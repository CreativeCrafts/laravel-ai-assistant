<?php

namespace CreativeCrafts\LaravelAiAssistant\Contract;

interface AiAssistantContract
{
    /**
     * @param string $prompt
     * @return AiAssistantContract
     */
    public static function acceptPrompt(string $prompt): self;
}
