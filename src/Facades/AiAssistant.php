<?php

declare(strict_types=1);

namespace CreativeCrafts\LaravelAiAssistant\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * AiAssistant Facade
 *
 * @method static \CreativeCrafts\LaravelAiAssistant\AiAssistant acceptPrompt(string $prompt)
 * @method static \CreativeCrafts\LaravelAiAssistant\Assistant init(?\CreativeCrafts\LaravelAiAssistant\Services\AssistantService $client = null)
 * @method static \CreativeCrafts\LaravelAiAssistant\AiAssistant client(\CreativeCrafts\LaravelAiAssistant\Services\AssistantService $client)
 * @method static string draft()
 * @method static string translateTo(string $language)
 * @method static array andRespond()
 * @method static array withCustomFunction(\CreativeCrafts\LaravelAiAssistant\DataTransferObjects\CustomFunctionData $customFunctionData)
 * @method static string spellingAndGrammarCorrection()
 * @method static string improveWriting()
 * @method static string transcribeTo(string $language, ?string $optionalText)
 * @method static array reply(?string $message = null)
 * @method static \CreativeCrafts\LaravelAiAssistant\DataTransferObjects\ResponseEnvelope sendChatMessageEnvelope()
 * @method static \CreativeCrafts\LaravelAiAssistant\DataTransferObjects\ChatResponseDto sendChatMessageDto()
 * @method static \Generator streamChatText(callable $onTextChunk, ?callable $shouldStop = null)
 *
 * @see \CreativeCrafts\LaravelAiAssistant\AiAssistant
 */
class AiAssistant extends Facade
{
    /**
     * Get the registered name of the component.
     *
     * @return string
     */
    protected static function getFacadeAccessor(): string
    {
        return \CreativeCrafts\LaravelAiAssistant\AiAssistant::class;
    }
}
