<?php

declare(strict_types=1);

namespace CreativeCrafts\LaravelAiAssistant\Repositories\Http;

use CreativeCrafts\LaravelAiAssistant\Contracts\ResponsesInputItemsRepositoryContract;
use CreativeCrafts\LaravelAiAssistant\Support\PathSegment;
use CreativeCrafts\LaravelAiAssistant\Support\QueryString;
use CreativeCrafts\LaravelAiAssistant\Transport\OpenAITransport;

/**
 * @internal This class is used internally by AssistantService.
 * Do not use directly - use Ai::responses() or Ai::conversations() instead.
 */
final readonly class ResponsesInputItemsHttpRepository implements ResponsesInputItemsRepositoryContract
{
    public function __construct(
        private OpenAITransport $transport,
        private string $basePath = '/v1'
    ) {
    }

    /**
     * @deprecated The OpenAI API has no endpoint for appending input items to an existing response.
     */
    public function append(string $responseId, array $items): array
    {
        $payload = ['items' => $items];
        return $this->transport->postJson($this->endpoint('responses/' . PathSegment::encode($responseId) . '/input/items'), $payload, idempotent: true);
    }

    public function list(string $responseId, array $params = []): array
    {
        return $this->transport->getJson(QueryString::append($this->endpoint('responses/' . PathSegment::encode($responseId) . '/input_items'), $params));
    }

    private function endpoint(string $path): string
    {
        $prefix = rtrim($this->basePath, '/');
        $suffix = ltrim($path, '/');
        return $prefix . '/' . $suffix;
    }
}
