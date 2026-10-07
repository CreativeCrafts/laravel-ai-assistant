<?php

declare(strict_types=1);

namespace CreativeCrafts\LaravelAiAssistant\Contracts;

/**
 * @internal Low-level abstraction for response input items operations. Do not use directly.
 * Use InputBuilder via ResponsesBuilder instead.
 */
interface ResponsesInputItemsRepositoryContract
{
    /**
     * Append input items to a response.
     *
     * @deprecated The OpenAI API has no endpoint for appending input items to an existing response.
     *             Send the items with the next Ai::responses() turn or add them to a conversation instead.
     *
     * @param string $responseId
     * @param array $items
     * @return array
     */
    public function append(string $responseId, array $items): array;

    /**
     * List the input items used to generate a response (GET /v1/responses/{id}/input_items).
     *
     * @param string $responseId
     * @param array $params
     * @param array<string, string> $headers Extra request headers, e.g. ['OpenAI-Beta' => 'responses_multi_agent=v1']
     * @return array
     */
    public function list(string $responseId, array $params = [], array $headers = []): array;
}
