<?php

declare(strict_types=1);

namespace CreativeCrafts\LaravelAiAssistant\Support;

use CreativeCrafts\LaravelAiAssistant\DataTransferObjects\ChatResponseDto;
use CreativeCrafts\LaravelAiAssistant\Services\AssistantService;
use Generator;

/**
 * Fluent builder for the Responses API.
 */
final class ResponsesBuilder
{
    private ?string $conversationId = null;
    private ?string $instructions = null;
    private ?string $model = null;
    /** @var array<int,array<string,mixed>> */
    private array $tools = [];
    private ?array $responseFormat = null; // text|json|schema arrays handled by service
    private ?array $modalities = null;
    /** @var array<string,mixed> */
    private array $metadata = [];
    private ?string $idempotencyKey = null;
    private array|string|null $toolChoice = null;

    private InputItemsBuilder $input;

    public function __construct(private readonly AssistantService $service)
    {
        $this->input = new InputItemsBuilder();
    }

    public function inConversation(string $conversationId): self
    {
        $this->conversationId = $conversationId;
        return $this;
    }

    public function instructions(string $instructions): self
    {
        $this->instructions = $instructions;
        return $this;
    }

    public function model(string $model): self
    {
        $this->model = $model;
        return $this;
    }

    /**
     * Replace input items with the provided list.
     * @param array<int,array<string,mixed>> $items
     */
    public function withInput(array $items): self
    {
        $this->input = new InputItemsBuilder();
        foreach ($items as $it) {
            $this->input->appendRaw($it);
        }
        return $this;
    }

    public function input(): InputItemsBuilder
    {
        return $this->input;
    }

    /**
     * Send a response using the Responses API.
     */
    public function send(): ChatResponseDto
    {
        $conv = $this->conversationId ?? $this->service->createConversation();
        $arr = $this->service->sendTurn(
            conversationId: $conv,
            instructions: $this->instructions,
            model: $this->model,
            tools: $this->tools,
            inputItems: $this->input->list(),
            responseFormat: $this->responseFormat,
            modalities: $this->modalities,
            metadata: $this->metadata,
            idempotencyKey: $this->idempotencyKey,
            toolChoice: $this->toolChoice,
        );
        return ChatResponseDto::fromArray($arr);
    }

    /**
     * Stream a response using the Responses API.
     *
     * @param callable(array|string):void|null $onEvent
     * @param callable():bool|null $shouldStop
     * @return Generator
     */
    public function stream(?callable $onEvent = null, ?callable $shouldStop = null): Generator
    {
        $conv = $this->conversationId ?? $this->service->createConversation();
        return $this->service->streamTurn(
            conversationId: $conv,
            instructions: $this->instructions,
            model: $this->model,
            tools: $this->tools,
            inputItems: $this->input->list(),
            responseFormat: $this->responseFormat,
            modalities: $this->modalities,
            metadata: $this->metadata,
            onEvent: $onEvent,
            shouldStop: $shouldStop,
            idempotencyKey: $this->idempotencyKey,
            toolChoice: $this->toolChoice,
        );
    }
}
