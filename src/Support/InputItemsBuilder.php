<?php

declare(strict_types=1);

namespace CreativeCrafts\LaravelAiAssistant\Support;

/**
 * Builder for Responses API input items.
 * Maintains a list of input entries: [ { role: 'user'|'assistant'|'system', content: [blocks...] } ]
 */
final class InputItemsBuilder
{
    /** @var array<int,array<string,mixed>> */
    private array $items = [];

    /**
     * Append a user text block as a new input item.
     */
    public function appendUserText(string $text): self
    {
        $this->items[] = [
            'role' => 'user',
            'content' => [
                ['type' => 'input_text', 'text' => $text],
            ],
        ];
        return $this;
    }

    /**
     * Append a user image by URL as a new input item.
     */
    public function appendUserImageUrl(string $url): self
    {
        $this->items[] = [
            'role' => 'user',
            'content' => [
                ['type' => 'input_image', 'image_url' => $url],
            ],
        ];
        return $this;
    }

    /**
     * Append a user image by file_id as a new input item.
     */
    public function appendUserImageId(string $fileId): self
    {
        $this->items[] = [
            'role' => 'user',
            'content' => [
                ['type' => 'input_image', 'file_id' => $fileId],
            ],
        ];
        return $this;
    }

    /**
     * Append a raw input item; must match the Responses schema.
     * @param array<string,mixed> $item
     */
    public function appendRaw(array $item): self
    {
        $this->items[] = $item;
        return $this;
    }

    /**
     * List the built items.
     *
     * @return array<int,array<string,mixed>>
     */
    public function list(): array
    {
        return $this->items;
    }
}
