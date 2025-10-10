<?php

declare(strict_types=1);

namespace CreativeCrafts\LaravelAiAssistant\Support;

use CreativeCrafts\LaravelAiAssistant\Adapters\AdapterFactory;
use CreativeCrafts\LaravelAiAssistant\DataTransferObjects\ChatResponseDto;
use CreativeCrafts\LaravelAiAssistant\DataTransferObjects\ResponseDto;
use CreativeCrafts\LaravelAiAssistant\Enums\OpenAiEndpoint;
use CreativeCrafts\LaravelAiAssistant\Services\AssistantService;
use CreativeCrafts\LaravelAiAssistant\Services\OpenAiClient;
use CreativeCrafts\LaravelAiAssistant\Services\RequestRouter;
use Generator;
use InvalidArgumentException;
use RuntimeException;
use Throwable;

/**
 * Fluent builder for the unified Responses API.
 *
 * This builder supports multiple input types (text, audio, images) and internally
 * routes requests to the appropriate OpenAI endpoint (Response API, Audio, Image, Chat Completion).
 *
 * Usage examples:
 * ```php
 * // Audio transcription
 * $response = Ai::responses()
 *     ->input()
 *     ->audio(['file' => 'audio.mp3', 'action' => 'transcribe'])
 *     ->send();
 *
 * // Image generation
 * $response = Ai::responses()
 *     ->input()
 *     ->image(['prompt' => 'A sunset', 'size' => '1024x1024'])
 *     ->send();
 *
 * // Text chat (standard)
 * $response = Ai::responses()
 *     ->input()
 *     ->message('Hello')
 *     ->send();
 * ```
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

    private InputItemsBuilder $inputItems;
    private InputBuilder $unifiedInput;
    private readonly RequestRouter $router;
    private readonly AdapterFactory $adapterFactory;
    private readonly OpenAiClient $openAiClient;

    public function __construct(
        private readonly AssistantService $service,
        ?RequestRouter $router = null,
        ?AdapterFactory $adapterFactory = null,
        ?OpenAiClient $openAiClient = null,
    ) {
        $this->inputItems = new InputItemsBuilder();
        $this->unifiedInput = InputBuilder::make();
        $this->router = $router ?? new RequestRouter();
        $this->adapterFactory = $adapterFactory ?? new AdapterFactory();
        $this->openAiClient = $openAiClient ?? app(OpenAiClient::class);
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
     * For backward compatibility with InputItemsBuilder usage.
     * @param array<int,array<string,mixed>> $items
     */
    public function withInput(array $items): self
    {
        $this->inputItems = new InputItemsBuilder();
        foreach ($items as $it) {
            $this->inputItems->appendRaw($it);
        }
        return $this;
    }

    /**
     * Get the unified input builder for audio, image, and text inputs.
     * This is the recommended way to add inputs to the response builder.
     */
    public function input(): InputBuilder
    {
        return $this->unifiedInput;
    }

    /**
     * Get the legacy input items builder.
     * For backward compatibility with existing Response API usage.
     */
    public function inputItems(): InputItemsBuilder
    {
        return $this->inputItems;
    }

    /**
     * Send a response using the unified API with automatic endpoint routing.
     *
     * This method implements the SSOT (Single Source of Truth) pattern:
     * 1. Builds unified request from builder state
     * 2. Determines appropriate endpoint using RequestRouter
     * 3. Gets adapter from AdapterFactory
     * 4. Transforms request using adapter
     * 5. Makes API call to correct endpoint
     * 6. Transforms response using adapter
     * 7. Returns unified ResponseDto
     *
     * For backward compatibility, if using legacy InputItemsBuilder,
     * falls back to direct Response API call.
     */
    public function send(): ResponseDto|ChatResponseDto
    {
        // Check if using legacy InputItemsBuilder (backward compatibility)
        $inputItemsList = $this->inputItems->list();
        $unifiedData = $this->unifiedInput->toArray();

        // If only using legacy input items (no unified input), use original behavior
        if (empty($unifiedData) && !empty($inputItemsList)) {
            $conv = $this->conversationId ?? $this->service->createConversation();
            $arr = $this->service->sendTurn(
                conversationId: $conv,
                instructions: $this->instructions,
                model: $this->model,
                tools: $this->tools,
                inputItems: $inputItemsList,
                responseFormat: $this->responseFormat,
                modalities: $this->modalities,
                metadata: $this->metadata,
                idempotencyKey: $this->idempotencyKey,
                toolChoice: $this->toolChoice,
            );
            return ChatResponseDto::fromArray($arr);
        }

        // Step 1: Build unified request from builder state
        $unifiedRequest = $this->buildRequest();

        try {
            // Step 2: Determine which endpoint to use
            $endpoint = $this->router->determineEndpoint($unifiedRequest);

            // Step 3: Get appropriate adapter
            $adapter = $this->adapterFactory->make($endpoint);

            // Step 4: Transform request for specific endpoint
            $endpointRequest = $adapter->transformRequest($unifiedRequest);

            // Step 5: Make API call (placeholder - will be implemented with actual HTTP client)
            // For now, we'll use the service for Response API endpoint
            // TODO: Implement actual endpoint-specific API calls
            $apiResponse = $this->makeApiCall($endpoint, $endpointRequest);

            // Step 6: Transform response to unified format
            return $adapter->transformResponse($apiResponse);
        } catch (InvalidArgumentException $e) {
            throw new InvalidArgumentException(
                'Failed to process unified API request: ' . $e->getMessage() . '. ' .
                'Please check your input configuration (audio, image, or text parameters).',
                $e->getCode(),
                $e
            );
        } catch (RuntimeException $e) {
            throw new RuntimeException(
                'API request failed: ' . $e->getMessage() . '. ' .
                'This may indicate a network issue, invalid API key, or unsupported parameter combination.',
                $e->getCode(),
                $e
            );
        } catch (Throwable $e) {
            throw new RuntimeException(
                'Unexpected error during unified API request: ' . $e->getMessage() . '. ' .
                'Error type: ' . get_class($e),
                0,
                $e
            );
        }
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
            inputItems: $this->inputItems->list(),
            responseFormat: $this->responseFormat,
            modalities: $this->modalities,
            metadata: $this->metadata,
            onEvent: $onEvent,
            shouldStop: $shouldStop,
            idempotencyKey: $this->idempotencyKey,
            toolChoice: $this->toolChoice,
        );
    }

    /**
     * Build unified request array from builder state.
     * This merges the unified input data with builder configuration.
     *
     * @return array<string,mixed>
     */
    private function buildRequest(): array
    {
        $request = $this->unifiedInput->toArray();

        if ($this->instructions !== null) {
            $request['instructions'] = $this->instructions;
        }

        if ($this->model !== null) {
            $request['model'] = $this->model;
        }

        if (!empty($this->tools)) {
            $request['tools'] = $this->tools;
        }

        if ($this->responseFormat !== null) {
            $request['response_format'] = $this->responseFormat;
        }

        if ($this->modalities !== null) {
            $request['modalities'] = $this->modalities;
        }

        if (!empty($this->metadata)) {
            $request['metadata'] = $this->metadata;
        }

        if ($this->conversationId !== null) {
            $request['conversation_id'] = $this->conversationId;
        }

        if ($this->toolChoice !== null) {
            $request['tool_choice'] = $this->toolChoice;
        }

        return $request;
    }

    /**
     * Make API call to the specified endpoint.
     *
     * @param OpenAiEndpoint $endpoint
     * @param array<string,mixed> $request
     * @return array<string,mixed>
     */
    private function makeApiCall(OpenAiEndpoint $endpoint, array $request): array
    {
        // For Response API endpoint, use existing service for backward compatibility
        if ($endpoint === OpenAiEndpoint::ResponseApi) {
            $conv = $this->conversationId ?? $this->service->createConversation();

            // Extract and cast parameters with proper type safety
            $instructions = isset($request['instructions']) && is_string($request['instructions'])
                ? $request['instructions']
                : $this->instructions;

            $model = isset($request['model']) && is_string($request['model'])
                ? $request['model']
                : $this->model;

            $tools = isset($request['tools']) && is_array($request['tools'])
                ? $request['tools']
                : $this->tools;

            $inputItems = isset($request['input_items']) && is_array($request['input_items'])
                ? $request['input_items']
                : [];

            $responseFormat = isset($request['response_format']) && is_array($request['response_format'])
                ? $request['response_format']
                : $this->responseFormat;

            $modalities = isset($request['modalities']) && is_array($request['modalities'])
                ? $request['modalities']
                : $this->modalities;

            $metadata = isset($request['metadata']) && is_array($request['metadata'])
                ? $request['metadata']
                : $this->metadata;

            $toolChoice = $this->toolChoice;
            if (isset($request['tool_choice'])) {
                if (is_string($request['tool_choice']) || is_array($request['tool_choice'])) {
                    $toolChoice = $request['tool_choice'];
                }
            }

            return $this->service->sendTurn(
                conversationId: $conv,
                instructions: $instructions,
                model: $model,
                tools: $tools,
                inputItems: $inputItems,
                responseFormat: $responseFormat,
                modalities: $modalities,
                metadata: $metadata,
                idempotencyKey: $this->idempotencyKey,
                toolChoice: $toolChoice,
            );
        }

        // For all other endpoints, use the OpenAiClient
        return $this->openAiClient->callEndpoint($endpoint, $request);
    }
}
