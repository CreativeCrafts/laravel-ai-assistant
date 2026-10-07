<?php

declare(strict_types=1);

namespace CreativeCrafts\LaravelAiAssistant\Tests\Fakes;

use CreativeCrafts\LaravelAiAssistant\Transport\GuzzleOpenAITransport;
use GuzzleHttp\Client as GuzzleClient;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Middleware;
use GuzzleHttp\Psr7\Response;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use RuntimeException;

/**
 * A real Guzzle client backed by queued responses that records every request it sends,
 * so tests can assert on the exact HTTP request (method, URI, headers and serialized body).
 */
final class RecordingHttpClient
{
    /** @var array<int, array{request: RequestInterface}> */
    public array $history = [];

    public readonly GuzzleClient $client;

    public function __construct(ResponseInterface ...$responses)
    {
        $handler = HandlerStack::create(new MockHandler(
            $responses !== [] ? $responses : [new Response(200, ['Content-Type' => 'application/json'], '{"id":"ok"}')]
        ));
        $handler->push(Middleware::history($this->history));

        $this->client = new GuzzleClient([
            'handler' => $handler,
            'base_uri' => 'https://api.openai.com',
            'http_errors' => false,
        ]);
    }

    public static function json(array $body, int $status = 200): self
    {
        return new self(new Response($status, ['Content-Type' => 'application/json'], (string)json_encode($body)));
    }

    public static function sse(string ...$events): self
    {
        $body = implode('', array_map(static fn (string $event): string => "data: {$event}\n\n", $events));

        return new self(new Response(200, ['Content-Type' => 'text/event-stream'], $body));
    }

    public function transport(): GuzzleOpenAITransport
    {
        return new GuzzleOpenAITransport($this->client);
    }

    public function lastRequest(): RequestInterface
    {
        $last = end($this->history);
        if ($last === false) {
            throw new RuntimeException('No request was sent.');
        }

        return $last['request'];
    }

    public function lastUri(): string
    {
        return (string)$this->lastRequest()->getUri();
    }

    public function lastBody(): string
    {
        return (string)$this->lastRequest()->getBody();
    }

    public function lastJson(): mixed
    {
        return json_decode($this->lastBody(), true);
    }
}
