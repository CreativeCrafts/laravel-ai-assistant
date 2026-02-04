# Changelog

All notable changes to `laravel-ai-assistant` will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [3.1] - 2026-02-04

### Added

- Moderations, Batches, Realtime Sessions, Assistants, Vector Stores, Vector Store Files, and Vector Store File Batches repositories.
- File content download support for Files and Vector Store Files.
- Centralized HTTP client factory with optional connection pool settings.

### Changed

- Transport retry behavior now respects idempotency and safe HTTP methods.
- Webhook signature validation can require timestamps to enforce replay protection.
- Tool execution via queue in parallel mode returns a queued placeholder instead of inline execution.
- Multipart uploads now enforce per-audio/per-image size and format limits from config.
- Tool allowlist and schema config now accept comma/pipe strings or JSON via env.

### Fixed

- SSE streaming parsing now buffers partial frames to avoid corrupted events.
- Binary responses (e.g., audio) are handled without JSON decoding errors.
- Response body decoding avoids double-reading streams.

### Breaking

- `OpenAITransport` now requires `getContent()`; custom transports must implement it.
- `FilesRepositoryContract` now requires `content()`; custom implementations must implement it.
- Queue tool execution in parallel mode no longer executes inline (returns a queued placeholder).

## [3.0.31-beta] - 2025-10-21

### Changed

- Response API adapter automatically converts Chat-style `messages` to Response API `input` when `input` is not provided.
  - Normalizes content into Response API blocks:
    - Strings and `type: text|input_text` → `input_text`
    - `type: image_url` (string or `{ url }`) → `input_image` with `image_url`
    - `type: input_image` supports `image_url` or `file_id`
  - Removes `messages` after conversion and preserves explicit `input` if already set
  - Backwards compatible; no breaking changes

- `InputBuilder::message()` now also sets `input` (single `user` message with `input_text`) while preserving the original `message` key for backward compatibility and tests.

### Why this matters

- Lets you pass chat-style payloads to the Responses API without manual transformation, improving developer experience and aligning builders across endpoints.

## [3.0.30-beta] - 2025-10-15

### Fixed

- **Enhanced audio detection in `RequestRouter` for multi-content messages**
  - `hasAudioInput()` now detects audio embedded within message content arrays
  - Automatically routes to Chat Completions API when `input_audio` type is found in message content
  - Previously only detected audio via dedicated `audio_input` field
  - Ensures proper API routing for messages with mixed content (text + audio)

### Changed

- **Improved `hasAudioInput()` method documentation**
  - Clarified that audio can be provided in two ways:
    1. Via `audio_input` field (existing behavior)
    2. Via audio embedded in messages array as multi-content (new detection)
  - Enhanced inline documentation explaining routing behavior and API limitations

### Technical Details

- The router now iterates through message content arrays to detect `input_audio` type
- Handles various message structures:
  - Single multi-content messages with audio
  - Conversations with multiple multi-content audio messages
  - Messages with different roles (developer, user, assistant) containing audio
- Maintains backward compatibility with existing `audio_input` field detection
- Comprehensive test coverage added for:
  - Multi-content message with audio routing
  - Multi-role conversations with audio content
  - Multiple multi-content messages in single request
  - Verification that text-only messages still route to Response API

**Why This Matters:** This fix ensures that audio embedded using OpenAI's multi-content message format (introduced in v3.0.29-beta) is correctly detected and routed to the Chat Completions API, which
supports audio input.
The Response API does not yet support audio input, making this routing critical for proper functionality.

## [3.0.29-beta] - 2025-10-15

### Added

- **Multi-content message support for mixed content types**
  - New `messages()` method in `InputBuilder` to set complete pre-formatted OpenAI messages
  - New `withMessages()` method in `ResponsesBuilder` for fluent API support
  - Enables single messages with multiple content types (text + audio, text + image, etc.)
  - Full support for OpenAI's multi-content message format within a single message object

