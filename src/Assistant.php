<?php

declare(strict_types=1);

namespace CreativeCrafts\LaravelAiAssistant;

use CreativeCrafts\LaravelAiAssistant\Contracts\AssistantContract;
use CreativeCrafts\LaravelAiAssistant\Contracts\FunctionCallParameterContract;
use CreativeCrafts\LaravelAiAssistant\DataFactories\ChatAssistantMessageDataFactory;
use CreativeCrafts\LaravelAiAssistant\DataFactories\ModelConfigDataFactory;
use CreativeCrafts\LaravelAiAssistant\DataTransferObjects\FunctionCallData;
use CreativeCrafts\LaravelAiAssistant\DataTransferObjects\MessageData;
use CreativeCrafts\LaravelAiAssistant\DataTransferObjects\NewAssistantResponseData;
use CreativeCrafts\LaravelAiAssistant\Exceptions\CreateNewAssistantException;
use CreativeCrafts\LaravelAiAssistant\Exceptions\MissingRequiredParameterException;
use CreativeCrafts\LaravelAiAssistant\Services\AssistantService;
use InvalidArgumentException;
use OpenAI\Responses\Assistants\AssistantResponse;
use RuntimeException;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

/**
 * The Assistant class is responsible for managing and interacting with an AI assistant using the OpenAI API.
 * It provides methods for configuring the assistant, creating tasks, and retrieving responses.
 */
final class Assistant implements AssistantContract
{
    protected AssistantService $client;

    protected array $tools = [];

    protected AssistantResponse $assistant;

    protected string $threadId;

    protected MessageData $assistantMessageData;

    protected array $modelConfig = [];

    protected bool $shouldCacheChatMessages = false;

    /**
     * Creates and returns a new instance of the Assistant class.
     *
     * This static factory method provides a convenient way to instantiate
     * the Assistant class without directly using the 'new' keyword.
     *
     * @return Assistant A new instance of the Assistant class.
     */
    public static function new(): Assistant
    {
        return new self();
    }

    /**
     * Sets the AssistantService client for making API requests.
     *
     * This method assigns the provided AssistantService instance to the current Assistant object,
     * allowing it to make API requests to the AI service.
     *
     * @param AssistantService $client The AssistantService instance to be used for API communication.
     *
     * @return Assistant Returns the current Assistant instance, allowing for method chaining.
     */
    public function client(AssistantService $client): Assistant
    {
        $this->client = $client;
        return $this;
    }

    /**
     * Sets the model name for the AI assistant.
     *
     * This method allows you to specify which AI model should be used for the assistant.
     * Different models have different capabilities and performance characteristics.
     *
     * @param string $modelName The name of the AI model to be used (e.g., 'gpt-3.5-turbo', 'gpt-4').
     *
     * @return Assistant Returns the current Assistant instance, allowing for method chaining.
     */
    public function setModelName(string $modelName): Assistant
    {
        $this->modelConfig['model'] = $modelName;
        return $this;
    }

    /**
     * Adjusts the temperature of the AI assistant.
     *
     * This method allows you to set the temperature for the AI assistant. The temperature controls the randomness of the assistant's responses.
     * A lower temperature (closer to 0) makes the assistant more deterministic and less creative,
     * while a higher temperature (closer to 1) makes the assistant more random and creative.
     */
    public function adjustTemperature(int|float $temperature): Assistant
    {
        $this->modelConfig['temperature'] = $temperature;
        return $this;
    }

    /**
     * Sets the name of the AI assistant.
     *
     * This method allows you to set the name for the AI assistant. If no name is provided,
     * the assistant will have no identifiable name.
     */
    public function setAssistantName(string $assistantName = ''): Assistant
    {
        $this->modelConfig['name'] = $assistantName;
        return $this;
    }

