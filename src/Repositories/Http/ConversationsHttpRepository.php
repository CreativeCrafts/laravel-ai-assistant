<?php

declare(strict_types=1);

namespace CreativeCrafts\LaravelAiAssistant\Repositories\Http;

use CreativeCrafts\LaravelAiAssistant\Contracts\ConversationsRepositoryContract;
use CreativeCrafts\LaravelAiAssistant\Exceptions\ApiResponseValidationException;
use CreativeCrafts\LaravelAiAssistant\Exceptions\MaxRetryAttemptsExceededException;
use CreativeCrafts\LaravelAiAssistant\Transport\GuzzleOpenAITransport;
use CreativeCrafts\LaravelAiAssistant\Transport\OpenAITransport;
use GuzzleHttp\Client as GuzzleClient;
use JsonException;

final readonly class ConversationsHttpRepository implements ConversationsRepositoryContract
{
    private OpenAITransport $transport;

    public function __construct(
        private GuzzleClient $http,
        private string $basePath = '/v1'
    ) {
        $this->transport = new GuzzleOpenAITransport($this->http, $this->basePath);
    }

    /**
     * Creates a new conversation via HTTP API request.
     *
     * @param array $payload Optional array of conversation data to be sent in the request body.
     * @return array The decoded JSON response from the API containing the created conversation data.
     * @throws ApiResponseValidationException|JsonException When the API returns an error response or the response format is invalid.
     * @throws MaxRetryAttemptsExceededException When the maximum number of retry attempts is exceeded.
     */
    public function createConversation(array $payload = []): array
    {
        return $this->transport->postJson($this->endpoint('conversations'), $payload, idempotent: true);
    }

    /**
     * Retrieves a specific conversation by its ID via HTTP API request.
     *
     * @param string $conversationId The unique identifier of the conversation to retrieve.
     * @return array The decoded JSON response from the API.
     * @throws ApiResponseValidationException|JsonException When the API returns an error response or the response format is invalid.
     * @throws MaxRetryAttemptsExceededException When the maximum number of retry attempts is exceeded.
     */
    public function getConversation(string $conversationId): array
    {
        return $this->transport->getJson($this->endpoint("conversations/{$conversationId}"));
    }

    public function updateConversation(string $conversationId, array $payload): array
    {
        return $this->transport->postJson($this->endpoint("conversations/{$conversationId}"), $payload, idempotent: true);
    }

    public function deleteConversation(string $conversationId): bool
    {
        return $this->transport->delete($this->endpoint("conversations/{$conversationId}"));
    }

    /**
     * Retrieves a list of items from a specific conversation via HTTP API request.
     *
     * @param string $conversationId The unique identifier of the conversation whose items should be retrieved.
     * @param array $params Optional query parameters (e.g., pagination, filtering).
     * @return array The decoded JSON response from the API containing an array of conversation items.
     * @throws ApiResponseValidationException|JsonException When the API returns an error response or the response format is invalid.
     * @throws MaxRetryAttemptsExceededException When the maximum number of retry attempts is exceeded.
     */
    public function listItems(string $conversationId, array $params = []): array
    {
        $query = '';
        if (!empty($params)) {
            $query = '?' . http_build_query($params);
        }
        return $this->transport->getJson($this->endpoint("conversations/{$conversationId}/items") . $query);
    }

    /**
     * Creates multiple items within a specific conversation via HTTP API request.
     *
     * @param string $conversationId The unique identifier of the conversation where the items should be created.
     * @param array $items An array of item data to be created within the conversation.
     * @return array The decoded JSON response from the API containing the created items data.
     * @throws ApiResponseValidationException|JsonException When the API returns an error response or the response format is invalid.
     * @throws MaxRetryAttemptsExceededException When the maximum number of retry attempts is exceeded.
     */
    public function createItems(string $conversationId, array $items): array
    {
        $payload = ['items' => $items];
        return $this->transport->postJson($this->endpoint("conversations/{$conversationId}/items"), $payload, idempotent: true);
    }

    /**
     * Deletes a specific item from a conversation via HTTP API request.
     *
     * @param string $conversationId The conversation ID that contains the item to be deleted.
     * @param string $itemId The item ID within the conversation.
     * @return bool True if the item was successfully deleted.
     * @throws ApiResponseValidationException|JsonException When the API returns an error response or the response format is invalid.
     * @throws MaxRetryAttemptsExceededException When the maximum number of retry attempts is exceeded.
     */
    public function deleteItem(string $conversationId, string $itemId): bool
    {
        return $this->transport->delete($this->endpoint("conversations/{$conversationId}/items/{$itemId}"));
    }

    /**
     * Constructs a complete API endpoint URL by combining the base path with a given path.
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
