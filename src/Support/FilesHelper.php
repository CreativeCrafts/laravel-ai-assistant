<?php

declare(strict_types=1);

namespace CreativeCrafts\LaravelAiAssistant\Support;

use CreativeCrafts\LaravelAiAssistant\AiAssistant;

final readonly class FilesHelper
{
    public function __construct(private AiAssistant $core)
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
     */
    public function upload(string $path, string $purpose = 'assistants'): string
    {
        return $this->core->uploadFile($path, $purpose);
    }

    /**
     * Set file attachments for the AI assistant.
     * This method configures file attachments that will be used by the AI assistant
     * for processing or reference during assistant operations. The attachments are
     * passed to the underlying AI core service.
     *
     * @param array $attachments An array of file attachments to be set for the AI assistant
     * @return self Returns the current instance for method chaining
     */
    public function setAttachments(array $attachments): self
    {
        $this->core->setAttachments($attachments);
        return $this;
    }
}
