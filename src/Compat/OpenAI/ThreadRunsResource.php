<?php

declare(strict_types=1);

namespace CreativeCrafts\LaravelAiAssistant\Compat\OpenAI;

use CreativeCrafts\LaravelAiAssistant\Compat\OpenAI\Responses\Threads\Runs\ThreadRunResponse;

class ThreadRunsResource
{
    public function create(string $threadId, array $parameters): ThreadRunResponse
    {
        $response = new ThreadRunResponse();
        $response->id = 'run_test_id';
        $response->status = 'queued';
        return $response;
    }

    public function retrieve(string $threadId, string $runId): ThreadRunResponse
    {
        $response = new ThreadRunResponse();
        $response->id = $runId;
        $response->status = 'completed';
        return $response;
    }
}
