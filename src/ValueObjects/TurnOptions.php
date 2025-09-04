<?php

declare(strict_types=1);

namespace CreativeCrafts\LaravelAiAssistant\ValueObjects;

use InvalidArgumentException;

/**
 * Immutable per-turn options builder for the Responses API.
 * Intentionally mirrors the array shape used by AssistantService::sendChatMessage/getStreamingResponse
 * so AiAssistant can pass $options->toArray() directly.
 */
final class TurnOptions
{
    /** @var array<string, mixed> */
    private array $data;

    private function __construct(array $data)
    {
        // Normalize known keys a bit to reduce accidental wrong shapes
        $this->data = $data;
        $this->data['tools'] = array_values(array_filter((array)($this->data['tools'] ?? [])));
        $this->data['metadata'] = (array)($this->data['metadata'] ?? []);
        $this->data['attachments'] = array_values((array)($this->data['attachments'] ?? []));
        $this->data['file_ids'] = array_values(array_filter((array)($this->data['file_ids'] ?? []), 'is_string'));
        $this->data['input_images'] = array_values((array)($this->data['input_images'] ?? []));
    }

    public static function new(): self
    {
        return new self([]);
    }

    public static function fromArray(array $data): self
    {
        return new self($data);
    }

    public function model(?string $model): self
    {
        if ($model !== null && trim($model) === '') {
            throw new InvalidArgumentException('Model name must be a non-empty string when provided.');
        }
        $copy = $this->data;
        $copy['model'] = $model;
        return new self($copy);
    }

    public function instructions(?string $instructions): self
    {
        $copy = $this->data;
        $copy['instructions'] = $instructions;
        return new self($copy);
    }

    public function addTool(array $tool): self
    {
        if (!isset($tool['type'])) {
            throw new InvalidArgumentException('Tool definition must have a type.');
        }
        $tools = (array)($this->data['tools'] ?? []);
        $tools[] = $tool;
        $copy = $this->data;
        $copy['tools'] = $tools;
        return new self($copy);
    }

    public function responseFormatText(): self
    {
        $copy = $this->data;
        $copy['response_format'] = 'text';
        return new self($copy);
    }

    public function responseFormatJsonSchema(array $schema, ?string $name = 'response'): self
    {
        if ($schema === []) {
            throw new InvalidArgumentException('JSON Schema must not be empty.');
        }
        $n = $name ?? 'response';
        if (trim($n) === '') {
            throw new InvalidArgumentException('JSON schema name must be non-empty.');
        }
        $copy = $this->data;
        $copy['response_format'] = [
            'type' => 'json_schema',
            'json_schema' => [
                'name' => $n,
                'schema' => $schema,
            ],
        ];
        return new self($copy);
    }

    public function toolChoice(array|string|null $choice): self
    {
        $copy = $this->data;
        $copy['tool_choice'] = $choice;
        return new self($copy);
    }

    public function modalities(array|string|null $modalities): self
    {
        $copy = $this->data;
        $copy['modalities'] = $modalities;
        return new self($copy);
    }

    public function metadata(array $metadata): self
    {
        $copy = $this->data;
        $copy['metadata'] = array_merge((array)($copy['metadata'] ?? []), $metadata);
        return new self($copy);
    }

    public function idempotencyKey(?string $key): self
    {
        $copy = $this->data;
        $copy['idempotency_key'] = $key;
        return new self($copy);
    }

    public function useFileSearch(bool $enabled): self
    {
        $copy = $this->data;
        $copy['use_file_search'] = $enabled;
        return new self($copy);
    }

    public function attachments(array $attachments): self
    {
        $copy = $this->data;
        $copy['attachments'] = array_values($attachments);
        return new self($copy);
    }

    public function addFileId(string $fileId): self
    {
        $ids = array_values(array_filter((array)($this->data['file_ids'] ?? []), 'is_string'));
        if (!in_array($fileId, $ids, true)) {
            $ids[] = $fileId;
        }
        return $this->fileIds($ids);
    }

    public function fileIds(array $fileIds): self
    {
        $copy = $this->data;
        $copy['file_ids'] = array_values(array_filter($fileIds, 'is_string'));
        return new self($copy);
    }

    public function addInputImageId(string $fileId): self
    {
        $imgs = (array)($this->data['input_images'] ?? []);
        if (!in_array($fileId, $imgs, true)) {
            $imgs[] = $fileId;
        }
        return $this->inputImages($imgs);
    }

    public function inputImages(array $images): self
    {
        $copy = $this->data;
        $copy['input_images'] = array_values($images);
        return new self($copy);
    }

    public function addInputImageUrl(string $url): self
    {
        $imgs = (array)($this->data['input_images'] ?? []);
        $imgs[] = ['url' => $url];
        return $this->inputImages($imgs);
    }

    /**
     * Generic raw setter to help bridging existing array-based code paths.
     * Use typed methods where possible.
     */
    public function withRaw(string $key, mixed $value): self
    {
        $copy = $this->data;
        $copy[$key] = $value;
        return new self($copy);
    }

    /**
     * Export normalized array suitable for AssistantService.
     *
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'model' => $this->data['model'] ?? null,
            'instructions' => $this->data['instructions'] ?? null,
            'tools' => $this->data['tools'] ?? [],
            'response_format' => $this->data['response_format'] ?? null,
            'file_ids' => $this->data['file_ids'] ?? [],
            'input_images' => $this->data['input_images'] ?? [],
            'modalities' => $this->data['modalities'] ?? null,
            'metadata' => $this->data['metadata'] ?? [],
            'idempotency_key' => $this->data['idempotency_key'] ?? null,
            'attachments' => $this->data['attachments'] ?? [],
            'tool_choice' => $this->data['tool_choice'] ?? null,
            'use_file_search' => $this->data['use_file_search'] ?? true,
        ];
    }
}
