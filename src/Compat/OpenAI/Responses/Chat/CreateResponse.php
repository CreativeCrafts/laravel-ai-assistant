<?php

declare(strict_types=1);

namespace CreativeCrafts\LaravelAiAssistant\Compat\OpenAI\Responses\Chat;

/**
 * Minimal DTO to mimic OpenAI Chat Create response used in tests.
 */
final class CreateResponse
{
    /**
     * @var array<int, object> Choices array where each item has a `message` object
     */
    public array $choices = [];
}
