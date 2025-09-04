<?php

declare(strict_types=1);

namespace CreativeCrafts\LaravelAiAssistant\Events;

readonly class ToolCallRequested
{
    public function __construct(
        public string $responseId,
        public array $toolCalls,
        public array $payload
    ) {
    }
}
