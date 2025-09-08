<?php

declare(strict_types=1);

namespace CreativeCrafts\LaravelAiAssistant\Compat\OpenAI;

use CreativeCrafts\LaravelAiAssistant\Compat\OpenAI\Responses\Completions\CreateResponse as CompletionResponse;

class CompletionsResource
{
    public function create(array $parameters): CompletionResponse
    {
        $response = new CompletionResponse();
        $response->choices = [
            (object) ['text' => 'Mock completion text', 'finish_reason' => 'stop']
        ];
        return $response;
    }

    public function createStreamed(array $parameters): iterable
    {
        return [
            (object) ['choices' => [(object) ['text' => 'Mock', 'finish_reason' => null]]],
            (object) ['choices' => [(object) ['text' => ' streamed', 'finish_reason' => null]]],
            (object) ['choices' => [(object) ['text' => ' text', 'finish_reason' => 'stop']]]
        ]; // Mock implementation for testing
    }
}
