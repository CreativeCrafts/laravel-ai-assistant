<?php

declare(strict_types=1);

namespace CreativeCrafts\LaravelAiAssistant\Support;

use CreativeCrafts\LaravelAiAssistant\AiAssistant;
use CreativeCrafts\LaravelAiAssistant\DataTransferObjects\CustomFunctionData;

/**
 * Backwards-compat shim object exposing legacy Chat/Text/Edits helpers.
 * Internally delegates to AiAssistant's LegacyCompletionsShim methods.
 */
final readonly class LegacyCompletions
{
    public function __construct(private AiAssistant $core)
    {
    }

    public static function make(?string $prompt = ''): self
    {
        return new self(new AiAssistant($prompt ?? ''));
    }

    public function draft(): string
    {
        return $this->core->draft();
    }

    public function translateTo(string $language): string
    {
        return $this->core->translateTo($language);
    }

    public function andRespond(): array
    {
        return $this->core->andRespond();
    }

    public function withCustomFunction(CustomFunctionData $customFunctionData): array
    {
        return $this->core->withCustomFunction($customFunctionData);
    }

    public function spellingAndGrammarCorrection(): string
    {
        return $this->core->spellingAndGrammarCorrection();
    }

    public function improveWriting(): string
    {
        return $this->core->improveWriting();
    }

    public function transcribeTo(string $language, ?string $optionalText = ''): string
    {
        return $this->core->transcribeTo($language, $optionalText);
    }

    public function translateAudioTo(): string
    {
        return $this->core->translateAudioTo();
    }
}
