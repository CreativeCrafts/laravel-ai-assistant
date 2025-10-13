<?php

declare(strict_types=1);

namespace CreativeCrafts\LaravelAiAssistant\Adapters;

use CreativeCrafts\LaravelAiAssistant\DataTransferObjects\ResponseDto;

/**
 * Interface for adapting unified requests to endpoint-specific formats.
 *
 * This interface defines the contract for transforming requests and responses
 * between the unified Response API format and specific OpenAI endpoint formats
 * (audio, image, chat completion, etc.).
 *
 * @internal Used internally by ResponsesBuilder to transform requests for specific endpoints.
 * Do not use directly.
 */
interface EndpointAdapter
{
    /**
     * Transform a unified request into an endpoint-specific format.
     *
     * Takes the unified request data structure and converts it into the format
     * expected by a specific OpenAI endpoint (e.g., audio transcription, image generation).
     *
     * @param array<string, mixed> $unifiedRequest The unified request data from the Response API
     * @return array<string, mixed> The transformed request data specific to the target endpoint
     */
    public function transformRequest(array $unifiedRequest): array;

    /**
     * Transform an endpoint-specific API response into a unified ResponseDto.
     *
     * Takes the raw response from a specific OpenAI endpoint and converts it into
     * the standardized ResponseDto format used throughout the package.
     *
     * @param array<string, mixed> $apiResponse The raw API response from the OpenAI endpoint
     * @return ResponseDto The unified response data transfer object
     */
    public function transformResponse(array $apiResponse): ResponseDto;
}