### Changed

- **Enhanced message handling in builders**
  - `InputBuilder::messages()` accepts array of pre-formatted OpenAI messages
  - `ResponsesBuilder::withMessages()` delegates to `InputBuilder` for consistent behavior
  - Both methods support complex message structures with mixed content arrays
  - Maintains backward compatibility with existing single-content message methods

### Technical Details

- The `messages()` method allows passing complete OpenAI-formatted message arrays
- Supports multi-content messages where a single message contains multiple content items:
  - Text + audio in the same user message
  - Text + image in the same user message
  - Any combination of content types supported by OpenAI
- Comprehensive test coverage added for:
  - Single multi-content messages
  - Multiple multi-content messages in conversation
  - Multi-content messages with modalities
  - Preservation of message structure through transformation
- Compatible with `gpt-4o-audio-preview` and other vision/audio models

**Reference:** https://platform.openai.com/docs/api-reference/chat/create

## [3.0.28-beta] - 2025-10-14

### Changed

- **Enhanced `ResponseApiAdapter` with automatic message wrapping**
  - Automatically wraps single message objects in an array when passed to Response API
  - Detects message format: checks for `role` and `content` keys
  - Ensures compliance with OpenAI Response API input requirements
  - Improves developer experience by handling both formats transparently:
    - Single message object: `{ role: 'user', content: [...] }` → wrapped in array
    - Array of messages: passed through as-is
  - No breaking changes: backward compatible with existing code

### Technical Details

- The adapter now intelligently detects single message objects vs. message arrays
- Single message objects are automatically wrapped: `[$input]`
- Arrays are passed through without modification
- Complies with OpenAI's Response API specification for input parameter
- Works seamlessly with `InputBuilder::imageInput()` output

**Reference:** https://platform.openai.com/docs/api-reference/responses/create

## [3.0.27-beta] - 2025-10-14

### Added

- **Image input routing support for Response API with vision models**
  - New `response_api_image_input` route configuration in `RequestRouter`
  - New `hasImageInput()` method to detect image input in Response API format
  - Validates image input structure: requires `input` field with `role` and `content` array
  - Automatically routes vision model requests with image input to Response API endpoint
  - Supports `imageInput()` method from `InputBuilder` for vision model processing

### Changed

- **Enhanced `RequestRouter` with image input detection**
  - Added `response_api_image_input` to endpoint priorities and mappings
  - Comprehensive PHPDoc documentation for `hasImageInput()` method with API reference
  - Routes requests to Response API when image input structure is detected

- **Updated `InputBuilder` documentation**
  - Clarified that `imageInput()` method is for Response API context (not chat context)
  - Improved method documentation for better developer experience
  - Updated validation comments to reflect Response API usage

### Technical Details

- The image input detection checks for the presence of `input` field with proper structure
- Structure must conform to: `role: 'user'` and `content` as an array with image/text items
- Routes to `OpenAiEndpoint::ResponseApi` when image input is detected
- Falls back to default Response API routing when image input is not present
- Full test coverage added for image input routing scenarios

**Reference:** https://platform.openai.com/docs/api-reference/responses/create

## [3.0.26-beta] - 2025-10-14

### Added

- **Preset input support for Single Source of Truth (SSOT) approach**
  - New `presetInput` parameter in `AssistantService::sendTurn()` and `AssistantService::sendTurnStreaming()` methods
  - New `presetInput` parameter in `AssistantService::buildResponsesCreatePayload()` method
  - Allows input to be preset via `InputBuilder` and passed directly without further normalization
  - When `presetInput` is provided, it takes precedence over `inputItems` parameter
  - Enables unified input handling through `InputBuilder::toArray()['input']`

### Changed

- **Enhanced `ResponsesBuilder` to support preset input**
  - `ResponsesBuilder::stream()` now extracts and passes preset input from `UnifiedInput`
  - `ResponsesBuilder::execute()` now extracts and passes preset input from request data
  - Improved input handling consistency across sync and streaming operations