    /**
     * Sets the description of the AI assistant.
     *
     * This method allows you to set the description for the AI assistant. If no description is provided,
     * the assistant will have no description.
     */
    public function setAssistantDescription(string $assistantDescription = ''): Assistant
    {
        $this->modelConfig['description'] = $assistantDescription;
        return $this;
    }

    /**
     * Sets the instructions for the AI assistant.
     *
     * This method allows you to set the instructions for the AI assistant. These instructions will be used
     * as a guideline for the assistant's responses. If no instructions are provided, the assistant will
     * follow its default behavior.
     */
    public function setInstructions(string $instructions = ''): Assistant
    {
        $this->modelConfig['instructions'] = $instructions;
        return $this;
    }

    /**
     * Includes the code interpreter tool in the AI assistant's toolset.
     *
     * This method adds the 'code_interpreter' tool to the assistant's toolset.
     * If file IDs are provided, they will be associated with the code interpreter tool.
     */
    public function includeCodeInterpreterTool(array $fileIds = []): Assistant
    {
        $this->modelConfig['tools'][] = [
            'type' => 'code_interpreter',
        ];

        if ($fileIds !== []) {
            $this->modelConfig['tool_resources'] = [
                'code_interpreter' => [
                    'file_ids' => $fileIds,
                ],
            ];
        }

        return $this;
    }

    /**
     * Includes the file search tool in the AI assistant's toolset.
     *
     * This method adds the 'file_search' tool to the assistant's toolset.
     * If vector store IDs are provided, they will be associated with the file search tool.
     */
    public function includeFileSearchTool(array $vectorStoreIds = []): Assistant
    {
        $this->modelConfig['tools'][] = [
            'type' => 'file_search',
        ];

        if ($vectorStoreIds !== []) {
            $this->modelConfig['tool_resources'] = [
                'file_search' => [
                    'vector_store_ids' => $vectorStoreIds,
                ],
            ];
        }

        return $this;
    }

    /**
     * Includes the function call tool in the AI assistant's toolset.
     *
     * This method adds a 'function' tool to the assistant's toolset, allowing it to execute custom functions.
     */
    public function includeFunctionCallTool(
        string $functionName,
        string $functionDescription = '',
        FunctionCallParameterContract|array $functionParameters = [],
        bool $isStrict = false,
        array $requiredParameters = [],
        bool $hasAdditionalProperties = false
    ): Assistant {
        $functionData = new FunctionCallData(
            functionName: $functionName,
            functionDescription: $functionDescription,
            parameters: $functionParameters,
            isStrict: $isStrict,
            requiredParameters: $requiredParameters,
            hasAdditionalProperties: $hasAdditionalProperties,
        );
        $this->modelConfig['tools'][] = [
            'type' => 'function',
            'function' => $functionData->toArray(),
        ];
        return $this;
    }

    /**
     * Creates a new AI assistant using the provided configuration.
     *
     * This method sends a request to the OpenAI API to create a new AI assistant with the specified configuration.
     * The assistant's model, name, description, instructions, tools, temperature, and tool resources are set using the provided parameters.
     */
    public function create(): NewAssistantResponseData
    {
        try {
            $assistantResponse = $this->client->createAssistant(
                ModelConfigDataFactory::buildCreateAssistantData($this->modelConfig)->toArray()
            );
            return new NewAssistantResponseData($assistantResponse);
        } catch (Throwable $e) {
            $errorCode = is_int($e->getCode()) ? $e->getCode() : Response::HTTP_INTERNAL_SERVER_ERROR;
            throw new CreateNewAssistantException($e->getMessage(), $errorCode);
        }
    }

    /**
     * Assigns an existing AI assistant to the current instance.
     *
     * This method retrieves an existing AI assistant from the OpenAI API using the provided assistant ID.
     * The retrieved assistant is then assigned to the current instance for further interactions.
     */
    public function assignAssistant(?string $assistantId = null): Assistant
    {
        if ($assistantId === null) {
            $assistantId = $this->modelConfig['assistant_id'];
        }
        $this->assistant = $this->client->getAssistantViaId($assistantId);
        return $this;
    }

