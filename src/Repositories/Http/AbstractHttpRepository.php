<?php

declare(strict_types=1);

namespace CreativeCrafts\LaravelAiAssistant\Repositories\Http;

use CreativeCrafts\LaravelAiAssistant\Support\MultipartFormData;
use CreativeCrafts\LaravelAiAssistant\Support\QueryString;
use CreativeCrafts\LaravelAiAssistant\Support\ServerSentEvents;
use CreativeCrafts\LaravelAiAssistant\Transport\OpenAITransport;
use Generator;
use InvalidArgumentException;

/**
 * Shared plumbing for the low-level OpenAI HTTP repositories: endpoint building with safe
 * path parameters, JSON / multipart / binary / Server-Sent Events requests and resource
 * specific headers (e.g. OpenAI-Beta).
 *
 * @internal
 */
abstract readonly class AbstractHttpRepository
{
    public function __construct(
        protected OpenAITransport $transport,
        protected string $basePath = '/v1'
    ) {
    }

    /**
     * Headers sent with every request of the repository.
     *
     * @return array<string, string>
     */
    protected function headers(): array
    {
        return [];
    }

    /**
     * @param array<string, mixed> $query
     */
    protected function getJson(string $path, array $query = []): array
    {
        return $this->transport->request('GET', $this->endpoint($path), [
            'query' => $query,
            'headers' => $this->headers(),
        ]);
    }

    /**
     * @param array<string, mixed> $payload
     * @param array<string, mixed> $query
     */
    protected function postJson(string $path, array $payload = [], array $query = []): array
    {
        $options = ['query' => $query, 'headers' => $this->headers()];
        // Action endpoints (cancel, archive, hangup, ...) take no body at all
        if ($payload !== []) {
            $options['json'] = $payload;
        }

        return $this->transport->request('POST', $this->endpoint($path), $options);
    }

    /**
     * POST multipart/form-data. Entries named in $fileFields accept paths, SplFileInfo, streams or explicit parts.
     *
     * @param array<string, mixed> $payload
     * @param array<int, string> $fileFields
     */
    protected function postForm(string $path, array $payload, array $fileFields): array
    {
        return $this->transport->request('POST', $this->endpoint($path), [
            'multipart' => MultipartFormData::encode($payload, $fileFields),
            'headers' => $this->headers(),
        ]);
    }

    /**
     * @param array<string, mixed> $query
     */
    protected function deleteJson(string $path, array $query = []): array
    {
        return $this->transport->request('DELETE', $this->endpoint($path), [
            'query' => $query,
            'headers' => $this->headers(),
        ]);
    }

    /**
     * Download raw bytes.
     *
     * @param array<string, mixed> $query
     * @return array{content: string, content_type: string}
     */
    protected function download(string $path, array $query = []): array
    {
        return $this->transport->getContent(QueryString::append($this->endpoint($path), $query), $this->headers());
    }

    /**
     * Send a JSON (or body-less GET) request answered with Server-Sent Events and yield decoded events.
     *
     * @param array<string, mixed>|null $payload
     * @param array<string, mixed> $query
     * @return Generator<int, array<string, mixed>>
     */
    protected function streamJson(string $method, string $path, ?array $payload = null, array $query = []): Generator
    {
        $options = ['query' => $query, 'headers' => $this->headers()];
        if ($payload !== null) {
            $options['json'] = $payload;
        }

        yield from ServerSentEvents::decode($this->transport->streamRequest($method, $this->endpoint($path), $options));
    }

    /**
     * POST multipart/form-data answered with Server-Sent Events and yield decoded events.
     *
     * @param array<string, mixed> $payload
     * @param array<int, string> $fileFields
     * @return Generator<int, array<string, mixed>>
     */
    protected function streamForm(string $path, array $payload, array $fileFields): Generator
    {
        yield from ServerSentEvents::decode($this->transport->streamRequest('POST', $this->endpoint($path), [
            'multipart' => MultipartFormData::encode($payload, $fileFields),
            'headers' => $this->headers(),
        ]));
    }

    /**
     * Build a relative path from a template, e.g. path('containers/%s/files/%s', $containerId, $fileId).
     * Placeholders are filled with percent-encoded path parameters so IDs cannot inject segments or queries.
     */
    protected function path(string $template, string ...$params): string
    {
        return sprintf($template, ...array_map(fn (string $param): string => $this->segment($param), $params));
    }

    protected function endpoint(string $path): string
    {
        return rtrim($this->basePath, '/') . '/' . ltrim($path, '/');
    }

    /**
     * Percent-encode a single path parameter while preserving RFC 3986 path characters (like the official SDKs).
     */
    protected function segment(string $value): string
    {
        if ($value === '' || $value === '.' || $value === '..') {
            throw new InvalidArgumentException("Invalid path parameter: '{$value}'.");
        }

        return strtr(rawurlencode($value), [
            '%21' => '!', '%24' => '$', '%26' => '&', '%27' => "'", '%28' => '(', '%29' => ')', '%2A' => '*',
            '%2B' => '+', '%2C' => ',', '%3B' => ';', '%3D' => '=', '%3A' => ':', '%40' => '@',
        ]);
    }
}
