# Changelog

All notable changes to `laravel-ai-assistant` will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [3.0.16-beta] - 2025-09-10

feat(config): add skip-able config validation; default to skip in dev/test

• Gate validateConfiguration() behind shouldSkipValidation()
• Add shouldSkipValidation() checking:
• AI_ASSISTANT_SKIP_VALIDATION constant
• env: GITHUB_ACTIONS, CI, SKIP_AI_ASSISTANT_CONFIG_VALIDATION
• config('ai-assistant.validation.skip')
• Apply environment overlays before validation
• Set validation.skip=true in development and testing overlays
• Minor doc/grammar tweaks and helper reorganisation (no behaviour change)

This allows CI and explicit overrides to bypass strict config validation while keeping production-safe defaults.

## [3.0.15-beta] - 2025-09-10

feat: add streaming responses, install command, and webhook signature verification

• Introduce StreamedAiResponse and Blade stream component for real-time AI output
• Add VerifyAiWebhookSignature middleware to secure inbound webhooks
• Add InstallCommand to simplify package setup
• Provide example stubs: StreamingController and routes
• Update ChatSession flow and OpenAI compat aliases
• Expand ModelConfigDataFactory and update corresponding tests
• Refresh config, composer.json, .gitattributes, and README
• Remove obsolete SCALING_VERIFICATION_REPORT.md

## [3.0.14-beta] - 2025-09-09

fix(data-factory): set temp=1.0 for gpt-5/o3/o4 models; make modalities optional

- Read model/temperature from config and force temperature=1.0 for reasoning-capable models
  (gpt-5, o3, o4) to comply with model constraints.
- Only include outputTypes when the `modalities` key is provided; otherwise pass null to
  avoid sending an invalid parameter.

File: src/DataFactories/ModelConfigDataFactory.php

## [3.0.13-beta] - 2025-09-09

feat (data-factory): support array-based response_format with json_schema options and stricter validation

- Accept an array form for response_format with { type, json_schema } and validate required fields
- Support json_schema { name, schema?, strict? } and return standardised structure
- Normalise validation and error messages; minor style tidy-ups (spacing, docblocks)
- Clear cached config when appropriate
- Add idempotency key generation, content-type–aware decode, refined error detail assembly
- Group retry helpers, reintroduce SSE timeout, and tidy imports/formatting

## [3.0.12-beta] - 2025-09-09

feat: Improve GuzzleOpenAITransport: robust decode, idempotency key

• Add generateIdempotencyKey() with secure random_bytes and layered fallbacks
• Make decodeOrFail() content-type aware (handles text/plain) and validate JSON with JSON_THROW_ON_ERROR
• Move endpoint() and delete() earlier; group retry helpers; reintroduce resolveSseTimeout()
• Fix error detail assembly to only include fields when present
• Tidy imports and minor formatting for readability

## [3.0.11-beta] - 2025-09-08

feat: add OpenAI transport layer and refactor clients/repositories

• Introduce OpenAITransport interface and GuzzleOpenAITransport implementation
• Unified JSON, multipart, DELETE and SSE streaming helpers
• Built-in retries with exponential backoff and optional jitter
• Idempotency-Key handling (configurable) and consistent error normalization
• Centralized timeout resolution
• Wire Compat OpenAI Client to real HTTP via transport
• Keep legacy constructor signature for BC (HttpClientInterface arg ignored)
• Support API key, organization header, base URI and per-call timeouts
• Add Compat OpenAI resources backed by transport
• Assistants, Chat (incl. streamed responses), Completions
• Threads (+messages, +runs), Audio (transcribe/translate)
• Refactor HTTP repositories to delegate network I/O to transport
• Conversations: POST/GET/DELETE now via transport; simpler list query building
• Responses: create/stream/get/cancel/delete now via transport; remove duplicated retry/decoder logic
• Files: upload/retrieve/delete via transport; safer file handling and resource cleanup on errors
• Update AppConfig to instantiate the compat Client with configured API key/org/timeout
• Remove stray phpunit.xml.dist.bak

