<?php

declare(strict_types=1);

namespace CreativeCrafts\LaravelAiAssistant\Repositories\Http\Admin;

use CreativeCrafts\LaravelAiAssistant\Contracts\Admin\UsageRepositoryContract;

/**
 * @internal Use Ai::admin()->usage() instead.
 */
final readonly class UsageHttpRepository extends AbstractAdminHttpRepository implements UsageRepositoryContract
{
    public function completions(array $params = []): array
    {
        return $this->getJson('organization/usage/completions', $params);
    }

    public function embeddings(array $params = []): array
    {
        return $this->getJson('organization/usage/embeddings', $params);
    }

    public function moderations(array $params = []): array
    {
        return $this->getJson('organization/usage/moderations', $params);
    }

    public function images(array $params = []): array
    {
        return $this->getJson('organization/usage/images', $params);
    }

    public function audioSpeeches(array $params = []): array
    {
        return $this->getJson('organization/usage/audio_speeches', $params);
    }

    public function audioTranscriptions(array $params = []): array
    {
        return $this->getJson('organization/usage/audio_transcriptions', $params);
    }

    public function vectorStores(array $params = []): array
    {
        return $this->getJson('organization/usage/vector_stores', $params);
    }

    public function codeInterpreterSessions(array $params = []): array
    {
        return $this->getJson('organization/usage/code_interpreter_sessions', $params);
    }

    public function fileSearchCalls(array $params = []): array
    {
        return $this->getJson('organization/usage/file_search_calls', $params);
    }

    public function webSearchCalls(array $params = []): array
    {
        return $this->getJson('organization/usage/web_search_calls', $params);
    }

    public function costs(array $params = []): array
    {
        return $this->getJson('organization/costs', $params);
    }
}
