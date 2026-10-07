<?php

declare(strict_types=1);

namespace CreativeCrafts\LaravelAiAssistant\Contracts;

/**
 * Chat Completions API, including stored completions.
 * Resolve through Ai::chatCompletions().
 */
interface ChatCompletionsRepositoryContract
{
    /**
     * Starting a new project? We recommend trying Responses to take advantage of the latest OpenAI platform features. Compare Chat Completions with Responses.
     *
     * POST /v1/chat/completions
     *
     * @param array<string, mixed> $payload
     */
    public function create(array $payload): array;

    /**
     * Stream a chat completion as Server-Sent Events (stream=true).
     *
     * POST /v1/chat/completions
     *
     * @param array<string, mixed> $payload
     * @return iterable<array<string, mixed>> Decoded Server-Sent Events
     */
    public function stream(array $payload): iterable;

    /**
     * Get a stored chat completion. Only Chat Completions that have been created with the store parameter set to true will be returned.
     *
     * GET /v1/chat/completions/{completionId}
     */
    public function retrieve(string $completionId): array;

    /**
     * Modify a stored chat completion. Only Chat Completions that have been created with the store parameter set to true can be modified. Currently, the only supported modification is to update the metadata field.
     *
     * POST /v1/chat/completions/{completionId}
     *
     * @param array<string, mixed> $payload
     */
    public function update(string $completionId, array $payload): array;

    /**
     * List stored Chat Completions. Only Chat Completions that have been stored with the store parameter set to true will be returned.
     *
     * GET /v1/chat/completions
     *
     * @param array<string, mixed> $params Query parameters
     */
    public function list(array $params = []): array;

    /**
     * Delete a stored chat completion. Only Chat Completions that have been created with the store parameter set to true can be deleted.
     *
     * DELETE /v1/chat/completions/{completionId}
     */
    public function delete(string $completionId): array;

    /**
     * Get the messages in a stored chat completion. Only Chat Completions that have been created with the store parameter set to true will be returned.
     *
     * GET /v1/chat/completions/{completionId}/messages
     *
     * @param array<string, mixed> $params Query parameters
     */
    public function listMessages(string $completionId, array $params = []): array;
}
