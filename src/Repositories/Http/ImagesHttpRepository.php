<?php

declare(strict_types=1);

namespace CreativeCrafts\LaravelAiAssistant\Repositories\Http;

use CreativeCrafts\LaravelAiAssistant\Contracts\ImagesRepositoryContract;

/**
 * @internal Use Ai::images() instead.
 */
final readonly class ImagesHttpRepository extends AbstractHttpRepository implements ImagesRepositoryContract
{
    public function generate(array $payload): array
    {
        return $this->postJson('images/generations', $payload);
    }

    public function streamGeneration(array $payload): iterable
    {
        yield from $this->streamJson('POST', 'images/generations', array_merge($payload, ['stream' => true]));
    }

    public function edit(array $payload): array
    {
        return $this->postForm('images/edits', $payload, ['image', 'mask']);
    }

    public function streamEdit(array $payload): iterable
    {
        yield from $this->streamForm('images/edits', array_merge($payload, ['stream' => true]), ['image', 'mask']);
    }

    public function createVariation(array $payload): array
    {
        return $this->postForm('images/variations', $payload, ['image']);
    }
}