- **Updated `AssistantService::processAudioInput()` method signature**
  - Added `presetInput` parameter for consistency with other turn-based methods
  - All turn-based methods now share the same parameter signature

### Technical Details

- The SSOT approach eliminates duplicate input normalization logic
- Input prepared by `InputBuilder` is now passed directly to the API
- Backward compatible: falls back to `inputItems` when `presetInput` is not provided
- Maintains existing input normalization for legacy code paths

## [3.0.25-beta] - 2025-10-14

### Added

- **Image input support for vision models** in `InputBuilder`
  - New `imageInput()` method for handling image inputs in chat contexts
  - Validates image URLs and base64-encoded images
  - Comprehensive validation with descriptive error messages
  - Supports GPT-4 Vision and other vision-capable models
  - 416 lines of test coverage for all validation scenarios

### Changed

- Improved type hints and return type declarations
- Refactored validation logic for better readability
- Enhanced error messages for developer experience

## [3.0.24-beta] - 2025-10-14

### Fixed

**OpenAI Responses API Compatibility:**

- **Migrated `response_format` to `text.format` structure** to comply with OpenAI's latest API specification
  - OpenAI deprecated the root-level `response_format` parameter in the Responses API
  - The parameter must now be nested under `text.format` according to the API documentation
  - Updated `ResponseApiAdapter` to transform `response_format` to `{ text: { format: ... } }` structure
  - Updated `AssistantService::buildResponsesCreatePayload()` to use nested `text.format` parameter
  - Prevents "Unsupported parameter: 'response_format'" errors when calling the Responses API

**Breaking Change Note:**

- This fix ensures compatibility with OpenAI's Responses API v1
- No breaking changes to package API - the public `ResponsesBuilder::responseFormat()` method remains unchanged
- Internal payload transformation now handles the new API structure automatically

**Files Modified:**

- `src/Adapters/ResponseApiAdapter.php` - Transform request to use `text.format`
- `src/Services/AssistantService.php` - Build payload with nested `text.format` structure
- `tests/Unit/ResponseApiAdapterTest.php` - Update test expectations for new format

**Reference:** https://platform.openai.com/docs/api-reference/responses/create

## [3.0.23-beta] - 2025-10-14

### Added

**API Request Control Parameters:**

- **Temperature parameter support** across the assistant service and responses builder
  - Added `temperature` parameter to `AssistantService::sendTurn()` and `sendTurnStreaming()` methods
  - Added `ResponsesBuilder::temperature()` fluent builder method
  - Validates temperature values between 0.0 and 2.0
  - Allows fine-tuning response randomness and creativity

- **Max completion tokens parameter support** for controlling response length
  - Added `maxCompletionTokens` parameter to `AssistantService::sendTurn()` and `sendTurnStreaming()` methods
  - Added `ResponsesBuilder::maxCompletionTokens()` fluent builder method
  - Validates minimum value of 1 token
  - Provides control over maximum response length

- **Additional fluent builder methods** in `ResponsesBuilder`:
  - `responseFormat()` - Set structured output format
  - `toolChoice()` - Control tool/function calling behavior
  - `modalities()` - Configure response modalities (text, audio, etc.)

### Changed

- **Parameter prioritization logic** in payload building
  - Method parameters now take precedence over configuration values
  - Enables per-request customization while maintaining sensible defaults
  - Configuration values serve as fallbacks when parameters not provided

- **Payload construction** in `AssistantService::buildResponsesCreatePayload()`
  - Integrated temperature and maxCompletionTokens into request payload
  - Added parameter validation before API submission
  - Improved error messages for invalid parameter values

### Fixed

- Added missing `JsonException` import to `ResponsesBuilder` class
- Removed redundant code comments for cleaner codebase

## [3.0.22-beta] - 2025-10-14

### Fixed

