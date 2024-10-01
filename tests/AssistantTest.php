<?php

declare(strict_types=1);

use CreativeCrafts\LaravelAiAssistant\AppConfig;
use CreativeCrafts\LaravelAiAssistant\Assistant;
use CreativeCrafts\LaravelAiAssistant\DataTransferObjects\NewAssistantResponse;
use CreativeCrafts\LaravelAiAssistant\Exceptions\CreateNewAssistantException;
use CreativeCrafts\LaravelAiAssistant\Exceptions\InvalidApiKeyException;
use Illuminate\Support\Facades\Config;
use OpenAI\Client;
use OpenAI\Responses\Assistants\AssistantResponse;

covers(Assistant::class);

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

it('can instantiate a new Assistant', function () {
    $assistant = Assistant::new();
    expect($assistant)->toBeInstanceOf(Assistant::class);
});

it('can set the model name', function () {
    $assistant = Assistant::new()->setModelName('gpt-3.5-turbo');

    $reflection = new ReflectionClass($assistant);
    $property = $reflection->getProperty('modelName');
    $property->setAccessible(true);

    expect($property->getValue($assistant))->toBe('gpt-3.5-turbo');
});

it('can adjust the temperature', function () {
    $assistant = Assistant::new()->adjustTemperature(0.7);

    $reflection = new ReflectionClass($assistant);
    $property = $reflection->getProperty('temperature');
    $property->setAccessible(true);

    expect($property->getValue($assistant))->toBe(0.7);
});

it('can set the assistant name and description', function () {
    $assistant = Assistant::new()
        ->setAssistantName('MyAssistant')
        ->setAssistantDescription('This is a test assistant.');

    $reflection = new ReflectionClass($assistant);
    $property = $reflection->getProperty('assistantName');
    $property->setAccessible(true);

    expect($property->getValue($assistant))->toBe('MyAssistant');

    $reflection = new ReflectionClass($assistant);
    $propertyDescription = $reflection->getProperty('assistantDescription');
    $propertyDescription->setAccessible(true);

    expect($propertyDescription->getValue($assistant))->toBe('This is a test assistant.');
});

it('can set instructions', function () {
    $assistant = Assistant::new()->setInstructions('Follow these steps...');

    $reflection = new ReflectionClass($assistant);
    $property = $reflection->getProperty('instructions');
    $property->setAccessible(true);

    expect($property->getValue($assistant))->toBe('Follow these steps...');
});

it('can include a code interpreter tool', function () {
    $assistant = Assistant::new()->includeCodeInterpreterTool(['file1', 'file2']);

    $reflection = new ReflectionClass($assistant);
    $property = $reflection->getProperty('tools');
    $property->setAccessible(true);

    expect($property->getValue($assistant))->toBe([['type' => 'code_interpreter']]);

    $reflection = new ReflectionClass($assistant);
    $propertyToolResources = $reflection->getProperty('toolResources');
    $propertyToolResources->setAccessible(true);

    expect($propertyToolResources->getValue($assistant))->toBe([
        'code_interpreter' => ['file_ids' => ['file1', 'file2']]
    ]);
});

it('can include a file search tool', function () {
    $assistant = Assistant::new()->includeFileSearchTool(['vector1', 'vector2']);

    $reflection = new ReflectionClass($assistant);
    $property = $reflection->getProperty('tools');
    $property->setAccessible(true);

    expect($property->getValue($assistant))->toBe([['type' => 'file_search']]);

    $reflection = new ReflectionClass($assistant);
    $propertyToolResources = $reflection->getProperty('toolResources');
    $propertyToolResources->setAccessible(true);

    expect($propertyToolResources->getValue($assistant))->toBe([
        'file_search' => ['vector_store_ids' => ['vector1', 'vector2']]
    ]);
});

it('can include a function call tool', function () {
    $assistant = Assistant::new()->includeFunctionCallTool(
        'testFunction',
        'This is a test function',
        ['param1' => 'string'],
        true,
        ['param1'],
        false
    );

    $reflection = new ReflectionClass($assistant);
    $property = $reflection->getProperty('tools');
    $property->setAccessible(true);

    expect($property->getValue($assistant))->toBe([
        [
            'type' => 'function',
            'function' => [
                'name' => 'testFunction',
                'description' => 'This is a test function',
                'parameters' => ['param1' => 'string'],
                'strict' => true,
                'required' => ['param1'],
                'additionalProperties' => false,
            ]
        ]
    ]);
});

it('creates a new assistant successfully', function () {
    // Mock the OpenAI AssistantResponse object
    $mockedResponse = Mockery::mock(AssistantResponse::class);
    $mockedResponse->shouldReceive('id')->andReturn('1234');
    $mockedResponse->shouldReceive('name')->andReturn('Test Assistant');

    // Mock the OpenAI client
    $mockedClient = Mockery::mock(Client::class);
    $mockedClient->shouldReceive('assistants->create')
        ->once()
        ->andReturn($mockedResponse);

    // Set up the Assistant class with mocked client and parameters
    $assistant = Assistant::new()
        ->client($mockedClient)
        ->setModelName('gpt-4o')
        ->setAssistantName('Test Assistant')
        ->setAssistantDescription('Test description')
        ->setInstructions('Test instructions')
        ->adjustTemperature(0.7)
        ->includeCodeInterpreterTool(['file1', 'file2']);

    // Call the create method and get the NewAssistantResponse object
    $response = $assistant->create();

    // Verify the NewAssistantResponse object
    expect($response)->toBeInstanceOf(NewAssistantResponse::class);
});

it('throws an exception when creating an assistant fails', function () {
    // Mock the OpenAI client to throw an exception
    $mockedClient = Mockery::mock(Client::class);
    $mockedClient->shouldReceive('assistants->create')
        ->once()
        ->andThrow(new Exception('API error', 500));

    // Prepare the assistant instance
    $assistant = Assistant::new()->client($mockedClient);

    // Expect the custom exception to be thrown
    $this->expectException(CreateNewAssistantException::class);

    // Call the create method
    $assistant->create();
});
