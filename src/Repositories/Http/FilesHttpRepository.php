<?php

declare(strict_types=1);

namespace CreativeCrafts\LaravelAiAssistant\Repositories\Http;

use CreativeCrafts\LaravelAiAssistant\Contracts\FilesRepositoryContract;
use CreativeCrafts\LaravelAiAssistant\Exceptions\ApiResponseValidationException;
use CreativeCrafts\LaravelAiAssistant\Exceptions\FileOperationException;
use CreativeCrafts\LaravelAiAssistant\Transport\OpenAITransport;
use JsonException;

/**
 * @internal This class is used internally by AssistantService.
 * Do not use directly - use Ai::responses() or Ai::conversations() instead.
 */
final readonly class FilesHttpRepository implements FilesRepositoryContract
{
    public function __construct(
        private OpenAITransport $transport,
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
        // Detect MIME type to help the API infer a file type and ensure an image has a proper extension
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
            'contents' => $filePath,
            'filename' => $filename,
        ];
        if (is_string($mime) && $mime !== '') {
            $filePart['content_type'] = $mime;
        }

        return $this->transport->postMultipart($this->endpoint('files'), [
            'file' => $filePart,
            'purpose' => $purpose,
        ]);
    }

    /**
     * Retrieves metadata and information for a specific file from the OpenAI API.
     * This method delegates to the transport for unified retries and exceptions.
     *
     * @param string $fileId The unique identifier of the file to retrieve.
     * @return array The decoded JSON response from the OpenAI API containing complete file metadata.
     * @throws ApiResponseValidationException If the API response indicates an error or the response format is invalid.
     * @throws JsonException
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

    public function content(string $fileId): array
    {
        return $this->transport->getContent($this->endpoint("files/{$fileId}/content"));
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