- **Automatic file path to stream conversion in multipart requests**
  - `GuzzleOpenAITransport` now automatically opens file resources when a readable file path string is provided in multipart request contents
  - Improves file upload handling for audio transcription, translation, and image operations
  - Eliminates the need to manually open file handles before passing to the transport layer
  - Provides better developer experience when working with file-based OpenAI endpoints

## [3.0.21-beta] - 2025-10-14

### Changed

**Architecture Improvements:**

- **RequestRouter refactored to use dependency injection** instead of accessing global config
  - Constructor now accepts all configuration parameters (`endpointPriority`, `validateConflicts`, `conflictBehavior`, `validateEndpointNames`) with sensible defaults
  - Added `LoggerInterface` dependency for proper logging without facade coupling
  - Eliminates tight coupling to Laravel's configuration system
  - Improves testability - can now be instantiated with custom configuration without modifying global state
  - Follows Dependency Inversion Principle (SOLID)

**Service Provider:**

- Updated `CoreServiceProvider` to properly configure `RequestRouter` singleton
  - Reads configuration values and validates them before injection
  - Provides fallback defaults for missing or invalid configuration
  - Injects logger instance for better observability

**Service Layer:**

- `AiManager` now resolves `RequestRouter` from container instead of direct instantiation
- `ResponsesBuilder` and `ConversationsBuilder` updated to use container-resolved router
- `ThreadsToConversationsMapper` added missing `@throws` annotations for `JsonException` and `InvalidArgumentException`

**Testing:**

- All `RequestRouter` tests refactored to use constructor parameters instead of config mocking
  - Removed `config()` helper usage in test setup
  - Tests are now isolated and don't modify global configuration state
  - Improved test clarity and maintainability
- Updated unit tests in `RequestRouterTest.php` (20+ test cases)
- Updated service tests in `Services/RequestRouterTest.php` (10+ test cases)

### Benefits

- ✅ **Better Testability:** Router can be tested in isolation without global state
- ✅ **Improved DX:** Constructor parameters provide clear configuration requirements
- ✅ **SOLID Compliance:** Follows Dependency Inversion and Single Responsibility principles
- ✅ **Type Safety:** All configuration parameters are properly typed
- ✅ **Better Observability:** Proper logger injection for debugging and monitoring

## [3.0.20-beta] - 2025-10-13

### Major Architecture Migration: SSOT (Single Source of Truth) API

This release represents a significant architectural evolution, establishing `Ai::responses()` as the unified entry point for all OpenAI operations.

#### Added

**Core Architecture:**

- Unified Response API with an adapter pattern for all OpenAI endpoints
  - New `OpenAiClient` service with unified request/response handling
  - New `RequestRouter` service for intelligent endpoint detection
  - New `MultipartRequestBuilder` for audio/image file uploads
- Endpoint routing enums: `OpenAiEndpoint`, `AudioAction`, `ImageAction`
- Nine specialized adapters for different OpenAI endpoints:
  - `ResponseApiAdapter` (default unified API)
  - `ChatCompletionAdapter` (legacy chat endpoint)
  - `AudioTranscriptionAdapter`, `AudioTranslationAdapter`, `AudioSpeechAdapter`
  - `ImageGenerationAdapter`, `ImageEditAdapter`, `ImageVariationAdapter`
  - `EndpointAdapter` (base adapter)

**Exceptions:**

- Domain-specific exception classes with detailed context:
  - `AudioSpeechException`, `AudioTranscriptionException`, `AudioTranslationException`
  - `ImageGenerationException`, `ImageEditException`, `ImageVariationException`
  - `FileValidationException`

**Observability:**

- New `Observability` facade for unified telemetry access
  - `ObservabilityContract` interface with 20 telemetry methods
  - `ObservabilityService` with automatic correlation ID propagation
  - Single import replaces direct service calls
  - Automatic correlation ID threading across all operations

**Documentation:**

