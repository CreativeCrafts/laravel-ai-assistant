<?php

declare(strict_types=1);

namespace CreativeCrafts\LaravelAiAssistant\Compat\OpenAI\Responses\Assistants;

final class AssistantResponse
{
    // Provide an id property to satisfy tests that set/read it on partial mocks
    public string $id;
}
