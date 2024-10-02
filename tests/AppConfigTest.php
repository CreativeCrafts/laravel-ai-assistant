<?php

declare(strict_types=1);

use CreativeCrafts\LaravelAiAssistant\AppConfig;
use CreativeCrafts\LaravelAiAssistant\Exceptions\InvalidApiKeyException;
use Illuminate\Support\Facades\Config;
use Mockery\MockInterface;
use OpenAI\Client;

beforeEach(function () {
    // Reset config to ensure clean state for each test
    Config::set([
        'ai-assistant.api_key' => 'test_api_key',
        'ai-assistant.organization' => 'test_organization',
    ]);
});

it('throws InvalidApiKeyException if api_key is missing or invalid', function () {
    Config::set('ai-assistant.api_key', null);
    Config::set('ai-assistant.organization', null);

    expect(fn () => AppConfig::openAiClient())->toThrow(InvalidApiKeyException::class);
});

it('throws InvalidApiKeyException if api_key is set to placeholder value', function () {
    Config::set('ai-assistant.api_key', 'YOUR_OPENAI_API_KEY');
    Config::set('ai-assistant.organization', 'YOUR_OPENAI_ORGANIZATION');

    expect(fn () => AppConfig::openAiClient())->toThrow(InvalidApiKeyException::class);
});

it('returns a custom client if provided', function () {
    /** @var Client|MockInterface $clientMock */
    $clientMock = Mockery::mock(Client::class);

    $result = AppConfig::openAiClient($clientMock);

    expect($result)->toBe($clientMock);
});

it('creates a new OpenAI client if no custom client is provided', function () {
    Config::set('ai-assistant.api_key', 'valid-api-key');
    Config::set('ai-assistant.organization', 'valid-organization');
    $mockedClient = Mockery::mock(Client::class);
    $client = AppConfig::openAiClient($mockedClient);

    expect($client)->toBe($mockedClient);
});

it('returns correct text generator config', function () {
    Config::set([
        'ai-assistant.model' => 'text-davinci-003',
        'ai-assistant.max_tokens' => 100,
        'ai-assistant.temperature' => 0.7,
        'ai-assistant.stream' => true,
        'ai-assistant.echo' => false,
        'ai-assistant.n' => 1,
        'ai-assistant.suffix' => 'suffix',
        'ai-assistant.top_p' => 0.9,
        'ai-assistant.presence_penalty' => 0,
        'ai-assistant.frequency_penalty' => 0.5,
        'ai-assistant.best_of' => 1,
        'ai-assistant.stop' => ['stop1', 'stop2'],
    ]);

    expect(AppConfig::textGeneratorConfig())->toBe([
        'model' => 'text-davinci-003',
        'max_tokens' => 100,
        'temperature' => 0.7,
        'stream' => true,
        'echo' => false,
        'n' => 1,
        'suffix' => 'suffix',
        'top_p' => 0.9,
        'presence_penalty' => 0,
        'frequency_penalty' => 0.5,
        'best_of' => 1,
        'stop' => ['stop1', 'stop2'],
    ]);
});

it('returns correct chat text generator config', function () {
    Config::set([
        'ai-assistant.chat_model' => 'gpt-3.5-turbo',
        'ai-assistant.max_tokens' => 150,
        'ai-assistant.temperature' => 0.8,
        'ai-assistant.stream' => true,
        'ai-assistant.n' => 2,
        'ai-assistant.top_p' => 0.85,
        'ai-assistant.presence_penalty' => 0.1,
        'ai-assistant.frequency_penalty' => 0.2,
        'ai-assistant.stop' => null,
    ]);

    expect(AppConfig::chatTextGeneratorConfig())->toBe([
        'model' => 'gpt-3.5-turbo',
        'max_tokens' => 150,
        'temperature' => 0.8,
        'stream' => true,
        'n' => 2,
        'top_p' => 0.85,
        'presence_penalty' => 0.1,
        'frequency_penalty' => 0.2,
        'stop' => null,
    ]);
});

it('returns correct edit text generator config', function () {
    Config::set([
        'ai-assistant.edit_model' => 'text-edit-davinci',
        'ai-assistant.temperature' => 0.6,
        'ai-assistant.top_p' => 0.95,
    ]);

    expect(AppConfig::editTextGeneratorConfig())->toBe([
        'model' => 'text-edit-davinci',
        'temperature' => 0.6,
        'top_p' => 0.95,
    ]);
});

it('returns correct audio to text generator config', function () {
    Config::set([
        'ai-assistant.audio_model' => 'whisper-1',
        'ai-assistant.temperature' => 0.65,
        'ai-assistant.response_format' => 'json',
    ]);

    expect(AppConfig::audioToTextGeneratorConfig())->toBe([
        'model' => 'whisper-1',
        'temperature' => 0.65,
        'response_format' => 'json',
    ]);
});