- Comprehensive migration guides:
  - `MIGRATION.md` (1,952 lines) - Complete SSOT API migration guide
  - `AUDIO_MIGRATION.md` (627 lines) - Audio operations migration
  - `IMAGE_MIGRATION.md` (736 lines) - Image operations migration
- Five runnable examples demonstrating key features:
  - `01-hello-world.php` - Basic chat completion
  - `02-streaming.php` - Real-time streaming responses
  - `03-cancellation.php` - Request cancellation patterns
  - `04-complete-api.php` - Unified completion API usage
  - `05-observability.php` - Monitoring and observability
- `examples/README.md` with detailed usage instructions
- `examples/smoke-test.php` for verifying package setup

**Configuration:**

- Enhanced adapter-specific settings in `config/ai-assistant.php`
- New configuration options for endpoint routing and adapter behavior

**Testing:**

- 18 new unit tests covering all adapters and core components
- 7 new feature tests for end-to-end workflows
- 3 new integration tests for real API interactions
- `BackwardCompatibilityTest` ensuring smooth migration
- `PerformanceOptimizationTest` for performance benchmarking
- Test fixtures for audio and image file handling
- Comprehensive integration tests for Conversations CRUD lifecycle
- Enhanced `CacheBackedProgressTracker` with metadata merging tests

#### Changed

**Core Services:**

- `AssistantService` refactored to use new adapter architecture (405 lines reduced)
- `StreamingService` enhanced with improved chunk handling and progress tracking
- `AiManager` now uses unified completion API as single entry point
- `HealthCheckService` improved with better endpoint validation
- `ChatSession` enhanced with better fluent API support
- `FilesHelper` streamlined with fewer convenience methods (moved to ChatSession)

**Repositories:**

- All HTTP repositories now implement consistent contract interfaces
- `ConversationsHttpRepository` improved with proper update handling
- Better error handling and type safety across all repositories

**Configuration & Providers:**

- `CoreServiceProvider` refactored with new adapter bindings (68 lines modified)
- `LaravelAiAssistantServiceProvider` updated to register new services
- Improved configuration validation and adapter registration

**Documentation:**

- README.md significantly enhanced with improved quick start guide (≤5 minutes)
  - Added sync vs stream comparison table
  - Restructured for better onboarding
  - Added unified completion API section
- UPGRADE.md expanded with detailed migration guidance
- Enhanced inline documentation and PHPDoc blocks

#### Removed

**Legacy Compatibility Layer (Breaking):**

- Deleted entire `src/Compat/OpenAI/` directory structure:
  - `AudioResource`, `ChatResource`, `CompletionsResource`
  - `Client` (legacy compatibility client)
  - `TranscriptionResponse`, `TranslationResponse`, `CreateResponse`
  - `StreamedCompletionResponse`, `MetaInformation`, `StreamResponse`
  - `aliases.php` (legacy alias mappings)

**Legacy Repositories:**

- `OpenAiRepository` - Direct OpenAI client wrapper (192 lines deleted)
- `NullOpenAiRepository` - Null object pattern implementation (72 lines deleted)

**Legacy Contracts:**

- `OpenAiRepositoryContract` - Old repository interface (67 lines deleted)
- `AppConfigContract` - Superseded by `ModelConfigFactory` (12 lines deleted)

**Obsolete Tests:**

- `AppConfigClientCreationTest`, `AppConfigTest`
- `RepositoryBindingTest`, `ContractComplianceTest`
- `NewAssistantResponseDataTest`
- `AiManagerCompleteParityTest`
- `ApiIntegrationTest`
- `AssistantServicePerformanceTest`, `PerformanceTest`
- `OpenAiRepositoryTest`, `AssistantServiceTest`

#### Deprecated

