<?php

declare(strict_types=1);

namespace CreativeCrafts\LaravelAiAssistant\Repositories\Http;

use CreativeCrafts\LaravelAiAssistant\Contracts\FilesRepositoryContract;
use CreativeCrafts\LaravelAiAssistant\Exceptions\ApiResponseValidationException;
use CreativeCrafts\LaravelAiAssistant\Exceptions\FileOperationException;
use CreativeCrafts\LaravelAiAssistant\Transport\GuzzleOpenAITransport;
use CreativeCrafts\LaravelAiAssistant\Transport\OpenAITransport;
use GuzzleHttp\Client as GuzzleClient;
use JsonException;
use Psr\Http\Message\ResponseInterface;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

final readonly class FilesHttpRepository implements FilesRepositoryContract
{
    private OpenAITransport $transport;

    public function __construct(
        private GuzzleClient $http,
        private string $basePath = '/v1'
    ) {
        $this->transport = new GuzzleOpenAITransport($this->http, $this->basePath);
    }

    /**
     * Uploads a file to the OpenAI API for use with assistants or other purposes.
     * This method reads a local file, validates its accessibility, and sends it to the
     * OpenAI Files API using a multipart form request. The uploaded file can then be
     * referenced by its returned file ID in subsequent API calls.
     *
     * @param string $filePath The absolute or relative path to the file to be uploaded.
     *                         The file must be readable and accessible to the current process.
     * @param string $purpose The intended purpose for the uploaded file. Defaults to 'assistants'.
     *                        Allowed values include 'assistants', 'batch', 'fine-tune', 'vision', 'user_data'.
     * @return array The decoded JSON response from the OpenAI API containing file metadata,
     *               including the file ID, filename, purpose, and other file properties.
     * @throws FileOperationException If the file is not readable or cannot be opened for reading.
     * @throws ApiResponseValidationException If the API response indicates an error or contains invalid data, or on transport/network errors.
     * @throws JsonException If the API response cannot be decoded as valid JSON.
     */
    public function upload(string $filePath, string $purpose = 'assistants'): array
    {
        if (!is_readable($filePath)) {
            throw new FileOperationException("File not readable: {$filePath}");
        }
        $filename = basename($filePath);
        $resource = fopen($filePath, 'rb');
        if ($resource === false) {
            throw new FileOperationException("Failed to open file: {$filePath}");
        }

        // Detect MIME type to help the API infer file type and ensure an image has a proper extension
        $mime = null;
        if (function_exists('finfo_open')) {
            $f = finfo_open(FILEINFO_MIME_TYPE);
            if ($f) {
                $detected = finfo_file($f, $filePath);
                if (is_string($detected) && $detected !== '') {
                    $mime = $detected;
                }
                finfo_close($f);
            }
        }
        $imageExtMap = [
            'image/jpeg' => 'jpg',
            'image/jpg' => 'jpg',
            'image/png' => 'png',
            'image/gif' => 'gif',
            'image/webp' => 'webp',
        ];
        $hasKnownImageExt = (bool)preg_match('/\.(jpe?g|png|gif|webp)$/i', $filename);
        if ($mime !== null && isset($imageExtMap[$mime]) && !$hasKnownImageExt) {
            $filename .= '.' . $imageExtMap[$mime];
        }

        $filePart = [
            'name' => 'file',
            'filename' => $filename,
            'contents' => $resource,
        ];
        if (is_string($mime) && $mime !== '') {
            $filePart['headers'] = ['Content-Type' => $mime];
        }

        try {
            $response = $this->http->post($this->endpoint('files'), [
                'multipart' => [
                    $filePart,
                    [
                        'name' => 'purpose',
                        'contents' => $purpose,
                    ],
                ],
            ]);
        } catch (Throwable $e) {
            if (is_resource($resource)) {
                fclose($resource);
            }
            throw new ApiResponseValidationException($e->getMessage() ?: 'Transport error during file upload.');
        }

        if (is_resource($resource)) {
            fclose($resource);
        }

        return $this->decodeOrFail($response);
    }

    /**
     * Retrieves metadata and information for a specific file from the OpenAI API.
     * This method delegates to the transport for unified retries and exceptions.
     *
     * @param string $fileId The unique identifier of the file to retrieve.
     * @return array The decoded JSON response from the OpenAI API containing complete file metadata.
     * @throws ApiResponseValidationException If the API response indicates an error or the response format is invalid.
     */
    public function retrieve(string $fileId): array
    {
        return $this->transport->getJson($this->endpoint("files/{$fileId}"));
    }

    /**
     * Deletes a specific file from the OpenAI API.
     * Delegates to the transport for unified retries and exception handling.
     *
     * @param string $fileId The unique identifier of the file to delete.
     * @return bool Returns true if the file was successfully deleted.
     * @throws ApiResponseValidationException When the API returns an error response (status >= 400).
     */
    public function delete(string $fileId): bool
    {
        return $this->transport->delete($this->endpoint("files/{$fileId}"));
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
        $body = (string)$response->getBody();
        $msg = 'OpenAI API error';
        $json = json_decode($body, true, 512, JSON_THROW_ON_ERROR);
        if (is_array($json) && isset($json['error']['message'])) {
            $msg = (string)$json['error']['message'];
        } elseif ($body !== '') {
            $msg = $body;
        }
        throw new ApiResponseValidationException($msg, $response->getStatusCode());
    }
}
