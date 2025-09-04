<?php

declare(strict_types=1);

namespace CreativeCrafts\LaravelAiAssistant\Events;

readonly class ResponseFailed
{
    public function __construct(
        public string $responseId,
        public ?string $error,
        public array $payload
    ) {
    }
}
