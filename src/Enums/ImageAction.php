<?php

declare(strict_types=1);

namespace CreativeCrafts\LaravelAiAssistant\Enums;

enum ImageAction: string
{
    case Generate = 'generate';
    case Edit = 'edit';
    case Variation = 'variation';

    public function requiresPrompt(): bool
    {
        return in_array($this, [self::Generate, self::Edit]);
    }

    public function requiresSourceImage(): bool
    {
        return in_array($this, [self::Edit, self::Variation]);
    }

    public function requiresMask(): bool
    {
        return $this === self::Edit;
    }

    public function toEndpoint(): OpenAiEndpoint
    {
        return match ($this) {
            self::Generate => OpenAiEndpoint::ImageGeneration,
            self::Edit => OpenAiEndpoint::ImageEdit,
            self::Variation => OpenAiEndpoint::ImageVariation,
        };
    }
}
