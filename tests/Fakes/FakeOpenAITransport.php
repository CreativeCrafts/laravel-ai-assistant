<?php

declare(strict_types=1);

namespace CreativeCrafts\LaravelAiAssistant\Tests\Fakes;

use CreativeCrafts\LaravelAiAssistant\Transport\OpenAITransport;

final class FakeOpenAITransport implements OpenAITransport
{
    public array $responses = [];

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

    public function delete(string $path, array $headers = [], ?float $timeout = null): bool
    {
        return true;
    }
}
