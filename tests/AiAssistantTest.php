<?php

declare(strict_types=1);

use CreativeCrafts\LaravelAiAssistant\AiAssistant;
use CreativeCrafts\LaravelAiAssistant\DataTransferObjects\CustomFunctionData;
use CreativeCrafts\LaravelAiAssistant\Services\AssistantService;
use org\bovigo\vfs\vfsStream;

covers(AiAssistant::class);

beforeEach(function () {
    $this->clientMock = Mockery::mock(AssistantService::class);
    $this->prompt = 'Test prompt';
    $this->aiAssistant = new AiAssistant($this->prompt);
});

it('can accept a prompt', function () {
    $aiAssistant = AiAssistant::acceptPrompt('New prompt');

    $reflection = new ReflectionClass($aiAssistant);
    $property = $reflection->getProperty('prompt');
    $property->setAccessible(true);

    expect($aiAssistant)->toBeInstanceOf(AiAssistant::class)
        ->and($property->getValue($aiAssistant))->toBe('New prompt');
});

it('can set client service', function () {
    $this->aiAssistant->client($this->clientMock);

    $reflection = new ReflectionClass($this->aiAssistant);
    $property = $reflection->getProperty('client');
    $property->setAccessible(true);

    expect($property->getValue($this->aiAssistant))->toBe($this->clientMock);
});

it('can generate a draft', function () {
    $this->aiAssistant->client($this->clientMock);
    $this->clientMock->shouldReceive('textCompletion')
        ->once()
        ->andReturn('Generated draft');

    $draft = $this->aiAssistant->draft();

    expect($draft)->toBe('Generated draft');
});

it('can translate the prompt to a specific language', function () {
    $this->aiAssistant->client($this->clientMock);
    $this->clientMock->shouldReceive('textCompletion')
        ->once()
        ->andReturn('Translated text');

    $translatedText = $this->aiAssistant->translateTo('Spanish');
    expect($translatedText)->toBe('Translated text');
});

it('can respond to chat prompts', function () {
    $this->aiAssistant->client($this->clientMock);
    $this->clientMock->shouldReceive('chatTextCompletion')
        ->once()
        ->andReturn([
        'choices' => [
            ['message' => ['content' => 'Chat response']],
        ],
    ]);

    $response = $this->aiAssistant->andRespond();
    expect($response['choices'][0]['message']['content'])->toBe('Chat response');
});

it('can process chat with custom function', function () {
    $this->aiAssistant->client($this->clientMock);

    $customFunctionData = mock(CustomFunctionData::class);
    $customFunctionData->shouldReceive('toArray')
        ->once()
        ->andReturn(['function' => 'data']);

    $this->clientMock->shouldReceive('chatTextCompletion')
        ->once()
        ->andReturn([
        'choices' => [
            ['message' => ['content' => 'Chat response with function']],
        ],
    ]);

    $response = $this->aiAssistant->withCustomFunction($customFunctionData);
    expect($response['choices'][0]['message']['content'])->toBe('Chat response with function');
});

it('can correct spelling and grammar', function () {
    $this->aiAssistant->client($this->clientMock);

    $this->clientMock->shouldReceive('chatTextCompletion')
        ->once()
        ->andReturn([
            'choices' => [
                ['message' => ['content' => 'Corrected text']]
            ]
        ]);

    $correctedText = $this->aiAssistant->spellingAndGrammarCorrection();

    expect($correctedText)->toBe('Corrected text');
});

it('can improve writing', function () {
    $this->aiAssistant->client($this->clientMock);
    $this->clientMock->shouldReceive('chatTextCompletion')
        ->once()
        ->andReturn([
            'choices' => [
                ['message' => ['content' => 'Improved text']]
            ]
        ]);

    $improvedText = $this->aiAssistant->improveWriting();

    expect($improvedText)->toBe('Improved text');
});

it('can transcribe audio to text with vfsStream', function () {
    // Set up virtual file system
    vfsStream::setup('root', null, ['testfile.mp3' => 'audio data']);
    $filePath = vfsStream::url('root/testfile.mp3');

    $this->aiAssistant = AiAssistant::acceptPrompt($filePath);
    $this->aiAssistant->client($this->clientMock);

    $this->clientMock->shouldReceive('transcribeTo')
        ->once()
        ->andReturn('Transcribed text');

    $transcription = $this->aiAssistant->transcribeTo('en', 'Optional prompt');

    expect($transcription)->toBe('Transcribed text');
});

it('can transcribe audio to text', function () {
    // Set up virtual file system
    vfsStream::setup('root', null, ['testfile.mp3' => 'audio data']);
    $filePath = vfsStream::url('root/testfile.mp3');

    $this->aiAssistant = AiAssistant::acceptPrompt($filePath);
    $this->aiAssistant->client($this->clientMock);

     $this->clientMock->shouldReceive('translateTo')
        ->once()
        ->andReturn('Transcribe audio text');

    $translatedAudio = $this->aiAssistant->translateAudioTo();
    expect($translatedAudio)->toBe('Transcribe audio text');
});

it('can process text completion', function () {
    $this->aiAssistant->client($this->clientMock);

    $this->clientMock->shouldReceive('textCompletion')
        ->once()
        ->andReturn('Completed text');

    $completedText = $this->aiAssistant->draft();
    expect($completedText)->toBe('Completed text');
});

it('can process chat text completion', function () {
    $this->aiAssistant->client($this->clientMock);

    $this->clientMock->shouldReceive('chatTextCompletion')
        ->once()
        ->andReturn([
        'choices' => [
            ['message' => ['content' => 'Completed chat']],
        ],
    ]);

    $response = $this->aiAssistant->andRespond();
    expect($response['choices'][0]['message']['content'])->toBe('Completed chat');
});

