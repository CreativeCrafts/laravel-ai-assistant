# Changelog

All notable changes to `laravel-ai-assistant` will be documented in this file.

## 1.1.0 - 2023-09-01

- Added a feature that allow function call in chat

## 1.0.0 - 2023-05-24

updated openai composer package

## 0.1.9 - 2023-05-24

updated composer packages

## 0.1.8 - 2023-05-24

updated dependent composer packages

## 0.0.5 - 2023-05-15

- updated dependencies

## 0.0.1 - 2023-05-11

- initial release
- features implemented are:
- - Translation
- - Brainstorming ideas
- - Chat
-

## 0.0.2 - 2023-05-11

- Added a draft functionality. This will allow the user to brainstorm ideas such as asking the AI to write a blog about a subject.

## 0.0.3 - 2023-05-14

- Added text edit functionlity. This will allow the user to do spell check, grammar check, and other text editing features.
- clean up code

## 0.0.4 - 2023-05-15

- Updated dependencies

## 0.0.5 - 2023-05-15

- Updated more dependencies

## 0.0.6 - 2023-05-15

- Added Mock test for translation and draft methods

## 0.0.7 - 2023-05-23

- Added method to transcribe and translate audio files

## 1.2.0 - 2024-03-18

- Added support for Laravel 11
- Removed support for php8.1

## 1.3.0 - 2024-10-05
 - Replaced the deprecated /v1/edits endpoint with the chat completion endpoint in the TextEditCompletion class.
 - Updated the configuration to use the chat model for text editing tasks. 
   - both first time contributions by @AlvinCoded

## 2.0.0 - 2024-10-07
   - Refactored the code base to use the new Assistant service class
     - Added AssistantService Methods
      •	createAssistant(array $parameters): AssistantResponse: Creates a new assistant with the given parameters.
      •	getAssistantViaId(string $assistantId): AssistantResponse: Retrieves an assistant by ID.
      •	createThread(array $parameters): ThreadResponse: Creates a new thread for interactions.
      •	writeMessage(string $threadId, array $messageData): ThreadMessageResponse: Sends a message to the assistant.
      •	runMessageThread(string $threadId, array $messageData): bool: Runs a message thread for processing.
      •	listMessages(string $threadId): string: Retrieves a list of messages from the thread.
      •	textCompletion(array $payload): string: Gets a text completion response.
      •	streamedCompletion(array $payload): string: Gets a streamed completion response.
      •	chatTextCompletion(array $payload): array: Handles chat-based text completion.
      •	streamedChat(array $payload): array: Handles streamed chat responses.
      •	transcribeTo(array $payload): string: Transcribes audio to text.
      •	translateTo(array $payload): string: Translates audio to a specific language.

   - Added Assistant methods
	•	new(): Assistant: Returns a new instance of the Assistant class.
	•	client(AssistantService $client): Assistant: Sets the AssistantService client for API requests.
	•	setModelName(string $modelName): Assistant: Sets the model name for the AI assistant.
	•	adjustTemperature(int|float $temperature): Assistant: Adjusts the assistant’s response temperature.
	•	setAssistantName(string $assistantName): Assistant: Sets the name for the assistant.
	•	setAssistantDescription(string $assistantDescription): Assistant: Sets the assistant’s description.
	•	setInstructions(string $instructions): Assistant: Sets instructions for the assistant.
	•	includeCodeInterpreterTool(array $fileIds = []): Assistant: Adds the code interpreter tool to the assistant.
	•	includeFileSearchTool(array $vectorStoreIds = []): Assistant: Adds the file search tool to the assistant.
	•	includeFunctionCallTool(...): Assistant: Adds a function call tool to the assistant.
	•	create(): NewAssistantResponseData: Creates the assistant using the specified configurations.
	•	assignAssistant(string $assistantId): Assistant: Assigns an existing assistant by ID.
	•	createTask(array $parameters = []): Assistant: Creates a new task thread for interactions.
	•	askQuestion(string $message): Assistant: Asks a question in the task thread.
	•	process(): Assistant: Processes the task thread.
	•	response(): string: Retrieves the assistant’s response.
   - Added AssistantMessageData DTO
   - Added NewAssistantResponseData DTO
   - Added FunctionCalData DTO
   - Updated all tests to reflect the new changes
   - Update all test coverage and mutation to 100%

## 2.0.1 - 2025-02-11
### Changed
- Updated composer dependencies with package version adjustments
- Minor documentation updates in changelog

## 2.0.2 - 2025-02-11
### Fixed
- Fixed create assistant functionality
- Updated test cases in AiAssistantTest.php

## 2.0.3 - 2025-02-11
### Changed
- Move transcription logic to Assistant class
- Deprecate transcribeTo method in AiAssistant class with warning
- Add new transcription functionality to Assistant class
- Implement setFilePath method for better file handling
- Add robust file handling with error checking
- Improve code organization and maintainability

The change moves the transcription functionality to a more appropriate
location while maintaining backward compatibility through a deprecation
notice. This improves the overall architecture and provides better
error handling for file operations.
