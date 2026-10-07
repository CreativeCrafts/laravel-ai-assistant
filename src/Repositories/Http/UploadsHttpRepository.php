<?php

declare(strict_types=1);

namespace CreativeCrafts\LaravelAiAssistant\Repositories\Http;

use CreativeCrafts\LaravelAiAssistant\Contracts\UploadsRepositoryContract;
use CreativeCrafts\LaravelAiAssistant\Exceptions\FileValidationException;
use CreativeCrafts\LaravelAiAssistant\Support\MultipartFormData;
use InvalidArgumentException;
use RuntimeException;
use Throwable;

/**
 * @internal Use Ai::uploads() instead.
 */
final readonly class UploadsHttpRepository extends AbstractHttpRepository implements UploadsRepositoryContract
{
    public function create(array $payload): array
    {
        return $this->postJson('uploads', $payload);
    }

    public function addPart(string $uploadId, array $payload): array
    {
        return $this->postForm($this->path('uploads/%s/parts', $uploadId), $payload, ['data']);
    }

    public function complete(string $uploadId, array $payload): array
    {
        return $this->postJson($this->path('uploads/%s/complete', $uploadId), $payload);
    }

    public function cancel(string $uploadId): array
    {
        return $this->postJson($this->path('uploads/%s/cancel', $uploadId));
    }

    public function uploadFile(string $filePath, string $purpose, ?string $mimeType = null, int $partSize = 67108864): array
    {
        if (!is_file($filePath) || !is_readable($filePath)) {
            throw FileValidationException::fileNotReadable($filePath);
        }
        if ($partSize < 1 || $partSize > 67108864) {
            throw new InvalidArgumentException('Upload part size must be between 1 byte and 64 MB.');
        }

        $bytes = (int)filesize($filePath);
        $upload = $this->create([
            'bytes' => $bytes,
            'filename' => basename($filePath),
            'mime_type' => $mimeType ?? MultipartFormData::mimeTypeFor($filePath) ?? 'application/octet-stream',
            'purpose' => $purpose,
        ]);
        $uploadId = isset($upload['id']) && is_string($upload['id']) ? $upload['id'] : '';
        if ($uploadId === '') {
            throw new RuntimeException('The Uploads API did not return an upload id.');
        }

        $handle = fopen($filePath, 'rb');
        if ($handle === false) {
            throw FileValidationException::fileNotReadable($filePath);
        }

        $partIds = [];
        try {
            $index = 0;
            while (!feof($handle)) {
                $chunk = fread($handle, $partSize);
                if ($chunk === false || $chunk === '') {
                    break;
                }
                $stream = fopen('php://temp', 'r+b');
                if ($stream === false) {
                    throw new RuntimeException('Unable to buffer upload part.');
                }
                fwrite($stream, $chunk);
                rewind($stream);

                $part = $this->addPart($uploadId, ['data' => ['contents' => $stream, 'filename' => basename($filePath) . '.part' . $index++]]);
                if (isset($part['id']) && is_string($part['id'])) {
                    $partIds[] = $part['id'];
                }
            }
        } catch (Throwable $e) {
            try {
                $this->cancel($uploadId);
            } catch (Throwable) {
                // Keep the original failure; an unfinished upload expires on its own after an hour
            }
            throw $e;
        } finally {
            fclose($handle);
        }

        $payload = ['part_ids' => $partIds];
        $md5 = md5_file($filePath);
        if ($md5 !== false) {
            $payload['md5'] = $md5;
        }

        return $this->complete($uploadId, $payload);
    }
}
