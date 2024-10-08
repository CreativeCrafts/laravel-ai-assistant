<?php

declare(strict_types=1);

namespace CreativeCrafts\LaravelAiAssistant\Contracts;

use OpenAI\Responses\Assistants\AssistantResponse;

interface NewAssistantResponseDataContract
{
    public function __construct(
        AssistantResponse $assistantResponse
    );

    public function assistantId(): string;

    public function assistant(): AssistantResponse;
}
