<?php

declare(strict_types=1);

namespace CreativeCrafts\LaravelAiAssistant\Contract;

use OpenAI\Responses\Assistants\AssistantResponse;

interface NewAssistantResponseContract
{
    public function __construct(
        AssistantResponse $assistantResponse
    );

    public function assistantId(): string;

    public function assistant(): AssistantResponse;
}
