<?php

declare(strict_types=1);

namespace CreativeCrafts\LaravelAiAssistant\DataTransferObjects;

final readonly class CompletionResult
{
    /**
     * For sync text completions: $text contains the final string and $data repeats ['text' => ...].
     * For sync chat completions: $data contains the normalized array returned by chatTextCompletion().
     * For streams: $text/$data contains the fully accumulated result after streaming is complete.
     */
    public function __construct(
        public ?string $text = null,
        public ?array $data = null
    ) {
    }

    public function __toString(): string
    {
        return (string)($this->text ?? '');
    }

    public static function fromText(string $text): self
    {
        return new self($text, ['text' => $text]);
    }

    public static function fromArray(array $data): self
    {
        return new self(null, $data);
    }

    public function toArray(): array
    {
        return $this->data ?? ['text' => (string)$this->text];
    }
}
