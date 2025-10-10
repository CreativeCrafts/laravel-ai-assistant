<?php

declare(strict_types=1);

namespace CreativeCrafts\LaravelAiAssistant\Services;

use CreativeCrafts\LaravelAiAssistant\Compat\OpenAI\Client;
use CreativeCrafts\LaravelAiAssistant\Enums\OpenAiEndpoint;
use CreativeCrafts\LaravelAiAssistant\Http\MultipartRequestBuilder;
use CreativeCrafts\LaravelAiAssistant\Transport\OpenAITransport;
use InvalidArgumentException;
use ReflectionClass;
use RuntimeException;
use SplFileInfo;
use Throwable;

/**
 * OpenAI Client Handler for making HTTP calls to different OpenAI API endpoints.
 *
 * This service handles:
 * - Routing requests to correct OpenAI endpoint URLs
 * - Automatic multipart encoding detection
 * - Binary response data handling (audio generation)
 * - Endpoint-specific timeout configuration
 * - Error handling with endpoint context
 * - Retry logic (delegated to transport layer)
 */
final class OpenAiClient
{
    private const DEFAULT_TIMEOUT = 60.0;
    private const AUDIO_SPEECH_TIMEOUT = 120.0;
    private const IMAGE_TIMEOUT = 180.0;

    private OpenAITransport $transport;

    public function __construct(
        private readonly Client $client,
        private readonly MultipartRequestBuilder $multipartBuilder,
    ) {
        // Access the transport from the client's audio resource
        // This ensures we use the same configured transport
        $audioResource = $this->client->audio();
        $reflection = new ReflectionClass($audioResource);
        $transportProperty = $reflection->getProperty('transport');
        $transportProperty->setAccessible(true);
        $transport = $transportProperty->getValue($audioResource);

        if (!$transport instanceof OpenAITransport) {
            throw new RuntimeException('Failed to extract OpenAITransport from client');
        }

        $this->transport = $transport;
    }

    /**
     * Call a specific OpenAI endpoint with the provided data.
     *
     * @param OpenAiEndpoint $endpoint The endpoint to call
     * @param array<string,mixed> $data The request data
     * @return array<string,mixed> The API response
     * @throws InvalidArgumentException If endpoint or data is invalid
     * @throws RuntimeException If the API call fails
     */
    public function callEndpoint(OpenAiEndpoint $endpoint, array $data): array
    {
        try {
            $url = $endpoint->url();
            $timeout = $this->getTimeoutForEndpoint($endpoint);

            // For audio speech endpoint, handle binary response differently
            if ($endpoint === OpenAiEndpoint::AudioSpeech) {
                return $this->handleAudioSpeechRequest($url, $data, $timeout);
            }

            // Determine if this endpoint requires multipart encoding
            if ($endpoint->requiresMultipart()) {
                return $this->makeMultipartRequest($endpoint, $url, $data, $timeout);
            }

            // Standard JSON request
            return $this->makeJsonRequest($endpoint, $url, $data, $timeout);
        } catch (InvalidArgumentException $e) {
            throw new InvalidArgumentException(
                "Invalid request for endpoint '{$endpoint->value}': {$e->getMessage()}",
                $e->getCode(),
                $e
            );
        } catch (RuntimeException $e) {
            throw new RuntimeException(
                "API call to '{$endpoint->value}' failed: {$e->getMessage()}",
                $e->getCode(),
                $e
            );
        } catch (Throwable $e) {
            throw new RuntimeException(
                "Unexpected error calling '{$endpoint->value}' endpoint: {$e->getMessage()}",
                0,
                $e
            );
        }
    }

    /**
     * Get endpoint-specific timeout value.
     */
    private function getTimeoutForEndpoint(OpenAiEndpoint $endpoint): float
    {
        return match (true) {
            $endpoint === OpenAiEndpoint::AudioSpeech => self::AUDIO_SPEECH_TIMEOUT,
            $endpoint->isImage() => self::IMAGE_TIMEOUT,
            default => self::DEFAULT_TIMEOUT,
        };
    }

