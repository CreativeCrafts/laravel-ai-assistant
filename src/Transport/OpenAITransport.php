<?php

declare(strict_types=1);

namespace CreativeCrafts\LaravelAiAssistant\Transport;

interface OpenAITransport
{
    /**
     * Send a JSON POST request and return the decoded array response.
     *
     * @param string $path Relative API path, e.g. '/v1/responses'
     * @param array $payload JSON payload to send
     * @param array $headers Optional extra headers
     * @param float|null $timeout Per-call timeout override (seconds)
     * @param bool $idempotent Whether to enforce/provide Idempotency-Key and persist it over retries
     * @return array Decoded JSON response
     */
    public function postJson(string $path, array $payload, array $headers = [], ?float $timeout = null, bool $idempotent = false): array;

    /**
     * Send a multipart/form-data POST request and return the decoded array response.
     * Values in $fields may be strings, numbers, booleans, or file resources/streams.
     *
     * @param string $path Relative API path, e.g. '/v1/audio/transcriptions'
     * @param array $fields Form fields to send (including 'file' where applicable)
     * @param array $headers Optional extra headers
     * @param float|null $timeout Per-call timeout override (seconds)
     * @param bool $idempotent Whether to enforce/provide Idempotency-Key and persist it over retries
     * @param callable|null $progressCallback Optional callback for upload progress tracking (downloadTotal, downloadedBytes, uploadTotal, uploadedBytes)
     * @return array Decoded JSON response
     */
    public function postMultipart(string $path, array $fields, array $headers = [], ?float $timeout = null, bool $idempotent = false, ?callable $progressCallback = null): array;

    /**
     * Send a JSON POST request initiating an SSE stream and yield raw SSE lines.
     *
     * @param string $path Relative API path, e.g. '/v1/responses'
     * @param array $payload JSON payload to send (should include ['stream' => true])
     * @param array $headers Optional extra headers
     * @param float|null $timeout Per-call timeout override (seconds)
     * @param bool $idempotent Whether to enforce/provide Idempotency-Key and persist it over retries
     * @return iterable<string>
     */
    public function streamSse(string $path, array $payload, array $headers = [], ?float $timeout = null, bool $idempotent = false): iterable;

    /**
     * Send a GET request and return the decoded array response.
     *
     * @param string $path Relative API path, e.g. '/v1/responses/{id}'
     * @param array $headers Optional extra headers
     * @param float|null $timeout Per-call timeout override (seconds)
     * @return array Decoded JSON response
     */
    public function getJson(string $path, array $headers = [], ?float $timeout = null): array;

    /**
     * Send a GET request and return raw content with content type.
     *
     * @param string $path Relative API path, e.g. '/v1/files/{id}/content'
     * @param array $headers Optional extra headers
     * @param float|null $timeout Per-call timeout override (seconds)
     * @return array{content:string, content_type:string}
     */
    public function getContent(string $path, array $headers = [], ?float $timeout = null): array;

    /**
     * Send a DELETE request and return true on success.
     *
     * @param string $path Relative API path, e.g. '/v1/responses/{id}'
     * @param array $headers Optional extra headers
     * @param float|null $timeout Per-call timeout override (seconds)
     * @return bool True if the operation succeeded (status < 400)
     */
    public function delete(string $path, array $headers = [], ?float $timeout = null): bool;

    /**
     * Send a request with any HTTP method and body type and return the decoded response.
     *
     * Supported options:
     *  - query: array of query parameters (arrays are encoded as key[]=a&key[]=b, booleans as true/false)
     *  - json: JSON body
     *  - multipart: multipart fields, either a name => value map (same format as postMultipart())
     *    or a list of explicit parts: [['name' => ..., 'contents' => ..., 'filename' => ..., 'headers' => [...]], ...]
     *  - body: raw request body (set the Content-Type via headers)
     *  - headers: extra headers
     *  - timeout: per-call timeout override (seconds)
     *  - idempotent: whether to enforce/provide an Idempotency-Key and persist it over retries
     *
     * @param string $method HTTP method, e.g. 'GET', 'POST', 'DELETE'
     * @param string $path Relative API path, e.g. '/v1/audio/transcriptions'
     * @param array{query?: array<string, mixed>, json?: array<mixed>, multipart?: array<mixed>, body?: string, headers?: array<string, string>, timeout?: float|null, idempotent?: bool} $options
     * @return array Decoded JSON response; ['text' => ...] for text/plain bodies; ['content' => ..., 'content_type' => ...]
     *               for other non-JSON bodies (audio, video, SDP, ...); an empty array for empty bodies
     */
    public function request(string $method, string $path, array $options = []): array;

    /**
     * Send a request with any HTTP method and body type that answers with Server-Sent Events and yield raw SSE lines.
     * Accepts the same options as request().
     *
     * @param string $method HTTP method, e.g. 'GET', 'POST'
     * @param string $path Relative API path, e.g. '/v1/audio/transcriptions'
     * @param array{query?: array<string, mixed>, json?: array<mixed>, multipart?: array<mixed>, body?: string, headers?: array<string, string>, timeout?: float|null, idempotent?: bool} $options
     * @return iterable<string>
     */
    public function streamRequest(string $method, string $path, array $options = []): iterable;
}