    /**
     * Sets the ID of the AI assistant.
     *
     * This method allows you to set or update the assistant ID for the current instance.
     * It's useful when you want to work with a specific, existing assistant.
     *
     * @param string $assistantId The unique identifier of the AI assistant.
     *
     * @return Assistant Returns the current Assistant instance, allowing for method chaining.
     */
    public function setAssistantId(string $assistantId): Assistant
    {
        $this->modelConfig['assistant_id'] = $assistantId;
        return $this;
    }

    /**
     * Creates a new task thread for the AI assistant.
     *
     * This method sends a request to the OpenAI API to create a new task thread for the AI assistant.
     * The thread can be used to interact with the assistant in a conversation-like manner.
     */
    public function createTask(array $parameters = []): Assistant
    {
        $this->threadId = $this->client->createThread($parameters)->id;
        return $this;
    }

    /**
     * Asks a question to the AI assistant and prepares the assistant to respond.
     *
     * This method creates a new MessageData object with the provided message,
     * validates the message data to ensure it contains both 'content' and 'role' fields,
     * and writes the message to the AI assistant's task thread.
     */
    public function askQuestion(string $message): Assistant
    {
        $this->assistantMessageData = new MessageData(
            message: $message,
        );

        if (! isset($this->assistantMessageData->toArray()['content'], $this->assistantMessageData->toArray()['role'])) {
            throw new MissingRequiredParameterException('Either content or role is missing in the message data.');
        }

        $this->client->writeMessage(
            $this->threadId,
            $this->assistantMessageData->toArray()
        );
        return $this;
    }

    /**
     * Processes the current task thread by running the AI assistant's message thread.
     *
     * This method sends a request to the OpenAI API to run the message thread associated with the current assistant and thread ID.
     * The method does not return any specific data, but it updates the AI assistant's internal state based on the provided message thread.
     */
    public function process(): Assistant
    {
        $this->client->runMessageThread(
            threadId: $this->threadId,
            runThreadParameter: $this->modelConfig
        );
        return $this;
    }

    /**
     * Retrieves the list of messages from the current task thread.
     *
     * This method sends a request to the OpenAI API to retrieve the list of messages
     * associated with the current assistant and thread ID. The retrieved messages are
     * returned as a JSON string.
     */
    public function response(): string
    {
        return $this->client->listMessages($this->threadId);
    }

    /**
     * Sets the file path for audio transcription.
     *
     * This method sets the file path of the audio file that will be used for transcription.
     * It's typically used before calling the transcribeTo method.
     *
     * @param string $filePath The full path to the audio file to be transcribed.
     *
     * @return Assistant Returns the current Assistant instance, allowing for method chaining.
     */
    public function setFilePath(string $filePath): Assistant
    {
        $this->modelConfig['file'] = $this->openFile($filePath);
        return $this;
    }

    /**
     * Sets the response format for the AI assistant.
     * This method allows you to specify the desired format for the assistant's responses.
     * The response format determines how the assistant's output will be structured.
     *
     * @return Assistant Returns the current Assistant instance, allowing for method chaining.
     * @see https://platform.openai.com/docs/api-reference/assistants/createAssistant
     * @see https://platform.openai.com/docs/api-reference/audio/createTranscription
     */
    public function setResponseFormat(array|string $responseFormat): Assistant
    {
        $this->modelConfig['response_format'] = $responseFormat;
        return $this;
    }

    /**
     * Sets metadata for the AI assistant.
     *
     * Set of 16 key-value pairs that can be attached to an object.
     * This can be useful for storing additional information about the object in a structured format, and querying for objects via API or the dashboard.
     * Keys are strings with a maximum length of 64 characters. Values are strings with a maximum length of 512 characters.
     *
     * @param array $metadata An associative array of metadata key-value pairs to be set for the assistant.
     *
     * @return Assistant Returns the current Assistant instance, allowing for method chaining.
     */
    public function setMetaData(array $metadata): Assistant
    {
        $this->modelConfig['metadata'] = $metadata;
        return $this;
    }

