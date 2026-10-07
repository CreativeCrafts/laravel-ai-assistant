<?php

declare(strict_types=1);

namespace CreativeCrafts\LaravelAiAssistant\Repositories\Http;

use CreativeCrafts\LaravelAiAssistant\Contracts\DecisionsRepositoryContract;

/**
 * @internal Use Ai::decisions() instead.
 */
final readonly class DecisionsHttpRepository extends AbstractHttpRepository implements DecisionsRepositoryContract
{
    public function create(array $payload): array
    {
        return $this->postJson('decisions', $payload);
    }
}
