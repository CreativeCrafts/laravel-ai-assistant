<?php

declare(strict_types=1);

use CreativeCrafts\LaravelAiAssistant\Contracts\ChatCompletionDataContract;
use CreativeCrafts\LaravelAiAssistant\Contracts\CreateAssistantDataContract;
use CreativeCrafts\LaravelAiAssistant\Contracts\TranscribeToDataContract;
use CreativeCrafts\LaravelAiAssistant\DataFactories\ModelConfigDataFactory;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Config;

covers(ModelConfigDataFactory::class);

describe('ModelConfigDataFactory::buildTranscribeData', function () {
    it('builds a TranscribeToData object using provided configuration values', function () {
        Config::set('ai-assistant.audio_model', 'default_audio_model');
        Config::set('ai-assistant.temperature', 0.5);

        $config = [
            'model' => 'custom_model',
            'temperature' => 0.7,
            'response_format' => 'json',
            'file' => '/path/to/audio.mp3',
            'language' => 'en',
            'prompt' => 'Test prompt'
        ];

        $data = ModelConfigDataFactory::buildTranscribeData($config);

        expect($data)->toBeInstanceOf(TranscribeToDataContract::class)
            ->and($data->toArray()['model'])->toBe('custom_model')
            ->and($data->toArray()['temperature'])->toBe(0.7)
            ->and($data->toArray()['response_format'])->toBe('json')
            ->and($data->toArray()['file'])->toBe('/path/to/audio.mp3')
            ->and($data->toArray()['language'])->toBe('en')
            ->and($data->toArray()['prompt'])->toBe('Test prompt');
    });

    it('applies default values when configuration keys are missing', function () {
        Config::set('ai-assistant.audio_model', 'default_audio_model');
        Config::set('ai-assistant.temperature', 0.5);

        $config = [
            'file' => '/path/to/audio.mp3',
        ];

        $data = ModelConfigDataFactory::buildTranscribeData($config);

        expect($data)->toBeInstanceOf(TranscribeToDataContract::class)
            ->and($data->toArray()['model'])->toBe('default_audio_model')
            ->and($data->toArray()['temperature'])->toBe(0.5)
            ->and($data->toArray()['response_format'])->toBe('json')
            ->and($data->toArray()['file'])->toBe('/path/to/audio.mp3')
            ->and($data->toArray()['language'])->toBe('en')
            ->and($data->toArray()['prompt'])->toBe('');
    });
});

describe('ModelConfigDataFactory::buildCreateAssistantData', function () {
    it('builds a CreateAssistantData object with provided configuration values', function () {
        $config = [
            'model' => 'custom_model',
            'top_p' => 0.9,
            'temperature' => 0.7,
            'description' => 'Test assistant description',
            'name' => 'TestAssistant',
            'instructions' => 'Follow these instructions',
            'reasoning_effort' => 'high',
            'tools' => ['tool1', 'tool2'],
            'tool_resources' => ['resource1'],
            'metadata' => ['key' => 'value'],
            'response_format' => 'auto',
        ];

        $assistantData = ModelConfigDataFactory::buildCreateAssistantData($config);

        expect($assistantData)->toBeInstanceOf(CreateAssistantDataContract::class)
            ->and($assistantData->toArray()['model'])->toBe('custom_model')
            ->and($assistantData->toArray()['top_p'])->toBe(0.9)
            ->and($assistantData->toArray()['temperature'])->toBe(0.7)
            ->and($assistantData->toArray()['description'])->toBe('Test assistant description')
            ->and($assistantData->toArray()['name'])->toBe('TestAssistant')
            ->and($assistantData->toArray()['instructions'])->toBe('Follow these instructions')
            ->and($assistantData->toArray()['reasoning_effort'])->toBe('high')
            ->and($assistantData->toArray()['tools'])->toEqual(['tool1', 'tool2'])
            ->and($assistantData->toArray()['tool_resources'])->toEqual(['resource1'])
            ->and($assistantData->toArray()['metadata'])->toEqual(['key' => 'value'])
            ->and($assistantData->toArray()['response_format'])->toBe('auto');
    });

    it('applies default configuration values when optional keys are missing', function () {
        Config::set('ai-assistant.model', 'default_model');
        Config::set('ai-assistant.top_p', 1);
        Config::set('ai-assistant.temperature', 0.5);

        $config = [
            'description' => 'Default description',
            'name' => 'DefaultAssistant',
            'instructions' => 'Do something',
            'reasoning_effort' => 'medium',
        ];

        $assistantData = ModelConfigDataFactory::buildCreateAssistantData($config);

        expect($assistantData)->toBeInstanceOf(CreateAssistantDataContract::class)
            ->and($assistantData->toArray()['model'])->toBe('default_model')
            ->and($assistantData->toArray()['top_p'])->toBe(1.0)
            ->and($assistantData->toArray()['temperature'])->toBe(0.5)
            ->and($assistantData->toArray()['description'])->toBe('Default description')
            ->and($assistantData->toArray()['name'])->toBe('DefaultAssistant')
            ->and($assistantData->toArray()['instructions'])->toBe('Do something')
            ->and($assistantData->toArray()['reasoning_effort'])->toBe('medium')
            ->and($assistantData->toArray()['response_format'])->toBe('auto');
    });
});

