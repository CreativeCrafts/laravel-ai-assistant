<?php

declare(strict_types=1);

namespace CreativeCrafts\LaravelAiAssistant\Contracts;

/**
 * @internal Low-level abstraction for responses operations. Do not use directly.
 * Use ResponsesBuilder via Ai::responses() instead.
 */
interface ResponsesRepositoryContract
{
    /**
     * Create a response for a turn.
     *
     * @param array $payload
     * @param array<string, string> $headers Extra request headers, e.g. ['OpenAI-Beta' => 'responses_multi_agent=v1']
     * @return array Response resource as array
     */
    public function createResponse(array $payload, array $headers = []): array;

    /**
     * Stream a response for a turn via SSE/iterable.
     * Implementations should return an iterable yielding parsed events or raw SSE lines.
     *
     * @param array $payload
     * @param array<string, string> $headers Extra request headers, e.g. ['OpenAI-Beta' => 'responses_multi_agent=v1']
     * @return iterable
     */
    public function streamResponse(array $payload, array $headers = []): iterable;

    /**
     * Retrieve a response by id.
     *
     * @param string $responseId
     * @param array<string, mixed> $params Optional query parameters, e.g. ['include' => ['message.output_text.logprobs']]
     * @param array<string, string> $headers Extra request headers, e.g. ['OpenAI-Beta' => 'responses_multi_agent=v1']
     * @return array
     */
    public function getResponse(string $responseId, array $params = [], array $headers = []): array;

    /**
     * Resume streaming a response created with background=true and stream=true (GET /v1/responses/{id}?stream=true).
     *
     * @param array<string, mixed> $params Optional query parameters, e.g. ['starting_after' => 42]
     * @param array<string, string> $headers Extra request headers, e.g. ['OpenAI-Beta' => 'responses_multi_agent=v1']
     * @return iterable<array<string, mixed>> Decoded Server-Sent Events
     */
    public function resumeStream(string $responseId, array $params = [], array $headers = []): iterable;

    /**
     * Compact a long-running conversation into a smaller input for the next turn (POST /v1/responses/compact).
     *
     * @param array<string, mixed> $payload
     * @param array<string, string> $headers Extra request headers, e.g. ['OpenAI-Beta' => 'responses_multi_agent=v1']
     */
    public function compactResponse(array $payload, array $headers = []): array;

    /**
     * Count the input tokens a response request would use without generating it (POST /v1/responses/input_tokens).
     *
     * @param array<string, mixed> $payload The same parameters as a responses.create request
     * @param array<string, string> $headers Extra request headers, e.g. ['OpenAI-Beta' => 'responses_multi_agent=v1']
     */
    public function countInputTokens(array $payload, array $headers = []): array;

    /**
     * List responses.
     *
     * @param array $params Optional query params for pagination/filtering
     * @return array
     */
    public function listResponses(array $params = []): array;

    /**
     * Cancel a response by id.
     *
     * @param string $responseId
     * @param array<string, string> $headers Extra request headers, e.g. ['OpenAI-Beta' => 'responses_multi_agent=v1']
     * @return bool
     */
    public function cancelResponse(string $responseId, array $headers = []): bool;

    /**
     * Delete a response by id.
     *
     * @param string $responseId
     * @param array<string, string> $headers Extra request headers, e.g. ['OpenAI-Beta' => 'responses_multi_agent=v1']
     * @return bool
     */
    public function deleteResponse(string $responseId, array $headers = []): bool;
}
