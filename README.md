# Laravel AI Assistant

[![Latest Version on Packagist](https://img.shields.io/packagist/v/creativecrafts/laravel-ai-assistant.svg?style=flat-square)](https://packagist.org/packages/creativecrafts/laravel-ai-assistant)
[![GitHub Tests Action Status](https://img.shields.io/github/actions/workflow/status/creativecrafts/laravel-ai-assistant/run-tests.yml?branch=main&label=tests&style=flat-square)](https://github.com/creativecrafts/laravel-ai-assistant/actions?query=workflow%3Arun-tests+branch%3Amain)
[![GitHub Code Style Action Status](https://img.shields.io/github/actions/workflow/status/creativecrafts/laravel-ai-assistant/fix-php-code-style-issues.yml?branch=main&label=code%20style&style=flat-square)](https://github.com/creativecrafts/laravel-ai-assistant/actions?query=workflow%3A"Fix+PHP+code+style+issues"+branch%3Amain)
[![Total Downloads](https://img.shields.io/packagist/dt/creativecrafts/laravel-ai-assistant.svg?style=flat-square)](https://packagist.org/packages/creativecrafts/laravel-ai-assistant)

Laravel AI Assistant is a comprehensive package designed to seamlessly integrate OpenAI’s powerful language models into your Laravel applications. It provides an easy-to-use, fluent API to configure, manage, and interact with AI-driven chatbots, audio transcription services, custom function calls, and additional advanced tools.

---

## Table of Contents

- [Overview](#overview)
- [Features](#features)
- [Installation](#installation)
- [Configuration](#configuration)
- [Usage](#usage)
  - [Initializing the Assistant](#initializing-the-assistant)
  - [Configuring Chat and Task Settings](#configuring-chat-and-task-settings)
  - [Audio Transcription](#audio-transcription)
  - [Tool Integrations](#tool-integrations)
- [Data Transfer Objects (DTOs) and Factories](#data-transfer-objects-dtos-and-factories)
- [Configuration File](#configuration-file)
- [Error Handling](#error-handling)
- [Examples](#examples)
- [Contributing](#contributing)
- [License](#license)

---

## Overview

Laravel AI Assistant simplifies the integration of AI models into your Laravel application. Whether you’re building a conversational chatbot, automating content creation, or transcribing audio files, this package provides a clean, expressive API to handle complex tasks with minimal effort.

It leverages OpenAI’s API to:
- Generate chat completions
- Process audio transcriptions using Whisper
- Manage custom function calls for dynamic workflows
- Support advanced configurations like streaming, caching, and tool integrations

---

## Features

- **Fluent API:** Chain method calls for a clean and intuitive setup.
- **Chat Messaging:** Easily manage user, developer, and tool messages.
- **Audio Transcription:** Convert audio files to text with optional prompts.
- **Tool Integration:** Extend functionality with file search, code interpreter, and custom function call tools.
- **Custom Configuration:** Configure model parameters, temperature, top_p, and more.
- **DTOs & Factories:** Use data transfer objects to structure and validate your data.
- **Error Handling:** Robust exception handling for file operations and API interactions.
- **Caching & Streaming:** Optimize chat workflows with caching and streaming options.

---

## Installation

You can install the package via composer:

```bash
composer require creativecrafts/laravel-ai-assistant
```

You can publish the config file with:

```bash
php artisan vendor:publish --tag="ai-assistant-config"
```

## Configuration
After publishing, you will find a configuration file at config/ai-assistant.php. This file includes settings for:

### API Credentials
Set your OpenAI API key and organization:

```php
    'api_key' => env('OPENAI_API_KEY', null),
    'organization' => env('OPENAI_ORGANIZATION', null),
```

### Model Settings
Choose your default models for chat, editing, and audio transcription:

```php
    'model' => env('OPENAI_CHAT_MODEL', 'gpt-3.5-turbo'),
    'chat_model' => env('OPENAI_CHAT_MODEL', 'gpt-3.5-turbo'),
    'edit_model' => 'gpt-4o',
    'audio_model' => 'whisper-1',
```

### Response Settings
Configure parameters such as temperature, top_p, max tokens, streaming options, stop sequences, etc.:

```php
    'temperature' => 0.3,
    'top_p' => 1,
    'max_completion_tokens' => 400,
    'stream' => false,
    'n' => 1,
    'stop' => null,
    'suffix' => null,
    'presence_penalty' => 0,
    'frequency_penalty' => 0,
    'best_of' => 1,
```

### Roles
Define the roles for AI responses and user messages:

```php
    'ai_role' => 'assistant',
    'user_role' => 'user',
```

## Usage

### Initializing the Assistant
Create a new assistant instance using the fluent API:
```php
    use CreativeCrafts\LaravelAiAssistant\Tasks\AiAssistant;
    $assistant = AiAssistant::init();
```

### Configuring Chat and Task Settings
Configure the model, temperature, and other parameters:
```php
    $assistant->setModelName('gpt-3.5-turbo')
          ->adjustTemperature(0.7)
          ->setDeveloperMessage('Please maintain a friendly tone.')
          ->setUserMessage('What is the weather like today?');
```
Send your chat message and retrieve a response:
```php
    $response = $assistant->sendChatMessage();
```
You can also manage caching of chat messages, adjust the maximum number of tokens, or define stop sequences using dedicated methods.

### Audio Transcription
Transcribe audio files by setting the file path and specifying language and an optional prompt:
```php
    $assistant->setFilePath('/path/to/audio.mp3');
    $transcription = $assistant->transcribeTo('en', 'Transcribe the following audio:');
```

### Tool Integrations
Integrate additional tools like custom function calls, code interpreter, or file search:

```php
    // Adding a custom function tool
    $assistant->includeFunctionCallTool(
        'calculateSum',
        'Calculates the sum of two numbers',
        ['num1' => 'number', 'num2' => 'number'],
        isStrict: true,
        requiredParameters: ['num1', 'num2']
    );
```

## Data Transfer Objects (DTOs) and Factories
The package includes several DTOs and factory classes to structure data consistently:
	•	CreateAssistantData: Used when creating a new assistant.
	•	ChatCompletionData: Represents data for chat completions.
	•	ChatAssistantMessageData: Structures assistant messages.
	•	TranscribeToData: Structures data for audio transcription requests.
	•	CustomFunctionData: Represents custom function tool data.

Each DTO includes a toArray() method to facilitate easy conversion and integration with the API.

This is the contents of the published config file:

## Configuration File
The config/ai-assistant.php file allows you to customize settings such as API credentials, model names, temperature, top_p, and more. Adjust these settings to fit your use case:
```php
    return [
        'api_key' => env('OPENAI_API_KEY', null),
        'organization' => env('OPENAI_ORGANIZATION', null),
        'model' => env('OPENAI_CHAT_MODEL', 'gpt-3.5-turbo'),
        'temperature' => 0.3,
        'top_p' => 1,
        'max_completion_tokens' => 400,
        'stream' => false,
        'n' => 1,
        'stop' => null,
        'chat_model' => env('OPENAI_CHAT_MODEL', 'gpt-3.5-turbo'),
        'ai_role' => 'assistant',
        'user_role' => 'user',
        'edit_model' => 'gpt-4o',
        'audio_model' => 'whisper-1',
    ];
```
## Error Handling
The package provides robust error handling:
	•	File Operations:
Methods like openFile() throw a RuntimeException if the file cannot be opened.
	•	API Interactions:
The create() method catches exceptions from OpenAI and rethrows them as a CreateNewAssistantException with a descriptive message.
	•	Validation:
Methods that require specific parameters (e.g., audio voice and format for audio output) throw an InvalidArgumentException when expectations are not met.

## Examples

### Example: Simple Chat Interaction
```php
    use CreativeCrafts\LaravelAiAssistant\AiAssistant;

    $assistant = AiAssistant::init()
        ->setModelName('gpt-3.5-turbo')
        ->adjustTemperature(0.7)
        ->setDeveloperMessage('Maintain a formal tone.')
        ->setUserMessage('Tell me a joke.')
        ->sendChatMessage();
```

### Example: Audio Transcription
```php
    use CreativeCrafts\LaravelAiAssistant\AiAssistant;
    
    $transcription = AiAssistant::init()
        ->setFilePath('/path/to/audio.mp3');
        ->transcribeTo('en', 'Transcribe this audio:');
```

### Example: Custom Function Call
```php
    use CreativeCrafts\LaravelAiAssistant\AiAssistant;
    
    $response = AiAssistant::init()
        ->includeFunctionCallTool(
            'calculateSum',
            'Calculates the sum of two numbers',
            ['num1' => 'number', 'num2' => 'number'],
            isStrict: true,
            requiredParameters: ['num1', 'num2']
        )
        ->create();
```

### Example: Creating and Configuring an AI Assistant
```php
use CreativeCrafts\LaravelAiAssistant\AiAssistant;

$assistant = AiAssistant::init()
    ->setModelName('gpt-4') // Optional, defaults to config default model
    ->adjustTemperature(0.5) // Optional defaults to 0.7
    ->setAssistantName('My Assistant') // Optional, defaults to '' and Open Ai will assign random name
    ->setAssistantDescription('An assistant for handling tasks') // Optional, defaults to ''
    ->setInstructions('Be as helpful as possible.') // Optional, defaults to ''
    ->create();
```

### Example: Interacting with the Assistant
```php
    use CreativeCrafts\LaravelAiAssistant\AiAssistant;
    
    $response = \CreativeCrafts\LaravelAiAssistant\AiAssistant::init()
        ->setAssistantId($assistantId) // Required
        ->createTask() // Can optionally pass a list of tasks as an array, defaults to []
        ->askQuestion('Translate this text to French: "Hello, how are you?"')
        ->process()
        ->response(); // returns the response from the assistant as a string
```

## Newly Added Features

	•	Create and manage AI assistants.
	•	Set models, temperature, and custom instructions for the assistant.
	•	Utilize tools like code interpretation, file search, and custom function calls.
	•	Interact with assistants via threads for tasks like message completion, chat, and task processing.
	•	Supports both synchronous and streamed completions.
	•	Handle audio transcription and translation with OpenAI models.

## Available Methods
      init(): Assistant: Initializes the AI assistant.
    • setModelName(string $modelName): Assistant: Sets the model name for the AI assistant.
	• adjustTemperature(int|float $temperature): Assistant: Adjusts the assistant’s response temperature.
	• setAssistantName(string $assistantName): Assistant: Sets the name for the assistant.
	• setAssistantDescription(string $assistantDescription): Assistant: Sets the assistant’s description.
	• setInstructions(string $instructions): Assistant: Sets instructions for the assistant.
	• includeCodeInterpreterTool(array $fileIds = []): Assistant: Adds the code interpreter tool to the assistant.
	• includeFileSearchTool(array $vectorStoreIds = []): Assistant: Adds the file search tool to the assistant.
	• includeFunctionCallTool(...): Assistant: Adds a function call tool to the assistant.
	• create(): NewAssistantResponseData: Creates the assistant using the specified configurations.
	• assignAssistant(string $assistantId): Assistant: Assigns an existing assistant by ID.
	• createTask(array $parameters = []): Assistant: Creates a new task thread for interactions.
	• askQuestion(string $message): Assistant: Asks a question in the task thread.
	• process(): Assistant: Processes the task thread.
	• response(): string: Retrieves the assistant’s response.
    . setAssistantId(string $assistantId): Assistant: Sets the assistant ID for the current interaction.    

## Upcoming features

    • Create and upload files for code interpretation, then assign it to a specific assistant.
    • Create and upload vector store, then attach the vector store ids to an assistant.
    • Get a list of all created assistant.
    • Get a list of all created files.
    • Get a list of all created vector stores.
    • Update assistant.
    • Delete assistant.

If you have any feature requests or suggestions, please feel free to open an issue or submit a pull request.

### Compatibility

This fork is compatible with OpenAI API as of August 2024. It uses the gpt-3.5-turbo model by default, but you can specify gpt-4 or other available models in your configuration if you have access to them.

## Add to .env

```bash
OPENAI_API_KEY=
OPENAI_ORGANIZATION=
OPENAI_CHAT_MODEL=
```

## Testing
The package now has 100% code and mutation test coverage. You can run the tests using the following command:

```bash
composer test
```

## Changelog

Please see [CHANGELOG](CHANGELOG.md) for more information on what has changed recently.

## Contributing

Please see [CONTRIBUTING](CONTRIBUTING.md) for details.

## Security Vulnerabilities

Please review [our security policy](../../security/policy) on how to report security vulnerabilities.

## Credits

- [Godspower Oduose](https://github.com/rockblings)
- [All Contributors](../../contributors)

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.
