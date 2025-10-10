<?php

declare(strict_types=1);

namespace CreativeCrafts\LaravelAiAssistant\Enums;

enum AudioAction: string
{
    case Transcribe = 'transcribe';
    case Translate = 'translate';
    case Speech = 'speech';

    public function requiresAudioFile(): bool
    {
        return in_array($this, [self::Transcribe, self::Translate]);
    }

    public function requiresTextInput(): bool
    {
        return $this === self::Speech;
    }

    public function toEndpoint(): OpenAiEndpoint
    {
        return match ($this) {
            self::Transcribe => OpenAiEndpoint::AudioTranscription,
            self::Translate => OpenAiEndpoint::AudioTranslation,
            self::Speech => OpenAiEndpoint::AudioSpeech,
        };
    }
}
