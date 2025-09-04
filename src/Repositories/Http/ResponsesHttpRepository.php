<?php

declare(strict_types=1);

namespace CreativeCrafts\LaravelAiAssistant\Repositories\Http;

use CreativeCrafts\LaravelAiAssistant\Contracts\ResponsesRepositoryContract;
use CreativeCrafts\LaravelAiAssistant\Exceptions\ApiResponseValidationException;
use CreativeCrafts\LaravelAiAssistant\Exceptions\MaxRetryAttemptsExceededException;
use GuzzleHttp\Client as GuzzleClient;
use GuzzleHttp\Exception\GuzzleException;
use Illuminate\Support\Facades\Config;
use JsonException;
use Psr\Http\Message\ResponseInterface;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

final readonly class ResponsesHttpRepository implements ResponsesRepositoryContract
{
    public function __construct(
        private GuzzleClient $http,
        private string $basePath = '/v1'
    ) {
    }

    /**
     * Creates a new AI response by sending a request to the API with automatic retry logic.
     *
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
        $headers = ['Content-Type' => 'application/json'];
        // Generate or propagate an idempotency key for create calls if enabled
        $idemEnabled = (bool) (config('ai-assistant.responses.idempotency_enabled', true));
        if (isset($payload['_idempotency_key']) && is_string($payload['_idempotency_key']) && $payload['_idempotency_key'] !== '') {
            $headers['Idempotency-Key'] = $payload['_idempotency_key'];
            unset($payload['_idempotency_key']);
        } elseif ($idemEnabled) {
            $headers['Idempotency-Key'] = $this->generateIdempotencyKey();
        }

        $timeout = Config::integer(key: 'ai-assistant.responses.timeout', default: 120);
        if (!is_numeric($timeout)) {
            $timeout = 120;
        }
        $options = [
            'headers' => $headers,
            'json' => $payload,
            'timeout' => (float) $timeout,
        ];

        $res = $this->requestWithRetry($options, true);
        return $this->decodeOrFail($res);
    }

    /**
     * Initiates a streaming AI response using Server-Sent Events (SSE) for real-time data delivery.
     *
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
        // Request SSE stream with idempotency and retry on initial POST
        $headers = [
            'Accept' => 'text/event-stream',
            'Content-Type' => 'application/json',
        ];
        $idemEnabled = Config::boolean(key: 'ai-assistant.responses.idempotency_enabled', default: true);
        if (isset($payload['_idempotency_key']) && is_string($payload['_idempotency_key']) && $payload['_idempotency_key'] !== '') {
            $headers['Idempotency-Key'] = $payload['_idempotency_key'];
            unset($payload['_idempotency_key']);
        } elseif ($idemEnabled) {
            $headers['Idempotency-Key'] = $this->generateIdempotencyKey();
        }

        $sseTimeout = Config::integer(key: 'ai-assistant.streaming.sse_timeout');
        if (!is_numeric($sseTimeout)) {
            $responseTimeout = Config::integer(key: 'ai-assistant.responses.timeout', default: 120);
            if (!is_numeric($responseTimeout)) {
                $responseTimeout = 120;
            }
            $sseTimeout = $responseTimeout;
        }
        $options = [
            'headers' => $headers,
            'json' => $payload + ['stream' => true],
            'stream' => true,
            'timeout' => (float) $sseTimeout,
        ];

        $res = $this->requestWithRetry($options, true);
        if ($res->getStatusCode() >= Response::HTTP_BAD_REQUEST) {
            $this->throwForError($res);
        }

        $body = $res->getBody();
        while (!$body->eof()) {
            $chunk = $body->read(1024);
            if ($chunk === '') {
                // allow yielding control to the consumer
                continue;
            }
            $lines = preg_split('/\r?\n/', $chunk);
            if ($lines !== false) {
                foreach ($lines as $line) {
                    if ($line === '') {
                        continue;
                    }
                    yield $line;
                }
            }
        }
    }

    /**
     * Retrieves a specific AI response by its unique identifier.
     *
     * This method sends a GET request to the API to fetch details of a previously
     * created response. The request includes a configurable timeout to handle
     * potentially slow API responses. If the retrieval fails due to network issues
     * or API errors, appropriate exceptions are thrown with error details.
     *
     * @param string $responseId The unique identifier of the response to retrieve
     * @return array The response data as an associative array containing the AI response details
     * @throws ApiResponseValidationException When the API returns an error response (status >= 400) or when the response format is invalid
     * @throws GuzzleException When the HTTP request fails due to network or client issues
     * @throws JsonException When the response body cannot be decoded as valid JSON
     */
    public function getResponse(string $responseId): array
    {
        $timeout = Config::integer(key: 'ai-assistant.responses.timeout', default: 120);
        if (!is_numeric($timeout)) {
            $timeout = 120;
        }
        $res = $this->http->get($this->endpoint("responses/{$responseId}"), [
            'timeout' => (float) $timeout,
        ]);
        return $this->decodeOrFail($res);
    }

    /**
     * Cancels an active AI response by its unique identifier.
     *
     * This method sends a POST request to the API to cancel an ongoing response generation.
     * If the cancellation request fails (HTTP status >= 400), an exception is thrown with
     * details about the error. The method is typically used to stop streaming responses
     * or abort long-running response generation processes.
     *
     * @param string $responseId The unique identifier of the response to be cancelled
     * @return bool Always returns true when the cancellation request is successful
     * @throws ApiResponseValidationException When the API returns an error response (status >= 400)
     * @throws GuzzleException|JsonException When the HTTP request fails due to network or client issues
     */
    public function cancelResponse(string $responseId): bool
    {
        $timeout = Config::integer(key: 'ai-assistant.responses.timeout', default: 120);
        if (!is_numeric($timeout)) {
            $timeout = 120;
        }
        $response = $this->http->post($this->endpoint("responses/{$responseId}/cancel"), [
            'timeout' => (float) $timeout,
        ]);
        if ($response->getStatusCode() >= Response::HTTP_BAD_REQUEST) {
            $this->throwForError($response);
        }
        return true;
    }

    /**
     * Deletes a specific AI response by its unique identifier.
     * This method sends a DELETE request to the API to permanently remove the specified
     * response from the system. If the deletion request fails (HTTP status >= 400),
     * an exception is thrown with details about the error.
     *
     * @param string $responseId The unique identifier of the response to be deleted
     * @return bool Always returns true when the deletion is successful
     * @throws JsonException
     * @throws GuzzleException
     */
    public function deleteResponse(string $responseId): bool
    {
        $response = $this->http->delete($this->endpoint("responses/{$responseId}"));
        if ($response->getStatusCode() >= Response::HTTP_BAD_REQUEST) {
            $this->throwForError($response);
        }
        return true;
    }

    /**
     * Decodes a successful HTTP response body as JSON or throws an exception on failure.
     *
     * This method validates the HTTP response status code and attempts to decode the response
     * body as JSON. If the response indicates an error (status code >= 400), it delegates
     * error handling to throwForError(). If JSON decoding succeeds but doesn't result in
     * an array, it throws a validation exception indicating an unexpected response format.
     *
     * @param ResponseInterface $response The HTTP response object to decode and validate
     * @return array The decoded JSON response body as an associative array
     * @throws ApiResponseValidationException When the response status indicates an error (>= 400) or when the decoded JSON is not an array
     * @throws JsonException When JSON decoding fails due to malformed JSON content
     */
    private function decodeOrFail(ResponseInterface $response): array
    {
        if ($response->getStatusCode() >= Response::HTTP_BAD_REQUEST) {
            $this->throwForError($response);
        }
        $data = json_decode((string)$response->getBody(), true, 512, JSON_THROW_ON_ERROR);
        if (!is_array($data)) {
            throw new ApiResponseValidationException('Unexpected response format from OpenAI.');
        }
        return $data;
    }

    /**
     * Throws an ApiResponseValidationException for HTTP error responses.
     * This method extracts error information from the HTTP response body and throws
     * an appropriate exception. It attempts to parse the response body as JSON to
     * extract a structured error message, falling back to the raw body content if
     * JSON parsing fails or no structured error is found.
     *
     * @param ResponseInterface $response The HTTP response object containing the error
     * @return void This method does not return as it always throws an exception
     * @throws ApiResponseValidationException|JsonException Always thrown with the extracted error message and HTTP status code
     */
    private function throwForError(ResponseInterface $response): void
    {
        $body = (string) $response->getBody();
        $msg = 'OpenAI API error';
        $json = json_decode($body, true, 512, JSON_THROW_ON_ERROR);
        if (is_array($json)) {
            if (isset($json['error']['message']) && is_string($json['error']['message'])) {
                $msg = fluent($json)->string(key: 'error.message')->value();
            } elseif (isset($json['message']) && is_string($json['message'])) {
                $msg = fluent($json)->string(key: 'message')->value();
            } elseif (isset($json['error']) && is_string($json['error'])) {
                $msg = fluent($json)->string(key: 'error')->value();
            } elseif (isset($json['errors'][0]['message']) && is_array($json['errors'])) {
                $msg = fluent($json)->string(key: 'errors.0.message')->value();
            } elseif ($body !== '') {
                $msg = $body;
            }
        } elseif ($body !== '') {
            $msg = $body;
        }
        throw new ApiResponseValidationException($msg, $response->getStatusCode());
    }

    /**
     * Generates a unique idempotency key for API requests.
     *
     * This method creates a secure random key using random_bytes, with fallbacks
     * for cases where secure random generation is not available.
     *
     * @return string The generated idempotency key
     */
    private function generateIdempotencyKey(): string
    {
        try {
            return bin2hex(random_bytes(16));
        } catch (Throwable) {
            // Fallback: derive a pseudo-random value without using insecure uniqid
            $ri = '';
            try {
                $ri = (string) random_int(PHP_INT_MIN, PHP_INT_MAX);
            } catch (Throwable) {
                // As a last resort, avoid insecure RNGs; derive mixed entropy from time, pid, and host
                $timeInt = (int) round(microtime(true) * 1_000_000);
                $pid = (int) getmypid();
                $hostCrc = (int) crc32((string) gethostname());
                $ri = (string) (($timeInt ^ $pid) ^ $hostCrc);
            }
            $data = microtime(true)
                . '|' . (string) getmypid()
                . '|' . (string) $ri;
            return hash('sha256', (string) $data);
        }
    }

    /**
     * Executes an HTTP POST request with automatic retry logic and exponential backoff.
     *
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
    private function requestWithRetry(array $options, bool $isCreateOrStream = false): ResponseInterface
    {
        $retryCfg = (array) config('ai-assistant.responses.retry', []);
        $enabled = (bool) ($retryCfg['enabled'] ?? true);
        $maxAttempts = (int) ($retryCfg['max_attempts'] ?? 3);
        $initialDelay = (float) ($retryCfg['initial_delay'] ?? 0.5);
        $multiplier = (float) ($retryCfg['backoff_multiplier'] ?? 2.0);
        $maxDelay = (float) ($retryCfg['max_delay'] ?? 8.0);
        $jitter = (bool) ($retryCfg['jitter'] ?? true);

        $attempt = 0;
        $lastException = null;
        $lastResponse = null;

        do {
            $attempt++;
            try {
                $res = $this->http->request('POST', $this->endpoint('responses'), $options);
                $lastResponse = $res;
                if (!$enabled || $attempt >= $maxAttempts || !$this->isRetryableResponse($res)) {
                    return $res;
                }
            } catch (Throwable $e) {
                $lastException = $e;
                if (!$enabled || $attempt >= $maxAttempts || !$this->isRetryableException($e)) {
                    // Normalize transport/network errors
                    throw new ApiResponseValidationException($e->getMessage() ?: 'Transport error during OpenAI request.', Response::HTTP_BAD_GATEWAY);
                }
            }

            // sleep before next retry
            $delay = $this->computeDelay($attempt, $initialDelay, $multiplier, $maxDelay, $jitter);
            usleep((int) round($delay * 1_000_000));

            // ensure Idempotency-Key remains for create/stream calls
            if ($isCreateOrStream) {
                $options['headers'] = ($options['headers'] ?? []) + ['Idempotency-Key' => $options['headers']['Idempotency-Key'] ?? $this->generateIdempotencyKey()];
            }

        } while ($attempt < $maxAttempts);

        if ($lastResponse instanceof ResponseInterface) {
            return $lastResponse;
        }

        throw new MaxRetryAttemptsExceededException('Maximum retry attempts exceeded for OpenAI request.');
    }

    /**
     * Determines whether an HTTP response status code indicates a retryable error condition.
     *
     * This method evaluates HTTP response status codes to identify transient failures that
     * could potentially succeed on retry. It considers conflict errors (409), rate limiting
     * errors (429), and server errors (5xx) as retryable conditions, while treating client
     * errors (4xx, except 409 and 429) and successful responses (2xx-3xx) as non-retryable.
     *
     * @param ResponseInterface $response The HTTP response object containing the status code to evaluate
     * @return bool Returns true if the response status indicates a retryable error condition, false otherwise
     */
    private function isRetryableResponse(ResponseInterface $response): bool
    {
        $status = $response->getStatusCode();
        if ($status === Response::HTTP_CONFLICT || $status === Response::HTTP_TOO_MANY_REQUESTS) {
            return true;
        }
        if ($status >= Response::HTTP_INTERNAL_SERVER_ERROR && $status <= Response::HTTP_VERSION_NOT_SUPPORTED) {
            return true;
        }
        return false;
    }

    /**
     * Determines whether a thrown exception should trigger a retry attempt.
     *
     * This method evaluates exceptions that occur during HTTP requests to determine
     * if they represent transient failures that could potentially succeed on retry.
     * Currently, all exceptions are considered retryable as they typically represent
     * network connectivity issues, timeouts, or other transport-level problems that
     * may resolve themselves on later attempts.
     *
     * @param Throwable $e The exception that was thrown during the HTTP request attempt
     * @return bool Always returns true, indicating that all exceptions are considered retryable
     */
    private function isRetryableException(Throwable $e): bool
    {
        // Network/transport errors considered retryable by default
        return true;
    }

    /**
     * Computes the delay duration for retry attempts using exponential backoff with optional jitter.
     *
     * This method calculates the delay time before the next retry attempt using an exponential
     * backoff algorithm. The delay starts with an initial value and increases exponentially
     * with each attempt using the specified multiplier, capped at a maximum delay. Optional
     * jitter can be applied to randomise the delay and help prevent thundering herd problems.
     *
     * @param int $attempt The current attempt number (1-based, where 1 is the first retry)
     * @param float $initial The initial delay duration in seconds for the first retry attempt
     * @param float $multiplier The exponential backoff multiplier applied to increase delay between attempts
     * @param float $max The maximum allowed delay duration in seconds to cap the exponential growth
     * @param bool $jitter Whether to apply random jitter (±50%) to the calculated delay to avoid synchronized retries
     * @return float The computed delay duration in seconds, potentially with jitter applied
     */
    private function computeDelay(int $attempt, float $initial, float $multiplier, float $max, bool $jitter): float
    {
        $delay = $initial * ($multiplier ** max(0, $attempt - 1));
        $delay = min($delay, $max);
        if ($jitter) {
            try {
                $rand = random_int(0, PHP_INT_MAX) / PHP_INT_MAX;
            } catch (Throwable) {
                // Fallback to neutral jitter if secure RNG is unavailable
                $rand = 0.5;
            }
            // apply +/- 50% jitter
            $delay *= (0.5 + $rand * 0.5);
        }
        return $delay;
    }

    /**
     * Constructs a complete API endpoint URL by combining the base path with a given path.
     *
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