- The following classes are deprecated in v3.0 and will be removed in v4.0:
  - **AiAssistant** (`src/AiAssistant.php`) - Use `Ai::responses()` or `Ai::chat()` instead
  - **AiAssistant Facade** (`src/Facades/AiAssistant.php`) - Use `Ai` facade instead
  - **OpenAIClientFacade** (`src/OpenAIClientFacade.php`) - Use `Ai` facade methods (`Ai::responses()`, `Ai::conversations()`) instead
  - **AppConfig** (`src/Services/AppConfig.php`) - Use `ModelConfigFactory::for(Modality, ModelOptions)` instead
- All deprecated classes trigger runtime deprecation warnings in development
- See `UPGRADE.md` for detailed migration guides and examples

#### Breaking Changes

**API Changes:**

- Legacy `OpenAiRepository` methods no longer available - migrate to `Ai::responses()`
- Compat Client layer completely removed - use unified Response API
- `FilesHelper` convenience methods removed - use `ChatSession` methods instead:
  - Removed: `attachFilesToTurn`, `addImageFromFile`, `addImageFromUrl`, etc.
  - Use: `ChatSession::attachFiles()`, `includeFileSearchTool()`, `attachUploadedFile()`, etc.

**Architecture:**

- Request routing now automatic based on input structure
- Priority-based routing: audio > image > response API
- Multipart form-data handling for file uploads now standardized

#### Migration Notes

- **Action Required:** Update all direct `OpenAiRepository` calls to use `Ai::responses()`
- **Action Required:** Replace Compat Client usage with unified Response API
- **Recommended:** Review `MIGRATION.md` for comprehensive migration paths
- **Recommended:** Test audio/image operations with new adapter-based architecture
- See `AUDIO_MIGRATION.md` and `IMAGE_MIGRATION.md` for specific migration guides

#### Benefits of This Release

- ✅ **Unified Interface:** One API for text, audio, and images
- ✅ **Automatic Routing:** Request routing handled internally
- ✅ **Type Safety:** Full IDE autocompletion and type hints
- ✅ **Better DX:** Fluent, intuitive builder pattern
- ✅ **Future-Proof:** Aligns with OpenAI's API evolution
- ✅ **Cleaner Code:** Less boilerplate, improved maintainability
- ✅ **Better Observability:** Unified telemetry with correlation ID threading
- ✅ **Comprehensive Testing:** 28+ new tests ensuring stability

## [3.0.19-beta] - 2025-10-07

Added

- A Production-ready AI cache system:
- New Facade AiAssistantCache with typed methods for config, responses, and completions (cache/remember/get/clear/purge; stats).
- New PrefixedKeyIndexer to maintain per-prefix key indexes for stores without tag support (enables safe prefix purges).
- New Artisan commands:
- ai-cache:clear with options --area=config|response|completion, --key=..., and --prefix=config:|response:|completion: for safe, targeted clears.
- ai-cache:stats to output JSON cache statistics.
- New cache configuration (config/ai-assistant.php):
- Store override, global_prefix, hash_algo.
- TTLs for default/config/response/completion/lock/grace and max_ttl guardrail.
- Safety: prevent_flush, prefix_clear_batch.
- Performance: optional compression/encryption.
- Stampede protection (lock_ttl, retry/backoff, max wait).
- Tagging controls with logical groups (auto-disabled when unsupported).
- Tests:
- Console command coverage for cache clear/stats.
- CacheService tests including encoding and core behaviors.

Changed

- CacheService: majorly refactor/expansion to support namespacing, hashed completion keys, targeted clears (config/response/completion), prefix-based purges, and stats reporting; improved safety and
performance.
- Service provider: registers cache bindings and new console commands.
- README: updated docs to cover the cache system and new commands.
- composer.json: small adjustments (metadata/autoload tweaks).

Deprecated

- The following classes are deprecated in v3.0 and will be removed in v4.0:
  - **AiAssistant** (src/AiAssistant.php) - Use Ai::responses() or Ai::chat() instead
  - **AiAssistant Facade** (src/Facades/AiAssistant.php) - Use Ai facade instead
  - **OpenAIClientFacade** (src/OpenAIClientFacade.php) - Use Ai facade methods (Ai::responses(), Ai::conversations()) instead
  - **AppConfig** (src/Services/AppConfig.php) - Use ModelConfigFactory::for(Modality, ModelOptions) instead
