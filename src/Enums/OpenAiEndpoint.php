<?php

declare(strict_types=1);

namespace CreativeCrafts\LaravelAiAssistant\Enums;

enum OpenAiEndpoint: string
{
    case ResponseApi = 'response_api';
    case ChatCompletion = 'chat_completion';
    case AudioTranscription = 'audio_transcription';
    case AudioTranslation = 'audio_translation';
    case AudioSpeech = 'audio_speech';
    case ImageGeneration = 'image_generation';
    case ImageEdit = 'image_edit';
    case ImageVariation = 'image_variation';

    public function url(): string
    {
        return match ($this) {
            self::ResponseApi => '/v1/responses',
            self::ChatCompletion => '/v1/chat/completions',
            self::AudioTranscription => '/v1/audio/transcriptions',
            self::AudioTranslation => '/v1/audio/translations',
            self::AudioSpeech => '/v1/audio/speech',
            self::ImageGeneration => '/v1/images/generations',
            self::ImageEdit => '/v1/images/edits',
            self::ImageVariation => '/v1/images/variations',
        };
    }

    public function isAudio(): bool
    {
        return in_array($this, [
            self::AudioTranscription,
            self::AudioTranslation,
            self::AudioSpeech,
        ]);
    }

    public function isImage(): bool
    {
        return in_array($this, [
            self::ImageGeneration,
            self::ImageEdit,
            self::ImageVariation,
        ]);
    }

    public function requiresMultipart(): bool
    {
        return in_array($this, [
            self::AudioTranscription,
            self::AudioTranslation,
            self::ImageEdit,
            self::ImageVariation,
        ]);
    }
}
