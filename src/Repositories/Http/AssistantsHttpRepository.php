<?php

declare(strict_types=1);

namespace CreativeCrafts\LaravelAiAssistant\Repositories\Http;

use CreativeCrafts\LaravelAiAssistant\Contracts\AssistantsRepositoryContract;
use CreativeCrafts\LaravelAiAssistant\Transport\OpenAITransport;

/**
 * @internal
 */
final readonly class AssistantsHttpRepository implements AssistantsRepositoryContract
{
    private const BETA_HEADER = ['OpenAI-Beta' => 'assistants=v2'];

    public function __construct(
        private OpenAITransport $transport,
        private string $basePath = '/v1'
    ) {
    }

    public function create(array $payload): array
    {
        return $this->transport->postJson($this->endpoint('assistants'), $payload, self::BETA_HEADER);
    }

    public function retrieve(string $assistantId): array
    {
        return $this->transport->getJson($this->endpoint("assistants/{$assistantId}"), self::BETA_HEADER);
    }

    public function update(string $assistantId, array $payload): array
    {
        return $this->transport->postJson($this->endpoint("assistants/{$assistantId}"), $payload, self::BETA_HEADER);
    }

    public function delete(string $assistantId): bool
    {
        return $this->transport->delete($this->endpoint("assistants/{$assistantId}"), self::BETA_HEADER);
    }

    public function list(array $params = []): array
    {
        $query = '';
        if ($params !== []) {
            $query = '?' . http_build_query($params);
        }
        return $this->transport->getJson($this->endpoint('assistants') . $query, self::BETA_HEADER);
    }

    private function endpoint(string $path): string
    {
        $prefix = rtrim($this->basePath, '/');
        $suffix = ltrim($path, '/');
        return $prefix . '/' . $suffix;
    }
}
