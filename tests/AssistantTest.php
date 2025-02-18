<?php

declare(strict_types=1);

use CreativeCrafts\LaravelAiAssistant\Assistant;
use CreativeCrafts\LaravelAiAssistant\DataFactories\ChatAssistantMessageDataFactory;
use CreativeCrafts\LaravelAiAssistant\DataTransferObjects\MessageData;
use CreativeCrafts\LaravelAiAssistant\DataTransferObjects\NewAssistantResponseData;
use CreativeCrafts\LaravelAiAssistant\Exceptions\CreateNewAssistantException;
use CreativeCrafts\LaravelAiAssistant\Services\AssistantService;
use OpenAI\Responses\Assistants\AssistantResponse;
use org\bovigo\vfs\vfsStream;

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
    $property = $reflection->getProperty('modelConfig');
    $property->setAccessible(true);
    $modelConfig = $property->getValue($assistant);
    expect($modelConfig['model'])->toBe('gpt-3.5-turbo');
});

it('can adjust the temperature', function () {
    $assistant = Assistant::new()->adjustTemperature(0.7);

    $reflection = new ReflectionClass($assistant);
    $property = $reflection->getProperty('modelConfig');
    $property->setAccessible(true);
    $modelConfig = $property->getValue($assistant);
    expect($modelConfig['temperature'])->toBe(0.7);
});

it('can set the assistant name and description', function () {
    $assistant = Assistant::new()
        ->setAssistantName('MyAssistant')
        ->setAssistantDescription('This is a test assistant.');

    $reflection = new ReflectionClass($assistant);
    $property = $reflection->getProperty('modelConfig');
    $property->setAccessible(true);
    $modelConfig = $property->getValue($assistant);

    expect($modelConfig['name'])->toBe('MyAssistant')
        ->and($modelConfig['description'])->toBe('This is a test assistant.');
});

it('can set instructions', function () {
    $assistant = Assistant::new()->setInstructions('Follow these steps...');

    $reflection = new ReflectionClass($assistant);
    $property = $reflection->getProperty('modelConfig');
    $property->setAccessible(true);
    $modelConfig = $property->getValue($assistant);

    expect($modelConfig['instructions'])->toBe('Follow these steps...');
});

it('can include a code interpreter tool', function () {
    $assistant = Assistant::new()->includeCodeInterpreterTool(['file1', 'file2']);

    $reflection = new ReflectionClass($assistant);
    $property = $reflection->getProperty('modelConfig');
    $property->setAccessible(true);
    $modelConfig = $property->getValue($assistant);

    expect($modelConfig['tools'])->toBeArray()
        ->and($modelConfig['tools'])->toBe([
            ['type' => 'code_interpreter']
        ]);
});

