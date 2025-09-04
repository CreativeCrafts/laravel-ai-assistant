<?php

declare(strict_types=1);

namespace CreativeCrafts\LaravelAiAssistant\Compat\OpenAI\Contracts\Resources;

use CreativeCrafts\LaravelAiAssistant\Compat\OpenAI\Responses\Threads\Messages\ThreadMessageListResponse;
use CreativeCrafts\LaravelAiAssistant\Compat\OpenAI\Responses\Threads\Messages\ThreadMessageResponse;

interface ThreadsMessagesContract
{
    /**
     * @param array<string,mixed> $parameters
     */
    public function create(string $threadId, array $parameters): ThreadMessageResponse;

    public function list(string $threadId): ThreadMessageListResponse;
}
