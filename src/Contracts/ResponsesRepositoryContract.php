<?php

declare(strict_types=1);

namespace CreativeCrafts\LaravelAiAssistant\Contracts;

interface ResponsesRepositoryContract
{
    /**
     * Create a response for a turn.
     *
     * @param array $payload
     * @return array Response resource as array
     */
    public function createResponse(array $payload): array;

    /**
     * Stream a response for a turn via SSE/iterable.
     * Implementations should return an iterable yielding parsed events or raw SSE lines.
     *
     * @param array $payload
     * @return iterable
     */
    public function streamResponse(array $payload): iterable;

    /**
     * Retrieve a response by id.
     *
     * @param string $responseId
     * @return array
     */
    public function getResponse(string $responseId): array;

    /**
     * Cancel a response by id.
     *
     * @param string $responseId
     * @return bool
     */
    public function cancelResponse(string $responseId): bool;

    /**
     * Delete a response by id.
     *
     * @param string $responseId
     * @return bool
     */
    public function deleteResponse(string $responseId): bool;
}