it('can include a file search tool', function () {
    $assistant = Assistant::new()->includeFileSearchTool(['vector1', 'vector2']);

   $reflection = new ReflectionClass($assistant);
    $property = $reflection->getProperty('modelConfig');
    $property->setAccessible(true);
    $modelConfig = $property->getValue($assistant);
    expect($modelConfig['tools'])->toBeArray()
        ->and($modelConfig['tools'])->toBe([
            ['type' => 'file_search']
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
    $property = $reflection->getProperty('modelConfig');
    $property->setAccessible(true);
    $modelConfig = $property->getValue($assistant);

    expect($modelConfig['tools'])->toBe([
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
    $expectedSubset = [
        'model'            => 'gpt-3.5-turbo',
        'temperature'      => 0.3,
        'response_format'  => 'auto',
    ];

    $responseMock = mock(AssistantResponse::class);
    $this->clientMock->shouldReceive('createAssistant')
        ->with(Mockery::on(function ($data) use ($expectedSubset) {
            foreach ($expectedSubset as $key => $value) {
                if (!array_key_exists($key, $data) || $data[$key] !== $value) {
                    return false;
                }
            }
            return true;
        }))
        ->andReturn($responseMock);

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

    expect($property->getValue($this->assistant))->toBeInstanceOf(MessageData::class);
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


describe('Assistant::setFilePath', function () {
    it('sets the file path for audio transcription successfully', function () {
        $tempFile = tempnam(sys_get_temp_dir(), 'test');
        file_put_contents($tempFile, 'dummy content');

        $assistant = Assistant::new();
        $returnedInstance = $assistant->setFilePath($tempFile);

        expect($returnedInstance)->toBeInstanceOf(Assistant::class);

        $reflection = new ReflectionClass($assistant);
        $property = $reflection->getProperty('modelConfig');
        $property->setAccessible(true);
        $modelConfig = $property->getValue($assistant);

        expect(array_key_exists('file', $modelConfig))->toBeTrue()
            ->and(is_resource($modelConfig['file']))->toBeTrue()
            ->and(get_resource_type($modelConfig['file']))->toEqual('stream');

        fclose($modelConfig['file']);
        unlink($tempFile);
    });

    it('throws a RuntimeException when the file cannot be opened', function () {
        $invalidFilePath = '/invalid/path/to/nonexistent/file.txt';
        $assistant = Assistant::new();
        $assistant->setFilePath($invalidFilePath);
    })->throws(ErrorException::class, 'fopen(/invalid/path/to/nonexistent/file.txt): Failed to open stream: No such file or directory');
});

describe('Assistant::setResponseFormat', function () {
    it('sets the response format when given an array', function () {
        $assistant = Assistant::new();
        $formats = [
            'type' => 'text',
        ];
        $result = $assistant->setResponseFormat($formats);
        expect($result)->toBeInstanceOf(Assistant::class);

        $reflection = new ReflectionClass($assistant);
        $property = $reflection->getProperty('modelConfig');
        $property->setAccessible(true);
        $config = $property->getValue($assistant);

        expect($config)->toHaveKey('response_format')
            ->and($config['response_format'])->toBe($formats);
    });
});

describe('Assistant::setMetaData', function () {
    it('sets metadata and returns the Assistant instance', function () {
        $assistant = Assistant::new();
        $metadata = [
            'project' => 'PestPhp',
            'version' => '1.0.0',
            'author'  => 'CreativeCrafts'
        ];

        $returned = $assistant->setMetaData($metadata);
        expect($returned)->toBeInstanceOf(Assistant::class);

        $reflection = new ReflectionClass($assistant);
        $property   = $reflection->getProperty('modelConfig');
        $property->setAccessible(true);
        $modelConfig = $property->getValue($assistant);

        expect($modelConfig)->toHaveKey('metadata')
            ->and($modelConfig['metadata'])->toBe($metadata);
    });

    it('sets an empty metadata array correctly', function () {
        $assistant = Assistant::new();
        $metadata = [];

        $returned = $assistant->setMetaData($metadata);
        expect($returned)->toBeInstanceOf(Assistant::class);

        $reflection = new ReflectionClass($assistant);
        $property   = $reflection->getProperty('modelConfig');
        $property->setAccessible(true);
        $modelConfig = $property->getValue($assistant);

        expect($modelConfig)->toHaveKey('metadata')
            ->and($modelConfig['metadata'])->toBeEmpty();
    });
});

describe('Assistant::setReasoningEffort', function () {
    it('sets reasoning effort to low', function () {
        $assistant = Assistant::new();
        $returned = $assistant->setReasoningEffort('low');

        expect($returned)->toBeInstanceOf(Assistant::class);

        $reflection = new ReflectionClass($assistant);
        $property = $reflection->getProperty('modelConfig');
        $property->setAccessible(true);
        $config = $property->getValue($assistant);

        expect($config)->toHaveKey('reasoning_effort');
        expect($config['reasoning_effort'])->toBe('low');
    });

    it('sets reasoning effort to medium', function () {
        $assistant = Assistant::new();
        $returned = $assistant->setReasoningEffort('medium');

        expect($returned)->toBeInstanceOf(Assistant::class);

        $reflection = new ReflectionClass($assistant);
        $property = $reflection->getProperty('modelConfig');
        $property->setAccessible(true);
        $config = $property->getValue($assistant);

        expect($config)->toHaveKey('reasoning_effort');
        expect($config['reasoning_effort'])->toBe('medium');
    });

    it('sets reasoning effort to high', function () {
        $assistant = Assistant::new();
        $returned = $assistant->setReasoningEffort('high');

        expect($returned)->toBeInstanceOf(Assistant::class);

        $reflection = new ReflectionClass($assistant);
        $property = $reflection->getProperty('modelConfig');
        $property->setAccessible(true);
        $config = $property->getValue($assistant);

        expect($config)->toHaveKey('reasoning_effort');
        expect($config['reasoning_effort'])->toBe('high');
    });
});

describe('Assistant::transcribeTo', function () {
    it('transcribes audio without an optional prompt', function () {
        vfsStream::setup('root', null, ['testfile.mp3' => 'audio data']);
        $filePath = vfsStream::url('root/testfile.mp3');

        $mockService = Mockery::mock(AssistantService::class);
        $language = [
            'language' => 'en',
            'file' => fopen($filePath, 'rb'),
        ] ;

        $mockService->shouldReceive('transcribeTo')
            ->once()
            ->with($language)
            ->andReturn('Mocked transcription');

        $assistant = Assistant::new()->client($mockService);
        $assistant->setFilePath($filePath);

        $result = $mockService->transcribeTo($language);
        expect($result)->toBe('Mocked transcription');
    });

    it('transcribes audio with an optional prompt', function () {
        vfsStream::setup('root', null, ['testfile.mp3' => 'audio data']);
        $filePath = vfsStream::url('root/testfile.mp3');

        $mockService = Mockery::mock(AssistantService::class);
        $language = [
            'language' => 'en',
            'file' => fopen($filePath, 'rb'),
            'prompt' => 'Transcribe this audio',
        ] ;

        $mockService->shouldReceive('transcribeTo')
            ->once()
            ->with($language)
            ->andReturn('Mocked transcription');

        $assistant = Assistant::new()->client($mockService);
        $assistant->setFilePath($filePath);

        $result = $mockService->transcribeTo($language);
        expect($result)->toBe('Mocked transcription');
    });
});

describe('Assistant::setDeveloperMessage', function () {
    it('sets the developer message with system role when model is gpt-3.5-turbo', function () {
        $assistant = Assistant::new()->setModelName('gpt-3.5-turbo');
        $developerMessage = 'Test system message';

        $returned = $assistant->setDeveloperMessage($developerMessage);
        expect($returned)->toBeInstanceOf(Assistant::class);

        $reflection = new ReflectionClass($assistant);
        $property   = $reflection->getProperty('modelConfig');
        $property->setAccessible(true);
        $config = $property->getValue($assistant);

        expect(array_key_exists('messages', $config))->toBeTrue()
            ->and(count($config['messages']))->toBe(1);

        $messageData = $config['messages'][0];
        expect($messageData['content'])->toBe($developerMessage)
            ->and($messageData['role'])->toBe('system');
    });

    it('sets the developer message with developer role when model is not gpt-3.5-turbo', function () {
        $assistant = Assistant::new()->setModelName('gpt-4');
        $developerMessage = 'Test developer message';

        $returned = $assistant->setDeveloperMessage($developerMessage);
        expect($returned)->toBeInstanceOf(Assistant::class);

        $reflection = new ReflectionClass($assistant);
        $property   = $reflection->getProperty('modelConfig');
        $property->setAccessible(true);
        $config = $property->getValue($assistant);

        expect(array_key_exists('messages', $config))->toBeTrue()
            ->and(count($config['messages']))->toBe(1);

        $messageData = $config['messages'][0];
        expect($messageData['content'])->toBe($developerMessage)
            ->and($messageData['role'])->toBe('developer');
    });
});

describe('Assistant::setUserMessage', function () {
    it('adds a user message and returns the Assistant instance', function () {
        $assistant = Assistant::new();
        $userMessage = 'This is a test user message';

        $returned = $assistant->setUserMessage($userMessage);
        expect($returned)->toBeInstanceOf(Assistant::class);

        $reflection = new ReflectionClass($assistant);
        $property = $reflection->getProperty('modelConfig');
        $property->setAccessible(true);
        $config = $property->getValue($assistant);

        expect(array_key_exists('messages', $config))->toBeTrue()
            ->and(count($config['messages']))->toBeGreaterThan(0);

        $lastMessage = end($config['messages']);
        expect($lastMessage['content'])->toBe($userMessage)
            ->and($lastMessage['role'])->toBe('user');
    });
});

describe('Assistant::setChatAssistantMessage', function () {
    it('adds an assistant message with optional parameters and returns the Assistant instance', function () {
        $assistant = Assistant::new();

        $content = 'This is an assistant response';
        $refusal = 'Optional refusal text';
        $name = 'AssistantName';
        $audio = null;
        $toolCalls = null;

        $returned = $assistant->setChatAssistantMessage($content, $refusal, $name, $audio, $toolCalls);
        expect($returned)->toBeInstanceOf(Assistant::class);

        $reflection = new ReflectionClass($assistant);
        $property = $reflection->getProperty('modelConfig');
        $property->setAccessible(true);
        $config = $property->getValue($assistant);

        expect(array_key_exists('messages', $config))->toBeTrue();
        $lastMessage = end($config['messages']);

        $expectedMessageData = ChatAssistantMessageDataFactory::buildChatAssistantMessageData(
            content: $content,
            refusal: $refusal,
            name: $name,
            audio: $audio,
            toolCalls: $toolCalls
        )->toArray();

        expect($lastMessage)->toBe($expectedMessageData);
    });

    it('throws an exception if the audio is provided but the id from the previous audio response is missing', function () {
        $assistant = Assistant::new();

        $content = 'This is an assistant response';
        $refusal = 'Optional refusal text';
        $name = 'AssistantName';
        $audio = ['duration' => 5, 'format' => 'mp3'];
        $toolCalls = null;

        $returned = $assistant->setChatAssistantMessage($content, $refusal, $name, $audio, $toolCalls);
        expect($returned)->toBeInstanceOf(Assistant::class);

        $reflection = new ReflectionClass($assistant);
        $property = $reflection->getProperty('modelConfig');
        $property->setAccessible(true);
        $config = $property->getValue($assistant);

        expect(array_key_exists('messages', $config))->toBeTrue();
        $lastMessage = end($config['messages']);

        $expectedMessageData = ChatAssistantMessageDataFactory::buildChatAssistantMessageData(
            content: $content,
            refusal: $refusal,
            name: $name,
            audio: $audio,
            toolCalls: $toolCalls
        )->toArray();

        expect($lastMessage)->toBe($expectedMessageData);
    })->throws(InvalidArgumentException::class, 'Id for the previous audio response from the model is required');

    it('throws an exception if the tool call array is provided but the id or type is missing', function () {
        $assistant = Assistant::new();

        $content = 'This is an assistant response';
        $refusal = 'Optional refusal text';
        $name = 'AssistantName';
        $audio = null;
        $toolCalls = ['tool' => 'call data'];
        ;

        $returned = $assistant->setChatAssistantMessage($content, $refusal, $name, $audio, $toolCalls);
        expect($returned)->toBeInstanceOf(Assistant::class);

        $reflection = new ReflectionClass($assistant);
        $property = $reflection->getProperty('modelConfig');
        $property->setAccessible(true);
        $config = $property->getValue($assistant);

        expect(array_key_exists('messages', $config))->toBeTrue();
        $lastMessage = end($config['messages']);

        $expectedMessageData = ChatAssistantMessageDataFactory::buildChatAssistantMessageData(
            content: $content,
            refusal: $refusal,
            name: $name,
            audio: $audio,
            toolCalls: $toolCalls
        )->toArray();

        expect($lastMessage)->toBe($expectedMessageData);
    })->throws(InvalidArgumentException::class, 'Missing required fields for tool call');
});

describe('Assistant::setToolMessage', function () {
    it('adds a tool message with the correct message, role, and toolCallId', function () {
        $assistant = Assistant::new();
        $toolMessage = 'This is a tool message';
        $toolCallId = 'tool-123';


        $returned = $assistant->setToolMessage($toolMessage, $toolCallId);

        expect($returned)->toBeInstanceOf(Assistant::class);

        $reflection = new ReflectionClass($assistant);
        $property   = $reflection->getProperty('modelConfig');
        $property->setAccessible(true);
        $modelConfig = $property->getValue($assistant);

        expect(array_key_exists('messages', $modelConfig))->toBeTrue()
            ->and(count($modelConfig['messages']))->toBeGreaterThan(0);

        $lastMessage = end($modelConfig['messages']);

        expect($lastMessage['content'])->toBe($toolMessage)
            ->and($lastMessage['role'])->toBe('tool')
            ->and($lastMessage['tool_call_id'])->toBe($toolCallId);
    });
});

describe('Assistant::useOutputForDistillation', function () {
    it('activates output for distillation when passed true', function () {
        $assistant = Assistant::new();
        $returned = $assistant->useOutputForDistillation(true);

        expect($returned)->toBeInstanceOf(Assistant::class);

        $reflection = new ReflectionClass($assistant);
        $property = $reflection->getProperty('modelConfig');
        $property->setAccessible(true);
        $config = $property->getValue($assistant);

        expect($config)->toHaveKey('store')
            ->and($config['store'])->toBeTrue();
    });

    it('deactivates output for distillation when passed false', function () {
        $assistant = Assistant::new();
        $returned = $assistant->useOutputForDistillation(false);

        expect($returned)->toBeInstanceOf(Assistant::class);

        $reflection = new ReflectionClass($assistant);
        $property = $reflection->getProperty('modelConfig');
        $property->setAccessible(true);
        $config = $property->getValue($assistant);

        expect($config)->toHaveKey('store')
            ->and($config['store'])->toBeFalse();
    });
});

describe('Assistant::setMaxCompletionTokens', function () {
    it('sets the maximum number of completion tokens and returns the Assistant instance', function () {
        $assistant = Assistant::new();
        $maxTokens = 150;

        $returned = $assistant->setMaxCompletionTokens($maxTokens);

        expect($returned)->toBeInstanceOf(Assistant::class);

        $reflection = new ReflectionClass($assistant);
        $property = $reflection->getProperty('modelConfig');
        $property->setAccessible(true);
        $config = $property->getValue($assistant);

        expect($config)->toHaveKey('max_completion_tokens')
            ->and($config['max_completion_tokens'])->toBe($maxTokens);
    });
});

describe('Assistant::setNumberOfCompletionChoices', function () {
    it('sets the number of completion choices and returns the Assistant instance', function () {
        $assistant = Assistant::new();
        $numberOfCompletionChoices = 3;

        $returned = $assistant->setNumberOfCompletionChoices($numberOfCompletionChoices);

        expect($returned)->toBeInstanceOf(Assistant::class);

        $reflection = new ReflectionClass($assistant);
        $property = $reflection->getProperty('modelConfig');
        $property->setAccessible(true);
        $modelConfig = $property->getValue($assistant);

        expect($modelConfig)->toHaveKey('n')
            ->and($modelConfig['n'])->toBe($numberOfCompletionChoices);
    });
});

describe('Assistant::setOutputTypes', function () {
    it('sets output types when audio is not requested', function () {
        $assistant = Assistant::new();
        $outputTypes = ['text'];

        $returned = $assistant->setOutputTypes($outputTypes);
        expect($returned)->toBeInstanceOf(Assistant::class);

        $reflection = new ReflectionClass($assistant);
        $property = $reflection->getProperty('modelConfig');
        $property->setAccessible(true);
        $config = $property->getValue($assistant);

        expect($config)->toHaveKey('modalities')
            ->and($config['modalities'])->toBe($outputTypes)
            ->and(array_key_exists('audio', $config))->toBeFalse();
    });

    it('sets output types with audio when both audioVoice and audioFormat are provided', function () {
        $assistant = Assistant::new();
        $outputTypes = ['text', 'audio'];
        $audioVoice = 'en-US';
        $audioFormat = 'mp3';

        $returned = $assistant->setOutputTypes($outputTypes, $audioVoice, $audioFormat);
        expect($returned)->toBeInstanceOf(Assistant::class);

        $reflection = new ReflectionClass($assistant);
        $property = $reflection->getProperty('modelConfig');
        $property->setAccessible(true);
        $config = $property->getValue($assistant);

        expect($config)->toHaveKey('modalities')
            ->and($config['modalities'])->toBe($outputTypes)
            ->and($config)->toHaveKey('audio')
            ->and($config['audio'])->toBe([
                'voice' => $audioVoice,
                'format' => $audioFormat,
            ]);
    });

    it('throws an exception if audio output is requested but audioVoice is missing', function () {
        $assistant = Assistant::new();
        $outputTypes = ['text', 'audio'];
        $audioFormat = 'mp3';

        expect(fn () => $assistant->setOutputTypes($outputTypes, null, $audioFormat))
            ->toThrow(InvalidArgumentException::class, 'To generate audio output, both audio voice and audio format must be provided.');
    });

    it('throws an exception if audio output is requested but audioFormat is missing', function () {
        $assistant = Assistant::new();
        $outputTypes = ['text', 'audio'];
        $audioVoice = 'en-US';

        expect(fn () => $assistant->setOutputTypes($outputTypes, $audioVoice, null))
            ->toThrow(InvalidArgumentException::class, 'To generate audio output, both audio voice and audio format must be provided.');
    });
});

describe('Assistant::shouldStream', function () {
    it('sets the stream flag when no stream options are provided', function () {
        $assistant = Assistant::new();
        $returned = $assistant->shouldStream(true);

        expect($returned)->toBeInstanceOf(Assistant::class);

        $reflection = new ReflectionClass($assistant);
        $property   = $reflection->getProperty('modelConfig');
        $property->setAccessible(true);
        $config = $property->getValue($assistant);

        expect($config)->toHaveKey('stream')
            ->and($config['stream'])->toBeTrue()
            ->and(array_key_exists('stream_options', $config))->toBeFalse();
    });

    it('throws an exception if stream options are provided without pre-existing include_usage', function () {
        $assistant = Assistant::new();
        $streamOptions = ['option1' => 'value1'];

        expect(fn () => $assistant->shouldStream(true, $streamOptions))
            ->toThrow(InvalidArgumentException::class, 'The include_usage option is required when setting stream options.');
    });

    it('sets stream options when provided and include_usage is already present in modelConfig', function () {
        $assistant = Assistant::new();

        $reflection = new ReflectionClass($assistant);
        $property   = $reflection->getProperty('modelConfig');
        $property->setAccessible(true);
        $config = $property->getValue($assistant);
        $config['stream_options'] = ['include_usage' => true];
        $property->setValue($assistant, $config);

        $newStreamOptions = ['include_usage' => true, 'option1' => 'value1'];
        $returned = $assistant->shouldStream(true, $newStreamOptions);
        expect($returned)->toBeInstanceOf(Assistant::class);

        $updatedConfig = $property->getValue($assistant);
        expect($updatedConfig)->toHaveKey('stream')
            ->and($updatedConfig['stream'])->toBeTrue()
            ->and($updatedConfig)->toHaveKey('stream_options')
            ->and($updatedConfig['stream_options'])->toBe($newStreamOptions);
    });
});

describe('Assistant::setTopP', function () {
    it('sets the top_p value in modelConfig and returns the Assistant instance', function () {
        $assistant = Assistant::new();
        $topP = 1;

        $returned = $assistant->setTopP($topP);
        expect($returned)->toBeInstanceOf(Assistant::class);

        $reflection = new ReflectionClass($assistant);
        $property   = $reflection->getProperty('modelConfig');
        $property->setAccessible(true);
        $config = $property->getValue($assistant);

        expect($config)->toHaveKey('top_p')
            ->and($config['top_p'])->toBe($topP);
    });
});

describe('Assistant::addAStop', function () {
    it('sets a stop sequence when given a string', function () {
        $assistant = Assistant::new();
        $stopSequence = "STOP";
        $returned = $assistant->addAStop($stopSequence);

        expect($returned)->toBeInstanceOf(Assistant::class);

        $reflection = new ReflectionClass($assistant);
        $property   = $reflection->getProperty('modelConfig');
        $property->setAccessible(true);
        $config = $property->getValue($assistant);

        expect($config)->toHaveKey('stop')
            ->and($config['stop'])->toBe($stopSequence);
    });

    it('sets stop sequences when given an array', function () {
        $assistant = Assistant::new();
        $stopSequences = ["STOP1", "STOP2"];
        $returned = $assistant->addAStop($stopSequences);

        expect($returned)->toBeInstanceOf(Assistant::class);

        $reflection = new ReflectionClass($assistant);
        $property   = $reflection->getProperty('modelConfig');
        $property->setAccessible(true);
        $config = $property->getValue($assistant);

        expect($config)->toHaveKey('stop')
            ->and($config['stop'])->toBe($stopSequences);
    });
});

describe('Assistant::shouldCacheChatMessages', function () {
    it('sets the cache configuration correctly', function () {
        $assistant = Assistant::new();
        $cacheKey = 'chat_cache_key';
        $ttl = 3600;

        $returned = $assistant->shouldCacheChatMessages($cacheKey, $ttl);
        expect($returned)->toBeInstanceOf(Assistant::class);

        $reflection = new ReflectionClass($assistant);
        $property = $reflection->getProperty('modelConfig');
        $property->setAccessible(true);
        $config = $property->getValue($assistant);

        expect($config)->toHaveKey('cacheConfig')
            ->and($config['cacheConfig'])->toBe([
                'cacheKey' => $cacheKey,
                'ttl' => $ttl,
            ]);
    });
});

describe('Assistant::sendChatMessage', function () {
    it('calls streamedChat on the client when chatCompletionData indicates streaming', function () {
        $dummyResponse = ['result' => 'streamed response'];

        $mockClient = Mockery::mock(AssistantService::class);
        $mockClient->shouldReceive('streamedChat')
            ->once()
            ->with(Mockery::type('array'))
            ->andReturn($dummyResponse);

        $assistant = Assistant::new()->client($mockClient);

        $reflection = new ReflectionClass($assistant);
        $property   = $reflection->getProperty('modelConfig');
        $property->setAccessible(true);
        $config = $property->getValue($assistant);
        $config['stream'] = true;
        $config['messages'] = (new MessageData(
            message: 'This is a test message',
            role: 'user',
        ))->toArray();
        $property->setValue($assistant, $config);

        $result = $assistant->sendChatMessage();
        expect($result)->toBe($dummyResponse);
    });

    it('calls chatTextCompletion on the client when chatCompletionData indicates no streaming', function () {
        $dummyResponse = ['result' => 'non-streamed response'];

        $mockClient = Mockery::mock(AssistantService::class);
        $mockClient->shouldReceive('chatTextCompletion')
            ->once()
            ->with(Mockery::type('array'))
            ->andReturn($dummyResponse);

        $assistant = Assistant::new()->client($mockClient);

        $reflection = new ReflectionClass($assistant);
        $property   = $reflection->getProperty('modelConfig');
        $property->setAccessible(true);
        $config = $property->getValue($assistant);
        $config['stream'] = false;
        $config['messages'] = (new MessageData(
            message: 'This is a test message',
            role: 'user',
        ))->toArray();
        $property->setValue($assistant, $config);

        $result = $assistant->sendChatMessage();
        expect($result)->toBe($dummyResponse);
    });
});

describe('Assistant::openFile', function () {
    it('opens a valid file and returns a resource', function () {
        $tempFile = tempnam(sys_get_temp_dir(), 'test');
        file_put_contents($tempFile, 'dummy content');

        $assistant = Assistant::new();

        $reflection = new ReflectionClass($assistant);
        $method = $reflection->getMethod('openFile');
        $method->setAccessible(true);

        $resource = $method->invoke($assistant, $tempFile);

        expect(is_resource($resource))->toBeTrue()
            ->and(get_resource_type($resource))->toBe('stream');

        fclose($resource);
        unlink($tempFile);
    });

    it('throws a ErrorException for an invalid file path', function () {
        $assistant = Assistant::new();

        $reflection = new ReflectionClass($assistant);
        $method = $reflection->getMethod('openFile');
        $method->setAccessible(true);

        $invalidFilePath = '/invalid/path/to/nonexistent/file.txt';
        expect(fn () => $method->invoke($assistant, $invalidFilePath))
            ->toThrow(ErrorException::class, "fopen($invalidFilePath): Failed to open stream: No such file or directory");
    });
});
