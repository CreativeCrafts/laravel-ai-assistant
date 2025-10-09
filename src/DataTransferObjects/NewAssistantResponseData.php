<?php

declare(strict_types=1);

namespace CreativeCrafts\LaravelAiAssistant\DataTransferObjects;

use CreativeCrafts\LaravelAiAssistant\Contracts\NewAssistantResponseDataContract;

final readonly class NewAssistantResponseData implements NewAssistantResponseDataContract
{
    public function __construct(
        protected mixed $assistant
    ) {
    }

    public function assistantId(): string
    {
        // Support both array and object assistant payloads
        if (is_array($this->assistant)) {
            return (string)($this->assistant['id'] ?? '');
        }
        if (is_object($this->assistant) && isset($this->assistant->id)) {
            return (string)$this->assistant->id;
        }
        return '';
    }

    public function assistant(): mixed
    {
        return $this->assistant;
    }
}