    /**
     * Sets the reasoning effort for the AI assistant.
     *
     * Constrains effort on reasoning for reasoning models. Currently supported values are low, medium, and high.
     * Reducing reasoning effort can result in faster responses and fewer tokens used on reasoning in a response.
     *
     * @param string $reasoningEffort The level of reasoning effort to be applied.
     *                                Possible values include 'low', 'medium', 'high',
     *
     * @return Assistant Returns the current Assistant instance, allowing for method chaining.
     */
    public function setReasoningEffort(string $reasoningEffort): Assistant
    {
        $this->modelConfig['reasoning_effort'] = $reasoningEffort;
        return $this;
    }

    /**
     * Transcribes an audio file to text in the specified language.
     *
     * This method configures and executes the audio transcription process using the
     * file path set by setFilePath(). It allows for an optional prompt text to guide
     * the transcription.
     *
     * @param string $language The target language for the transcription.
     * @param string|null $optionalText Optional text to guide the transcription process.
     *                                  If provided, it will be used as a prompt.
     *
     * @return string The transcribed text from the audio file.
     */
    public function transcribeTo(string $language, ?string $optionalText = ''): string
    {
        $this->modelConfig['language'] = $language;
        if ($optionalText !== '') {
            $this->modelConfig['prompt'] = $optionalText;
        }
        $transcribeData = ModelConfigDataFactory::buildTranscribeData($this->modelConfig);
        return $this->client->transcribeTo(payload: $transcribeData->toArray());
    }

    /**
     * Developer-provided instructions that the model should follow.
     *
     * @param string $developerMessage The message content.
     * @see https://platform.openai.com/docs/api-reference/chat/create
     */
    public function setDeveloperMessage(string $developerMessage): self
    {
        return $this->addMessageData(
            (new MessageData(
                message: $developerMessage,
                role: $this->modelConfig['model'] === 'gpt-3.5-turbo' ? 'system' : 'developer'
            ))->toArray()
        );
    }

    /**
     * Adds a message sent by an end user.
     *
     * @param string|array $userMessage The user message content.
     * @see https://platform.openai.com/docs/api-reference/chat/create
     */
    public function setUserMessage(string|array $userMessage): self
    {
        return $this->addMessageData(
            (new MessageData(
                message: $userMessage,
                role: 'user'
            ))->toArray()
        );
    }

    /**
     * Adds an assistant message, which may include various optional parameters.
     *
     * @param string|array|null $content The content of the assistant's message.
     * @param string|null $refusal Optional refusal message.
     * @param string|null $name Optional name.
     * @param array|null $audio Optional audio data.
     * @param array|null $toolCalls Optional tool call data.
     * @see https://platform.openai.com/docs/api-reference/chat/create
     */
    public function setChatAssistantMessage(
        string|array|null $content,
        ?string $refusal,
        ?string $name,
        ?array $audio,
        ?array $toolCalls
    ): self {
        return $this->addMessageData(
            ChatAssistantMessageDataFactory::buildChatAssistantMessageData(
                content: $content,
                refusal: $refusal,
                name: $name,
                audio: $audio,
                toolCalls: $toolCalls
            )->toArray()
        );
    }

    /**
     * Adds a message from a tool, identified by its tool call ID.
     *
     * @param string $message The content of the tool's message.
     * @param string $toolCallId The unique identifier for the tool call.
     * @see https://platform.openai.com/docs/api-reference/chat/create
     */
    public function setToolMessage(string $message, string $toolCallId): self
    {
        return $this->addMessageData(
            (new MessageData(
                message: $message,
                role: 'tool',
                toolCallId: $toolCallId
            ))->toArray()
        );
    }

