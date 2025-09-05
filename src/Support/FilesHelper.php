<?php

declare(strict_types=1);

namespace CreativeCrafts\LaravelAiAssistant\Support;

use CreativeCrafts\LaravelAiAssistant\AiAssistant;
use Illuminate\Http\UploadedFile;

final readonly class FilesHelper
{
    public function __construct(private AiAssistant $core)
    {
    }

    public function upload(string $path, string $purpose = 'assistants'): string
    {
        return $this->core->uploadFile($path, $purpose);
    }

    public function attachFilesToTurn(array $fileIds, ?bool $useFileSearch = null): self
    {
        $this->core->attachFilesToTurn($fileIds, $useFileSearch);
        return $this;
    }

    public function addImageFromFile(string $path, string $purpose = 'assistants'): self
    {
        $this->core->addImageFromFile($path, $purpose);
        return $this;
    }

    public function addImageFromUrl(string $url): self
    {
        $this->core->addImageFromUrl($url);
        return $this;
    }

    public function addImageFromUploadedFile(UploadedFile $file, string $purpose = 'assistants'): self
    {
        $this->core->addImageFromUploadedFile($file, $purpose);
        return $this;
    }

    public function attachUploadedFile(UploadedFile $file, string $purpose = 'assistants'): self
    {
        $this->core->attachUploadedFile($file, $purpose);
        return $this;
    }

    public function attachFilesFromStorage(array $paths, string $purpose = 'assistants'): self
    {
        $this->core->attachFilesFromStorage($paths, $purpose);
        return $this;
    }

    public function attachFileReference(string $fileId, ?bool $useFileSearch = null): self
    {
        $this->core->attachFileReference($fileId, $useFileSearch);
        return $this;
    }

    /**
     * @param string|array $fileIds
     */
    public function attachForFileSearch(string|array $fileIds, ?bool $useFileSearch = null): self
    {
        $this->core->attachForFileSearch($fileIds, $useFileSearch);
        return $this;
    }

    public function addInputImageFromFile(string $path, string $purpose = 'assistants'): self
    {
        $this->core->addInputImageFromFile($path, $purpose);
        return $this;
    }

    public function addInputImageFromUrl(string $url): self
    {
        $this->core->addInputImageFromUrl($url);
        return $this;
    }

    public function setAttachments(array $attachments): self
    {
        $this->core->setAttachments($attachments);
        return $this;
    }
}
