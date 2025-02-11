<?php

declare(strict_types=1);

namespace CreativeCrafts\LaravelAiAssistant;

use CreativeCrafts\LaravelAiAssistant\Contracts\AssistantContract;
use CreativeCrafts\LaravelAiAssistant\Contracts\FunctionCallParameterContract;
use CreativeCrafts\LaravelAiAssistant\DataTransferObjects\AssistantMessageData;
use CreativeCrafts\LaravelAiAssistant\DataTransferObjects\FunctionCallData;
use CreativeCrafts\LaravelAiAssistant\DataTransferObjects\NewAssistantResponseData;
use CreativeCrafts\LaravelAiAssistant\Exceptions\CreateNewAssistantException;
use CreativeCrafts\LaravelAiAssistant\Exceptions\MissingRequiredParameterException;
use CreativeCrafts\LaravelAiAssistant\Services\AssistantService;
use OpenAI\Responses\Assistants\AssistantResponse;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

/**
 * The Assistant class is responsible for managing and interacting with an AI assistant using the OpenAI API.
 * It provides methods for configuring the assistant, creating tasks, and retrieving responses.
 */
final class Assistant implements AssistantContract
{
    protected AssistantService $client;

    protected string $modelName = 'gpt-4o';

    // @pest-mutate-ignore
    protected int|float $temperature = 0.5;

    // @pest-mutate-ignore
    protected string $assistantName = '';

    // @pest-mutate-ignore
    protected string $assistantDescription = '';

    // @pest-mutate-ignore
    protected string $instructions = '';

    protected array $tools = [];

    protected array|null $toolResources = null;

    protected AssistantResponse $assistant;

    protected string $threadId;

    protected string $assistantId;

    protected AssistantMessageData $assistantMessageData;

    /**
     * Returns a new instance of the Assistant class.
     */
    public static function new(): Assistant
    {
        return new self();
    }

    /**
     * Sets the AssistantService client for making API requests.
     */
    public function client(AssistantService $client): Assistant
    {
        $this->client = $client;
        return $this;
    }

    /**
     * Sets the model name for the AI assistant.
     */
    public function setModelName(string $modelName): Assistant
    {
        $this->modelName = $modelName;
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
        $this->temperature = $temperature;
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
        $this->assistantName = $assistantName;

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
        $this->assistantDescription = $assistantDescription;

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
        $this->instructions = $instructions;

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
        $this->tools[] = [
            'type' => 'code_interpreter',
        ];

        if ($fileIds !== []) {
            $this->toolResources = [
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
        $this->tools[] = [
            'type' => 'file_search',
        ];

        if ($vectorStoreIds !== []) {
            $this->toolResources = [
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
        $this->tools[] = [
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
            $assistantData = [
                'model' => $this->modelName,
                'name' => $this->assistantName,
                'description' => $this->assistantDescription,
                'instructions' => $this->instructions,
                'tools' => $this->tools,
                'temperature' => $this->temperature,
                'tool_resources' => $this->toolResources,
            ];

            $assistantResponse = $this->client->createAssistant($assistantData);
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
            $assistantId = $this->assistantId;
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
        $this->assistantId = $assistantId;
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
     * This method creates a new AssistantMessageData object with the provided message,
     * validates the message data to ensure it contains both 'content' and 'role' fields,
     * and writes the message to the AI assistant's task thread.
     */
    public function askQuestion(string $message): Assistant
    {
        $this->assistantMessageData = new AssistantMessageData(
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
        $runThreadParameter = [
            'assistant_id' => $this->assistantId,
        ];
        $this->client->runMessageThread(
            threadId: $this->threadId,
            runThreadParameter: $runThreadParameter
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
}
