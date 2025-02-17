<?php

declare(strict_types=1);

namespace CreativeCrafts\LaravelAiAssistant\DataTransferObjects;

use CreativeCrafts\LaravelAiAssistant\Contracts\NewAssistantResponseDataContract;
use OpenAI\Responses\Assistants\AssistantResponse;

final readonly class NewAssistantResponseData implements NewAssistantResponseDataContract
{
    /**
     * Constructs a new NewAssistantResponseData instance.
     *
     * This constructor initializes the NewAssistantResponseData object with the provided AssistantResponse.
     *
     * @param AssistantResponse $assistantResponse The AssistantResponse object containing the assistant's data.
     */
    public function __construct(
        protected AssistantResponse $assistantResponse
    ) {
    }

    /**
     * Get the unique identifier of the assistant.
     *
     * This method retrieves the ID of the assistant from the AssistantResponse object.
     *
     * @return string The unique identifier (ID) of the assistant.
     */
    public function assistantId(): string
    {
        return $this->assistantResponse->id;
    }

    /**
     * Get the full AssistantResponse object.
     *
     * This method returns the complete AssistantResponse object that was used to initialize this instance.
     * It provides access to all the properties and methods of the original AssistantResponse.
     *
     * @return AssistantResponse The complete AssistantResponse object containing all assistant data.
     */
    public function assistant(): AssistantResponse
    {
        return $this->assistantResponse;
    }
}
