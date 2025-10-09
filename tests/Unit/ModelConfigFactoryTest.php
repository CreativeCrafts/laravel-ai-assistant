<?php

declare(strict_types=1);

use CreativeCrafts\LaravelAiAssistant\DataTransferObjects\ModelOptions;
use CreativeCrafts\LaravelAiAssistant\Enums\Modality;
use CreativeCrafts\LaravelAiAssistant\Factories\ModelConfigFactory;

beforeEach(function () {
    config([
        'ai-assistant.model' => 'gpt-4',
        'ai-assistant.chat_model' => 'gpt-4-turbo',
        'ai-assistant.edit_model' => 'gpt-4-edit',
        'ai-assistant.audio_model' => 'whisper-1',
        'ai-assistant.image_model' => 'dall-e-3',
        'ai-assistant.max_completion_tokens' => 1000,
        'ai-assistant.temperature' => 0.7,
        'ai-assistant.top_p' => 0.9,
        'ai-assistant.stream' => true,
        'ai-assistant.echo' => false,
        'ai-assistant.n' => 1,
        'ai-assistant.suffix' => null,
        'ai-assistant.presence_penalty' => 0.0,
        'ai-assistant.frequency_penalty' => 0.0,
        'ai-assistant.best_of' => 1,
        'ai-assistant.stop' => ['STOP', 'END'],
        'ai-assistant.response_format' => 'json',
    ]);
});

it('produces correct config for Text modality', function () {
    $options = ModelOptions::fromConfig();
    $config = ModelConfigFactory::for(Modality::Text, $options);

    expect($config->modality)->toBe(Modality::Text);
    expect($config->model)->toBe('gpt-4');
    expect($config->maxTokens)->toBe(1000);
    expect($config->temperature)->toBe(0.7);
    expect($config->stream)->toBeTrue();
    expect($config->echo)->toBeFalse();
    expect($config->n)->toBe(1);
    expect($config->topP)->toBe(0.9);
    expect($config->presencePenalty)->toBe(0.0);
    expect($config->frequencyPenalty)->toBe(0.0);
    expect($config->bestOf)->toBe(1);
    expect($config->stop)->toBe(['STOP', 'END']);

    $array = $config->toArray();
    expect($array)->toMatchSnapshot();
});

it('produces correct config for Chat modality', function () {
    $options = ModelOptions::fromConfig();
    $config = ModelConfigFactory::for(Modality::Chat, $options);

    expect($config->modality)->toBe(Modality::Chat);
    expect($config->model)->toBe('gpt-4-turbo');
    expect($config->maxTokens)->toBe(1000);
    expect($config->temperature)->toBe(0.7);
    expect($config->stream)->toBeTrue();
    expect($config->n)->toBe(1);
    expect($config->topP)->toBe(0.9);
    expect($config->presencePenalty)->toBe(0.0);
    expect($config->frequencyPenalty)->toBe(0.0);
    expect($config->stop)->toBe(['STOP', 'END']);

    $array = $config->toArray();
    expect($array)->toMatchSnapshot();
});

it('produces correct config for Edit modality', function () {
    $options = ModelOptions::fromConfig();
    $config = ModelConfigFactory::for(Modality::Edit, $options);

    expect($config->modality)->toBe(Modality::Edit);
    expect($config->model)->toBe('gpt-4-edit');
    expect($config->temperature)->toBe(0.7);
    expect($config->topP)->toBe(0.9);

    $array = $config->toArray();
    expect($array)->toMatchSnapshot();
});

it('produces correct config for AudioToText modality', function () {
    $options = ModelOptions::fromConfig();
    $config = ModelConfigFactory::for(Modality::AudioToText, $options);

    expect($config->modality)->toBe(Modality::AudioToText);
    expect($config->model)->toBe('whisper-1');
    expect($config->temperature)->toBe(0.7);
    expect($config->responseFormat)->toBe('json');

    $array = $config->toArray();
    expect($array)->toMatchSnapshot();
});

it('produces correct config for Image modality', function () {
    $options = ModelOptions::fromConfig();
    $config = ModelConfigFactory::for(Modality::Image, $options);

    expect($config->modality)->toBe(Modality::Image);
    expect($config->model)->toBe('dall-e-3');
    expect($config->n)->toBe(1);

    $array = $config->toArray();
    expect($array)->toMatchSnapshot();
});

it('normalizes stop parameter correctly', function () {
    expect(ModelConfigFactory::normalizeStop(null))->toBeNull();
    expect(ModelConfigFactory::normalizeStop(''))->toBeNull();
    expect(ModelConfigFactory::normalizeStop('  '))->toBeNull();
    expect(ModelConfigFactory::normalizeStop('STOP'))->toBe('STOP');
    expect(ModelConfigFactory::normalizeStop('  STOP  '))->toBe('STOP');
    expect(ModelConfigFactory::normalizeStop([]))->toBeNull();
    expect(ModelConfigFactory::normalizeStop(['']))->toBeNull();
    expect(ModelConfigFactory::normalizeStop(['  ']))->toBeNull();
    expect(ModelConfigFactory::normalizeStop(['STOP']))->toBe(['STOP']);
    expect(ModelConfigFactory::normalizeStop(['  STOP  ', 'END']))->toBe(['STOP', 'END']);
    expect(ModelConfigFactory::normalizeStop(['', '  ', 'STOP']))->toBe(['STOP']);
});

it('allows custom options to override config values', function () {
    $options = new ModelOptions(
        model: 'custom-model',
        temperature: 0.5,
        maxTokens: 500,
    );

    $config = ModelConfigFactory::for(Modality::Text, $options);

    expect($config->model)->toBe('custom-model');
    expect($config->temperature)->toBe(0.5);
    expect($config->maxTokens)->toBe(500);
});

it('includes null values in toArray for backward compatibility', function () {
    $options = new ModelOptions(
        model: 'gpt-4',
        temperature: 0.7,
    );

    $config = ModelConfigFactory::for(Modality::Text, $options);
    $array = $config->toArray();

    expect($array)->toHaveKey('stream');
    expect($array)->toHaveKey('echo');
    expect($array)->toHaveKey('suffix');
    expect($array['stream'])->toBeNull();
    expect($array['echo'])->toBeNull();
    expect($array['suffix'])->toBeNull();
});