## [3.0.5-beta] - 2025-09-05

"feat: make OpenAI SDK optional, add Compat aliases; default file purpose to "assistants"

• Move openai-php/client from required dependency to a suggested package
• Provide internal Compat classes with class_alias mappings so common OpenAI\Client and response types resolve when the SDK isn’t installed
• Expand alias coverage (Client, Chat, Completions incl. streaming, Audio, Meta, StreamResponse, Threads messages/runs)
• Align file upload defaults with OpenAI Files API
• Change default purpose from "assistants/answers" to "assistants"
• Validate and normalize purposes; allow: assistants, batch, fine-tune, vision, user_data
• Propagate purpose parameter through AssistantService, AiAssistant, FilesHelper, Http repository, and tests
• Internal refactors and polish
• Add and normalize endpoint() helpers in HTTP repositories
• Minor CS tweaks (casts/spacing), improved docblocks, consistent timeout casting
• Docs: update README to explain optional SDK usage and client behavior

Files changed: README.md, composer.json, src/Compat/OpenAI/aliases.php, src/AiAssistant.php, src/Services/AssistantService.php, src/Support/FilesHelper.php, src/Contracts/FilesRepositoryContract.php,
src/Repositories/Http/{ConversationsHttpRepository,FilesHttpRepository, ResponsesHttpRepository}.php, tests/Fakes/FakeFilesRepository.php

Note: No breaking API changes; added optional $purpose params have sensible defaults.

## [3.0.0-beta] - 2025-09-03

### 🚀 Major Release—Complete Architecture Overhaul (Not production ready)

This is a major release featuring a complete architectural redesign, enhanced performance, security improvements, and extensive new functionality.
**This release contains breaking changes**—please see [UPGRADE.md](UPGRADE.md) for detailed migration instructions.

### ✨ Added

#### Core Architecture & Services

- **New Service Architecture**: Complete refactor with unified `AssistantService` and improved service layer
- **OpenAI Compatibility Layer**: Full compatibility layer (`src/Compat/OpenAI/`) for seamless OpenAI SDK integration
- **Environment-Specific Configurations**: New environment overlay system (`config/environments/`)
    - `config/environments/development.php` - Development optimized settings
    - `config/environments/testing.php` - Test environment with mocks and isolation
    - `config/environments/production.php` - Production optimized settings
- **Configuration Presets**: Pre-configured templates for different use cases
    - `config/presets/simple.php` - Basic setup for simple implementations
    - `config/presets/advanced.php` - Advanced features and optimizations
    - `config/presets/production.php` - Production-ready configuration

#### Performance & Scaling Features

- **Connection Pooling**: Advanced HTTP connection pooling with Guzzle and CurlMultiHandler
- **Streaming Support**: Enhanced Server-Sent Events (SSE) streaming capabilities
- **Memory Monitoring**: Built-in memory usage monitoring and optimization
- **Metrics Collection**: Comprehensive metrics and monitoring system
- **Background Job Support**: Queue-based processing for tool calls and heavy operations
- **Response Caching**: Intelligent response caching system

#### Security & Reliability

- **Webhook Security**: Secure webhook handling with signature verification
- **Input Validation**: Enhanced configuration validation at boot time
- **Error Reporting**: Advanced error reporting and logging system
- **Retry Mechanisms**: Configurable retry logic with exponential backoff and jitter
- **Security Verification**: Added comprehensive security verification system

#### Storage & Persistence

- **Dual Persistence System**: Choice between in-memory and Eloquent storage drivers
- **Eloquent Storage**: Complete Eloquent-based storage implementation
    - `EloquentAssistantsStore`
    - `EloquentConversationsStore`
    - `EloquentConversationItemsStore`
    - `EloquentResponsesStore`
    - `EloquentToolInvocationsStore`
- **Migration System**: Automated database migrations for Eloquent storage
- **Model Stubs**: Publishable Eloquent model stubs for customization

#### Tool Calling & Function Integration

