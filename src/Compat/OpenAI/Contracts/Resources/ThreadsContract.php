<?php

declare(strict_types=1);

namespace CreativeCrafts\LaravelAiAssistant\Compat\OpenAI\Contracts\Resources;

use CreativeCrafts\LaravelAiAssistant\Compat\OpenAI\Responses\Threads\ThreadResponse;

interface ThreadsContract
{
    /**
     * @param array<string,mixed> $parameters
     */
    public function create(array $parameters): ThreadResponse;

    public function messages(): ThreadsMessagesContract;

    public function runs(): ThreadsRunsContract;
}
