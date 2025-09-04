<?php

declare(strict_types=1);

namespace CreativeCrafts\LaravelAiAssistant\Repositories\Http;

use CreativeCrafts\LaravelAiAssistant\Contracts\ConversationsRepositoryContract;
use CreativeCrafts\LaravelAiAssistant\Exceptions\ApiResponseValidationException;
use GuzzleHttp\Client as GuzzleClient;
use GuzzleHttp\Exception\GuzzleException;
use Illuminate\Support\Facades\Config;
use JsonException;
use Psr\Http\Message\ResponseInterface;
use Symfony\Component\HttpFoundation\Response;

final readonly class ConversationsHttpRepository implements ConversationsRepositoryContract
{
    public function __construct(
        private GuzzleClient $http,
        private string $basePath = '/v1'
    ) {
    }

    /**
     * Creates a new conversation via HTTP API request.
     * Sends a POST request to the conversation endpoint with the provided payload
     * to create a new conversation instance.
     *
     * @param array $payload Optional array of conversation data to be sent in the request body.
     *                      Can include conversation settings, initial messages, or other
     *                      configuration parameters as required by the API.
     * @return array The decoded JSON response from the API containing the created
     *               conversation data, typically including the conversation ID and
     *               other relevant conversation details.
     * @throws GuzzleException When the HTTP request fails due to network issues,
     *                        timeout, or other HTTP client errors.
     * @throws ApiResponseValidationException|JsonException When the API returns an error response*@throws JsonException
     *                                       or the response format is invalid.
     */
    public function createConversation(array $payload = []): array
    {
        $timeout = Config::integer(key: 'ai-assistant.responses.timeout', default: 120);
        if (!is_numeric($timeout)) {
            $timeout = 120;
        }
        $response = $this->http->post($this->endpoint('conversations'), [
            'headers' => ['Content-Type' => 'application/json'],
            'json' => $payload,
            'timeout' => (float) $timeout,
        ]);
        return $this->decodeOrFail($response);
    }

    /**
     * Retrieves a specific conversation by its ID via HTTP API request.
     *
     * Sends a GET request to the conversation endpoint to fetch detailed
     * information about a specific conversation instance.
     *
     * @param string $conversationId The unique identifier of the conversation
     *                              to retrieve. Must be a valid conversation ID
     *                              that exists in the system.
     * @return array The decoded JSON response from the API containing the
     *               conversation data, including conversation details, settings,
     *               and metadata associated with the specified conversation.
     * @throws GuzzleException When the HTTP request fails due to network issues,
     *                        timeout, or other HTTP client errors.
     * @throws ApiResponseValidationException|JsonException When the API returns an error response
     *                                       (e.g., conversation not found) or the
     *                                       response format is invalid.
     */
    public function getConversation(string $conversationId): array
    {
        $timeout = Config::integer(key: 'ai-assistant.responses.timeout', default: 120);
        if (!is_numeric($timeout)) {
            $timeout = 120;
        }
        $response = $this->http->get($this->endpoint("conversations/{$conversationId}"), [
            'timeout' => (float) $timeout,
        ]);
        return $this->decodeOrFail($response);
    }

    /**
     * Retrieves a list of items from a specific conversation via HTTP API request.
     *
     * Sends a GET request to the conversation items endpoint to fetch all items
     * associated with a particular conversation. Items typically represent messages,
     * responses, or other conversation elements.
     *
     * @param string $conversationId The unique identifier of the conversation
     *                              whose items should be retrieved. Must be a valid
     *                              conversation ID that exists in the system.
     * @param array $params Optional array of query parameters to filter or modify
     *                     the request. Can include pagination parameters (limit, offset),
     *                     filtering criteria, sorting options, or other API-specific
     *                     parameters as supported by the endpoint.
     * @return array The decoded JSON response from the API containing an array
     *               of conversation items, including their content, metadata,
     *               timestamps, and other relevant item details.
     * @throws GuzzleException When the HTTP request fails due to network issues,
     *                        timeout, or other HTTP client errors.
     * @throws ApiResponseValidationException|JsonException When the API returns an error response
     *                                       (e.g., conversation not found) or the
     *                                       response format is invalid.
     */
    public function listItems(string $conversationId, array $params = []): array
    {
        $timeout = Config::integer(key: 'ai-assistant.responses.timeout', default: 120);
        if (!is_numeric($timeout)) {
            $timeout = 120;
        }
        $response = $this->http->get($this->endpoint("conversations/{$conversationId}/items"), [
            'query' => $params,
            'timeout' => (float) $timeout,
        ]);
        return $this->decodeOrFail($response);
    }

    /**
     * Creates multiple items within a specific conversation via HTTP API request.
     * Sends a POST request to the conversation items endpoint to add multiple
     * new items to an existing conversation. Items typically represent messages,
     * responses, or other conversation elements that need to be batch-created.
     *
     * @param string $conversationId The unique identifier of the conversation
     *                              where the items should be created. Must be a valid
     *                              conversation ID that exists in the system.
     * @param array $items An array of item data to be created within the conversation.
     *                    Each item should contain the necessary fields and content
     *                    as required by the API, such as message content, type,
     *                    metadata, or other item-specific properties.
     * @return array The decoded JSON response from the API containing the created
     *               items data, typically including the item IDs, timestamps,
     *               and other relevant details for each successfully created item.
     * @throws GuzzleException When the HTTP request fails due to network issues,
     *                        timeout, or other HTTP client errors.
     * @throws ApiResponseValidationException|JsonException When the API returns an error response*@throws JsonException
     *                                       (e.g., conversation not found, invalid
     *                                       item data) or the response format is invalid.
     */
    public function createItems(string $conversationId, array $items): array
    {
        $payload = ['items' => $items];
        $timeout = Config::integer(key: 'ai-assistant.responses.timeout', default: 120);
        if (!is_numeric($timeout)) {
            $timeout = 120;
        }
        $response = $this->http->post($this->endpoint("conversations/{$conversationId}/items"), [
            'json' => $payload,
            'timeout' => (float) $timeout,
        ]);
        return $this->decodeOrFail($response);
    }

    /**
     * Deletes a specific item from a conversation via HTTP API request.
     * Sends a DELETE request to the conversation item endpoint to remove
     * a specific item from an existing conversation. This operation is
     * typically irreversible and will permanently remove the item.
     *
     * @param string $conversationId The unique identifier of the conversation
     *                              that contains the item to be deleted. Must be
     *                              a valid conversation ID that exists in the system.
     * @param string $itemId The unique identifier of the specific item within
     *                      the conversation that should be deleted. Must be a valid
     *                      item ID that exists within the specified conversation.
     * @return bool Returns true if the item was successfully deleted from the
     *              conversation. The method will throw an exception if the
     *              deletion fails rather than returning false.
     * @throws GuzzleException When the HTTP request fails due to network issues,
     *                        timeout, or other HTTP client errors.
     * @throws ApiResponseValidationException|JsonException When the API returns an error response*@throws JsonException
     *                                       (e.g. conversation not found, item not found,
     *                                       insufficient permissions) are indicating the
     *                                       deletion operation could not be completed.
     */
    public function deleteItem(string $conversationId, string $itemId): bool
    {
        $timeout = Config::integer(key: 'ai-assistant.responses.timeout', default: 120);
        if (!is_numeric($timeout)) {
            $timeout = 120;
        }
        $response = $this->http->delete($this->endpoint("conversations/{$conversationId}/items/{$itemId}"), [
            'timeout' => (float) $timeout,
        ]);
        if ($response->getStatusCode() >= Response::HTTP_BAD_REQUEST) {
            $this->throwForError($response);
        }
        return true;
    }

    /**
     * Decodes a JSON response from the API or throws an exception on failure.
     * This method validates the HTTP response status code and attempts to decode
     * the JSON response body. If the response indicates an error (status code >= 400)
     * or if the JSON cannot be decoded into an array, appropriate exceptions are thrown.
     *
     * @param ResponseInterface $response The HTTP response object received from the API.
     *                                   This should contain a JSON-encoded response body
     *                                   that can be decoded into an associative array.
     * @return array The decoded JSON response as an associative array containing
     *               the API response data. The structure of this array depends on
     *               the specific API endpoint that was called.
     * @throws ApiResponseValidationException|JsonException When the HTTP response status code indicates
     *                                       an error (>= 400) or when the response body
     *                                       cannot be decoded as a valid JSON array.
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
     * Throws an ApiResponseValidationException based on the error response from the API.
     *
     * This method extracts error information from an HTTP response and throws
     * an appropriate exception with a meaningful error message. It attempts to
     * parse the response body as JSON to extract a structured error message,
     * falling back to the raw response body if JSON parsing fails.
     *
     * @param ResponseInterface $response The HTTP response object that contains
     *                                   the error information. This response should
     *                                   have a status code indicating an error
     *                                   (typically >= 400) and may contain a JSON
     *                                   error payload in the response body.
     * @return void This method does not return a value as it always throws an exception.
     * @throws ApiResponseValidationException|JsonException Always thrown with an error message extracted
     *                                       from the response body (either from the JSON
     *                                       error structure or raw body content) and the
     *                                       HTTP status code from the response.
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
     * Constructs a complete API endpoint URL by combining the base path with a given path.
     *
     * This method normalises the URL construction by ensuring proper formatting
     * of the base path and the provided path segment. It removes trailing slashes
     * from the base path and leading slashes from the provided path, then combines
     * them with a single forward slash separator to create a well-formed endpoint URL.
     *
     * @param string $path The API path segments to append to the base path.
     *                    Can include leading slashes which will be normalised.
     *                    Examples: 'conversations', '/conversations/123/items'
     * @return string The complete endpoint URL formed by combining the base path
     *                with the provided path segment, properly formatted with
     *                appropriate slash separators. For example, if basePath is
     *                '/v1' and a path is 'conversations', it returns '/v1/conversations'.
     */
    private function endpoint(string $path): string
    {
        $prefix = rtrim($this->basePath, '/');
        $suffix = ltrim($path, '/');
        return $prefix . '/' . $suffix;
    }
}