- **Advanced Tool Calling**: Enhanced tool calling system with parallel execution support
- **Tool Registry**: Centralized tool management and execution
- **Sync/Queue Execution**: Choose between synchronous or queued tool execution
- **Tool Invocation Tracking**: Complete audit trail for tool calls

#### Developer Experience

- **Fluent API**: Redesigned fluent interface with method chaining
- **Strong Typing**: Data Transfer Objects (DTOs) throughout the system
- **Better Testing**: Enhanced test suite with environment-specific testing
- **Documentation**: Comprehensive documentation including:
    - `ARCHITECTURE.md` - System architecture overview
    - `CODEMAP.md` - Code navigation guide
    - `ENVIRONMENT_VARIABLES.md` - Configuration reference
    - `FEATURE_TOGGLES.md` - Feature flag documentation
    - `PERFORMANCE_TUNING.md` - Performance optimization guide
    - `PRODUCTION_CONFIGURATION.md` - Production deployment guide
    - `SCALING.md` - Scaling strategies and best practices

### 🔧 Changed

#### Breaking Changes

- **Service Instantiation**: `AiAssistant` class replaced with fluent `Assistant::new()` pattern
- **Method Signatures**: Standardized method signatures across all services
- **Data Structures**: Raw arrays replaced with strongly typed DTOs
- **Configuration**: New configuration structure with environment overlays
- **Dependencies**: Remove OpenAI PHP client (^0.10) and adopt a custom compatibility layer

#### Improvements

- **Performance**: Significant performance improvements through connection pooling and caching
- **Memory Usage**: Optimized memory consumption with monitoring and cleanup
- **Error Handling**: Enhanced error handling with detailed error reporting
- **Logging**: Improved logging system with structured logging support
- **Code Quality**: Enhanced static analysis, formatting, and testing

### 🏗️ Infrastructure

#### New Scripts & Tools

- `composer quality` - Run all quality checks
- `composer ci` - Continuous integration checks
- `composer security-audit` - Security vulnerability scanning
- `composer check-deps` - Check for outdated dependencies
- `composer validate-composer` - Validate composer.json structure

#### Testing Enhancements

- **Mutation Testing**: Added infection for mutation testing
- **Architecture Tests**: Pest architecture testing plugin
- **Performance Tests**: Dedicated performance testing suite
- **Integration Tests**: Comprehensive integration test coverage

### 📚 Documentation

#### New Documentation Files

- `CONTRIBUTING.md` - Contribution guidelines
- `UPGRADE.md` - Detailed upgrade instructions from 1.x/2.x to 3.0
- `SCALING_VERIFICATION_REPORT.md` - Performance and scaling verification
- `SECURITY_VERIFICATION_REPORT.md` - Security assessment report

### 🛡️ Security

- Enhanced API key validation and management
- Secure webhook signature verification
- Input sanitization and validation improvements
- Security audit integration in CI/CD pipeline
- Dependency vulnerability scanning with Roave Security Advisories

### ⚡ Performance

- Connection pooling reduces HTTP overhead
- In-memory caching for frequently accessed data
- Optimized database queries in Eloquent storage
- Background job processing for heavy operations
- Memory usage monitoring and optimization

### 🔄 Migration Guide

**Upgrading from 2.1.x:**

1. **Update Dependencies:**
   ```bash
   composer update creativecrafts/laravel-ai-assistant
   ```

2. **Update Service Usage:**
   ```php
   // Before (2.1.x)
   $assistant = app(AiAssistant::class);
   
   // After (3.0.0)
   $assistant = Assistant::new();
   ```

3. **Update Configuration:**
   ```bash
   # Republish configuration files
   php artisan vendor:publish --tag="laravel-ai-assistant-config" --force
   php artisan vendor:publish --tag="ai-assistant-migrations" --force
   php artisan vendor:publish --tag="ai-assistant-models" --force
   ```

4. **Update Method Calls:**
   ```php
   // Before (2.1.x)
   $response = $assistant->createAssistant([...]);
   
   // After (3.0.0)
   $response = $assistant->setAssistantName('Name')->create();
   ```