- All deprecated classes trigger runtime deprecation warnings in development
- See UPGRADE.md for detailed migration guides and examples

Notes

- Use ai-cache:clear for targeted deletion; avoid using Cache::flush() with this package.
- Consider publishing the config to tune store, TTLs, prefixing, and safety features.

## [3.0.18-beta] - 2025-09-25

refactor: migrate error reporting to log-only; harden PII scrubbing

- Remove external drivers (Sentry/Bugsnag) and route all reports to logs
- Default driver forced to 'log' in ErrorReportingService
- Update production configs/presets to default to 'log' (was 'sentry')
- Expand sensitive field list (tokens, cookies, client_secret, etc.)
- Redact sensitive query params and sanitize request URLs
- Improve recursive scrubbing with JSON_THROW_ON_ERROR and better typing
- Add helpers: sanitizeUrl, parseQueryParams, isSensitiveField
- Strengthen method signatures (union types), use static closures, tidy internals
- Simplify configuration validation for log-only mode

Why:

- Reduce external dependencies and ensure predictable behavior in restricted environments
- Improve security by aggressively redacting sensitive data in context and URLs

BREAKING CHANGE:

- External trackers (Sentry/Bugsnag) support removed; the service now always logs.
- Production default error_reporting.driver changed to 'log' and the service ignores non-log drivers.
- If you rely on Sentry/Bugsnag, implement a custom driver/adapter.

## [3.0.17-beta] - 2025-09-12

feat!: expand ChatSession; add ChatOptions/StreamReader; deprecation controls

- Introduce ChatOptions for strongly typed, chainable chat configuration (model, temperature, response_format, tool_choice, files, vector stores, metadata, idempotency, timeout)
- Add StreamReader to normalise streaming events into text chunks; ChatSession::streamText now uses it
- Enrich ChatSession API:
- setResponseFormatText(), setResponseFormatJson(), setResponseFormatJsonSchema()
- setTemperature(), setToolChoice(), attachFiles(), includeFileSearchTool(), includeFunctionCallTool()
- attachUploadedFile(), attachFilesFromStorage(), addImageFromUploadedFile()
- Improved make() to optionally seed the first user message; added setUserMessage()
- AiManager::quick now accepts string or array (message/prompt, model, temperature, response_format) and returns a ChatSession for fluent chaining
- DTOs:
- ChatResponseDto::toArray now returns a structured payload (id, status, content, raw)
- StreamingEventDto gains toArray()
- Add Deprecation helper and config flag (ai-assistant.deprecations.emit via AI_ASSISTANT_EMIT_DEPRECATIONS) for opt-in E_USER_DEPRECATED notices
- Composer/config:
- Register MacroAndMiddlewareServiceProvider in extra.laravel.providers
- Remove openai-php/client suggestion
- SecurityService: tighten validateRequestSize signature (mixed) and rely on JSON_THROW_ON_ERROR
- FilesHttpRepository: minor MIME comment fix; annotate retrieve() with @throws JsonException
- OpenAiRepository: annotate transport methods with @throws RandomException
- General docblock/typo/normalisation fixes

BREAKING CHANGES:

- ChatResponseDto::toArray changed shape (returns id/status/content/raw instead of the raw array). Use ->toArray()['raw'] if you need the original payload
- FilesHelper removed several convenience methods:
- attachFilesToTurn, addImageFromFile, addImageFromUrl, addImageFromUploadedFile,
  attachUploadedFile, attachFilesFromStorage, attachFileReference, attachForFileSearch,
  addInputImageFromFile, addInputImageFromUrl
  Migrate to ChatSession helpers (attachFiles, includeFileSearchTool, attachUploadedFile, attachFilesFromStorage, addImageFromUploadedFile) or use Assistant/Service-level methods directly

