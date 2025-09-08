<?php

declare(strict_types=1);

namespace CreativeCrafts\LaravelAiAssistant\Compat\OpenAI;

use CreativeCrafts\LaravelAiAssistant\Compat\OpenAI\Responses\Assistants\AssistantResponse;

class AssistantsResource
{
    public function create(array $parameters): AssistantResponse
    {
        $response = new AssistantResponse();
        $response->id = 'test_id';
        return $response;
    }

    public function retrieve(string $assistantId): AssistantResponse
    {
        $response = new AssistantResponse();
        $response->id = $assistantId;
        return $response;
    }
}
