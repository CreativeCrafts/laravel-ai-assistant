<?php

declare(strict_types=1);

namespace CreativeCrafts\LaravelAiAssistant\Contracts;

/**
 * @internal Low-level abstraction for moderations operations. Do not use directly.
 */
interface ModerationsRepositoryContract
{
    /**
     * Create a moderation request.
     *
     * @param array $payload
     * @return array
     */
    public function create(array $payload): array;
}
