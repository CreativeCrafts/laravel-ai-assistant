<?php

declare(strict_types=1);

use CreativeCrafts\LaravelAiAssistant\Adapters\ArrayConfigAdapter;
use CreativeCrafts\LaravelAiAssistant\DataTransferObjects\ModelOptions;
use CreativeCrafts\LaravelAiAssistant\Enums\Modality;
use CreativeCrafts\LaravelAiAssistant\Factories\ModelConfigFactory;

it('converts array to ModelConfig', function () {
    $array = [
        'model' => 'gpt-4',
        'temperature' => 0.8,
        'max_tokens' => 500,
        'top_p' => 0.95,
    ];

    $config = ArrayConfigAdapter::toModelConfig($array, Modality::Text);

    expect($config->model)->toBe('gpt-4');
    expect($config->temperature)->toBe(0.8);
    expect($config->maxTokens)->toBe(500);
    expect($config->topP)->toBe(0.95);
    expect($config->modality)->toBe(Modality::Text);
});

it('converts ModelConfig to array', function () {
    $options = new ModelOptions(
        model: 'gpt-4-turbo',
        temperature: 0.7,
        maxTokens: 1000,
    );

    $config = ModelConfigFactory::for(Modality::Chat, $options);
    $array = ArrayConfigAdapter::toArray($config);

    expect($array)->toBeArray();
    expect($array['model'])->toBe('gpt-4-turbo');
    expect($array['temperature'])->toBe(0.7);
    expect($array['max_tokens'])->toBe(1000);
});

it('merges ModelConfig with array overrides', function () {
    $options = new ModelOptions(
        model: 'gpt-4',
        temperature: 0.7,
        maxTokens: 1000,
    );

    $config = ModelConfigFactory::for(Modality::Text, $options);

    $overrides = [
        'temperature' => 0.9,
        'stream' => true,
    ];

    $merged = ArrayConfigAdapter::merge($config, $overrides);

    expect($merged['model'])->toBe('gpt-4');
    expect($merged['temperature'])->toBe(0.9);
    expect($merged['stream'])->toBeTrue();
    expect($merged['max_tokens'])->toBe(1000);
});

it('creates ModelConfig from modality string', function () {
    $options = [
        'model' => 'whisper-1',
        'temperature' => 0.5,
        'response_format' => 'json',
    ];

    $config = ArrayConfigAdapter::fromModalityString('audio_to_text', $options);

    expect($config->modality)->toBe(Modality::AudioToText);
    expect($config->model)->toBe('whisper-1');
    expect($config->temperature)->toBe(0.5);
    expect($config->responseFormat)->toBe('json');
});

it('handles empty array input', function () {
    $config = ArrayConfigAdapter::toModelConfig([], Modality::Chat);

    expect($config->modality)->toBe(Modality::Chat);
    // Model falls back to config default when not provided
    expect($config->model)->not->toBeNull();
    expect($config->temperature)->toBeNull();
});

it('preserves all fields when converting to array and back', function () {
    $originalArray = [
        'model' => 'gpt-4',
        'temperature' => 0.8,
        'max_tokens' => 500,
        'stream' => true,
        'n' => 2,
        'top_p' => 0.95,
        'presence_penalty' => 0.5,
        'frequency_penalty' => 0.3,
        'stop' => ['END'],
    ];

    $config = ArrayConfigAdapter::toModelConfig($originalArray, Modality::Chat);
    $resultArray = ArrayConfigAdapter::toArray($config);

    expect($resultArray['model'])->toBe($originalArray['model']);
    expect($resultArray['temperature'])->toBe($originalArray['temperature']);
    expect($resultArray['max_tokens'])->toBe($originalArray['max_tokens']);
    expect($resultArray['stream'])->toBe($originalArray['stream']);
    expect($resultArray['n'])->toBe($originalArray['n']);
    expect($resultArray['top_p'])->toBe($originalArray['top_p']);
    expect($resultArray['presence_penalty'])->toBe($originalArray['presence_penalty']);
    expect($resultArray['frequency_penalty'])->toBe($originalArray['frequency_penalty']);
    expect($resultArray['stop'])->toBe($originalArray['stop']);
});
