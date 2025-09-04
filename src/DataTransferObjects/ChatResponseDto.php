<?php

declare(strict_types=1);

namespace CreativeCrafts\LaravelAiAssistant\DataTransferObjects;

use CreativeCrafts\LaravelAiAssistant\Contracts\ChatResponseDtoContract;

final readonly class ChatResponseDto implements ChatResponseDtoContract
{
    public function __construct(
        public string $id,
        public string $status,
        public ?string $content,
        public array $raw,
    ) {
    }

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
    public static function fromArray(array $data): self
    {
        return new self(
            id: (string)($data['id'] ?? ''),
            status: (string)($data['status'] ?? ($data['response']['status'] ?? 'unknown')),
            content: self::extractContent($data),
            raw: $data,
        );
    }

    /**
     * Converts the ChatResponseDto instance to an array representation.
     *
     * Returns the raw response data originally used to create this DTO instance.
     * This method provides access to the complete, unprocessed response data from the AI service,
     * which may contain additional fields beyond those explicitly mapped to DTO properties.
     *
     * @return array The raw response data array containing all original fields and values from the AI service response
     */
    public function toArray(): array
    {
        return $this->raw;
    }

    /**
     * Extracts content from the response data array.
     *
     * Attempts to find content in the response data by checking for 'output_text'
     * first, then falling back to 'content'. Returns null if neither field is found.
     *
     * @param array $data The response data array from the AI service
     * @return string|null The extracted content as a string, or null if no content is found
     */
    private static function extractContent(array $data): ?string
    {
        if (isset($data['output_text'])) {
            return (string)$data['output_text'];
        }

        if (isset($data['content'])) {
            return (string)$data['content'];
        }

        return null;
    }
}
