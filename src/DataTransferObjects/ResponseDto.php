<?php

declare(strict_types=1);

namespace CreativeCrafts\LaravelAiAssistant\DataTransferObjects;

use Illuminate\Support\Str;

final readonly class ResponseDto
{
    /**
     * @param array<int, array{url?: string, b64_json?: string, revised_prompt?: string}> $images
     * @param array<string, mixed> $metadata
     * @param array<string, mixed> $raw
     */
    public function __construct(
        public string $id,
        public string $status,
        public ?string $text,
        public array $raw,
        public ?string $conversationId = null,
        public ?string $audioContent = null,
        public ?array $images = null,
        public ?string $type = null,
        public array $metadata = [],
    ) {
    }

    public static function fromArray(array $data): self
    {
        $text = self::extractText($data);
        $convId = isset($data['conversationId']) ? (string)$data['conversationId'] : (isset($data['conversation']['id']) ? (string)$data['conversation']['id'] : null);
        return new self(
            id: (string)($data['id'] ?? ''),
            status: (string)($data['status'] ?? ($data['response']['status'] ?? 'unknown')),
            text: $text,
            raw: $data,
            conversationId: $convId,
            audioContent: $data['audio_content'] ?? null,
            images: $data['images'] ?? null,
            type: $data['type'] ?? null,
            metadata: $data['metadata'] ?? [],
        );
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'status' => $this->status,
            'text' => $this->text,
            'conversation_id' => $this->conversationId,
            'audio_content' => $this->audioContent,
            'images' => $this->images,
            'type' => $this->type,
            'metadata' => $this->metadata,
            'raw' => $this->raw,
        ];
    }

    public function isText(): bool
    {
        return $this->type === null
            || $this->type === 'text'
            || $this->type === 'audio_transcription'
            || $this->type === 'audio_translation';
    }

    public function isAudio(): bool
    {
        return $this->type === 'audio_speech';
    }

    public function isImage(): bool
    {
        return $this->type !== null && str_starts_with($this->type, 'image_');
    }

    /**
     * Save audio content to a file.
     *
     * @param string $path The full path where the audio file should be saved
     * @return bool True if the audio was successfully saved, false otherwise
     */
    public function saveAudio(string $path): bool
    {
        if ($this->audioContent === null) {
            return false;
        }

        $directory = dirname($path);
        if (! is_dir($directory)) {
            if (! mkdir($directory, 0755, true) && ! is_dir($directory)) {
                return false;
            }
        }

        $result = file_put_contents($path, $this->audioContent);
        return $result !== false;
    }

    /**
     * Save all images to a directory.
     *
     * @param string $directory The directory where images should be saved
     * @return array<int, string> Array of file paths where images were saved
     */
    public function saveImages(string $directory): array
    {
        if ($this->images === null || count($this->images) === 0) {
            return [];
        }

        if (! is_dir($directory)) {
            if (! mkdir($directory, 0755, true) && ! is_dir($directory)) {
                return [];
            }
        }

        $savedPaths = [];
        foreach ($this->images as $index => $image) {
            $filename = sprintf('image_%d_%s.png', $index, Str::ulid());
            $path = rtrim($directory, '/') . '/' . $filename;

            if (isset($image['b64_json'])) {
                $imageData = base64_decode($image['b64_json'], true);
                if ($imageData !== false && file_put_contents($path, $imageData) !== false) {
                    $savedPaths[] = $path;
                }
            } elseif (isset($image['url'])) {
                $imageData = @file_get_contents($image['url']);
                if ($imageData !== false && file_put_contents($path, $imageData) !== false) {
                    $savedPaths[] = $path;
                }
            }
        }

        return $savedPaths;
    }

    private static function extractText(array $data): ?string
    {
        if (isset($data['output_text'])) {
            return (string)$data['output_text'];
        }
        if (isset($data['messages']) && is_string($data['messages'])) {
            return $data['messages'];
        }
        if (isset($data['content'])) {
            return (string)$data['content'];
        }
        return null;
    }
}
