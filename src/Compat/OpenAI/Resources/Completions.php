<?php

declare(strict_types=1);

namespace CreativeCrafts\LaravelAiAssistant\Compat\OpenAI\Resources;

use CreativeCrafts\LaravelAiAssistant\Compat\OpenAI\Responses\Completions\CreateResponse;
use CreativeCrafts\LaravelAiAssistant\Compat\OpenAI\Responses\StreamResponse;

final class Completions
{
    /**
     * @param array<string,mixed> $parameters
     */
    public function create(array $parameters): CreateResponse
    {
        // Compatibility stub for mocking in tests
        return new CreateResponse();
    }

    /**
     * @param array<string,mixed> $parameters
     */
    public function createStreamed(array $parameters): StreamResponse
    {
        // Compatibility stub for mocking in tests
        return new StreamResponse();
    }
}