    /**
     * Whether to store the output of this chat completion request for use in OPENAI model distillation or evals products.
     *
     * @param bool $activateStore Whether to activate the store feature.
     *                                 True to enable, false to disable
     *
     * @return Assistant Returns the current Assistant instance, allowing for method chaining.
     * @see https://platform.openai.com/docs/api-reference/chat/create
     */
    public function useOutputForDistillation(bool $activateStore): Assistant
    {
        $this->modelConfig['store'] = $activateStore;
        return $this;
    }

    /**
     * Sets the maximum number of tokens to generate in the completion.
     *
     * This method allows you to specify the maximum number of tokens that the AI model
     * should generate in its response. This can be useful for controlling the length
     * of the assistant's output.
     *
     * An upper bound for the number of tokens that can be generated for a completion, including visible output tokens and reasoning tokens.
     *
     * @param int $maxCompletionTokens The maximum number of tokens to generate in the completion.
     *                                 This value limits the length of the model's response.
     *
     * @return Assistant Returns the current Assistant instance, allowing for method chaining.
     * @see https://platform.openai.com/docs/api-reference/chat/create
     */
    public function setMaxCompletionTokens(int $maxCompletionTokens): Assistant
    {
        $this->modelConfig['max_completion_tokens'] = $maxCompletionTokens;
        return $this;
    }

    /**
     * Sets the number of completion choices for the AI assistant.
     *
     * This method allows you to specify how many alternative completions the model
     * should generate for a given input. This can be useful for obtaining multiple
     * variations of responses.
     *
     * @param int $numberOfCompletionChoices The number of completion choices to generate.
     *
     * @return Assistant Returns the current Assistant instance, allowing for method chaining.
     * @see https://platform.openai.com/docs/api-reference/chat/create
     */
    public function setNumberOfCompletionChoices(int $numberOfCompletionChoices): Assistant
    {
        $this->modelConfig['n'] = $numberOfCompletionChoices;
        return $this;
    }

    /**
     * Sets the output types (modalities) for the AI assistant.
     *
     * This method allows you to specify the types of output the AI assistant should generate.
     * Most models are capable of generating text, which is the default: ['text'].
     * The gpt-4o-audio-preview model can also be used to generate audio. To request that this model generate both text and audio responses, you can use: ['text', 'audio'].
     *
     * @param array $outputTypes An array of output types (modalities) to be set for the assistant.
     * @param string|null $audioVoice The voice to use for audio output.
     * @param string|null $audioFormat The format to use for audio output.
     *
     * @return Assistant Returns the current Assistant instance, allowing for method chaining.
     * @see https://platform.openai.com/docs/api-reference/chat/create
     */
    public function setOutputTypes(array $outputTypes, ?string $audioVoice = null, ?string $audioFormat = null): Assistant
    {
        $this->modelConfig['modalities'] = $outputTypes;

        if (in_array(needle: 'audio', haystack: $outputTypes, strict: true)) {
            if ($audioVoice === null || $audioFormat === null) {
                throw new InvalidArgumentException(
                    message: 'To generate audio output, both audio voice and audio format must be provided.',
                    code: Response::HTTP_BAD_REQUEST
                );
            }
            $this->modelConfig['audio'] = [
                'voice' => $audioVoice,
                'format' => $audioFormat,
            ];
        }
        return $this;
    }

    /**
     * Configures the streaming option for the AI assistant's response.
     *
     * This method enables streaming of the AI's response and optionally sets streaming-specific options.
     * When streaming is enabled, the response will be returned in chunks as it's being generated,
     * rather than waiting for the entire response to be completed.
     *
     * @param bool $activateStream Whether to activate streaming. This parameter is currently not used
     *                             in the function body, but is kept for potential future use or API consistency.
     * @param array|null $streamOptions Optional array of streaming-specific options. If provided,
     *                                  these options will be added to the model configuration.
     *
     * @return Assistant Returns the current Assistant instance, allowing for method chaining.
     * @see https://platform.openai.com/docs/api-reference/chat/create
     */
    public function shouldStream(bool $activateStream, ?array $streamOptions = null): Assistant
    {
        $this->modelConfig['stream'] = $activateStream;
        if ($streamOptions !== null) {
            if (! isset($this->modelConfig['stream_options']['include_usage'])) {
                throw new InvalidArgumentException(
                    message: 'The include_usage option is required when setting stream options.',
                    code: Response::HTTP_BAD_REQUEST
                );
            }
            $this->modelConfig['stream_options'] = $streamOptions;
        }
        return $this;
    }

