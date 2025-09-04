<?php

declare(strict_types=1);

namespace CreativeCrafts\LaravelAiAssistant\Compat\OpenAI\Resources;

use CreativeCrafts\LaravelAiAssistant\Compat\OpenAI\Responses\Assistants\AssistantResponse;

final class Assistants
{
    /**
     * @param array<string,mixed> $parameters
     */
    public function create(array $parameters): AssistantResponse
    {
        // Compatibility stub for mocking in tests
        return new AssistantResponse();
    }

    public function retrieve(string $assistantId): AssistantResponse
    {
        // Compatibility stub for mocking in tests
        return new AssistantResponse();
    }
}
