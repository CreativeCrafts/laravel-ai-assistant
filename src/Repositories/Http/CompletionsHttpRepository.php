<?php

declare(strict_types=1);

namespace CreativeCrafts\LaravelAiAssistant\Repositories\Http;

use CreativeCrafts\LaravelAiAssistant\Contracts\CompletionsRepositoryContract;

/**
 * @internal Use Ai::completions() instead.
 */
final readonly class CompletionsHttpRepository extends AbstractHttpRepository implements CompletionsRepositoryContract
{
    public function create(array $payload): array
    {
        return $this->postJson('completions', $payload);
    }

    public function stream(array $payload): iterable
    {
        yield from $this->streamJson('POST', 'completions', array_merge($payload, ['stream' => true]));
    }
}
