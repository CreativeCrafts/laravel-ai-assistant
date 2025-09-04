<?php

declare(strict_types=1);

namespace CreativeCrafts\LaravelAiAssistant\Repositories\Http;

use CreativeCrafts\LaravelAiAssistant\Contracts\FilesRepositoryContract;
use CreativeCrafts\LaravelAiAssistant\Exceptions\ApiResponseValidationException;
use CreativeCrafts\LaravelAiAssistant\Exceptions\FileOperationException;
use GuzzleHttp\Client as GuzzleClient;
use GuzzleHttp\Exception\GuzzleException;
use JsonException;
use Psr\Http\Message\ResponseInterface;
use Symfony\Component\HttpFoundation\Response;

final readonly class FilesHttpRepository implements FilesRepositoryContract
{
    public function __construct(
        private GuzzleClient $http,
        private string $basePath = '/v1'
    ) {
    }

    /**
     * Uploads a file to the OpenAI API for use with assistants or other purposes.
     * This method reads a local file, validates its accessibility, and sends it to the
     * OpenAI Files API using a multipart form request. The uploaded file can then be
     * referenced by its returned file ID in subsequent API calls.
     *
     * @param string $filePath The absolute or relative path to the file to be uploaded.
     *                         The file must be readable and accessible to the current process.
     * @param string $purpose The intended purpose for the uploaded file. Defaults to 'assistants/answers'.
     *                        Common values include 'assistants', 'fine-tune', or other OpenAI-supported purposes.
     * @return array The decoded JSON response from the OpenAI API containing file metadata,
     *               including the file ID, filename, purpose, and other file properties.
     * @throws FileOperationException If the file is not readable or cannot be opened for reading.
     * @throws ApiResponseValidationException If the API response indicates an error or contains invalid data.
     * @throws JsonException|GuzzleException If the API response cannot be decoded as valid JSON.
     */
    public function upload(string $filePath, string $purpose = 'assistants/answers'): array
    {
        if (!is_readable($filePath)) {
            throw new FileOperationException("File not readable: {$filePath}");
        }
        $filename = basename($filePath);
        $resource = fopen($filePath, 'rb');
        if ($resource === false) {
            throw new FileOperationException("Failed to open file: {$filePath}");
        }

        $response = $this->http->post($this->endpoint('files'), [
            'multipart' => [
                [
                    'name' => 'file',
                    'filename' => $filename,
                    'contents' => $resource,
                ],
                [
                    'name' => 'purpose',
                    'contents' => $purpose,
                ],
            ],
        ]);

        if (is_resource($resource)) {
            fclose($resource);
        }

        return $this->decodeOrFail($response);
    }

    /**
     * Retrieves metadata and information for a specific file from the OpenAI API.
     * This method fetches detailed information about a previously uploaded file using its
     * unique file identifier. The returned data includes file properties such as filename,
     * size, purpose, creation timestamp, and other metadata associated with the file.
     *
     * @param string $fileId The unique identifier of the file to retrieve. This ID is typically
     *                       obtained from a previous file upload operation or file listing.
     * @return array The decoded JSON response from the OpenAI API containing complete file metadata,
     *               including properties like id, filename, bytes, purpose, created_at, and status.
     * @throws ApiResponseValidationException If the API response indicates an error (e.g. file not found)
     *                                       or contains invalid data.
     * @throws JsonException|GuzzleException If the API response cannot be decoded as valid JSON or
     *                                      if there's a network/HTTP communication error.
     */
    public function retrieve(string $fileId): array
    {
        $response = $this->http->get($this->endpoint("files/{$fileId}"));
        return $this->decodeOrFail($response);
    }

    /**
     * Deletes a specific file from the OpenAI API.
     * This method sends a DELETE request to the OpenAI Files API to permanently remove
     * a previously uploaded file. Once deleted, the file cannot be recovered and will
     * no longer be accessible for use with assistants or other OpenAI services.
     *
     * @param string $fileId The unique identifier of the file to delete. This ID is typically
     *                       obtained from a previous file upload operation or file listing.
     * @return bool Returns true if the file was successfully deleted.
     * @throws ApiResponseValidationException If the API response indicates an error (e.g. file not found,
     *                                       insufficient permissions) or contains invalid data.
     * @throws GuzzleException|JsonException If there's a network/HTTP communication error during the request.
     */
    public function delete(string $fileId): bool
    {
        $response = $this->http->delete($this->endpoint("files/{$fileId}"));
        if ($response->getStatusCode() >= Response::HTTP_BAD_REQUEST) {
            $this->throwForError($response);
        }
        return true;
    }

    /**
     * Decodes a JSON response from the API or throws an exception on failure.
     * This method validates the HTTP response status code and attempts to decode
     * the JSON response body. If the status code indicates an error (>= 400) or
     * the response body cannot be decoded as a valid JSON array, appropriate
     * exceptions are thrown.
     *
     * @param ResponseInterface $response The HTTP response object to decode
     * @return array The decoded JSON response as an associative array
     * @throws ApiResponseValidationException|JsonException If the response status code indicates an error,
     *                                       if JSON decoding fails, or if the decoded data
     *                                       is not an array
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
     * Throws an ApiResponseValidationException with error details extracted from the HTTP response.
     * This method processes error responses from the OpenAI API by extracting error messages
     * from the response body. It attempts to parse the response as JSON to get structured
     * error information, falling back to the raw response body if JSON parsing fails.
     *
     * @param ResponseInterface $response The HTTP response object containing the error details
     * @return void This method never returns as it always throws an exception
     * @throws ApiResponseValidationException|JsonException Always thrown with the extracted error message and HTTP status code
     */
    private function throwForError(ResponseInterface $response): void
    {
        $body = (string) $response->getBody();
        $msg = 'OpenAI API error';
        $json = json_decode($body, true, 512, JSON_THROW_ON_ERROR);
        if (is_array($json) && isset($json['error']['message'])) {
            $msg = (string) $json['error']['message'];
        } elseif ($body !== '') {
            $msg = $body;
        }
        throw new ApiResponseValidationException($msg, $response->getStatusCode());
    }

    /**
     * Constructs a complete API endpoint URL by combining the base path with the given path.
     * This method ensures proper URL formatting by removing trailing slashes from the base path
     * and leading slashes from the provided path, then joining them with a single slash separator.
     *
     * @param string $path The relative path to append to the base path (e.g. 'files' or 'files/123')
     * @return string The complete endpoint URL with proper slash formatting
     */
    private function endpoint(string $path): string
    {
        $prefix = rtrim($this->basePath, '/');
        $suffix = ltrim($path, '/');
        return $prefix . '/' . $suffix;
    }
}
