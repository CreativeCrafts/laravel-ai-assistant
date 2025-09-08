<?php

declare(strict_types=1);

namespace CreativeCrafts\LaravelAiAssistant\Compat\OpenAI;

use CreativeCrafts\LaravelAiAssistant\Compat\OpenAI\Responses\Meta\MetaInformation;
use CreativeCrafts\LaravelAiAssistant\Compat\OpenAI\Responses\Threads\ThreadResponse;

class ThreadsResource
{
    public function create(array $parameters): ThreadResponse
    {
        return ThreadResponse::from(['id' => 'thread_test_id'], MetaInformation::from([]));
    }

    public function messages(): ThreadMessagesResource
    {
        return new ThreadMessagesResource();
    }

    public function runs(): ThreadRunsResource
    {
        return new ThreadRunsResource();
    }
}
