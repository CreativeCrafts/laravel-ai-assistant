<?php

declare(strict_types=1);

namespace CreativeCrafts\LaravelAiAssistant\Support;

use CreativeCrafts\LaravelAiAssistant\Adapters\AdapterFactory;
use CreativeCrafts\LaravelAiAssistant\DataTransferObjects\ChatResponseDto;
use CreativeCrafts\LaravelAiAssistant\Services\AssistantService;
use CreativeCrafts\LaravelAiAssistant\Services\RequestRouter;
use JsonException;
use RuntimeException;

/**
 * Fluent builder for Conversations operations with convenience to send turns.
 *
 * This builder follows the mutable fluent pattern:
 * - All methods modify internal state and return $this for method chaining
 * - The InputItemsBuilder returned by input() is also mutable
 * - No cloning is performed; the same instance is modified throughout the chain
 */
final class ConversationsBuilder
{
    private ?string $conversationId = null;
    private InputItemsBuilder $input;
    private readonly RequestRouter $router;
    private readonly AdapterFactory $adapterFactory;

    public function __construct(
        private readonly AssistantService $service,
        ?RequestRouter $router = null,
        ?AdapterFactory $adapterFactory = null,
    ) {
        $this->input = new InputItemsBuilder();
        $this->router = $router ?? app(RequestRouter::class);
        $this->adapterFactory = $adapterFactory ?? new AdapterFactory();
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
     *
     * @throws JsonException
     */
    public function send(): ChatResponseDto
    {
        $conv = $this->ensureConversationId();
        $resp = (new ResponsesBuilder($this->service, $this->router, $this->adapterFactory))
            ->inConversation($conv)
            ->withInput($this->input->list())
            ->send();

        if (!$resp instanceof ChatResponseDto) {
            throw new RuntimeException(
                'Expected ChatResponseDto but received ' . get_class($resp) . '. ' .
                'This indicates an internal routing error in the unified API.'
            );
        }

        return $resp;
    }

    /**
     * Get a ResponsesBuilder bound to the active conversation.
     */
    public function responses(): ResponsesBuilder
    {
        $conv = $this->ensureConversationId();
        return (new ResponsesBuilder($this->service, $this->router, $this->adapterFactory))
            ->inConversation($conv);
    }

    private function ensureConversationId(): string
    {
        if (!is_string($this->conversationId) || $this->conversationId === '') {
            $this->conversationId = $this->service->createConversation();
        }
        return $this->conversationId;
    }
}