## [3.0.16-beta] - 2025-09-10

feat(config): add skip-able config validation; default to skip in dev/test

- Gate validateConfiguration() behind shouldSkipValidation()
- Add shouldSkipValidation() checking:
- AI_ASSISTANT_SKIP_VALIDATION constant
- env: GITHUB_ACTIONS, CI, SKIP_AI_ASSISTANT_CONFIG_VALIDATION
- config('ai-assistant.validation.skip')
- Apply environment overlays before validation
- Set validation.skip=true in development and testing overlays
- Minor doc/grammar tweaks and helper reorganisation (no behaviour change)

This allows CI and explicit overrides to bypass strict config validation while keeping production-safe defaults.

## [3.0.15-beta] - 2025-09-10

feat: add streaming responses, install command, and webhook signature verification

- Introduce StreamedAiResponse and Blade stream component for real-time AI output
- Add VerifyAiWebhookSignature middleware to secure inbound webhooks
- Add InstallCommand to simplify package setup
- Provide example stubs: StreamingController and routes
- Update ChatSession flow and OpenAI compat aliases
- Expand ModelConfigDataFactory and update corresponding tests
- Refresh config, composer.json, .gitattributes, and README
- Remove obsolete SCALING_VERIFICATION_REPORT.md

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

- Add generateIdempotencyKey() with secure random_bytes and layered fallbacks
- Make decodeOrFail() content-type aware (handles text/plain) and validate JSON with JSON_THROW_ON_ERROR
- Move endpoint() and delete() earlier; group retry helpers; reintroduce resolveSseTimeout()
- Fix error detail assembly to only include fields when present
- Tidy imports and minor formatting for readability

## [3.0.11-beta] - 2025-09-08

feat: add OpenAI transport layer and refactor clients/repositories

- Introduce OpenAITransport interface and GuzzleOpenAITransport implementation
- Unified JSON, multipart, DELETE and SSE streaming helpers
- Built-in retries with exponential backoff and optional jitter
- Idempotency-Key handling (configurable) and consistent error normalization
- Centralized timeout resolution
- Wire Compat OpenAI Client to real HTTP via transport
- Keep legacy constructor signature for BC (HttpClientInterface arg ignored)
- Support API key, organization header, base URI and per-call timeouts
- Add Compat OpenAI resources backed by transport
- Assistants, Chat (incl. streamed responses), Completions
- Threads (+messages, +runs), Audio (transcribe/translate)
- Refactor HTTP repositories to delegate network I/O to transport
- Conversations: POST/GET/DELETE now via transport; simpler list query building
- Responses: create/stream/get/cancel/delete now via transport; remove duplicated retry/decoder logic
- Files: upload/retrieve/delete via transport; safer file handling and resource cleanup on errors
- Update AppConfig to instantiate the compat Client with configured API key/org/timeout
- Remove stray phpunit.xml.dist.bak

## [3.0.5-beta] - 2025-09-05

"feat: make OpenAI SDK optional, add Compat aliases; default file purpose to "assistants"

- Move openai-php/client from required dependency to a suggested package
- Provide internal Compat classes with class_alias mappings so common OpenAI\Client and response types resolve when the SDK isn’t installed
- Expand alias coverage (Client, Chat, Completions incl. streaming, Audio, Meta, StreamResponse, Threads messages/runs)
- Align file upload defaults with OpenAI Files API
- Change default purpose from "assistants/answers" to "assistants"
- Validate and normalize purposes; allow: assistants, batch, fine-tune, vision, user_data
- Propagate purpose parameter through AssistantService, AiAssistant, FilesHelper, Http repository, and tests
- Internal refactors and polish
- Add and normalize endpoint() helpers in HTTP repositories
- Minor CS tweaks (casts/spacing), improved docblocks, consistent timeout casting
- Docs: update README to explain optional SDK usage and client behavior

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
