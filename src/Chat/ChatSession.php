<?php

declare(strict_types=1);

namespace CreativeCrafts\LaravelAiAssistant\Chat;

use CreativeCrafts\LaravelAiAssistant\AiAssistant;
use CreativeCrafts\LaravelAiAssistant\DataTransferObjects\ChatResponseDto;
use CreativeCrafts\LaravelAiAssistant\DataTransferObjects\StreamingEventDto;
use CreativeCrafts\LaravelAiAssistant\Support\FilesHelper;
use CreativeCrafts\LaravelAiAssistant\Support\StreamReader;
use CreativeCrafts\LaravelAiAssistant\Support\ToolsBuilder;
use Generator;
use Illuminate\Http\UploadedFile;

/**
 * Focused chat session wrapper around AiAssistant (Responses API).
 * Provides a discoverable, chainable API with strong typing.
 */
final class ChatSession
{
    private ChatOptions $options;

    public function __construct(
        private readonly AiAssistant $core,
    ) {
        $this->options = ChatOptions::make();
    }

    /**
     * Create a new ChatSession instance with an optional initial prompt.
     * This static factory method provides a convenient way to instantiate a new
     * ChatSession with an underlying AiAssistant core. The prompt parameter allows
     * you to set an initial message or context for the chat session.
     *
     * @param string|null $prompt The initial prompt or message to start the chat session with.
     *                            If null is provided, it will be converted to an empty string.
     *                            Defaults to an empty string if not specified.
     * @return self A new ChatSession instance configured with the provided prompt
     */
    public static function make(?string $prompt = ''): self
    {
        $self = new self(new AiAssistant($prompt ?? ''));
        if ($prompt !== '') {
            /** @var string $prompt */
            $self->setUserMessage($prompt);
        }
        return $self;
    }

    /**
     * Set the user message for the current chat session.
     * This method configures the message content that will be sent from the user
     * to the AI assistant. The user message represents the human input or query
     * that the AI will respond to during the chat interaction.
     *
     * @param string $text The message content from the user that will be sent to
     *                     the AI assistant. This should contain the user's question,
     *                     request, or input that they want the AI to process and
     *                     respond to.
     * @return self Returns the current ChatSession instance to enable method chaining
     *              and fluent interface usage
     */
    public function setUserMessage(string $text): self
    {
        $this->core->setUserMessage($text);
        return $this;
    }

    /**
     * Set system instructions for the AI assistant in this chat session.
     * This method configures the system-level instructions that guide the AI assistant's
     * behaviour, personality, and response style throughout the chat conversation. These
     * instructions act as a persistent context that influences how the assistant interprets
     * and responds to user messages.
     *
     * @param string $instructions The system instructions that define how the AI assistant
     *                            should behave, respond, and interact during the chat session.
     *                            This can include personality traits, response formatting
     *                            guidelines, domain expertise, or any other behavioral
     *                            directives for the assistant.
     * @return self Returns the current ChatSession instance to enable method chaining
     *              and fluent interface usage
     */
    public function instructions(string $instructions): self
    {
        $this->core->instructions($instructions);
        return $this;
    }

    /**
     * Set a developer message for the AI assistant in this chat session.
     * This method configures a developer-specific message that provides additional
     * context or instructions to the AI assistant. Developer messages are typically
     * used for debugging, testing, or providing technical context that differs from
     * regular user messages or system instructions.
     *
     * @param string $text The developer message content that will be sent to the AI
     *                     assistant. This message provides developer-specific context,
     *                     debugging information, or technical instructions that help
     *                     guide the assistant's behavior during development or testing.
     * @return self Returns the current ChatSession instance to enable method chaining
     *              and fluent interface usage
     */
    public function setDeveloperMessage(string $text): self
    {
        $this->core->setDeveloperMessage($text);
        return $this;
    }

    /**
     * Set the AI model name for this chat session.
     * This method configures which AI model will be used to generate responses
     * in the chat session. Different models may have varying capabilities,
     * performance characteristics, and cost implications. The model name should
     * correspond to a valid model identifier supported by the underlying AI service.
     *
     * @param string $model The name or identifier of the AI model to use for this
     *                      chat session. This should be a valid model name recognized
     *                      by the AI service (e.g., 'gpt-4', 'gpt-3.5-turbo', etc.).
     *                      The specific available models depend on your AI service
     *                      provider and subscription level.
     * @return self Returns the current ChatSession instance to enable method chaining
     *              and fluent interface usage
     */
    public function setModelName(string $model): self
    {
        $this->core->setModelName($model);
        $this->options->withModel($model);
        return $this;
    }

    /**
     * Configure the AI assistant to return responses in plain text format.
     * This method sets the response format to text mode, ensuring that the AI assistant
     * will return its responses as plain text without any structured formatting like JSON.
     * This is useful when you want simple, human-readable text responses from the assistant.
     *
     * @return self Returns the current ChatSession instance to enable method chaining
     *              and fluent interface usage
     */
    public function setResponseFormatText(): self
    {
        $this->core->setResponseFormatText();
        $this->options->withResponseFormat(['type' => 'text']);
        return $this;
    }

