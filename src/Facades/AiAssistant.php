<?php

declare(strict_types=1);

namespace CreativeCrafts\LaravelAiAssistant\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * AiAssistant Facade
 *
 * @deprecated Since v3.0. Use the Ai facade instead.
 * This facade will be removed in v4.0.
 *
 * Migration Guide:
 * - For chat operations: Use Ai::chat() or Ai::responses()->input()->message()->send()
 * - For audio operations: Use Ai::responses()->input()->audio()->send()
 * - For image operations: Use Ai::responses()->input()->image()->send()
 * - For conversations: Use Ai::conversations()
 *
 * @see Ai
 * @see \CreativeCrafts\LaravelAiAssistant\Facades\Ai::responses()
 * @see \CreativeCrafts\LaravelAiAssistant\Facades\Ai::chat()
 * @see \CreativeCrafts\LaravelAiAssistant\Facades\Ai::conversations()
 *
 * @method static \CreativeCrafts\LaravelAiAssistant\AiAssistant acceptPrompt(string $prompt) @deprecated Use Ai::chat() instead
 * @method static \CreativeCrafts\LaravelAiAssistant\Assistant init(?\CreativeCrafts\LaravelAiAssistant\Services\AssistantService $client = null) @deprecated Use Ai::responses() instead
 * @method static \CreativeCrafts\LaravelAiAssistant\AiAssistant client(\CreativeCrafts\LaravelAiAssistant\Services\AssistantService $client) @deprecated Use Ai facade instead
 * @method static string draft() @deprecated Use Ai::responses() instead
 * @method static string translateTo(string $language) @deprecated Use Ai::responses() instead
 * @method static array andRespond() @deprecated Use Ai::responses() instead
 * @method static array withCustomFunction(\CreativeCrafts\LaravelAiAssistant\DataTransferObjects\CustomFunctionData $customFunctionData) @deprecated Use Ai::responses() instead
 * @method static string spellingAndGrammarCorrection() @deprecated Use Ai::responses() instead
 * @method static string improveWriting() @deprecated Use Ai::responses() instead
 * @method static string transcribeTo(string $language, ?string $optionalText) @deprecated Use Ai::responses() instead
 * @method static array reply(?string $message = null) @deprecated Use Ai::chat() instead
 * @method static \CreativeCrafts\LaravelAiAssistant\DataTransferObjects\ResponseEnvelope sendChatMessageEnvelope() @deprecated Use Ai::responses()->send() instead
 * @method static \CreativeCrafts\LaravelAiAssistant\DataTransferObjects\ChatResponseDto sendChatMessageDto() @deprecated Use Ai::responses()->send() instead
 * @method static \Generator streamChatText(callable $onTextChunk, ?callable $shouldStop = null) @deprecated Use Ai::stream() instead
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
        trigger_deprecation(
            'creativecrafts/laravel-ai-assistant',
            '3.0',
            'The "%s" facade is deprecated. Use the "Ai" facade instead. This facade will be removed in v4.0.',
            static::class
        );

        return \CreativeCrafts\LaravelAiAssistant\AiAssistant::class;
    }
}
