<?php

declare(strict_types=1);

namespace CreativeCrafts\LaravelAiAssistant\Chat;

use CreativeCrafts\LaravelAiAssistant\AiAssistant;
use CreativeCrafts\LaravelAiAssistant\DataTransferObjects\ChatResponseDto;
use CreativeCrafts\LaravelAiAssistant\DataTransferObjects\StreamingEventDto;
use CreativeCrafts\LaravelAiAssistant\Support\FilesHelper;
use CreativeCrafts\LaravelAiAssistant\Support\ToolsBuilder;
use Generator;

/**
 * Focused chat session wrapper around AiAssistant (Responses API).
 * Provides a discoverable, chainable API with strong typing.
 */
final readonly class ChatSession
{
    public function __construct(private AiAssistant $core)
    {
    }

    /**
     * Create a new ChatSession instance with an optional initial prompt.
     *
     * This static factory method provides a convenient way to instantiate a new
     * ChatSession with an underlying AiAssistant core. The prompt parameter allows
     * you to set an initial message or context for the chat session.
     *
     * @param string|null $prompt The initial prompt or message to start the chat session with.
     *                           If null is provided, it will be converted to an empty string.
     *                           Defaults to an empty string if not specified.
     *
     * @return self A new ChatSession instance configured with the provided prompt
     */
    public static function make(?string $prompt = ''): self
    {
        return new self(new AiAssistant($prompt ?? ''));
    }

    // Configuration chainers
    public function instructions(string $instructions): self
    {
    $this->core->instructions($instructions);
    return $this;
    }
    public function setUserMessage(string $text): self
    {
    $this->core->setUserMessage($text);
    return $this;
    }
    public function setDeveloperMessage(string $text): self
    {
    $this->core->setDeveloperMessage($text);
    return $this;
    }
    public function setModelName(string $model): self
    {
    $this->core->setModelName($model);
    return $this;
    }
    public function setResponseFormatText(): self
    {
    $this->core->setResponseFormatText();
    return $this;
    }
    public function setResponseFormatJsonSchema(array $jsonSchema, ?string $name = 'response'): self
    {
    $this->core->setResponseFormatJsonSchema($jsonSchema, $name);
    return $this;
    }

    /**
     * Get a ToolsBuilder instance for configuring AI assistant tools and functions.
     *
     * This method provides access to a fluent interface for defining and configuring
     * tools that the AI assistant can use during the chat session. Tools allow the
     * assistant to perform actions like function calls, API requests, or other
     * operations beyond simple text generation.
     *
     * @return ToolsBuilder A ToolsBuilder instance configured with the current chat session's
     *                     core AiAssistant, allowing you to define and configure tools
     *                     using a fluent, chainable interface
     */
    public function tools(): ToolsBuilder
    {
        return new ToolsBuilder($this->core);
    }

    /**
     * Get a FilesHelper instance for managing file operations within the chat session.
     *
     * This method provides access to a helper class that facilitates file-related
     * operations for the AI assistant, such as uploading files, managing file
     * attachments, or processing file content that can be used as context in
     * the chat conversation.
     *
     * @return FilesHelper A FilesHelper instance configured with the current chat session's
     *                    core AiAssistant, allowing you to perform file operations
     *                    and manage file-based interactions with the AI assistant
     */
    public function files(): FilesHelper
    {
        return new FilesHelper($this->core);
    }

    /**
     * Send the configured chat message to the AI assistant and receive a response.
     *
     * This method executes the chat request using the current configuration (instructions,
     * user message, model, tools, etc.) and returns the AI assistant's response wrapped
     * in a strongly typed data transfer object. This is a synchronous operation that
     * waits for the complete response before returning.
     *
     * @return ChatResponseDto The AI assistant's complete response, including the message
     *                        content, usage statistics, and any tool calls, wrapped in
     *                        a strongly typed data transfer object
     */
    public function send(): ChatResponseDto
    {
        $arr = $this->core->sendChatMessage();
        return ChatResponseDto::fromArray($arr);
    }

    /**
     * Stream the chat response as a series of events in real-time.
     *
     * This method initiates a streaming chat request that yields events as they arrive
     * from the AI assistant, allowing for real-time processing of the response. Each
     * event is wrapped in a strongly typed StreamingEventDto for consistent handling.
     * This is useful for building interactive chat interfaces or processing long
     * responses incrementally.
     *
     * @param callable|null $onEvent Optional callback function that will be invoked for each
     *                              streaming event as it arrives. The callback receives the
     *                              raw event data and can be used for logging, progress
     *                              tracking, or side effects. Defaults to null.
     * @param callable|null $shouldStop Optional callback function that determines whether to
     *                                 stop the streaming process early. The callback should
     *                                 return true to stop streaming or false to continue.
     *                                 Useful for implementing cancellation logic. Defaults to null.
     *
     * @return Generator<StreamingEventDto> A generator that yields StreamingEventDto objects
     *                                      representing each event in the streaming response,
     *                                      allowing for memory-efficient real-time processing
     */
    public function stream(?callable $onEvent = null, ?callable $shouldStop = null): Generator
    {
        $events = $this->core->streamChatMessage($onEvent, $shouldStop);
        foreach ($events as $evt) {
            yield StreamingEventDto::fromArray((array)$evt);
        }
    }

    /**
     * Convenience: stream normalised text chunks only; yields string chunks.
     * Use StreamReader to convert events to text chunks.
     *
     * @return Generator<string>
     */
    public function streamText(callable $onTextChunk, ?callable $shouldStop = null): Generator
    {
        return $this->core->streamChatText($onTextChunk, $shouldStop);
    }

    /**
     * Continue the chat conversation with tool execution results.
     *
     * This method allows you to provide the results of tool calls that were requested
     * by the AI assistant in a previous response. The assistant will then continue
     * the conversation using these tool results to generate its next response.
     *
     * @param array $toolResults An array of tool execution results, typically containing
     *                          the output from functions or tools that the AI requested
     *                          to be called in a previous interaction
     *
     * @return ChatResponseDto The AI assistant's response after processing the tool results,
     *                        wrapped in a strongly typed data transfer object
     */
    public function continueWithToolResults(array $toolResults): ChatResponseDto
    {
        $arr = $this->core->continueWithToolResults($toolResults);
        return ChatResponseDto::fromArray($arr);
    }

     /**
     * Get direct access to the underlying AiAssistant core instance.
     *
     * This method provides an escape hatch to access the raw AiAssistant instance
     * when you need functionality that isn't exposed through the ChatSession's
     * fluent interface. Use this sparingly and prefer the typed methods when possible.
     *
     * @return AiAssistant The underlying AiAssistant instance that powers this chat session
     */
    public function core(): AiAssistant
    {
        return $this->core;
    }
}
