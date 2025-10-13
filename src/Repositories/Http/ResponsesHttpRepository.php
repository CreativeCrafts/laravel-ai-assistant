<?php

declare(strict_types=1);

namespace CreativeCrafts\LaravelAiAssistant\Repositories\Http;

use CreativeCrafts\LaravelAiAssistant\Contracts\ResponsesRepositoryContract;
use CreativeCrafts\LaravelAiAssistant\Exceptions\ApiResponseValidationException;
use CreativeCrafts\LaravelAiAssistant\Exceptions\MaxRetryAttemptsExceededException;
use CreativeCrafts\LaravelAiAssistant\Transport\GuzzleOpenAITransport;
use CreativeCrafts\LaravelAiAssistant\Transport\OpenAITransport;
use GuzzleHttp\Client as GuzzleClient;
use Illuminate\Support\Facades\Config;
use JsonException;

/**
 * @internal This class is used internally by AssistantService.
 * Do not use directly - use Ai::responses() or Ai::conversations() instead.
 */
final readonly class ResponsesHttpRepository implements ResponsesRepositoryContract
{
    private OpenAITransport $transport;

    public function __construct(
        private GuzzleClient $http,
        private string $basePath = '/v1'
    ) {
        $this->transport = new GuzzleOpenAITransport($this->http, $this->basePath);
    }

    public function listResponses(array $params = []): array
    {
        $query = '';
        if (!empty($params)) {
            $query = '?' . http_build_query($params);
        }
        return $this->transport->getJson($this->endpoint('responses') . $query);
    }

    /**
     * Creates a new AI response by sending a request to the API with automatic retry logic.
     * This method sends a POST request to the AI API to generate a new response based on the
     * provided payload. It automatically handles idempotency key generation or extraction
     * from the payload to ensure request safety, configures appropriate headers and timeout
     * settings, and implements retry logic for handling transient failures. The method
     * processes the API response and returns the decoded result as an associative array.
     *
     * @param array $payload The request payload containing the AI prompt and parameters. May include an optional '_idempotency_key' field for request deduplication, which will be extracted and used as a header
     * @return array The decoded API response as an associative array containing the AI-generated response data
     * @throws ApiResponseValidationException When the API returns an error response (status >= 400) or when the response format is invalid
     * @throws MaxRetryAttemptsExceededException When the maximum number of retry attempts is exceeded without a successful response
     * @throws JsonException When the response body cannot be decoded as valid JSON or when network/transport errors occur
     */
    public function createResponse(array $payload): array
    {
        // Delegate to shared transport with idempotency and per-call timeout from config handled internally
        return $this->transport->postJson('/v1/responses', $payload, idempotent: true);
    }

    /**
     * Initiates a streaming AI response using Server-Sent Events (SSE) for real-time data delivery.
     * This method establishes a streaming connection to the AI API to receive response data
     * in real-time chunks via Server-Sent Events. It automatically handles idempotency key
     * generation or extraction from the payload, configures appropriate headers for SSE,
     * and implements retry logic for the initial connection. The method yields individual
     * lines from the stream as they arrive, allowing consumers to process the response
     * incrementally rather than waiting for the complete response.
     *
     * @param array $payload The request payload containing the AI prompt and parameters. May include an optional '_idempotency_key' field for request deduplication
     * @return iterable<string> A generator that yields individual lines from the SSE stream as they are received from the API
     * @throws ApiResponseValidationException When the API returns an error response (status >= 400) or when the response format is invalid
     * @throws MaxRetryAttemptsExceededException When the maximum number of retry attempts is exceeded during the initial connection
     * @throws JsonException When the HTTP request fails due to network or client issues
     */
    public function streamResponse(array $payload): iterable
    {
        // Delegate streaming to shared transport; include ['stream' => true] as per API
        return $this->transport->streamSse('/v1/responses', $payload + ['stream' => true], idempotent: true);
    }

    /**
     * Retrieves a specific AI response by its unique identifier.
     * This method delegates to the shared transport (with built-in retry and validation)
     * to fetch the response details as an associative array.
     *
     * @param string $responseId The unique identifier of the response to retrieve
     * @return array The response data as an associative array containing the AI response details
     * @throws ApiResponseValidationException When the API returns an error response (status >= 400) or when the response format is invalid
     * @throws MaxRetryAttemptsExceededException When the maximum number of retry attempts is exceeded due to transport errors
     * @throws JsonException When the response body cannot be decoded as valid JSON
     */
    public function getResponse(string $responseId): array
    {
        $timeout = Config::integer(key: 'ai-assistant.responses.timeout', default: 120);
        if (!is_numeric($timeout)) {
            $timeout = 120;
        }
        return $this->transport->getJson($this->endpoint("responses/{$responseId}"), timeout: (float)$timeout);
    }

    /**
     * Cancels an active AI response by its unique identifier.
     * Delegates to the transport layer to ensure unified retries and exception handling.
     *
     * @param string $responseId The unique identifier of the response to be cancelled
     * @return bool Always returns true when the cancellation request is successful
     * @throws ApiResponseValidationException When the API returns an error response (status >= 400)
     * @throws MaxRetryAttemptsExceededException When the maximum number of retry attempts is exceeded during the request
     * @throws JsonException When the response body cannot be decoded as valid JSON
     */
    public function cancelResponse(string $responseId): bool
    {
        $timeout = Config::integer(key: 'ai-assistant.responses.timeout', default: 120);
        if (!is_numeric($timeout)) {
            $timeout = 120;
        }
        // We ignore the response body; exceptions will be thrown by the transport if needed
        $this->transport->postJson($this->endpoint("responses/{$responseId}/cancel"), [], timeout: (float)$timeout);
        return true;
    }

    /**
     * Deletes a specific AI response by its unique identifier.
     * Delegates to the transport layer to ensure unified retries and exception handling.
     *
     * @param string $responseId The unique identifier of the response to be deleted
     * @return bool Always returns true when the deletion is successful
     * @throws ApiResponseValidationException When the API returns an error response (status >= 400)
     * @throws MaxRetryAttemptsExceededException When the maximum number of retry attempts is exceeded during the request
     */
    public function deleteResponse(string $responseId): bool
    {
        return $this->transport->delete($this->endpoint("responses/{$responseId}"));
    }

    /**
     * Generates a unique idempotency key for API requests.
     * This method creates a secure random key using random_bytes, with fallbacks
     * for cases where secure random generation is not available.
     *
     * @return string The generated idempotency key
     */

    /**
     * Executes an HTTP POST request with automatic retry logic and exponential backoff.
     * This method performs HTTP requests to the OpenAI API with configurable retry behavior
     * for handling transient failures. It implements exponential backoff with optional jitter
     * to avoid thundering herd problems. For create and stream operations, it preserves the
     * idempotency key across retry attempts to ensure request safety. The method evaluates
     * both HTTP response status codes and thrown exceptions to determine retry eligibility.
     *
     * @param array $options The HTTP request options array containing headers, JSON payload, timeout, and other Guzzle options
     * @param bool $isCreateOrStream Whether this is a create or stream operation that requires idempotency key preservation across retries
     * @return ResponseInterface The successful HTTP response object from the API
     * @throws ApiResponseValidationException When all retry attempts are exhausted due to non-retryable errors or when transport errors occur
     * @throws MaxRetryAttemptsExceededException When the maximum number of retry attempts is exceeded without a successful response
     */

    /**
     * Constructs a complete API endpoint URL by combining the base path with a given path.
     * This method ensures proper URL formatting by removing trailing slashes from the base path
     * and leading slashes from the provided path, then joining them with a single forward slash.
     * This prevents issues with double slashes or missing separators in the final endpoint URL.
     *
     * @param string $path The relative path to append to the base path (e.g. 'responses', 'responses/123/cancel')
     * @return string The complete endpoint URL with proper slash formatting (e.g. '/v1/responses')
     */
    private function endpoint(string $path): string
    {
        $prefix = rtrim($this->basePath, '/');
        $suffix = ltrim($path, '/');
        return $prefix . '/' . $suffix;
    }




}