For complete migration instructions, see [UPGRADE.md](UPGRADE.md).

### 📋 Notes

- **Minimum Requirements**: PHP 8.2+, Laravel 10.0+
- **Recommended**: Use environment-specific configurations for optimal performance
- **Testing**: All tests pass with 362 test cases covering new functionality
- **Backward Compatibility**: Breaking changes require code updates (see UPGRADE.md)

This release represents months of development work focused on performance, reliability, security, and developer experience. The new architecture provides a solid foundation for future enhancements
while maintaining the ease of use.

## [2.1.8] - 2025-04-30

### Fixed

- Issue with the Create Assistant DTO including items that are not provided in the constructor
- Optionally include reasoning effort, metadata, tools, and tool resources in the Create Assistant DTO

## [2.1.7] - 2025-04-29

### Fixed

- Resolve issue when creating an assistant and attaching a search file without using a reasoning model throws invalid value exception
- Added validation to ensure that the reasoning model is used when attaching a search file and reasoning effort is not null

### Changed

- Updated composer dependencies

### Added

- New test cases to ensure the correct behavior of the assistant creation process

## [2.1.6] - 2025-04-29

### Fixed

- Resolve issue when creating an assistant and attaching a search file without using a reasoning model throws invalid value exception
- Added validation to ensure that the reasoning model is used when attaching a search file and reasoning effort is not null

### Changed

- Updated composer dependencies

## [2.1.5] - 2025-04-01

### Fixed

- Resolve the issue with setResponseFormat method in Assistant class
- setResponseFormat method now correctly handles array and string input
- Improved error handling for unsupported formats

### Changed

- Updated composer dependencies

## [2.1.4] - 2025-03-03

### Changed

- Updated dependencies

## [2.1.3] - 2025-03-03

### Added

- Laravel 12 support

## [2.1.2] - 2025-02-18

### Fixed

- Issue with message data formatting

## [2.1.1] - 2025-02-18

### Added

- Comprehensive PHPDoc documentation for Assistant interface
- New methods for configuration and customization:
    - setOutputTypes
    - shouldStream
    - setTopP
    - addAStop
    - shouldCacheChatMessages
- Enhanced existing methods with detailed documentation
- Extended MessageData to support array input type

### Changed

- Update method signatures to support array input for messages
- Improve method parameter types and return type declarations

### Deprecated

- processTextCompletion method (with deprecation notice)

## [2.1.0] - 2025-02-17

### Added

- Moved transcription logic to Assistant class for better organization and maintainability

### Fixed

- Fixed create assistant functionality issues
- Updated composer dependencies

### Changed

- Updated changelog documentation

### Refactored

- Restructured chat completion implementation
- Add new contracts for chat completion and assistant message handling
- Introduce dedicated data factories for message and model configuration
- Reorganize data transfer objects for better separation of concerns
- Implement new tests for data factories and DTOs
- Update existing services and core classes to support new structure

## [2.0.3] - 2025-02-11

### Changed

- Move transcription logic to Assistant class
- Add new transcription functionality to Assistant class
- Implement setFilePath method for better file handling
- Add robust file handling with error checking
- Improve code organization and maintainability

### Deprecated

- transcribeTo method in AiAssistant class with warning

### Note

The change moves the transcription functionality to a more appropriate location while maintaining backward compatibility through a deprecation notice.

## [2.0.2] - 2025-02-11

### Fixed

- Fixed create assistant functionality
- Updated test cases in AiAssistantTest.php

## [2.0.1] - 2025-02-11

### Changed

- Updated composer dependencies with package version adjustments
- Minor documentation updates in changelog

## [2.0.0] - 2025-02-07

### BREAKING CHANGES

- Complete refactor of the codebase to use the new Assistant service architecture
- Replaced legacy service methods with new AssistantService implementation

### Added

