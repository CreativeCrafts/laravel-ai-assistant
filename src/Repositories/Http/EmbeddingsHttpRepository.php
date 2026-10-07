<?php

declare(strict_types=1);

namespace CreativeCrafts\LaravelAiAssistant\Repositories\Http;

use CreativeCrafts\LaravelAiAssistant\Contracts\EmbeddingsRepositoryContract;

/**
 * @internal Use Ai::embeddings() instead.
 */
final readonly class EmbeddingsHttpRepository extends AbstractHttpRepository implements EmbeddingsRepositoryContract
{
    public function create(array $payload): array
    {
        return $this->postJson('embeddings', $payload);
    }
}
