<?php

declare(strict_types=1);

namespace CreativeCrafts\LaravelAiAssistant\Contracts;

/**
 * @internal Low-level abstraction for realtime sessions. Do not use directly.
 */
interface RealtimeSessionsRepositoryContract
{
    /**
     * Create a realtime session.
     *
     * @param array $payload
     * @return array
     */
    public function create(array $payload): array;
}
