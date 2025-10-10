<?php

declare(strict_types=1);

use CreativeCrafts\LaravelAiAssistant\Enums\OpenAiEndpoint;

it('has all expected endpoint cases', function () {
    $cases = OpenAiEndpoint::cases();

    expect($cases)->toHaveCount(8)
        ->and(OpenAiEndpoint::ResponseApi->value)->toBe('response_api')
        ->and(OpenAiEndpoint::ChatCompletion->value)->toBe('chat_completion')
        ->and(OpenAiEndpoint::AudioTranscription->value)->toBe('audio_transcription')
        ->and(OpenAiEndpoint::AudioTranslation->value)->toBe('audio_translation')
        ->and(OpenAiEndpoint::AudioSpeech->value)->toBe('audio_speech')
        ->and(OpenAiEndpoint::ImageGeneration->value)->toBe('image_generation')
        ->and(OpenAiEndpoint::ImageEdit->value)->toBe('image_edit')
        ->and(OpenAiEndpoint::ImageVariation->value)->toBe('image_variation');
});

it('returns correct URLs for each endpoint', function () {
    expect(OpenAiEndpoint::ResponseApi->url())->toBe('/v1/responses')
        ->and(OpenAiEndpoint::ChatCompletion->url())->toBe('/v1/chat/completions')
        ->and(OpenAiEndpoint::AudioTranscription->url())->toBe('/v1/audio/transcriptions')
        ->and(OpenAiEndpoint::AudioTranslation->url())->toBe('/v1/audio/translations')
        ->and(OpenAiEndpoint::AudioSpeech->url())->toBe('/v1/audio/speech')
        ->and(OpenAiEndpoint::ImageGeneration->url())->toBe('/v1/images/generations')
        ->and(OpenAiEndpoint::ImageEdit->url())->toBe('/v1/images/edits')
        ->and(OpenAiEndpoint::ImageVariation->url())->toBe('/v1/images/variations');
});

it('identifies audio endpoints correctly', function () {
    expect(OpenAiEndpoint::AudioTranscription->isAudio())->toBeTrue()
        ->and(OpenAiEndpoint::AudioTranslation->isAudio())->toBeTrue()
        ->and(OpenAiEndpoint::AudioSpeech->isAudio())->toBeTrue()
        ->and(OpenAiEndpoint::ResponseApi->isAudio())->toBeFalse()
        ->and(OpenAiEndpoint::ChatCompletion->isAudio())->toBeFalse()
        ->and(OpenAiEndpoint::ImageGeneration->isAudio())->toBeFalse()
        ->and(OpenAiEndpoint::ImageEdit->isAudio())->toBeFalse()
        ->and(OpenAiEndpoint::ImageVariation->isAudio())->toBeFalse();
});

it('identifies image endpoints correctly', function () {
    expect(OpenAiEndpoint::ImageGeneration->isImage())->toBeTrue()
        ->and(OpenAiEndpoint::ImageEdit->isImage())->toBeTrue()
        ->and(OpenAiEndpoint::ImageVariation->isImage())->toBeTrue()
        ->and(OpenAiEndpoint::ResponseApi->isImage())->toBeFalse()
        ->and(OpenAiEndpoint::ChatCompletion->isImage())->toBeFalse()
        ->and(OpenAiEndpoint::AudioTranscription->isImage())->toBeFalse()
        ->and(OpenAiEndpoint::AudioTranslation->isImage())->toBeFalse()
        ->and(OpenAiEndpoint::AudioSpeech->isImage())->toBeFalse();
});

it('identifies endpoints requiring multipart correctly', function () {
    expect(OpenAiEndpoint::AudioTranscription->requiresMultipart())->toBeTrue()
        ->and(OpenAiEndpoint::AudioTranslation->requiresMultipart())->toBeTrue()
        ->and(OpenAiEndpoint::ImageEdit->requiresMultipart())->toBeTrue()
        ->and(OpenAiEndpoint::ImageVariation->requiresMultipart())->toBeTrue()
        ->and(OpenAiEndpoint::ResponseApi->requiresMultipart())->toBeFalse()
        ->and(OpenAiEndpoint::ChatCompletion->requiresMultipart())->toBeFalse()
        ->and(OpenAiEndpoint::AudioSpeech->requiresMultipart())->toBeFalse()
        ->and(OpenAiEndpoint::ImageGeneration->requiresMultipart())->toBeFalse();
});

it('can be created from string value', function () {
    expect(OpenAiEndpoint::from('response_api'))->toBe(OpenAiEndpoint::ResponseApi)
        ->and(OpenAiEndpoint::from('chat_completion'))->toBe(OpenAiEndpoint::ChatCompletion)
        ->and(OpenAiEndpoint::from('audio_transcription'))->toBe(OpenAiEndpoint::AudioTranscription)
        ->and(OpenAiEndpoint::from('audio_translation'))->toBe(OpenAiEndpoint::AudioTranslation)
        ->and(OpenAiEndpoint::from('audio_speech'))->toBe(OpenAiEndpoint::AudioSpeech)
        ->and(OpenAiEndpoint::from('image_generation'))->toBe(OpenAiEndpoint::ImageGeneration)
        ->and(OpenAiEndpoint::from('image_edit'))->toBe(OpenAiEndpoint::ImageEdit)
        ->and(OpenAiEndpoint::from('image_variation'))->toBe(OpenAiEndpoint::ImageVariation);
});

it('can be used in match expressions', function () {
    $endpoint = OpenAiEndpoint::AudioTranscription;

    $result = match ($endpoint) {
        OpenAiEndpoint::AudioTranscription => 'transcription',
        OpenAiEndpoint::AudioTranslation => 'translation',
        OpenAiEndpoint::AudioSpeech => 'speech',
        default => 'other',
    };

    expect($result)->toBe('transcription');
});
