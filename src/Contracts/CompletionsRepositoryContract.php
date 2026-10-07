<?php

declare(strict_types=1);

namespace CreativeCrafts\LaravelAiAssistant\Contracts;

/**
 * Legacy Completions API.
 * Resolve through Ai::completions().
 */
interface CompletionsRepositoryContract
{
    /**
     * Creates a completion for the provided prompt and parameters.
     *
     * POST /v1/completions
     *
     * @param array<string, mixed> $payload
     */
    public function create(array $payload): array;

    /**
     * Stream a completion as Server-Sent Events (stream=true).
     *
     * POST /v1/completions
     *
     * @param array<string, mixed> $payload
     * @return iterable<array<string, mixed>> Decoded Server-Sent Events
     */
    public function stream(array $payload): iterable;
}
