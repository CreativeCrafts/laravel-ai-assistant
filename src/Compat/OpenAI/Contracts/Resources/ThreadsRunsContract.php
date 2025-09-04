<?php

declare(strict_types=1);

namespace CreativeCrafts\LaravelAiAssistant\Compat\OpenAI\Contracts\Resources;

use CreativeCrafts\LaravelAiAssistant\Compat\OpenAI\Responses\Threads\Runs\ThreadRunResponse;

interface ThreadsRunsContract
{
    /**
     * @param array<string,mixed> $parameters
     */
    public function create(string $threadId, array $parameters): ThreadRunResponse;

    public function retrieve(string $threadId, string $runId): ThreadRunResponse;
}
