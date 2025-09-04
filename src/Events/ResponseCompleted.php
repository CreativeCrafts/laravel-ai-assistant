<?php

declare(strict_types=1);

namespace CreativeCrafts\LaravelAiAssistant\Events;

readonly class ResponseCompleted
{
    public function __construct(
        public string $responseId,
        public array $payload
    ) {
    }
}
