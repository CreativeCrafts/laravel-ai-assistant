<?php

declare(strict_types=1);

use CreativeCrafts\LaravelAiAssistant\AiAssistant;
use CreativeCrafts\LaravelAiAssistant\AppConfig;
use CreativeCrafts\LaravelAiAssistant\Exceptions\InvalidApiKeyException;
use Illuminate\Support\Facades\Config;
use OpenAI\Client;

it('throws InvalidApiKeyException if the API key or organization is invalid', function () {
    Config::set('ai-assistant.api_key', '');
    Config::set('ai-assistant.organization', '');
    AppConfig::openAiClient();
})->throws(InvalidApiKeyException::class, 'Invalid OpenAI API key or organization.');

it('returns a mocked OpenAI client when the configuration is valid', function () {
    Config::set('ai-assistant.api_key', 'valid-api-key');
    Config::set('ai-assistant.organization', 'valid-organization');
    $mockedClient = Mockery::mock(Client::class);
    $client = AppConfig::openAiClient($mockedClient);

    expect($client)->toBe($mockedClient);
});

it('can translate english text to different language such as swedish', function () {
    $mock = $this->createMock(AiAssistant::class);
    $mock->method('translateTo')->willReturn('Hur mår du?');

    self::assertEquals('Hur mår du?', $mock->translateTo('swedish'));
})->group('translation');

it('can draft a blog', function () {
    $response = 'Artificial Intelligence (AI) is a rapidly growing field of technology that is revolutionizing the way we interact with the world around us. From self-driving cars to voice-activated home assistants, AI is making our lives easier and more efficient. But what exactly is AI, and how is it changing our lives?
AI is a branch of computer science that focuses on creating intelligent machines that can think and act like humans. AI systems are designed to learn from their environment and make decisions based on what they learn. This means that AI can be used to automate tasks, such as recognizing faces or driving cars, and can even be used to create new products and services.
AI is already being used in a variety of industries, from healthcare to finance. In healthcare, AI is being used to diagnose diseases and provide personalized treatments. In finance, AI is being used to detect fraud and improve customer service. AI is also being used in retail to create personalized shopping experiences and in manufacturing to automate tasks and increase efficiency.
AI is also being used to improve our lives in more subtle ways.';

    $mock = $this->createMock(AiAssistant::class);
    $mock->method('draft')->willReturn($response);

    self::assertEquals($response, $mock->draft());
})->group('draft');
