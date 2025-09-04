<?php

declare(strict_types=1);

namespace CreativeCrafts\LaravelAiAssistant\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * Assistant Facade
 *
 * @method static \CreativeCrafts\LaravelAiAssistant\Assistant new()
 * @method static \CreativeCrafts\LaravelAiAssistant\Assistant client(\CreativeCrafts\LaravelAiAssistant\Services\AssistantService $client)
 * @method static \CreativeCrafts\LaravelAiAssistant\Assistant setModelName(string $modelName)
 * @method static \CreativeCrafts\LaravelAiAssistant\Assistant adjustTemperature(int|float $temperature)
 * @method static \CreativeCrafts\LaravelAiAssistant\Assistant setAssistantName(string $assistantName)
 * @method static \CreativeCrafts\LaravelAiAssistant\Assistant setAssistantDescription(string $assistantDescription)
 * @method static \CreativeCrafts\LaravelAiAssistant\Assistant setInstructions(string $instructions)
 * @method static \CreativeCrafts\LaravelAiAssistant\Assistant includeCodeInterpreterTool(array $fileIds)
 * @method static \CreativeCrafts\LaravelAiAssistant\Assistant includeFileSearchTool(array $vectorStoreIds)
 * @method static \CreativeCrafts\LaravelAiAssistant\Assistant includeFunctionCallTool(string $functionName, string $functionDescription, \CreativeCrafts\LaravelAiAssistant\Contracts\FunctionCallParameterContract|array $functionParameters, bool $isStrict, array $requiredParameters, bool $hasAdditionalProperties)
 * @method static \CreativeCrafts\LaravelAiAssistant\DataTransferObjects\NewAssistantResponseData create()
 * @method static \CreativeCrafts\LaravelAiAssistant\Assistant assignAssistant(?string $assistantId)
 * @method static \CreativeCrafts\LaravelAiAssistant\Assistant setAssistantId(string $assistantId)
 * @method static \CreativeCrafts\LaravelAiAssistant\Assistant createTask(array $parameters)
 * @method static \CreativeCrafts\LaravelAiAssistant\Assistant askQuestion(string $message)
 * @method static \CreativeCrafts\LaravelAiAssistant\Assistant process()
 * @method static string response()
 * @method static \CreativeCrafts\LaravelAiAssistant\Assistant setFilePath(string $filePath)
 * @method static \CreativeCrafts\LaravelAiAssistant\Assistant setResponseFormat(array|string $responseFormat)
 * @method static \CreativeCrafts\LaravelAiAssistant\Assistant setMetaData(array $metadata)
 * @method static \CreativeCrafts\LaravelAiAssistant\Assistant setReasoningEffort(string $reasoningEffort)
 * @method static string transcribeTo(string $language, ?string $optionalText)
 * @method static \CreativeCrafts\LaravelAiAssistant\Assistant setDeveloperMessage(string $developerMessage)
 * @method static \CreativeCrafts\LaravelAiAssistant\Assistant setUserMessage(string|array $userMessage)
 * @method static \CreativeCrafts\LaravelAiAssistant\Assistant setChatAssistantMessage(string|array|null $content, ?string $refusal, ?string $name, ?array $audio, ?array $toolCalls)
 * @method static \CreativeCrafts\LaravelAiAssistant\Assistant setToolMessage(string $message, string $toolCallId)
 * @method static \CreativeCrafts\LaravelAiAssistant\Assistant useOutputForDistillation(bool $activateStore)
 * @method static \CreativeCrafts\LaravelAiAssistant\Assistant setMaxCompletionTokens(int $maxCompletionTokens)
 * @method static \CreativeCrafts\LaravelAiAssistant\Assistant setNumberOfCompletionChoices(int $numberOfCompletionChoices)
 * @method static \CreativeCrafts\LaravelAiAssistant\Assistant setOutputTypes(array $outputTypes, ?string $audioVoice, ?string $audioFormat)
 * @method static \CreativeCrafts\LaravelAiAssistant\Assistant shouldStream(bool $activateStream, ?array $streamOptions)
 * @method static \CreativeCrafts\LaravelAiAssistant\Assistant setTopP(int $topP)
 * @method static \CreativeCrafts\LaravelAiAssistant\Assistant addAStop(string|array $stop)
 * @method static \CreativeCrafts\LaravelAiAssistant\Assistant shouldCacheChatMessages(string $cacheKey, int $ttl)
 * @method static array sendChatMessage()
 * @method static \CreativeCrafts\LaravelAiAssistant\DataTransferObjects\ChatResponseDto sendChatMessageDto()
 * @method static mixed openFile(string $filePath)
 * @method static \CreativeCrafts\LaravelAiAssistant\Assistant addMessageData(array $messageData)
 *
 * @see \CreativeCrafts\LaravelAiAssistant\Assistant
 */
class Assistant extends Facade
{
    /**
     * Get the registered name of the component.
     *
     * @return string
     */
    protected static function getFacadeAccessor(): string
    {
        return \CreativeCrafts\LaravelAiAssistant\Assistant::class;
    }
}
