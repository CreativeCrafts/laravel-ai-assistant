<?php

declare(strict_types=1);

namespace CreativeCrafts\LaravelAiAssistant\Compat\OpenAI\Responses\Completions;

/**
 * Minimal DTO to mimic OpenAI Completions Create response used in tests.
 */
final class CreateResponse
{
    /**
     * @var array<int, object> Choices array where each item has a `text` property
     */
    public array $choices = [];
}
