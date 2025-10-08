<?php

declare(strict_types=1);

namespace CreativeCrafts\LaravelAiAssistant\Support;

use CreativeCrafts\LaravelAiAssistant\DataTransferObjects\ChatResponseDto;
use CreativeCrafts\LaravelAiAssistant\Services\AssistantService;

/**
 * Fluent builder for Conversations operations with convenience to send turns.
 */
final class ConversationsBuilder
{
    private ?string $conversationId = null;
    private InputItemsBuilder $input;

    public function __construct(private readonly AssistantService $service)
    {
        $this->input = new InputItemsBuilder();
    }

    /**
     * Create a new conversation and return its id. Optionally set it as active.
     * @param array<string,mixed> $metadata
     */
    public function start(array $metadata = [], bool $setActive = true): string
    {
        $id = $this->service->createConversation($metadata);
        if ($setActive) {
            $this->conversationId = $id;
        }
        return $id;
    }

    public function use(string $conversationId): self
    {
        $this->conversationId = $conversationId;
        return $this;
    }

    /**
     * List items for the active conversation.
     * @param array<string,mixed> $params
     * @return array<int,array<string,mixed>>
     */
    public function items(array $params = []): array
    {
        $conv = $this->ensureConversationId();
        return $this->service->listConversationItems($conv, $params);
    }

    public function input(): InputItemsBuilder
    {
        return $this->input;
    }

    /**
     * Send a turn to the active conversation using the Responses API.
     */
    public function send(): ChatResponseDto
    {
        $conv = $this->ensureConversationId();
        $resp = (new ResponsesBuilder($this->service))
            ->inConversation($conv)
            ->withInput($this->input->list())
            ->send();
        return $resp;
    }

    /**
     * Get a ResponsesBuilder bound to the active conversation.
     */
    public function responses(): ResponsesBuilder
    {
        $conv = $this->ensureConversationId();
        return (new ResponsesBuilder($this->service))->inConversation($conv);
    }

    private function ensureConversationId(): string
    {
        if (!is_string($this->conversationId) || $this->conversationId === '') {
            $this->conversationId = $this->service->createConversation();
        }
        return $this->conversationId;
    }
}
