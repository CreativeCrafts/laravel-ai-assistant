<?php

declare(strict_types=1);

namespace CreativeCrafts\LaravelAiAssistant\Contracts;

interface ResponsesInputItemsRepositoryContract
{
    /**
     * Append input items to a response.
     *
     * @param string $responseId
     * @param array $items
     * @return array
     */
    public function append(string $responseId, array $items): array;

    /**
     * List input items for a response.
     *
     * @param string $responseId
     * @param array $params
     * @return array
     */
    public function list(string $responseId, array $params = []): array;
}