    /**
     * Get a FilesHelper instance for managing file operations within the chat session.
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
        return $this->core->sendChatMessageDto();
    }

    /**
     * Convenience: stream normalised text chunks only; yields string chunks.
     *
     * @return Generator<string>
     */
    public function streamText(callable $onTextChunk, ?callable $shouldStop = null): Generator
    {
        // Reuse the canonical event stream, then normalise to text chunks
        $events = $this->stream(onEvent: null, shouldStop: $shouldStop);

        // Use StreamReader to normalise events → text chunks
        return StreamReader::toTextChunks($events, $onTextChunk);
    }

    /**
     * Stream the chat response as a series of events in real-time.
     * This method initiates a streaming chat request that yields events as they arrive
     * from the AI assistant, allowing for real-time processing of the response. Each
     * event is wrapped in a strongly typed StreamingEventDto for consistent handling.
     * This is useful for building interactive chat interfaces or processing long
     * responses incrementally.
     *
     * @param callable|null $onEvent Optional callback function that will be invoked for each
     *                               streaming event as it arrives. The callback receives the
     *                               raw event data and can be used for logging, progress
     *                               tracking, or side effects. Defaults to null.
     * @param callable|null $shouldStop Optional callback function that determines whether to
     *                                  stop the streaming process early. The callback should
     *                                  return true to stop streaming or false to continue.
     *                                  Useful for implementing cancellation logic. Defaults to null.
     * @return Generator<StreamingEventDto> A generator that yields StreamingEventDto objects
     *                                      representing each event in the streaming response,
     *                                      allowing for memory-efficient real-time processing
     */
    public function stream(?callable $onEvent = null, ?callable $shouldStop = null): Generator
    {
        $events = $this->core->streamEvents($onEvent, $shouldStop);
        foreach ($events as $evt) {
            yield StreamingEventDto::fromArray((array)$evt);
        }
    }

    /**
     * Continue the chat conversation with tool execution results.
     * This method allows you to provide the results of tool calls that were requested
     * by the AI assistant in a previous response. The assistant will then continue
     * the conversation using these tool results to generate its next response.
     *
     * @param array $toolResults An array of tool execution results, typically containing
     *                           the output from functions or tools that the AI requested
     *                           to be called in a previous interaction
     * @return ChatResponseDto The AI assistant's response after processing the tool results,
     *                        wrapped in a strongly typed data transfer object
     */
    public function continueWithToolResults(array $toolResults): ChatResponseDto
    {
        return $this->core->continueWithToolResultsDto($toolResults);
    }

    /**
     * Get direct access to the underlying AiAssistant core instance.
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

    /**
     * Request a generic JSON object response (convenience wrapper).
     */
    public function setResponseFormatJson(): self
    {
        // Convenience wrapper for “JSON mode”. We use a permissive schema.
        return $this->setResponseFormatJsonSchema([
            '$schema' => 'https://json-schema.org/draft/2020-12/schema',
            'type' => 'object',
            'additionalProperties' => true,
        ], 'result');
    }

    /**
     * Configure the AI assistant to return responses in a specific JSON schema format.
     * This method sets the response format to use a structured JSON schema, ensuring that
     * the AI assistant will return its responses conforming to the specified schema structure.
     * This is useful when you need predictable, structured data output that can be easily
     * parsed and validated against a defined schema.
     *
     * @param array $jsonSchema The JSON schema definition that specifies the structure,
     *                         types, and validation rules for the AI assistant's response.
     *                         This should be a valid JSON Schema object that defines the
     *                         expected format of the response data.
     * @param string|null $name The name identifier for the JSON schema. This is used to
     *                         reference the schema internally and can be helpful for
     *                         debugging or logging purposes. Defaults to 'response' if
     *                         not specified or if null is provided.
     * @return self Returns the current ChatSession instance to enable method chaining
     *              and fluent interface usage
     */
    public function setResponseFormatJsonSchema(array $jsonSchema, ?string $name = 'response'): self
    {
        $this->core->setResponseFormatJsonSchema($jsonSchema, $name);
        // keep options in sync
        $this->options->withResponseFormat([
            'type' => 'json_schema',
            'json_schema' => [
                'name' => $name ?? 'response',
                'schema' => $jsonSchema,
            ],
        ]);
        return $this;
    }

    /**
     * Set the sampling temperature for this chat session.
     */
    public function setTemperature(float $temperature): self
    {
        $this->core->setTemperature($temperature);
        // keep options in sync
        $this->options->withTemperature($temperature);
        return $this;
    }

    /**
     * Attach file IDs to the current turn.
     *
     * @param array<int,string> $fileIds
     */
    public function attachFiles(array $fileIds, ?bool $useFileSearch = null): self
    {
        if (method_exists($this->core, 'attachFilesToTurn')) {
            $this->core->attachFilesToTurn($fileIds, $useFileSearch);
        } elseif (method_exists($this->core, 'attachFiles')) {
            $this->core->attachFiles($fileIds);
        }
        return $this;
    }

