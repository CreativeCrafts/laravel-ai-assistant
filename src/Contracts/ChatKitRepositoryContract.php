<?php

declare(strict_types=1);

namespace CreativeCrafts\LaravelAiAssistant\Contracts;

/**
 * ChatKit API (beta): sessions and threads for embedded chat experiences.
 * Requests carry the OpenAI-Beta: chatkit_beta=v1 header.
 * Resolve through Ai::chatKit().
 */
interface ChatKitRepositoryContract
{
    /**
     * Create a ChatKit session.
     *
     * POST /v1/chatkit/sessions
     *
     * @param array<string, mixed> $payload
     */
    public function createSession(array $payload): array;

    /**
     * Cancel an active ChatKit session and return its most recent metadata.
     *
     * POST /v1/chatkit/sessions/{sessionId}/cancel
     */
    public function cancelSession(string $sessionId): array;

    /**
     * Retrieve a ChatKit thread by its identifier.
     *
     * GET /v1/chatkit/threads/{threadId}
     */
    public function retrieveThread(string $threadId): array;

    /**
     * List ChatKit threads with optional pagination and user filters.
     *
     * GET /v1/chatkit/threads
     *
     * @param array<string, mixed> $params Query parameters
     */
    public function listThreads(array $params = []): array;

    /**
     * Delete a ChatKit thread along with its items and stored attachments.
     *
     * DELETE /v1/chatkit/threads/{threadId}
     */
    public function deleteThread(string $threadId): array;

    /**
     * List items that belong to a ChatKit thread.
     *
     * GET /v1/chatkit/threads/{threadId}/items
     *
     * @param array<string, mixed> $params Query parameters
     */
    public function listThreadItems(string $threadId, array $params = []): array;
}