it('can instantiate AiAssistant with a prompt', function () {
    $prompt = 'Sample prompt';
    $client = mock(AssistantService::class)
        ->shouldReceive('textCompletion')
        ->andReturn('Generated Draft')
        ->getMock();

    $aiAssistant = new AiAssistant($prompt);
    $aiAssistant->client($client);

    expect($aiAssistant)
        ->toBeInstanceOf(AiAssistant::class)
        ->and($aiAssistant->draft())->toBeString();
});

it('calls streamedCompletion when stream is enabled', function () {
    $prompt = 'Streamed prompt';

    $client = mock(AssistantService::class)
        ->shouldReceive('streamedCompletion')
        ->once()
        ->andReturn('Streamed Draft')
        ->getMock();

    $aiAssistant = new AiAssistant($prompt);
    $aiAssistant->client($client);

    $reflection = new ReflectionClass($aiAssistant);
    $textGeneratorConfigProperty = $reflection->getProperty('textGeneratorConfig');
    $textGeneratorConfigProperty->setAccessible(true);
    $textGeneratorConfigProperty->setValue($aiAssistant, ['stream' => true]);

    expect($aiAssistant->draft())->toBe('Streamed Draft');
});

it('calls textCompletion when stream is disabled', function () {
    $prompt = 'Regular prompt';

    $client = mock(AssistantService::class)
        ->shouldReceive('textCompletion')
        ->once()
        ->andReturn('Regular Draft')
        ->getMock();

    $aiAssistant = new AiAssistant($prompt);
    $aiAssistant->client($client);

    $reflection = new ReflectionClass($aiAssistant);
    $textGeneratorConfigProperty = $reflection->getProperty('textGeneratorConfig');
    $textGeneratorConfigProperty->setAccessible(true);

    $config = $textGeneratorConfigProperty->getValue($aiAssistant);
    unset($config['stream']);
    $textGeneratorConfigProperty->setValue($aiAssistant, $config);

    expect($aiAssistant->draft())->toBe('Regular Draft');
});

it('returns text from the first choice when draft is called', function () {
    $client = mock(AssistantService::class)
        ->shouldReceive('textCompletion')
        ->once()
        ->andReturn('Hello, world!')
        ->getMock();

    $aiAssistant = new AiAssistant('prompt');
    $aiAssistant->client($client);
    $result = $aiAssistant->draft();

    expect($result)->toBe('Hello, world!');
});

it('handles empty choices gracefully when draft is called', function () {
    $client = mock(AssistantService::class)
        ->shouldReceive('textCompletion')
        ->once()
        ->andReturn('')
        ->getMock();

    $aiAssistant = new AiAssistant('prompt');
    $aiAssistant->client($client);

    $result = $aiAssistant->draft();

    expect($result)->toBe('');
});

/* it('can create a new assistant', function () {
    $assistantMock = Mockery::mock('alias:CreativeCrafts\LaravelAiAssistant\Assistant');
    $newAssistantResponseMock = Mockery::mock(NewAssistantResponseData::class);

    $assistantMock->shouldReceive('init')
        ->andReturn($assistantMock);

    $assistantMock->shouldReceive('new')
        ->andReturn($assistantMock);

    $assistantMock->shouldReceive('client')
        ->andReturn($assistantMock);

    $assistantMock->shouldReceive('setModelName')
        ->with('gpt-4o')
        ->andReturn($assistantMock);

    $assistantMock->shouldReceive('setAssistantName')
        ->with('Test Assistant')
        ->andReturn($assistantMock);

    $assistantMock->shouldReceive('setAssistantDescription')
        ->with('This is a test assistant')
        ->andReturn($assistantMock);

    $assistantMock->shouldReceive('setInstructions')
        ->with('You can ask me anything')
        ->andReturn($assistantMock);

    $assistantMock->shouldReceive('create')
        ->andReturn($newAssistantResponseMock);

    $createdAssistant = AiAssistant::init($this->clientMock)
        ->setModelName('gpt-4o')
        ->setAssistantName('Test Assistant')
        ->setAssistantDescription('This is a test assistant')
        ->setInstructions('You can ask me anything')
        ->create();

    expect($createdAssistant)->toBeInstanceOf(NewAssistantResponseData::class);
});

it('can use an assistant to complete a task', function () {
    $assistantMock = Mockery::mock('alias:CreativeCrafts\LaravelAiAssistant\Assistant');

    $assistantMock->shouldReceive('init')
        ->andReturn($assistantMock);

    $assistantMock->shouldReceive('new')
        ->andReturn($assistantMock);

    $assistantMock->shouldReceive('client')
        ->andReturn($assistantMock);

    $assistantMock->shouldReceive('assignAssistant')
        ->andReturn($assistantMock);

    $assistantMock->shouldReceive('createTask')
        ->andReturn($assistantMock);

    $assistantMock->shouldReceive('askQuestion')
        ->with('Is the world round?')
        ->andReturn($assistantMock);

    $assistantMock->shouldReceive('process')
        ->andReturn($assistantMock);

    $assistantMock->shouldReceive('response')
        ->andReturn('Yes, the world is round');

    $response = AiAssistant::init($this->clientMock)
        ->assignAssistant('assistant-123')
        ->createTask()
        ->askQuestion('Is the world round?')
        ->process()
        ->response();
    expect($response)->toBe('Yes, the world is round');
});*/
