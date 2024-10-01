<?php

declare(strict_types=1);

namespace CreativeCrafts\LaravelAiAssistant\DataTransferObjects;

use CreativeCrafts\LaravelAiAssistant\Contract\NewAssistantResponseContract;
use OpenAI\Responses\Assistants\AssistantResponse;

final readonly class NewAssistantResponse implements NewAssistantResponseContract
{
    public function __construct(
        protected AssistantResponse $assistantResponse
    ) {
    }

    public function assistantId(): string
    {
        return $this->assistantResponse->id;
    }

    public function assistant(): AssistantResponse
    {
        return $this->assistantResponse;
    }
}
