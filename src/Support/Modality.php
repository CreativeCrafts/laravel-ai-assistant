<?php

declare(strict_types=1);

namespace CreativeCrafts\LaravelAiAssistant\Support;

final class Modality
{
    public const TEXT = 'text';
    public const AUDIO = 'audio';

    public static function text(): string
    {
        return self::TEXT;
    }

    public static function audio(): string
    {
        return self::AUDIO;
    }
}