describe('ModelConfigDataFactory::buildChatCompletionData (Simplified)', function () {
    beforeEach(function () {
        Cache::flush();
    });

    it('builds ChatCompletionData when cacheConfig is not provided and forgets existing cache', function () {
        $cacheKey = '';

        Cache::shouldReceive('has')
            ->once()
            ->with($cacheKey)
            ->andReturn(true);
        Cache::shouldReceive('forget')
            ->once()
            ->with($cacheKey);

        $config = [
            'messages' => ['message without cache'],
            'model' => 'default-model',
            'temperature' => 0.7,
            'store' => false,
            'reasoning_effort' => 'low',
            'metadata' => [],
            'max_completion_tokens' => 150,
            'number_of_completion_choices' => 1,
            'output_types' => ['text'],
            'audio' => [],
            'response_format' => 'auto',
            'stop_sequences' => [],
            'stream' => false,
            'top_p' => 1.0,
        ];

        $chatData = ModelConfigDataFactory::buildChatCompletionData($config);

        expect($chatData)->toBeInstanceOf(ChatCompletionDataContract::class)
            ->and($chatData->toArray()['model'])->toBe('default-model')
            ->and($chatData->toArray()['messages'])->toEqual(['message without cache'])
            ->and($chatData->toArray()['temperature'])->toBe(0.7)
            ->and($chatData->toArray()['store'])->toBeFalse()
            ->and($chatData->toArray()['reasoning_effort'])->toBe('low')
            ->and($chatData->toArray()['max_completion_tokens'])->toBe(150)
            ->and($chatData->toArray()['n'])->toBe(1)
            ->and($chatData->toArray()['stream'])->toBeFalse();
    });

    it('builds ChatCompletionData with cacheConfig, merging cached and new messages', function () {
        $cacheKey = 'chat_cache_key';
        $cacheTtl = 120;
        $cachedMessage = 'cached message';
        $newMessages = ['new message'];

        Cache::shouldReceive('get')
            ->once()
            ->with($cacheKey)
            ->andReturn($cachedMessage);
        Cache::shouldReceive('put')
            ->once()
            ->with(
                $cacheKey,
                Mockery::on(function ($messages) use ($cachedMessage, $newMessages) {
                    return $messages === array_merge([$cachedMessage], $newMessages);
                }),
                $cacheTtl
            );

        $config = [
            'cacheConfig' => [
                'key' => $cacheKey,
                'ttl' => $cacheTtl,
            ],
            'messages' => $newMessages,
            'model' => 'test-model',
            'temperature' => 0.8,
            'store' => true,
            'reasoning_effort' => 'high',
            'metadata' => ['meta' => 'data'],
            'max_completion_tokens' => 200,
            'n' => 3,
            'output_types' => ['text'],
            'audio' => ['voice' => 'en-US'],
            'response_format' => ['type' => 'json_object'],
            'stop' => ['STOP'],
            'stream' => true,
            'top_p' => 0.9,
        ];

        $chatData = ModelConfigDataFactory::buildChatCompletionData($config);

        expect($chatData)->toBeInstanceOf(ChatCompletionDataContract::class)
            ->and($chatData->toArray()['model'])->toBe('test-model')
            ->and($chatData->toArray()['messages'])->toEqual(array_merge([$cachedMessage], $newMessages))
            ->and($chatData->toArray()['temperature'])->toBe(0.8)
            ->and($chatData->toArray()['store'])->toBeTrue()
            ->and($chatData->toArray()['reasoning_effort'])->toBe('high')
            ->and($chatData->toArray()['metadata'])->toEqual(['meta' => 'data'])
            ->and($chatData->toArray()['max_completion_tokens'])->toBe(200)
            ->and($chatData->toArray()['n'])->toBe(3)
            ->and($chatData->toArray()['audio'])->toEqual(['voice' => 'en-US'])
            ->and($chatData->toArray()['stop'])->toEqual(['STOP'])
            ->and($chatData->toArray()['stream'])->toBeTrue();
    });
});
