<?php

declare(strict_types=1);

namespace CreativeCrafts\LaravelAiAssistant\Repositories\Http;

use CreativeCrafts\LaravelAiAssistant\Contracts\ChatCompletionsRepositoryContract;

/**
 * @internal Use Ai::chatCompletions() instead.
 */
final readonly class ChatCompletionsHttpRepository extends AbstractHttpRepository implements ChatCompletionsRepositoryContract
{
    public function create(array $payload): array
    {
        return $this->postJson('chat/completions', $payload);
    }

    public function stream(array $payload): iterable
    {
        yield from $this->streamJson('POST', 'chat/completions', array_merge($payload, ['stream' => true]));
    }

    public function retrieve(string $completionId): array
    {
        return $this->getJson($this->path('chat/completions/%s', $completionId));
    }

    public function update(string $completionId, array $payload): array
    {
        return $this->postJson($this->path('chat/completions/%s', $completionId), $payload);
    }

    public function list(array $params = []): array
    {
        return $this->getJson('chat/completions', $params);
    }

    public function delete(string $completionId): array
    {
        return $this->deleteJson($this->path('chat/completions/%s', $completionId));
    }

    public function listMessages(string $completionId, array $params = []): array
    {
        return $this->getJson($this->path('chat/completions/%s/messages', $completionId), $params);
    }
}
