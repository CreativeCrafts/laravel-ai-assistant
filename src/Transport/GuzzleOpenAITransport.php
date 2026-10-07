<?php

declare(strict_types=1);

namespace CreativeCrafts\LaravelAiAssistant\Transport;

use CreativeCrafts\LaravelAiAssistant\Exceptions\ApiResponseValidationException;
use CreativeCrafts\LaravelAiAssistant\Exceptions\MaxRetryAttemptsExceededException;
use CreativeCrafts\LaravelAiAssistant\Support\QueryString;
use Generator;
use GuzzleHttp\Client as GuzzleClient;
use GuzzleHttp\Exception\ConnectException;
use GuzzleHttp\Exception\TransferException;
use JsonException;
use Psr\Http\Message\ResponseInterface;
use SplFileInfo;
use Symfony\Component\HttpFoundation\Response as Http;
use Throwable;

final readonly class GuzzleOpenAITransport implements OpenAITransport
{
    public function __construct(
        private GuzzleClient $http,
        private string $basePath = '/v1'
    ) {
    }

    /**
     * @throws JsonException
     */
    public function postJson(string $path, array $payload, array $headers = [], ?float $timeout = null, bool $idempotent = false): array
    {
        $headers = $this->prepareHeaders($headers, false, $payload, $idempotent);
        $timeout = $this->resolveTimeout($timeout);
        $options = [
            'headers' => $headers,
            'json' => $payload,
            'timeout' => $timeout,
        ];
        $res = $this->requestWithRetry('POST', $path, $options, $idempotent);
        return $this->decodeOrFail($res);
    }

    public function delete(string $path, array $headers = [], ?float $timeout = null): bool
    {
        $payload = [];
        $headers = $this->prepareHeaders($headers, false, $payload, false);
        $timeout = $this->resolveTimeout($timeout);
        $options = [
            'headers' => $headers,
            'timeout' => $timeout,
        ];
        $res = $this->requestWithRetry('DELETE', $path, $options, false);
        if ($res->getStatusCode() >= Http::HTTP_BAD_REQUEST) {
            $this->throwForError($res);
        }
        return true;
    }

    public function postMultipart(string $path, array $fields, array $headers = [], ?float $timeout = null, bool $idempotent = false, ?callable $progressCallback = null): array
    {
        // Build Accept header and Idempotency for multipart without forcing Content-Type
        $baseHeaders = [
            'Accept' => 'application/json',
        ];
        $headers += $baseHeaders;

        $timeout = $this->resolveTimeout($timeout);

        if ($idempotent && (bool)config('ai-assistant.responses.idempotency_enabled', true)) {
            if (isset($fields['_idempotency_key']) && is_string($fields['_idempotency_key']) && $fields['_idempotency_key'] !== '') {
                $headers['Idempotency-Key'] = $fields['_idempotency_key'];
                unset($fields['_idempotency_key']);
            } else {
                $headers['Idempotency-Key'] = $headers['Idempotency-Key'] ?? $this->generateIdempotencyKey();
            }
        }

        $multipart = $this->buildMultipart($fields);

        $options = [
            'headers' => $headers,
            'multipart' => $multipart,
            'timeout' => $timeout,
        ];

        if ($progressCallback !== null) {
            $options['progress'] = $progressCallback;
        }

        $res = $this->requestWithRetry('POST', $path, $options, $idempotent);
        return $this->decodeOrFail($res);
    }

    /**
     * @throws JsonException
     */
    public function getJson(string $path, array $headers = [], ?float $timeout = null): array
    {
        $payload = [];
        $headers = $this->prepareHeaders($headers, false, $payload, false);
        $timeout = $this->resolveTimeout($timeout);
        $options = [
            'headers' => $headers,
            'timeout' => $timeout,
        ];
        $res = $this->requestWithRetry('GET', $path, $options, false);
        return $this->decodeOrFail($res);
    }

    public function getContent(string $path, array $headers = [], ?float $timeout = null): array
    {
        $timeout = $this->resolveTimeout($timeout);
        $headers = ['Accept' => '*/*'] + $headers;
        $options = [
            'headers' => $headers,
            'timeout' => $timeout,
        ];
        $res = $this->requestWithRetry('GET', $path, $options, false);
        if ($res->getStatusCode() >= Http::HTTP_BAD_REQUEST) {
            $this->throwForError($res);
        }

        return [
            'content' => (string)$res->getBody(),
            'content_type' => $res->getHeaderLine('Content-Type'),
        ];
    }

    public function streamSse(string $path, array $payload, array $headers = [], ?float $timeout = null, bool $idempotent = false): iterable
    {
        $headers = $this->prepareHeaders($headers + ['Accept' => 'text/event-stream'], true, $payload, $idempotent);
        $timeout = $this->resolveSseTimeout($timeout);
        $options = [
            'headers' => $headers,
            'json' => $payload + ['stream' => true],
            'stream' => true,
            'timeout' => $timeout,
        ];
        $res = $this->requestWithRetry('POST', $path, $options, $idempotent);
        if ($res->getStatusCode() >= Http::HTTP_BAD_REQUEST) {
            $this->throwForError($res);
        }

        yield from $this->readSseLines($res);
    }

    public function request(string $method, string $path, array $options = []): array
    {
        [$method, $uri, $requestOptions, $idempotent] = $this->prepareRequest($method, $path, $options, false);
        $res = $this->requestWithRetry($method, $uri, $requestOptions, $idempotent);

        return $this->decodeOrFail($res);
    }

    public function streamRequest(string $method, string $path, array $options = []): iterable
    {
        [$method, $uri, $requestOptions, $idempotent] = $this->prepareRequest($method, $path, $options, true);
        $res = $this->requestWithRetry($method, $uri, $requestOptions, $idempotent);
        if ($res->getStatusCode() >= Http::HTTP_BAD_REQUEST) {
            $this->throwForError($res);
        }

        yield from $this->readSseLines($res);
    }

    /**
     * Translate request() options into Guzzle request options.
     *
     * @param array{query?: array<string, mixed>, json?: array<mixed>, multipart?: array<mixed>, body?: string, headers?: array<string, string>, timeout?: float|null, idempotent?: bool} $options
     * @return array{0: string, 1: string, 2: array<string, mixed>, 3: bool}
     */
    private function prepareRequest(string $method, string $path, array $options, bool $isStream): array
    {
        $method = strtoupper($method);
        $idempotent = (bool)($options['idempotent'] ?? false);
        $headers = $options['headers'] ?? [];
        $headers += ['Accept' => $isStream ? 'text/event-stream' : 'application/json'];

        $requestOptions = [];
        if (isset($options['multipart'])) {
            $fields = $options['multipart'];
            if (isset($fields['_idempotency_key']) && is_string($fields['_idempotency_key'])) {
                $headers['Idempotency-Key'] ??= $fields['_idempotency_key'];
                unset($fields['_idempotency_key']);
            }
            $requestOptions['multipart'] = $this->buildMultipart($fields);
        } elseif (isset($options['json'])) {
            $payload = $options['json'];
            $headers = $this->prepareHeaders($headers, $isStream, $payload, $idempotent);
            $requestOptions['json'] = $payload;
        } elseif (isset($options['body'])) {
            $requestOptions['body'] = $options['body'];
        }

        if ($idempotent && (bool)config('ai-assistant.responses.idempotency_enabled', true)) {
            $headers['Idempotency-Key'] ??= $this->generateIdempotencyKey();
        }

        $timeout = $options['timeout'] ?? null;
        $requestOptions['headers'] = $headers;
        $requestOptions['timeout'] = $isStream ? $this->resolveSseTimeout($timeout) : $this->resolveTimeout($timeout);
        if ($isStream) {
            $requestOptions['stream'] = true;
        }

        $uri = QueryString::append($path, $options['query'] ?? []);

        return [$method, $uri, $requestOptions, $idempotent];
    }

    /**
     * Read a streamed response body and yield non-empty SSE lines, buffering partial frames.
     *
     * @return Generator<int, string>
     */
    private function readSseLines(ResponseInterface $res): Generator
    {
        $body = $res->getBody();
        $buffer = '';
        while (!$body->eof()) {
            $chunk = $body->read(1024);
            if ($chunk === '') {
                continue;
            }
            $buffer .= $chunk;
            $lines = preg_split('/\r?\n/', $buffer);
            if ($lines !== false) {
                $buffer = (string)array_pop($lines);
                foreach ($lines as $line) {
                    if ($line === '') {
                        continue;
                    }
                    yield $line;
                }
            }
        }
        $remaining = trim($buffer);
        if ($remaining !== '') {
            yield $remaining;
        }
    }

    /**
     * Build Guzzle multipart parts from either a name => value map or a list of explicit parts.
     *
     * Map entries keep their historical behavior: 'file' entries (paths, SplFileInfo, resources) become file
     * parts, entries shaped like ['contents' => ..., 'filename' => ...] become explicit parts, and other
     * non-scalar values are JSON encoded. A list of explicit parts ([['name' => ..., 'contents' => ...], ...])
     * is passed through as-is, which allows repeated names such as known_speaker_names[].
     *
     * @return array<int, array<string, mixed>>
     */
    private function buildMultipart(array $fields): array
    {
        if ($this->isExplicitPartList($fields)) {
            return array_map(fn (array $part): array => $this->normalizeExplicitPart($part), $fields);
        }

        $multipart = [];
        foreach ($fields as $name => $value) {
            // Allow explicit part specification via array: ['contents'=>..., 'filename'=>..., 'content_type'=>..., 'headers'=>[...]]
            if (is_array($value) && array_key_exists('contents', $value)) {
                $part = [
                    'name' => (string)$name,
                    'contents' => $value['contents'],
                ];
                if (is_string($value['contents']) && is_readable($value['contents'])) {
                    $part['contents'] = fopen($value['contents'], 'rb');
                }
                if (isset($value['filename']) && is_string($value['filename']) && $value['filename'] !== '') {
                    $part['filename'] = $value['filename'];
                }
                // Support both 'content_type' and 'headers' override
                $headersOverride = [];
                if (isset($value['content_type']) && is_string($value['content_type']) && $value['content_type'] !== '') {
                    $headersOverride['Content-Type'] = $value['content_type'];
                }
                if (isset($value['headers']) && is_array($value['headers'])) {
                    $headersOverride = $value['headers'] + $headersOverride;
                }
                if ($headersOverride) {
                    $part['headers'] = $headersOverride;
                }
                $multipart[] = $part;
                continue;
            }

            if ($name === 'file') {
                $part = [
                    'name' => (string)$name,
                ];

                $filename = null;
                $contentType = null;
                $contents = $value;

                // If a SplFileInfo is provided
                if ($value instanceof SplFileInfo) {
                    $path = $value->getRealPath() ?: $value->getPathname();
                    if (is_string($path) && is_readable($path)) {
                        $contents = fopen($path, 'rb');
                        $filename = $value->getFilename();
                        if (function_exists('finfo_open')) {
                            $f = finfo_open(FILEINFO_MIME_TYPE);
                            if ($f) {
                                $detected = finfo_file($f, $path);
                                if (is_string($detected) && $detected !== '') {
                                    $contentType = $detected;
                                }
                                finfo_close($f);
                            }
                        }
                    }
                }

                // If a string path is provided
                if (is_string($value) && is_readable($value)) {
                    $contents = fopen($value, 'rb');
                    $filename = basename($value);
                    if (function_exists('finfo_open')) {
                        $f = finfo_open(FILEINFO_MIME_TYPE);
                        if ($f) {
                            $detected = finfo_file($f, $value);
                            if (is_string($detected) && $detected !== '') {
                                $contentType = $detected;
                            }
                            finfo_close($f);
                        }
                    }
                }

                // If a resource is provided, try to infer filename and mime from stream metadata
                if (is_resource($contents)) {
                    $meta = @stream_get_meta_data($contents);
                    $uri = isset($meta['uri']) ? (string) $meta['uri'] : '';
                    if ($uri !== '') {
                        if ($filename === null) {
                            $filename = basename((string)$uri);
                        }
                        if ($contentType === null && function_exists('finfo_open') && @is_file($uri)) {
                            $f = finfo_open(FILEINFO_MIME_TYPE);
                            if ($f) {
                                $detected = finfo_file($f, (string)$uri);
                                if (is_string($detected) && $detected !== '') {
                                    $contentType = $detected;
                                }
                                finfo_close($f);
                            }
                        }
                    }
                }

                $part['contents'] = $contents;
                $part['filename'] = $filename ?: 'audio';
                if (is_string($contentType) && $contentType !== '') {
                    $part['headers'] = ['Content-Type' => $contentType];
                }
                $multipart[] = $part;
                continue;
            }

            // Default handling: convert non-scalar to JSON string
            if (is_array($value) || is_object($value)) {
                try {
                    $value = json_encode($value, JSON_THROW_ON_ERROR);
                } catch (Throwable) {
                    $value = (string)json_encode($value);
                }
            } elseif (is_bool($value)) {
                $value = $value ? 'true' : 'false';
            }
            $multipart[] = [
                'name' => (string)$name,
                'contents' => (string)$value,
            ];
        }

        return $multipart;
    }

    /**
     * @phpstan-assert-if-true list<array<string, mixed>> $fields
     */
    private function isExplicitPartList(array $fields): bool
    {
        if ($fields === [] || !array_is_list($fields)) {
            return false;
        }

        foreach ($fields as $part) {
            if (!is_array($part) || !isset($part['name']) || !is_string($part['name']) || !array_key_exists('contents', $part)) {
                return false;
            }
        }

        return true;
    }

    /**
     * @param array<string, mixed> $part
     * @return array<string, mixed>
     */
    private function normalizeExplicitPart(array $part): array
    {
        $contents = $part['contents'];
        if (is_bool($contents)) {
            $contents = $contents ? 'true' : 'false';
        } elseif (is_int($contents) || is_float($contents)) {
            $contents = (string)$contents;
        } elseif (is_array($contents)) {
            $contents = (string)json_encode($contents);
        }

        $normalized = [
            'name' => $part['name'],
            'contents' => $contents,
        ];

        if (isset($part['filename']) && is_string($part['filename']) && $part['filename'] !== '') {
            $normalized['filename'] = $part['filename'];
        }

        $headers = isset($part['headers']) && is_array($part['headers']) ? $part['headers'] : [];
        if (isset($part['content_type']) && is_string($part['content_type']) && $part['content_type'] !== '') {
            $headers += ['Content-Type' => $part['content_type']];
        }
        if ($headers !== []) {
            $normalized['headers'] = $headers;
        }

        return $normalized;
    }

    private function prepareHeaders(array $headers, bool $isStream, array &$payload, bool $idempotent): array
    {
        $base = [
            'Content-Type' => 'application/json',
            'Accept' => $isStream ? 'text/event-stream' : 'application/json',
        ];
        $headers += $base;
        $idemEnabled = (bool)config('ai-assistant.responses.idempotency_enabled', true);
        if ($idempotent && $idemEnabled) {
            if (isset($payload['_idempotency_key']) && is_string($payload['_idempotency_key']) && $payload['_idempotency_key'] !== '') {
                $headers['Idempotency-Key'] = $payload['_idempotency_key'];
                unset($payload['_idempotency_key']);
            } else {
                $headers['Idempotency-Key'] = $headers['Idempotency-Key'] ?? $this->generateIdempotencyKey();
            }
        }
        return $headers;
    }

    private function generateIdempotencyKey(): string
    {
        try {
            return bin2hex(random_bytes(16));
        } catch (Throwable) {
            $ri = '';
            try {
                $ri = (string)random_int(PHP_INT_MIN, PHP_INT_MAX);
            } catch (Throwable) {
                $timeInt = (int)round(microtime(true) * 1_000_000);
                $pid = (int)getmypid();
                $hostCrc = (int)crc32((string)gethostname());
                $ri = (string)(($timeInt ^ $pid) ^ $hostCrc);
            }
            $data = microtime(true) . '|' . (string)getmypid() . '|' . (string)$ri;
            return hash('sha256', (string)$data);
        }
    }

    private function resolveTimeout(?float $timeout): float
    {
        if ($timeout !== null) {
            return (float)$timeout;
        }
        $cfg = config('ai-assistant.responses.timeout', 120);
        if (!is_numeric($cfg)) {
            $cfg = 120;
        }
        return (float)$cfg;
    }

    private function requestWithRetry(string $method, string $path, array $options, bool $idempotent): ResponseInterface
    {
        $retryCfg = (array)config('ai-assistant.responses.retry', []);
        $transportCfg = (array)config('ai-assistant.transport', []);
        $enabled = (bool)($retryCfg['enabled'] ?? true);

        $maxAttempts = $retryCfg['max_attempts'] ?? null;
        if (!is_numeric($maxAttempts)) {
            $maxRetries = $transportCfg['max_retries'] ?? null;
            $maxAttempts = is_numeric($maxRetries) ? ((int)$maxRetries + 1) : 3;
        }
        $maxAttempts = (int)$maxAttempts;

        $initialDelay = $retryCfg['initial_delay'] ?? null;
        if (!is_numeric($initialDelay)) {
            $initialMs = $transportCfg['initial_delay_ms'] ?? null;
            $initialDelay = is_numeric($initialMs) ? ((float)$initialMs / 1000) : 0.5;
        }
        $initialDelay = (float)$initialDelay;

        $multiplier = (float)($retryCfg['backoff_multiplier'] ?? 2.0);

        $maxDelay = $retryCfg['max_delay'] ?? null;
        if (!is_numeric($maxDelay)) {
            $maxMs = $transportCfg['max_delay_ms'] ?? null;
            $maxDelay = is_numeric($maxMs) ? ((float)$maxMs / 1000) : 8.0;
        }
        $maxDelay = (float)$maxDelay;

        $jitter = (bool)($retryCfg['jitter'] ?? true);

        $attempt = 0;
        $lastException = null;
        $lastResponse = null;
        $url = $this->endpoint($path);
        $canRetry = $enabled && ($idempotent || $this->isSafeMethod($method));

        do {
            $attempt++;
            try {
                if (strtoupper($method) === 'GET') {
                    $res = $this->http->get($url, $options);
                } elseif (strtoupper($method) === 'DELETE') {
                    $res = $this->http->delete($url, $options);
                } else {
                    $res = $this->http->request($method, $url, $options);
                }
                $lastResponse = $res;
                if (!$canRetry || $attempt >= $maxAttempts || !$this->isRetryableResponse($res)) {
                    return $res;
                }
            } catch (Throwable $e) {
                $lastException = $e;
                if (!$canRetry || $attempt >= $maxAttempts || !$this->isRetryableException($e)) {
                    throw new ApiResponseValidationException($e->getMessage() ?: 'Transport error during OpenAI request.', Http::HTTP_BAD_GATEWAY);
                }
            }

            $delay = $this->computeDelay($attempt, $initialDelay, $multiplier, $maxDelay, $jitter);
            usleep((int)round($delay * 1_000_000));

            if ($idempotent) {
                $options['headers'] = ($options['headers'] ?? []) + [
                        'Idempotency-Key' => $options['headers']['Idempotency-Key'] ?? $this->generateIdempotencyKey(),
                    ];
            }
        } while ($attempt < $maxAttempts);

        if ($lastResponse instanceof ResponseInterface) {
            return $lastResponse;
        }
        throw new MaxRetryAttemptsExceededException('Maximum retry attempts exceeded for OpenAI request.');
    }

    private function endpoint(string $path): string
    {
        // If an absolute API path is provided (e.g., '/v1/responses'), return as-is to avoid double prefixing
        if ($path !== '' && $path[0] === '/') {
            return $path;
        }
        $prefix = rtrim($this->basePath, '/');
        $suffix = ltrim($path, '/');
        return $prefix . '/' . $suffix;
    }

    private function throwForError(ResponseInterface $response): void
    {
        $body = (string)$response->getBody();
        $msg = 'OpenAI API error';
        $type = null;
        $code = null;
        $param = null;
        try {
            $json = json_decode($body, true, 512, JSON_THROW_ON_ERROR);
        } catch (Throwable) {
            $json = null;
        }
        if (is_array($json)) {
            // Common OpenAI error shape: { error: { message, type, code, param } }
            if (isset($json['error']) && is_array($json['error'])) {
                $err = $json['error'];
                if (isset($err['message']) && is_string($err['message'])) {
                    $msg = (string)$err['message'];
                }
                if (isset($err['type']) && is_string($err['type'])) {
                    $type = (string)$err['type'];
                }
                if (isset($err['code']) && (is_string($err['code']) || is_numeric($err['code']))) {
                    $code = (string)$err['code'];
                }
                if (isset($err['param']) && is_string($err['param'])) {
                    $param = (string)$err['param'];
                }
            }
            // Other shapes
            if ($msg === 'OpenAI API error') {
                if (isset($json['message']) && is_string($json['message'])) {
                    $msg = (string)$json['message'];
                } elseif (isset($json['error']) && is_string($json['error'])) {
                    $msg = (string)$json['error'];
                } elseif (isset($json['errors'][0]['message']) && is_array($json['errors'])) {
                    $first = $json['errors'][0]['message'] ?? null;
                    if (is_string($first)) {
                        $msg = $first;
                    }
                } elseif ($body !== '') {
                    $msg = $body;
                }
            }
        } elseif ($body !== '') {
            $msg = $body;
        }

        // Enrich message with type/code/param if present
        $details = [];
        if ($type) {
            $details[] = "type=$type";
        }
        if ($code) {
            $details[] = "code=$code";
        }
        if ($param) {
            $details[] = "param=$param";
        }
        if ($details) {
            $msg .= ' [' . implode(' ', $details) . ']';
        }

        throw new ApiResponseValidationException($msg, $response->getStatusCode());
    }

    private function isRetryableResponse(ResponseInterface $response): bool
    {
        $status = $response->getStatusCode();
        if ($status === Http::HTTP_CONFLICT || $status === Http::HTTP_TOO_MANY_REQUESTS) {
            return true;
        }
        if ($status >= Http::HTTP_INTERNAL_SERVER_ERROR && $status <= Http::HTTP_VERSION_NOT_SUPPORTED) {
            return true;
        }
        return false;
    }

    private function isRetryableException(Throwable $e): bool
    {
        return $e instanceof ConnectException || $e instanceof TransferException;
    }

    private function computeDelay(int $attempt, float $initial, float $multiplier, float $max, bool $jitter): float
    {
        $delay = $initial * ($multiplier ** max(0, $attempt - 1));
        $delay = min($delay, $max);
        if ($jitter) {
            try {
                $rand = random_int(0, PHP_INT_MAX) / PHP_INT_MAX;
            } catch (Throwable) {
                $rand = 0.5;
            }
            $delay *= (0.5 + $rand * 0.5);
        }
        return $delay;
    }

    /**
     * @throws JsonException
     */
    private function decodeOrFail(ResponseInterface $response): array
    {
        if ($response->getStatusCode() >= Http::HTTP_BAD_REQUEST) {
            $this->throwForError($response);
        }

        $contentType = $response->getHeaderLine('Content-Type');
        $body = (string)$response->getBody();

        // Text bodies (text/plain, text/vtt, ...) are returned as-is, even when empty
        if (0 === mb_stripos($contentType, 'text/') && 0 !== mb_stripos($contentType, 'text/event-stream')) {
            return ['text' => $body];
        }

        // Some endpoints acknowledge with an empty body (e.g. 202 Accepted / 204 No Content)
        if (trim($body) === '') {
            return [];
        }

        if ($this->isBinaryContentType($contentType)) {
            $decoded = [
                'content' => $body,
                'content_type' => $contentType,
            ];
            // e.g. POST /v1/realtime/calls answers with the SDP and the new call in the Location header
            if ($response->hasHeader('Location')) {
                $decoded['location'] = $response->getHeaderLine('Location');
            }

            return $decoded;
        }

        $data = json_decode($body, true, 512, JSON_THROW_ON_ERROR);
        if (!is_array($data)) {
            throw new ApiResponseValidationException('Unexpected response format from OpenAI.');
        }
        return $data;
    }

    private function isBinaryContentType(string $contentType): bool
    {
        foreach (['audio/', 'video/', 'image/', 'application/octet-stream', 'application/sdp', 'application/binary', 'application/zip', 'application/pdf'] as $prefix) {
            if (0 === mb_stripos($contentType, $prefix)) {
                return true;
            }
        }

        return false;
    }

    private function isSafeMethod(string $method): bool
    {
        $upper = strtoupper($method);
        return $upper === 'GET' || $upper === 'HEAD';
    }

    private function resolveSseTimeout(?float $timeout): float
    {
        if ($timeout !== null) {
            return (float)$timeout;
        }
        $sse = config('ai-assistant.streaming.sse_timeout');
        if (is_numeric($sse)) {
            return (float)$sse;
        }
        return $this->resolveTimeout(null);
    }
}
