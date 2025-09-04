<?php

declare(strict_types=1);

use CreativeCrafts\LaravelAiAssistant\Compat\OpenAI\Client;
use CreativeCrafts\LaravelAiAssistant\Services\AppConfig;
use Illuminate\Support\Facades\Config;

beforeEach(function (): void {
    Config::set('ai-assistant.api_key', 'valid-api-key');
    Config::set('ai-assistant.organization', 'test-org');
    Config::set('ai-assistant.connection_pool', ['enabled' => true]);
});

it('creates client without organization', function () {
    Config::set('ai-assistant.organization', null);

    $client = AppConfig::openAiClient();

    expect($client)->toBeInstanceOf(Client::class);
});

it('creates client with organization', function () {
    Config::set('ai-assistant.organization', 'org_123');

    $client = AppConfig::openAiClient();

    expect($client)->toBeInstanceOf(Client::class);
});

it('creates client with connection pooling enabled (default)', function () {
    Config::set('ai-assistant.connection_pool', ['enabled' => true]);

    $client = AppConfig::openAiClient();

    expect($client)->toBeInstanceOf(Client::class);
});

it('creates client with connection pooling disabled', function () {
    Config::set('ai-assistant.connection_pool', ['enabled' => false]);

    $client = AppConfig::openAiClient();

    expect($client)->toBeInstanceOf(Client::class);
});
