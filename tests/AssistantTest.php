<?php

declare(strict_types=1);

use CreativeCrafts\LaravelAiAssistant\Assistant;
use CreativeCrafts\LaravelAiAssistant\DataTransferObjects\AssistantMessageData;
use CreativeCrafts\LaravelAiAssistant\DataTransferObjects\NewAssistantResponseData;
use CreativeCrafts\LaravelAiAssistant\Exceptions\CreateNewAssistantException;
use CreativeCrafts\LaravelAiAssistant\Services\AssistantService;
use OpenAI\Responses\Assistants\AssistantResponse;

covers(Assistant::class);

beforeEach(function () {
    $this->clientMock = Mockery::mock(AssistantService::class);
    $this->assistant = Assistant::new()->client($this->clientMock);
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

it('can create assistant and return response', function () {
    $assistantData = [
        'model' => 'gpt-4o',
        'name' => '',
        'description' => '',
        'instructions' => '',
        'tools' => [],
        'temperature' => 0.5,
        'tool_resources' => null,
    ];

    $responseMock = mock(AssistantResponse::class);
    $this->clientMock->shouldReceive('createAssistant')->with($assistantData)->andReturn($responseMock);

    $newAssistantResponse = $this->assistant->create();

    expect($newAssistantResponse)->toBeInstanceOf(NewAssistantResponseData::class);
});

it('throws exception when creating assistant fails', function () {
    $this->clientMock->shouldReceive('createAssistant')->andThrow(new CreateNewAssistantException('Unable to create new assistant.'));

    $this->assistant->create();
})->throws(CreateNewAssistantException::class);

it('can retrieve assistant by id', function () {
    $assistantId = 'test-assistant-id';
    $responseMock = mock(AssistantResponse::class);

    $this->clientMock->shouldReceive('getAssistantViaId')->with($assistantId)->andReturn($responseMock);

    $assistant = $this->assistant->assignAssistant($assistantId);

    expect($assistant)->toBeInstanceOf(Assistant::class);

    $reflection = new ReflectionClass($assistant);
    $property = $reflection->getProperty('assistant');
    $property->setAccessible(true);
    expect($property->getValue($assistant))->toBe($responseMock);
});

it('can create task thread', function () {
    $threadData = ['param1' => 'value1'];
    $threadResponseMock = Mockery::mock(OpenAI\Responses\Threads\ThreadResponse::class);

    $reflectionClass = new ReflectionClass($threadResponseMock);
    $idProperty = $reflectionClass->getProperty('id');
    $idProperty->setAccessible(true);
    $idProperty->setValue($threadResponseMock, 'test-thread-id');

    $this->clientMock->shouldReceive('createThread')->with($threadData)->andReturn($threadResponseMock);
    $this->assistant->createTask($threadData);

    $reflection = new ReflectionClass($this->assistant);
    $property = $reflection->getProperty('threadId');
    $property->setAccessible(true);

    expect($property->getValue($this->assistant))->toBe('test-thread-id');
});

it('can ask a question and send message', function () {
    $message = 'What is the weather today?';
    $threadData = ['param1' => 'value1'];
    $threadResponseMock = mock(OpenAI\Responses\Threads\ThreadResponse::class);
    $threadMessageResponseMock = Mockery::mock(OpenAI\Responses\Threads\Messages\ThreadMessageResponse::class);

    $reflectionClass = new ReflectionClass($threadResponseMock);
    $idProperty = $reflectionClass->getProperty('id');
    $idProperty->setAccessible(true);
    $idProperty->setValue($threadResponseMock, 'test-thread-id');

    $this->clientMock->shouldReceive('createThread')->with($threadData)->andReturn($threadResponseMock);
    $this->assistant->createTask($threadData);

    $this->clientMock->shouldReceive('writeMessage')
        ->with('test-thread-id', Mockery::on(static function ($messageDataArray) use ($message) {
            return $messageDataArray['content'] === $message;
        }))
        ->andReturn($threadMessageResponseMock);

    $this->assistant->askQuestion($message);

    $reflection = new ReflectionClass($this->assistant);
    $property = $reflection->getProperty('assistantMessageData');
    $property->setAccessible(true);

    expect($property->getValue($this->assistant))->toBeInstanceOf(AssistantMessageData::class);
});

it('can process a message thread', function () {
    $message = 'What is the weather today?';
    $threadData = ['param1' => 'value1'];
    $threadResponseMock = mock(OpenAI\Responses\Threads\ThreadResponse::class);
    $threadMessageResponseMock = Mockery::mock(OpenAI\Responses\Threads\Messages\ThreadMessageResponse::class);

    $reflectionClass = new ReflectionClass($threadResponseMock);
    $idProperty = $reflectionClass->getProperty('id');
    $idProperty->setAccessible(true);
    $idProperty->setValue($threadResponseMock, 'test-thread-id');

    $this->clientMock->shouldReceive('createThread')->with($threadData)->andReturn($threadResponseMock);
    $this->assistant->createTask($threadData);

     $this->clientMock->shouldReceive('writeMessage')
        ->with('test-thread-id', Mockery::on(static function ($messageDataArray) use ($message) {
            return $messageDataArray['content'] === $message;
        }))
        ->andReturn($threadMessageResponseMock);

     $assistantId = fake()->word();
     $runThreadParameter = [
         'assistant_id' => $assistantId,
     ];

     $this->clientMock->shouldReceive('runMessageThread')
        ->with('test-thread-id', $runThreadParameter)
        ->andReturnTrue();

    $this->assistant
        ->setAssistantId($assistantId)
        ->askQuestion($message);

    $reflection = new ReflectionClass($this->assistant);
    $property = $reflection->getProperty('assistantMessageData');
    $property->setAccessible(true);

    $this->assistant->process();
    $this->clientMock->shouldHaveReceived('runMessageThread')
        ->with('test-thread-id', $runThreadParameter)
        ->once();

    $updatedMessageData = $property->getValue($this->assistant);

    $reflection = new ReflectionClass($updatedMessageData);
    $property = $reflection->getProperty('message');
    $property->setAccessible(true);
    expect($property->getValue($updatedMessageData))->toBe($message);
});

it('can return response from message thread', function () {
    $messageResponse = 'The weather today is sunny';
    $threadData = ['param1' => 'value1'];
    $threadResponseMock = mock(OpenAI\Responses\Threads\ThreadResponse::class);

    $reflectionClass = new ReflectionClass($threadResponseMock);
    $idProperty = $reflectionClass->getProperty('id');
    $idProperty->setAccessible(true);
    $idProperty->setValue($threadResponseMock, 'test-thread-id');

    $this->clientMock->shouldReceive('createThread')->with($threadData)->andReturn($threadResponseMock);

    $this->assistant->createTask($threadData);

    $this->clientMock->shouldReceive('listMessages')
        ->with('test-thread-id')
        ->andReturn($messageResponse);

    $response = $this->assistant->response();

    expect($response)->toBe($messageResponse);
});
