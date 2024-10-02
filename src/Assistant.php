<?php

declare(strict_types=1);

namespace CreativeCrafts\LaravelAiAssistant;

use CreativeCrafts\LaravelAiAssistant\Contract\AssistantContract;
use CreativeCrafts\LaravelAiAssistant\Contract\FunctionCallParameterContract;
use CreativeCrafts\LaravelAiAssistant\DataTransferObjects\AssistantMessageData;
use CreativeCrafts\LaravelAiAssistant\DataTransferObjects\FunctionCallData;
use CreativeCrafts\LaravelAiAssistant\DataTransferObjects\NewAssistantResponseData;
use CreativeCrafts\LaravelAiAssistant\Exceptions\CreateNewAssistantException;
use CreativeCrafts\LaravelAiAssistant\Exceptions\MissingRequiredParameterException;
use CreativeCrafts\LaravelAiAssistant\Tasks\AssistantResource;
use OpenAI\Responses\Assistants\AssistantResponse;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

/**
 * The Assistant class is responsible for managing and interacting with an AI assistant using the OpenAI API.
 * It provides methods for configuring the assistant, creating tasks, and retrieving responses.
 */
final class Assistant implements AssistantContract
{
    protected AssistantResource $client;

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

    protected AssistantMessageData $assistantMessageData;

    /**
     * Returns a new instance of the Assistant class.
     */
    public static function new(): Assistant
    {
        return new self();
    }

    /**
     * Sets the AssistantResource client for making API requests.
     */
    public function client(AssistantResource $client): Assistant
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

    public function assignAssistant(string $assistantId): Assistant
    {
        $this->assistant = $this->client->getAssistantViaId($assistantId);
        return $this;
    }

    public function createTaskThread(array $parameters = []): Assistant
    {
        $this->threadId = $this->client->createThread($parameters)->id;
        return $this;
    }

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

    public function process(): Assistant
    {
        $this->client->runMessageThread(
            $this->threadId,
            $this->assistantMessageData->toArray()
        );
        return $this;
    }

    public function get(): string
    {
        return $this->client->listMessages($this->threadId);
    }
}