    /**
     * Include OpenAI's file_search tool; optionally bind vector store IDs.
     *
     * @param array<int,string> $vectorStoreIds
     */
    public function includeFileSearchTool(array $vectorStoreIds = []): self
    {
        if (method_exists($this->core, 'includeFileSearchTool')) {
            $this->core->includeFileSearchTool($vectorStoreIds);
        } else {
            // Fallback to the typed ToolsBuilder; avoids class-string|object type on core->tools()
            $this->tools()->includeFileSearchTool($vectorStoreIds);
        }
        return $this;
    }

    /**
     * Get a ToolsBuilder instance for configuring AI assistant tools and functions.
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
     * Register a callable tool (function calling).
     *
     * @param string $name
     * @param string $description
     * @param array<string,mixed> $jsonSchema JSON Schema for parameters
     * @param bool $isStrict
     * @return ChatSession
     */
    public function includeFunctionCallTool(
        string $name,
        string $description,
        array $jsonSchema,
        bool $isStrict = true,
    ): self {
        if (method_exists($this->core, 'includeFunctionCallTool')) {
            $this->core->includeFunctionCallTool(
                functionName: $name,
                functionDescription: $description,
                functionParameters: $jsonSchema,
                isStrict: $isStrict
            );
        } else {
            // Fallback to the typed ToolsBuilder (avoids class-string|object issues)
            $this->tools()->includeFunctionCallTool(
                $name,
                $description,
                $jsonSchema,
                $isStrict
            );
        }
        return $this;
    }

    /**
     * Control tool selection: 'auto' | 'required' | 'none' | ['type'=>'function','name'=>'...']
     */
    public function setToolChoice(string|array $choice): self
    {
        if (method_exists($this->core, 'setToolChoice')) {
            $this->core->setToolChoice($choice);
        }
        return $this;
    }

    /**
     * Attach an uploaded file to the current chat session.
     * This method allows you to attach a file that has been uploaded through Laravel's
     * file upload system to the AI assistant chat session. The attached file can be
     * used by the AI assistant for analysis, processing, or as context for generating
     * responses. The file will be processed according to the specified purpose.
     *
     * @param UploadedFile $file The uploaded file instance from Laravel's file upload
     *                          system that you want to attach to the chat session.
     *                          This should be a valid UploadedFile object containing
     *                          the file data and metadata.
     * @param string $purpose The purpose or context for which the file is being attached.
     *                       This parameter helps the AI service understand how to process
     *                       and utilize the file. Common values include 'assistants' for
     *                       general assistant use, 'vision' for image analysis, or other
     *                       service-specific purposes. Defaults to 'assistants'.
     * @return self Returns the current ChatSession instance to enable method chaining
     *              and fluent interface usage
     */
    public function attachUploadedFile(UploadedFile $file, string $purpose = 'assistants'): self
    {
        $this->core->attachUploadedFile($file, $purpose);
        return $this;
    }

    /**
     * Attach files from Laravel's storage system to the current chat session.
     * This method allows you to attach multiple files that are stored in Laravel's
     * storage filesystem to the AI assistant chat session. The attached files can be
     * used by the AI assistant for analysis, processing, or as context for generating
     * responses. This is useful when you need to provide existing files from your
     * application's storage as input to the AI assistant.
     *
     * @param array $paths An array of file paths relative to Laravel's storage disk.
     *                     Each path should point to a valid file within the configured
     *                     storage system. The files will be read from storage and
     *                     attached to the chat session for AI processing.
     * @param string $purpose The purpose or context for which the files are being attached.
     *                       This parameter helps the AI service understand how to process
     *                       and utilize the files. Common values include 'assistants' for
     *                       general assistant use, 'vision' for image analysis, or other
     *                       service-specific purposes. Defaults to 'assistants'.
     * @return self Returns the current ChatSession instance to enable method chaining
     *              and fluent interface usage
     */
    public function attachFilesFromStorage(array $paths, string $purpose = 'assistants'): self
    {
        $this->core->attachFilesFromStorage($paths, $purpose);
        return $this;
    }

    /**
     * Add an uploaded image file to the current chat session for vision analysis.
     * This method allows you to add an image file that has been uploaded through Laravel's
     * file upload system to the AI assistant chat session. The image will be processed
     * and made available for vision-based analysis, allowing the AI assistant to analyze,
     * describe, or answer questions about the visual content of the image.
     *
     * @param UploadedFile $file The uploaded image file instance from Laravel's file upload
     *                          system that you want to add to the chat session. This should
     *                          be a valid UploadedFile object containing the image data and
     *                          metadata. The file should be in a supported image format
     *                          (e.g., JPEG, PNG, GIF, WebP).
     * @param string $purpose The purpose or context for which the image is being added.
     *                       This parameter helps the AI service understand how to process
     *                       and utilize the image. Common values include 'assistants' for
     *                       general assistant use, 'vision' for image analysis, or other
     *                       service-specific purposes. Defaults to 'assistants'.
     * @return self Returns the current ChatSession instance to enable method chaining
     *              and fluent interface usage
     */
    public function addImageFromUploadedFile(UploadedFile $file, string $purpose = 'assistants'): self
    {
        $this->core->addImageFromUploadedFile($file, $purpose);
        return $this;
    }
}
