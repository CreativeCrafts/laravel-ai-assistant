<?php

declare(strict_types=1);

namespace CreativeCrafts\LaravelAiAssistant\Support;

use CreativeCrafts\LaravelAiAssistant\DataTransferObjects\CustomFunctionData;
use InvalidArgumentException;

/**
 * Backwards-compat shim object exposing legacy Chat/Text/Edits helpers.
 * All methods are deprecated and will be removed in a future version.
 */
final readonly class LegacyCompletions
{
    public function __construct()
    {
    }

    public static function make(?string $prompt = ''): self
    {
        return new self();
    }

    public function draft(): string
    {
        throw new InvalidArgumentException('LegacyCompletions::draft is deprecated. Use AiAssistant modern chat/text APIs.');
    }

    public function translateTo(string $language): string
    {
        throw new InvalidArgumentException('LegacyCompletions::translateTo is deprecated. Use AiAssistant modern chat/text APIs.');
    }

    public function andRespond(): array
    {
        throw new InvalidArgumentException('LegacyCompletions::andRespond is deprecated. Use AiAssistant::reply() or sendChatMessageDto().');
    }

    public function withCustomFunction(CustomFunctionData $customFunctionData): array
    {
        throw new InvalidArgumentException('LegacyCompletions::withCustomFunction is deprecated. Register tools and use AiAssistant::reply().');
    }

    public function spellingAndGrammarCorrection(): string
    {
        throw new InvalidArgumentException('LegacyCompletions::spellingAndGrammarCorrection is deprecated. Use Responses API with instructions.');
    }

    public function improveWriting(): string
    {
        throw new InvalidArgumentException('LegacyCompletions::improveWriting is deprecated. Use Responses API with instructions.');
    }

    public function transcribeTo(string $language, ?string $optionalText = ''): string
    {
        throw new InvalidArgumentException('LegacyCompletions::transcribeTo is deprecated. Use AssistantService::transcribeTo().');
    }

    public function translateAudioTo(): string
    {
        throw new InvalidArgumentException('LegacyCompletions::translateAudioTo is deprecated. Use AssistantService::translateTo().');
    }
}