    /**
     * Make a JSON request to the specified endpoint.
     *
     * @param OpenAiEndpoint $endpoint
     * @param string $url
     * @param array<string,mixed> $data
     * @param float $timeout
     * @return array<string,mixed>
     */
    private function makeJsonRequest(OpenAiEndpoint $endpoint, string $url, array $data, float $timeout): array
    {
        try {
            // Determine if this endpoint supports idempotency
            $idempotent = $endpoint === OpenAiEndpoint::ResponseApi
                || $endpoint === OpenAiEndpoint::ChatCompletion;

            return $this->transport->postJson($url, $data, [], $timeout, $idempotent);
        } catch (Throwable $e) {
            throw new RuntimeException(
                "JSON request to '{$endpoint->value}' failed: {$e->getMessage()}",
                0,
                $e
            );
        }
    }

    /**
     * Make a multipart request to the specified endpoint.
     *
     * @param OpenAiEndpoint $endpoint
     * @param string $url
     * @param array<string,mixed> $data
     * @param float $timeout
     * @return array<string,mixed>
     */
    private function makeMultipartRequest(OpenAiEndpoint $endpoint, string $url, array $data, float $timeout): array
    {
        try {
            $this->multipartBuilder->clear();

            // Add file(s) to multipart request
            if (isset($data['file'])) {
                $file = $data['file'];
                if (!is_string($file) && !$file instanceof SplFileInfo) {
                    throw new InvalidArgumentException('File parameter must be a string path or SplFileInfo instance');
                }
                $fileType = $endpoint->isAudio() ? 'audio' : 'image';
                $this->multipartBuilder->addFile('file', $file, null, null, $fileType);
                unset($data['file']);
            }

            // For image edit endpoint, add mask if present
            if ($endpoint === OpenAiEndpoint::ImageEdit && isset($data['mask'])) {
                $mask = $data['mask'];
                if (!is_string($mask) && !$mask instanceof SplFileInfo) {
                    throw new InvalidArgumentException('Mask parameter must be a string path or SplFileInfo instance');
                }
                $this->multipartBuilder->addFile('mask', $mask, null, null, 'image');
                unset($data['mask']);
            }

            // For image edit/variation, add image if present
            if (($endpoint === OpenAiEndpoint::ImageEdit || $endpoint === OpenAiEndpoint::ImageVariation)
                && isset($data['image'])) {
                $image = $data['image'];
                if (!is_string($image) && !$image instanceof SplFileInfo) {
                    throw new InvalidArgumentException('Image parameter must be a string path or SplFileInfo instance');
                }
                $this->multipartBuilder->addFile('image', $image, null, null, 'image');
                unset($data['image']);
            }

            // Add remaining fields
            foreach ($data as $key => $value) {
                if ($value !== null) {
                    $this->multipartBuilder->addField($key, $value);
                }
            }

            $multipartData = $this->multipartBuilder->build();

            // Multipart endpoints don't support idempotency in the same way
            return $this->transport->postMultipart($url, $multipartData, [], $timeout, false);
        } catch (Throwable $e) {
            throw new RuntimeException(
                "Multipart request to '{$endpoint->value}' failed: {$e->getMessage()}",
                0,
                $e
            );
        }
    }

    /**
     * Handle audio speech request which returns binary data.
     *
     * @param string $url
     * @param array<string,mixed> $data
     * @param float $timeout
     * @return array<string,mixed>
     */
    private function handleAudioSpeechRequest(string $url, array $data, float $timeout): array
    {
        try {
            // For audio speech, we still use postJson but need to handle binary response
            // The transport will handle the actual HTTP call
            $response = $this->transport->postJson($url, $data, [], $timeout, false);

            // If the response contains binary data in the 'content' key, preserve it
            // Otherwise, return the response as-is
            return $response;
        } catch (Throwable $e) {
            throw new RuntimeException(
                "Audio speech request failed: {$e->getMessage()}",
                0,
                $e
            );
        }
    }
}
