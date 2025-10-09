<?php

declare(strict_types=1);

namespace CreativeCrafts\LaravelAiAssistant\Services;

use CreativeCrafts\LaravelAiAssistant\Chat\ChatSession;
use CreativeCrafts\LaravelAiAssistant\DataTransferObjects\ChatResponseDto;
use CreativeCrafts\LaravelAiAssistant\DataTransferObjects\CompletionRequest;
use CreativeCrafts\LaravelAiAssistant\DataTransferObjects\CompletionResult;
use CreativeCrafts\LaravelAiAssistant\Enums\Mode;
use CreativeCrafts\LaravelAiAssistant\Enums\Transport;
use CreativeCrafts\LaravelAiAssistant\Support\ConversationsBuilder;
use CreativeCrafts\LaravelAiAssistant\Support\ResponsesBuilder;
use Generator;
use JsonException;
use Psr\SimpleCache\InvalidArgumentException;

final class AiManager
{
    public function __construct(public AssistantService $assistantService)
    {
    }

    /**
     * Access the Responses API via a fluent builder.
     */
    public function responses(): ResponsesBuilder
    {
        return new ResponsesBuilder($this->assistantService);
    }

    /**
     * Access the Conversations API via a fluent builder.
     */
    public function conversations(): ConversationsBuilder
    {
        return new ConversationsBuilder($this->assistantService);
    }

    /**
     * Convenience: one-off chat using the ChatSession path.
     * Accepts a string prompt or an array of options (message/prompt, model, temperature, response_format).
     * Examples:
     *  Ai::quick('Say hello')->send();
     *  Ai::quick([
     *      'message' => 'Hi',
     *      'model' => 'gpt-4o-mini',
     *      'temperature' => 0.2,
     *      'response_format' => 'json'
     *  ])->send();
     */
    public function quick(string|array $input): ChatResponseDto
    {
        if (is_string($input)) {
            return ChatSession::make($input)->send();
        }

        // Normalise array input
        $message = (string)($input['message'] ?? ($input['prompt'] ?? ''));
        $model = $input['model'] ?? null;
        $temperature = $input['temperature'] ?? null;
        $format = $input['response_format'] ?? null; // 'json' | 'text' | array schema

        // Build a ChatSession via the primary entrypoint
        $session = $this->chat($message);

        if ($model !== null) {
            $session->setModelName((string)$model);
        }

        if ($temperature !== null) {
            $session->setTemperature((float)$temperature);
        }

        if ($format === 'text') {
            $session->setResponseFormatText();
        } elseif ($format === 'json') {
            $session->setResponseFormatJson();
        } elseif (is_array($format)) {
            $schema = $format['schema'] ?? $format;
            $name = $format['name'] ?? 'result';
            $session->setResponseFormatJsonSchema($schema, $name);
        }

        return $session->send();
    }

    public function chat(?string $prompt = ''): ChatSession
    {
        return ChatSession::make($prompt ?? '');
    }

    /**
     * Stream events from a prompt. Yields string chunks or typed events.
     *
     * @param callable(array|string):void|null $onEvent
     * @param callable():bool|null $shouldStop
     * @return Generator
     */
    public function stream(string $prompt, ?callable $onEvent = null, ?callable $shouldStop = null): Generator
    {
        return ChatSession::make($prompt)->stream($onEvent, $shouldStop);
    }

    /**
     * NEW: Unified completion entrypoint (public front door).
     * Wraps AssistantService::completeSync/completeStream.
     *
     * @throws JsonException
     * @throws InvalidArgumentException
     */
    public function complete(Mode $mode, Transport $transport, CompletionRequest $request): CompletionResult
    {
        if ($transport === Transport::SYNC) {
            return $this->assistantService->completeSync($mode, $request);
        }

        // Stream → accumulate into a final result (callers who need incremental events should still use ChatSession::stream)
        return $this->assistantService->completeStream($mode, $request);
    }

}
