<?php

declare(strict_types=1);

namespace CreativeCrafts\LaravelAiAssistant\Contracts;

interface ChatMessageDataContract
{
    public function __construct(
        string $prompt,
    );

    public function messages(): array;

    public function setAssistantInstructions(string $instructions): array;

    public function cacheConversation(array $conversation): void;
}
