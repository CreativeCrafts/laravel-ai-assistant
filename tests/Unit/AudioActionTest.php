<?php

declare(strict_types=1);

use CreativeCrafts\LaravelAiAssistant\Enums\AudioAction;
use CreativeCrafts\LaravelAiAssistant\Enums\OpenAiEndpoint;

it('has all expected audio action cases', function () {
    $cases = AudioAction::cases();

    expect($cases)->toHaveCount(3)
        ->and(AudioAction::Transcribe->value)->toBe('transcribe')
        ->and(AudioAction::Translate->value)->toBe('translate')
        ->and(AudioAction::Speech->value)->toBe('speech');
});

it('identifies actions requiring audio file correctly', function () {
    expect(AudioAction::Transcribe->requiresAudioFile())->toBeTrue()
        ->and(AudioAction::Translate->requiresAudioFile())->toBeTrue()
        ->and(AudioAction::Speech->requiresAudioFile())->toBeFalse();
});

it('identifies actions requiring text input correctly', function () {
    expect(AudioAction::Speech->requiresTextInput())->toBeTrue()
        ->and(AudioAction::Transcribe->requiresTextInput())->toBeFalse()
        ->and(AudioAction::Translate->requiresTextInput())->toBeFalse();
});

it('maps to correct endpoint', function () {
    expect(AudioAction::Transcribe->toEndpoint())->toBe(OpenAiEndpoint::AudioTranscription)
        ->and(AudioAction::Translate->toEndpoint())->toBe(OpenAiEndpoint::AudioTranslation)
        ->and(AudioAction::Speech->toEndpoint())->toBe(OpenAiEndpoint::AudioSpeech);
});

it('can be created from string value', function () {
    expect(AudioAction::from('transcribe'))->toBe(AudioAction::Transcribe)
        ->and(AudioAction::from('translate'))->toBe(AudioAction::Translate)
        ->and(AudioAction::from('speech'))->toBe(AudioAction::Speech);
});

it('can be used in match expressions', function () {
    $action = AudioAction::Transcribe;

    $result = match ($action) {
        AudioAction::Transcribe => 'convert audio to text',
        AudioAction::Translate => 'translate audio to english',
        AudioAction::Speech => 'generate speech from text',
    };

    expect($result)->toBe('convert audio to text');
});
