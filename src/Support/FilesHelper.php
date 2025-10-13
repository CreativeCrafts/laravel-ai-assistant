<?php

declare(strict_types=1);

namespace CreativeCrafts\LaravelAiAssistant\Support;

use CreativeCrafts\LaravelAiAssistant\Contracts\FilesRepositoryContract;
use CreativeCrafts\LaravelAiAssistant\Exceptions\ApiResponseValidationException;
use CreativeCrafts\LaravelAiAssistant\Exceptions\FileOperationException;

/**
 * @internal Used internally for file upload operations.
 * Do not use directly. Use Ai::responses() or Ai::chat() for file operations.
 */
final readonly class FilesHelper
{
    public function __construct(private FilesRepositoryContract $filesRepository)
    {
    }

    /**
     * Upload a file to the AI assistant service.
     * This method uploads a file from the specified path to the AI assistant service
     * for use with the specified purpose, typically for assistant operations.
     *
     * @param string $path The file system path to the file that should be uploaded
     * @param string $purpose The purpose for which the file is being uploaded (default: 'assistants')
     * @return string The file identifier or reference returned by the AI service after successful upload
     * @throws FileOperationException If the file is not readable
     * @throws ApiResponseValidationException If no file ID is returned from upload
     */
    public function upload(string $path, string $purpose = 'assistants'): string
    {
        if ($path === '' || !is_readable($path)) {
            throw new FileOperationException("File not readable: {$path}");
        }

        $purpose = trim((string)$purpose);
        if ($purpose === '' || $purpose === 'assistant') {
            $purpose = 'assistants';
        }

        $allowed = ['assistants', 'batch', 'fine-tune', 'vision', 'user_data', 'responses'];
        if (!in_array($purpose, $allowed, true)) {
            $purpose = 'assistants';
        }

        $res = $this->filesRepository->upload($path, $purpose);
        $id = (string)($res['id'] ?? ($res['data']['id'] ?? ''));
        if ($id === '') {
            throw new ApiResponseValidationException('Upload succeeded but no file id returned.');
        }

        return $id;
    }
}