- AssistantService Methods:
    - `createAssistant(array $parameters): AssistantResponse`
    - `getAssistantViaId(string $assistantId): AssistantResponse`
    - `createThread(array $parameters): ThreadResponse`
    - `writeMessage(string $threadId, array $messageData): ThreadMessageResponse`
    - `runMessageThread(string $threadId, array $messageData): bool`
    - `listMessages(string $threadId): string`
    - `textCompletion(array $payload): string`
    - `streamedCompletion(array $payload): string`
    - `chatTextCompletion(array $payload): array`
    - `streamedChat(array $payload): array`
    - `transcribeTo(array $payload): string`
    - `translateTo(array $payload): string`
- Assistant Methods:
    - `new(): Assistant`
    - `client(AssistantService $client): Assistant`
    - `setModelName(string $modelName): Assistant`
    - `adjustTemperature(int|float $temperature): Assistant`
    - `setAssistantName(string $assistantName): Assistant`
    - `setAssistantDescription(string $assistantDescription): Assistant`
    - `setInstructions(string $instructions): Assistant`
    - `includeCodeInterpreterTool(array $fileIds = []): Assistant`
    - `includeFileSearchTool(array $vectorStoreIds = []): Assistant`
    - `includeFunctionCallTool(...): Assistant`
    - `create(): NewAssistantResponseData`
    - `assignAssistant(string $assistantId): Assistant`
    - `createTask(array $parameters = []): Assistant`
    - `askQuestion(string $message): Assistant`
    - `process(): Assistant`
    - `response(): string`
- New Data Transfer Objects:
    - AssistantMessageData DTO
    - NewAssistantResponseData DTO
    - FunctionCalData DTO

### Changed

- Updated all tests to reflect the new changes
- Update all test coverage and mutation to 100%

## [1.3.0] - 2024-10-05

### Changed

- Replaced the deprecated /v1/edits endpoint with the chat completion endpoint in the TextEditCompletion class
- Updated the configuration to use the chat model for text editing tasks

### Note

Both first time contributions by @AlvinCoded

## [1.2.0] - 2024-03-18

### Added

- Support for Laravel 11

### Removed

- Support for PHP 8.1

## [1.1.0] - 2023-09-01

### Added

- Feature that allows function call in chat

## [1.0.0] - 2023-05-24

### Changed

- Updated OpenAI composer package

## [0.1.9] - 2023-05-24

### Changed

- Updated composer packages

## [0.1.8] - 2023-05-24

### Changed

- Updated dependent composer packages

## [0.0.7] - 2023-05-23

### Added

- Method to transcribe and translate audio files

## [0.0.6] - 2023-05-15

### Added

- Mock test for translation and draft methods

## [0.0.5] - 2023-05-15

### Changed

- Updated more dependencies

## [0.0.4] - 2023-05-15

### Changed

- Updated dependencies

## [0.0.3] - 2023-05-14

### Added

- Text edit functionality for spell check, grammar check, and other text editing features

### Changed

- Clean up code

## [0.0.2] - 2023-05-11

### Added

- Draft functionality for brainstorming ideas (e.g., asking the AI to write a blog about a subject)

## [0.0.1] - 2023-05-11

### Added

- Initial release
- Translation functionality
- Brainstorming ideas functionality
- Chat functionality

## Upgrade Guide

### Upgrading from 1.x to 2.0

Version 2.0 introduces significant breaking changes with the new Assistant service architecture.

#### Breaking Changes:

1. **Service Architecture**: Complete refactor to use AssistantService instead of legacy methods
2. **Method Signatures**: Many method signatures have changed
3. **Data Transfer Objects**: New DTOs replace previous data structures

#### Migration Steps:

1. Update your code to use the new AssistantService methods
2. Replace legacy service calls with corresponding AssistantService methods
3. Update any custom DTOs to use the new data structures
4. Run tests to ensure compatibility

#### Before (1.x):

```php
$assistant = app(AiAssistant::class);
// Legacy method calls
```

#### After (2.0+):

```php
$assistant = Assistant::new()
    ->setModelName('gpt-4')
    ->setAssistantName('My Assistant')
    ->create();
```

For detailed migration examples, see the documentation.