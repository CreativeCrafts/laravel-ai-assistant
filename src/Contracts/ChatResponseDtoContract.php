<?php

declare(strict_types=1);

namespace CreativeCrafts\LaravelAiAssistant\Contracts;

interface ChatResponseDtoContract
{
    /**
     * Creates a new ChatResponseDto instance from an array of response data.
     *
     * This factory method constructs a ChatResponseDto object by extracting and mapping
     * values from the provided array. It handles various response formats by checking
     * multiple possible locations for status information and uses the extractContent
     * method to intelligently parse content from different response structures.
     *
     * @param array $data The response data array from the AI service containing the chat response information
     * @return self A new ChatResponseDto instance populated with data from the input array
     */
    public static function fromArray(array $data): self;

    /**
     * Converts the ChatResponseDto instance to an array representation.
     *
     * Returns the raw response data originally used to create this DTO instance.
     * This method provides access to the complete, unprocessed response data from the AI service,
     * which may contain additional fields beyond those explicitly mapped to DTO properties.
     *
     * @return array The raw response data array containing all original fields and values from the AI service response
     */
    public function toArray(): array;
}
