<?php

declare(strict_types=1);

namespace CreativeCrafts\LaravelAiAssistant\Contracts;

/**
 * Contract for assistant management operations.
 *
 * This interface defines methods for creating and retrieving AI assistants.
 */
interface AssistantManagementContract
{
    /**
     * Create a new assistant with the specified parameters.
     *
     * This function creates a new assistant using the OpenAI API client.
     *
     * @param array $parameters An array of parameters for creating the assistant.
     *                          This may include properties such as name, instructions,
     *                          tools, and model.
     *
     * @return \CreativeCrafts\LaravelAiAssistant\Compat\OpenAI\Responses\Assistants\AssistantResponse The response object containing details of the created assistant.
     */
    public function createAssistant(array $parameters): \CreativeCrafts\LaravelAiAssistant\Compat\OpenAI\Responses\Assistants\AssistantResponse;

    /**
     * Retrieve an assistant by its ID.
     *
     * This function fetches the details of a specific assistant using the OpenAI API client.
     *
     * @param string $assistantId The unique identifier of the assistant to retrieve.
     *
     * @return \CreativeCrafts\LaravelAiAssistant\Compat\OpenAI\Responses\Assistants\AssistantResponse The response object containing details of the retrieved assistant.
     */
    public function getAssistantViaId(string $assistantId): \CreativeCrafts\LaravelAiAssistant\Compat\OpenAI\Responses\Assistants\AssistantResponse;
}
