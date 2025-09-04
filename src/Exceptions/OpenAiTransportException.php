<?php

declare(strict_types=1);

namespace CreativeCrafts\LaravelAiAssistant\Exceptions;

use RuntimeException;
use Throwable;

final class OpenAiTransportException extends RuntimeException
{
    private ?int $httpCode;
    private ?string $requestId;
    private ?string $responseSnippet;

    public function __construct(string $message, ?int $httpCode = null, ?string $requestId = null, ?string $responseSnippet = null, ?Throwable $previous = null)
    {
        parent::__construct($message, $httpCode ?? 0, $previous);
        $this->httpCode = $httpCode;
        $this->requestId = $requestId;
        $this->responseSnippet = $responseSnippet;
    }

    public static function from(Throwable $e): self
    {
        $code = $e->getCode();
        $requestId = null;
        $snippet = null;

        // Try to extract extra info from common HTTP exception types without hard dependency
        $message = $e->getMessage();

        // If the exception has a getResponse() method, try to read status and body partially
        if (method_exists($e, 'getResponse')) {
            try {
                $resp = $e->getResponse();
                if ($resp && is_object($resp)) {
                    if (method_exists($resp, 'getStatusCode')) {
                        $code = (int) $resp->getStatusCode();
                    }
                    if (method_exists($resp, 'getHeaderLine')) {
                        $requestId = $resp->getHeaderLine('x-request-id') ?: $resp->getHeaderLine('request-id') ?: null;
                    }
                    if (method_exists($resp, 'getBody')) {
                        $body = (string) $resp->getBody();
                        $snippet = substr($body, 0, 512);
                    }
                }
            } catch (Throwable) {
                // ignore extraction errors
            }
        }

        return new self($message, is_numeric($code) ? (int) $code : null, $requestId, $snippet, $e);
    }

    public function getHttpCode(): ?int
    {
        return $this->httpCode;
    }

    public function getRequestId(): ?string
    {
        return $this->requestId;
    }

    public function getResponseSnippet(): ?string
    {
        return $this->responseSnippet;
    }
}
