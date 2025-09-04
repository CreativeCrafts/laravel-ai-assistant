<?php

declare(strict_types=1);

namespace CreativeCrafts\LaravelAiAssistant\Compat\OpenAI\Resources;

use CreativeCrafts\LaravelAiAssistant\Compat\OpenAI\Responses\Audio\TranscriptionResponse;
use CreativeCrafts\LaravelAiAssistant\Compat\OpenAI\Responses\Audio\TranslationResponse;

final class Audio
{
    /**
     * @param array<string,mixed> $parameters
     */
    public function transcribe(array $parameters): TranscriptionResponse
    {
        // Compatibility stub for mocking in tests
        return new TranscriptionResponse();
    }

    /**
     * @param array<string,mixed> $parameters
     */
    public function translate(array $parameters): TranslationResponse
    {
        // Compatibility stub for mocking in tests
        return new TranslationResponse();
    }
}
