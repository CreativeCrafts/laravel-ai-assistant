<?php

declare(strict_types=1);

namespace CreativeCrafts\LaravelAiAssistant\Repositories\Http;

use CreativeCrafts\LaravelAiAssistant\Contracts\LiveRepositoryContract;

/**
 * @internal Use Ai::live() instead.
 */
final readonly class LiveHttpRepository extends AbstractHttpRepository implements LiveRepositoryContract
{
    public function create(array $payload): array
    {
        return $this->postJson('live/sessions', $payload);
    }

    public function accept(string $sessionId, array $payload): array
    {
        return $this->postJson($this->path('live/sessions/%s/accept', $sessionId), $payload);
    }

    public function hangup(string $sessionId): array
    {
        return $this->postJson($this->path('live/sessions/%s/hangup', $sessionId));
    }

    public function refer(string $sessionId, array $payload): array
    {
        return $this->postJson($this->path('live/sessions/%s/refer', $sessionId), $payload);
    }

    public function reject(string $sessionId, array $payload = []): array
    {
        return $this->postJson($this->path('live/sessions/%s/reject', $sessionId), $payload);
    }

    public function fork(string $sessionId, array $payload): array
    {
        return $this->postJson($this->path('live/sessions/%s/fork', $sessionId), $payload);
    }

    public function downloadRecording(string $sessionId): array
    {
        return $this->download($this->path('live/sessions/%s/content', $sessionId));
    }
}
