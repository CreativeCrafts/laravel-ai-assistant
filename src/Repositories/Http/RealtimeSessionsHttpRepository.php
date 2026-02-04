<?php

declare(strict_types=1);

namespace CreativeCrafts\LaravelAiAssistant\Repositories\Http;

use CreativeCrafts\LaravelAiAssistant\Contracts\RealtimeSessionsRepositoryContract;
use CreativeCrafts\LaravelAiAssistant\Transport\OpenAITransport;

/**
 * @internal
 */
final readonly class RealtimeSessionsHttpRepository implements RealtimeSessionsRepositoryContract
{
    public function __construct(
        private OpenAITransport $transport,
        private string $basePath = '/v1'
    ) {
    }

    public function create(array $payload): array
    {
        return $this->transport->postJson($this->endpoint('realtime/sessions'), $payload);
    }

    private function endpoint(string $path): string
    {
        $prefix = rtrim($this->basePath, '/');
        $suffix = ltrim($path, '/');
        return $prefix . '/' . $suffix;
    }
}
