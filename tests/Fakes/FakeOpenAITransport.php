<?php

declare(strict_types=1);

namespace CreativeCrafts\LaravelAiAssistant\Tests\Fakes;

use CreativeCrafts\LaravelAiAssistant\Transport\OpenAITransport;

final class FakeOpenAITransport implements OpenAITransport
{
    public array $responses = [];

    /** @var array<string, array<int, string>> SSE lines returned by streamRequest(), keyed by path */
    public array $streams = [];

    /** @var array<int, array{method: string, path: string, options: array}> */
    public array $requests = [];

    public function postJson(string $path, array $payload, array $headers = [], ?float $timeout = null, bool $idempotent = false): array
    {
        return $this->responses[$path] ?? ['id' => 'fake', 'object' => 'response', 'output' => [['content' => [['type' => 'output_text','text' => ['value' => 'ok']]]]]];
    }

    public function postMultipart(string $path, array $fields, array $headers = [], ?float $timeout = null, bool $idempotent = false, ?callable $progressCallback = null): array
    {
        return $this->responses[$path] ?? ['status' => 'ok'];
    }

    public function streamSse(string $path, array $payload, array $headers = [], ?float $timeout = null, bool $idempotent = false): iterable
    {
        return [];
    }

    public function getJson(string $path, array $headers = [], ?float $timeout = null): array
    {
        return $this->responses[$path] ?? ['status' => 'ok'];
    }

    public function getContent(string $path, array $headers = [], ?float $timeout = null): array
    {
        $payload = $this->responses[$path] ?? [];
        if (is_array($payload)) {
            return [
                'content' => (string)($payload['content'] ?? ''),
                'content_type' => (string)($payload['content_type'] ?? 'application/octet-stream'),
            ];
        }

        return [
            'content' => (string)$payload,
            'content_type' => 'application/octet-stream',
        ];
    }

    public function delete(string $path, array $headers = [], ?float $timeout = null): bool
    {
        return true;
    }

    public function request(string $method, string $path, array $options = []): array
    {
        $this->requests[] = ['method' => $method, 'path' => $path, 'options' => $options];

        return $this->responses[$path] ?? ['status' => 'ok'];
    }

    public function streamRequest(string $method, string $path, array $options = []): iterable
    {
        $this->requests[] = ['method' => $method, 'path' => $path, 'options' => $options];

        return $this->streams[$path] ?? [];
    }
}
