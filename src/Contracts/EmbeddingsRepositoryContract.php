<?php

declare(strict_types=1);

namespace CreativeCrafts\LaravelAiAssistant\Contracts;

/**
 * Embeddings API: vector representations of text.
 * Resolve through Ai::embeddings().
 */
interface EmbeddingsRepositoryContract
{
    /**
     * Creates an embedding vector representing the input text.
     *
     * POST /v1/embeddings
     *
     * @param array<string, mixed> $payload
     */
    public function create(array $payload): array;
}