    /**
     * Sets the top_p value for the AI assistant's response generation.
     *
     * An alternative to sampling with temperature, called nucleus sampling, where the model considers the results of the tokens with top_p probability mass.
     * So 0.1 means only the tokens comprising the top 10% probability mass are considered.
     * it is recommended altering this or temperature but not both.
     *
     * @return Assistant Returns the current Assistant instance, allowing for method chaining.
     * @see https://platform.openai.com/docs/api-reference/chat/create
     */
    public function setTopP(int $topP): Assistant
    {
        $this->modelConfig['top_p'] = $topP;
        return $this;
    }

    /**
     * Sets the stop sequence(s) for the AI assistant's response generation.
     *
     * This method allows you to specify one or more sequences where the AI should stop generating further tokens.
     * The assistant will stop generating immediately before the first appearance of the specified sequence(s).
     *
     * @param string|array $stop A string or an array of strings that the model will stop at.
     *                           Up to 4 sequences where the API will stop generating further tokens.
     *
     * @return Assistant Returns the current Assistant instance, allowing for method chaining.
     * @see https://platform.openai.com/docs/api-reference/chat/create
     */
    public function addAStop(string|array $stop): Assistant
    {
        $this->modelConfig['stop'] = $stop;
        return $this;
    }

    /**
     * Sets the cache key and time-to-live (TTL) for caching chat messages.
     *
     * This method allows you to specify a cache key and time-to-live (TTL) value for caching chat messages.
     * The assistant's responses will be cached using the provided cache key and TTL value.
     *
     * @param string $cacheKey The cache key to use for storing chat messages.
     * @param int $ttl The time-to-live (TTL) value in seconds for the cached chat messages.
     *
     * @return Assistant Returns the current Assistant instance, allowing for method chaining.
     */
    public function shouldCacheChatMessages(string $cacheKey, int $ttl): Assistant
    {
        $this->modelConfig['cacheConfig'] = [
            'cacheKey' => $cacheKey,
            'ttl' => $ttl,
        ];
        return $this;
    }

    /**
     * Sends a chat message to the AI chat completion assistant and retrieves the response.
     *
     * This method prepares the chat completion data, determines whether to use
     * streaming or standard chat completion based on the configuration, and sends
     * the request to the AI service.
     *
     * @return array The response from the AI assistant, which may include the
     *               generated message, token usage information, and other metadata.
     *               The exact structure of the array depends on the AI service's
     *               response format and whether streaming was used.
     */
    public function sendChatMessage(): array
    {
        $chatCompletionData = ModelConfigDataFactory::buildChatCompletionData($this->modelConfig);

        if ($chatCompletionData->shouldStream()) {
            return $this->client->streamedChat($chatCompletionData->toArray());
        }
        return $this->client->chatTextCompletion($chatCompletionData->toArray());
    }

    /**
     * Opens a file and returns the file handle.
     *
     * @param string $filePath The path to the file.
     * @return resource The opened file handle.
     * @throws RuntimeException If the file cannot be opened.
     */
    private function openFile(string $filePath)
    {
        $file = fopen($filePath, 'rb');
        if ($file === false) {
            throw new RuntimeException("Unable to open file: $filePath");
        }
        return $file;
    }

    /**
     * Appends a message data array to the model configuration.
     */
    private function addMessageData(array $messageData): self
    {
        $this->modelConfig['messages'][] = $messageData;
        return $this;
    }
}
