<?php

declare(strict_types=1);

namespace CreativeCrafts\LaravelAiAssistant\Compat\OpenAI;

use CreativeCrafts\LaravelAiAssistant\Compat\OpenAI\Responses\Meta\MetaInformation;
use CreativeCrafts\LaravelAiAssistant\Compat\OpenAI\Responses\Threads\Messages\ThreadMessageListResponse;
use CreativeCrafts\LaravelAiAssistant\Compat\OpenAI\Responses\Threads\Messages\ThreadMessageResponse;

class ThreadMessagesResource
{
    public function create(string $threadId, array $parameters): ThreadMessageResponse
    {
        return ThreadMessageResponse::from([
            'id' => 'message_test_id',
            'thread_id' => $threadId,
            'content' => []
        ], MetaInformation::from([]));
    }

    public function list(string $threadId): ThreadMessageListResponse
    {
        return new ThreadMessageListResponse(['data' => []]);
    }
}
