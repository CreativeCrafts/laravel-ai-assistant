<?php

declare(strict_types=1);

namespace CreativeCrafts\LaravelAiAssistant\Tests\Fakes;

use CreativeCrafts\LaravelAiAssistant\Contracts\FilesRepositoryContract;

final class FakeFilesRepository implements FilesRepositoryContract
{
    public array $uploads = [];
    public array $deleted = [];

    public function upload(string $filePath, string $purpose = 'assistants/answers'): array
    {
        $id = 'file_' . md5($filePath . '|' . $purpose);
        $this->uploads[] = compact('filePath', 'purpose', 'id');
        return ['id' => $id, 'filename' => basename($filePath), 'purpose' => $purpose];
    }

    public function retrieve(string $fileId): array
    {
        return ['id' => $fileId];
    }

    public function delete(string $fileId): bool
    {
        $this->deleted[] = $fileId;
        return true;
    }
}
