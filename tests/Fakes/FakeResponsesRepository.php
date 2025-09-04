<?php

declare(strict_types=1);

namespace CreativeCrafts\LaravelAiAssistant\Tests\Fakes;

use CreativeCrafts\LaravelAiAssistant\Contracts\ResponsesRepositoryContract;

final class FakeResponsesRepository implements ResponsesRepositoryContract
{
    /** @var array<int,array> */
    public array $queue = [];
    /** @var array<int,string> */
    public array $streamLines = [];
    public array $lastPayload = [];
    public array $canceled = [];
    public array $deleted = [];
    public ?array $lastResponse = null;

    public function pushResponse(array $response): void
    {
        $this->queue[] = $response;
    }

    public function setStream(array $lines): void
    {
        $this->streamLines = $lines;
    }

    public function createResponse(array $payload): array
    {
        $this->lastPayload = $payload;
        $resp = array_shift($this->queue);
        if ($resp === null) {
            // default dummy response
            $resp = [
                'id' => 'resp_dummy',
                'conversation_id' => (string)($payload['conversation'] ?? ''),
                'status' => 'completed',
                'output' => [
                    ['type' => 'output_text', 'content' => [['text' => 'ok']]],
                ],
                'finish_reason' => 'stop',
            ];
        }
        $this->lastResponse = $resp;
        return $resp;
    }

    public function streamResponse(array $payload): iterable
    {
        $this->lastPayload = $payload;
        if ($this->streamLines === []) {
            // default trivial stream
            return [
                'event: response.output_text.delta',
                'data: {"delta":"ok"}',
                '',
                'event: response.completed',
                'data: {"type":"response.completed"}',
                '',
            ];
        }
        return $this->streamLines;
    }

    public function getResponse(string $responseId): array
    {
        return $this->lastResponse ?? ['id' => $responseId, 'status' => 'completed'];
    }

    public function cancelResponse(string $responseId): bool
    {
        $this->canceled[] = $responseId;
        return true;
    }

    public function deleteResponse(string $responseId): bool
    {
        $this->deleted[] = $responseId;
        return true;
    }
}
