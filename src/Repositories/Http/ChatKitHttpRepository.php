<?php

declare(strict_types=1);

namespace CreativeCrafts\LaravelAiAssistant\Repositories\Http;

use CreativeCrafts\LaravelAiAssistant\Contracts\ChatKitRepositoryContract;

/**
 * @internal Use Ai::chatKit() instead.
 */
final readonly class ChatKitHttpRepository extends AbstractHttpRepository implements ChatKitRepositoryContract
{
    public function createSession(array $payload): array
    {
        return $this->postJson('chatkit/sessions', $payload);
    }

    public function cancelSession(string $sessionId): array
    {
        return $this->postJson($this->path('chatkit/sessions/%s/cancel', $sessionId));
    }

    public function retrieveThread(string $threadId): array
    {
        return $this->getJson($this->path('chatkit/threads/%s', $threadId));
    }

    public function listThreads(array $params = []): array
    {
        return $this->getJson('chatkit/threads', $params);
    }

    public function deleteThread(string $threadId): array
    {
        return $this->deleteJson($this->path('chatkit/threads/%s', $threadId));
    }

    public function listThreadItems(string $threadId, array $params = []): array
    {
        return $this->getJson($this->path('chatkit/threads/%s/items', $threadId), $params);
    }

    protected function headers(): array
    {
        return ['OpenAI-Beta' => 'chatkit_beta=v1'];
    }
}
