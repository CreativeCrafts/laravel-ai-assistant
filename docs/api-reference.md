# API reference

Every public method the package exposes, grouped by entry point. For explanations and examples, follow the links
to the guides. Repository methods take the OpenAI request body as `array $payload` and query parameters as
`array $params`, and return the decoded JSON (see [Core concepts](core-concepts.md#return-values)).

All contracts live in `CreativeCrafts\LaravelAiAssistant\Contracts` (agents under `Contracts\Agents`, admin
under `Contracts\Admin`), so you can type-hint or mock any of them.

## High-level builders

### `Ai` facade (`AiManager`)

`CreativeCrafts\LaravelAiAssistant\Services\AiManager`

```php
responses(): ResponsesBuilder
conversations(): ConversationsBuilder
moderations(): ModerationsRepositoryContract
batches(): BatchesRepositoryContract
realtimeSessions(): RealtimeSessionsRepositoryContract
vectorStores(): VectorStoresRepositoryContract
vectorStoreFiles(): VectorStoreFilesRepositoryContract
vectorStoreFileBatches(): VectorStoreFileBatchesRepositoryContract
assistants(): AssistantsRepositoryContract
files(): FilesRepositoryContract
audio(): AudioRepositoryContract
diarize(mixed $file = null, ?string $filename = null): DiarizationBuilder
quick(string|array $input): ChatResponseDto
chat(?string $prompt = ''): ChatSession
stream(string $prompt, ?callable $onEvent = null, ?callable $shouldStop = null): Generator
complete(Mode $mode, Transport $transport, CompletionRequest $request): CompletionResult
```

The facade also exposes one accessor per API repository, listed below, and `admin(): AdminManager`.

### `Ai::responses()`

`CreativeCrafts\LaravelAiAssistant\Support\ResponsesBuilder`

```php
inConversation(string $conversationId): self
instructions(string $instructions): self
model(string $model): self
responseFormat(array|string $format): self
temperature(float $temperature): self
maxCompletionTokens(int $maxTokens): self
toolChoice(array|string $toolChoice): self
modalities(array $modalities): self
withInput(array $items): self
withMessages(array $messages): self
input(): InputBuilder
inputItems(): InputItemsBuilder
send(): ResponseDto|ChatResponseDto
stream(?callable $onEvent = null, ?callable $shouldStop = null): Generator
retrieve(string $responseId, array $params = []): array
resume(string $responseId, ?int $startingAfter = null): iterable
cancel(string $responseId): bool
delete(string $responseId): bool
listInputItems(string $responseId, array $params = []): array
countInputTokens(array $payload): array
compact(array $payload): array
```

### `Ai::responses()->input()`

`CreativeCrafts\LaravelAiAssistant\Support\InputBuilder`

```php
message(string $text): self
audio(array $config): self
audioInput(array $config): self
image(array $config): self
messages(array $messages): self
toArray(): array
send(): ChatResponseDto|ResponseDto
imageInput(array $imageInput): self
```

### `Ai::responses()->inputItems()` / `Ai::conversations()->input()`

`CreativeCrafts\LaravelAiAssistant\Support\InputItemsBuilder`

```php
appendUserText(string $text): self
appendUserImageUrl(string $url): self
appendUserImageId(string $fileId): self
appendRaw(array $item): self
list(): array
```

### `Ai::conversations()`

`CreativeCrafts\LaravelAiAssistant\Support\ConversationsBuilder`

```php
start(array $metadata = [], bool $setActive = true): string
use(string $conversationId): self
items(array $params = []): array
retrieve(): array
update(array $metadata): array
delete(): bool
item(string $itemId, array $params = []): array
addItems(array $items, array $params = []): array
deleteItem(string $itemId): bool
input(): InputItemsBuilder
send(): ChatResponseDto
responses(): ResponsesBuilder
```

### `Ai::chat()`

`CreativeCrafts\LaravelAiAssistant\Chat\ChatSession`

```php
setUserMessage(string $text): self
instructions(string $instructions): self
setDeveloperMessage(string $text): self
setModelName(string $model): self
setResponseFormatText(): self
files(): FilesHelper
send(): ChatResponseDto
streamText(callable $onTextChunk, ?callable $shouldStop = null): Generator
stream(?callable $onEvent = null, ?callable $shouldStop = null): Generator
continueWithToolResults(array $toolResults): ChatResponseDto
core(): AiAssistant
setResponseFormatJson(): self
setResponseFormatJsonSchema(array $jsonSchema, ?string $name = 'response'): self
setTemperature(float $temperature): self
attachFiles(array $fileIds, ?bool $useFileSearch = null): self
includeFileSearchTool(array $vectorStoreIds = []): self
tools(): ToolsBuilder
includeFunctionCallTool(string $name, string $description, array $jsonSchema, bool $isStrict = true): self
setToolChoice(string|array $choice): self
attachUploadedFile(UploadedFile $file, string $purpose = 'assistants'): self
attachFilesFromStorage(array $paths, string $purpose = 'assistants'): self
addImageFromUploadedFile(UploadedFile $file, string $purpose = 'assistants'): self
```

### `ChatSession::tools()`

`CreativeCrafts\LaravelAiAssistant\Support\ToolsBuilder`

```php
includeFunctionCallTool(string $functionName, string $functionDescription, array|FunctionCallParameterContract $functionParameters, bool $isStrict = false): self
includeFunctionFromCallable(callable $fn, ?string $exportedName = null, string $description = '', bool $isStrict = false): self
includeFileSearchTool(array $vectorStoreIds = []): self
includeCodeInterpreterTool(array $fileIds = []): self
setToolChoice(string|array $choice): self
useFileSearch(bool $enabled = true): self
getConfig(): array
```

### `Ai::diarize()`

`CreativeCrafts\LaravelAiAssistant\Support\DiarizationBuilder`

```php
file(mixed $file, ?string $filename = null): self
fromDisk(string $disk, string $path): self
model(string $model): self
language(string $language): self
temperature(float $temperature): self
autoChunking(): self
serverVad(?float $threshold = null, ?int $prefixPaddingMs = null, ?int $silenceDurationMs = null): self
knownSpeaker(string $name, string|SplFileInfo $reference): self
knownSpeakers(array $speakers): self
withOptions(array $options): self
toPayload(): array
send(): DiarizedTranscription
stream(?callable $onDelta = null): Generator
```

## Result objects

### `ResponseDto`

`CreativeCrafts\LaravelAiAssistant\DataTransferObjects\ResponseDto`

```php
toArray(): array
isText(): bool
isAudio(): bool
isImage(): bool
diarization(): ?DiarizedTranscription
saveAudio(string $path): bool
saveImages(string $directory): array
```

Properties: `id`, `status`, `text`, `raw`, `conversationId`, `audioContent`, `images`, `type`, `metadata`.

### `ChatResponseDto`

`CreativeCrafts\LaravelAiAssistant\DataTransferObjects\ChatResponseDto`

```php
toArray(): array
```

Properties: `id`, `status`, `content`, `raw`, `text`, `conversationId`.

### `StreamingEventDto`

`CreativeCrafts\LaravelAiAssistant\DataTransferObjects\StreamingEventDto`

```php
jsonSerialize(): array
toArray(): array
```

Properties: `type`, `data`, `isFinal`.

### `DiarizedTranscription`

`CreativeCrafts\LaravelAiAssistant\DataTransferObjects\DiarizedTranscription`

```php
speakers(): array
speakerCount(): int
hasSpeaker(string $speaker): bool
segmentsFor(string $speaker): array
textFor(string $speaker): string
speakingTime(): array
speakingTimeFor(string $speaker): float
speakingShare(): array
dominantSpeaker(): ?string
turns(): array
renameSpeakers(array $names): self
toTranscript(bool $withTimestamps = false): string
toWebVtt(): string
toArray(): array
```

Properties: `text`, `segments`, `duration`, `usage`, `raw`.

### `DiarizedSegment`

`CreativeCrafts\LaravelAiAssistant\DataTransferObjects\DiarizedSegment`

```php
duration(): float
withSpeaker(string $speaker): self
toArray(): array
```

Properties: `id`, `speaker`, `start`, `end`, `text`.

## API repositories

### Text

#### `ResponsesRepositoryContract`

Low-level access: `app(ResponsesRepositoryContract::class)`.

```php
createResponse(array $payload): array
streamResponse(array $payload): iterable
getResponse(string $responseId, array $params = []): array
resumeStream(string $responseId, array $params = []): iterable
compactResponse(array $payload): array
countInputTokens(array $payload): array
listResponses(array $params = []): array
cancelResponse(string $responseId): bool
deleteResponse(string $responseId): bool
```

#### `ConversationsRepositoryContract`

Low-level access: `app(ConversationsRepositoryContract::class)`.

```php
createConversation(array $payload = []): array
getConversation(string $conversationId): array
updateConversation(string $conversationId, array $payload): array
deleteConversation(string $conversationId): bool
listItems(string $conversationId, array $params = []): array
createItems(string $conversationId, array $items, array $params = []): array
getItem(string $conversationId, string $itemId, array $params = []): array
deleteItem(string $conversationId, string $itemId): bool
```

#### `Ai::chatCompletions()`

`ChatCompletionsRepositoryContract`

```php
create(array $payload): array
stream(array $payload): iterable
retrieve(string $completionId): array
update(string $completionId, array $payload): array
list(array $params = []): array
delete(string $completionId): array
listMessages(string $completionId, array $params = []): array
```

#### `Ai::completions()`

`CompletionsRepositoryContract`

```php
create(array $payload): array
stream(array $payload): iterable
```

### Audio, images and video

#### `Ai::audio()`

`AudioRepositoryContract`

```php
createSpeech(array $payload): array
streamSpeech(array $payload): iterable
createTranscription(array $payload): array
streamTranscription(array $payload): iterable
createTranslation(array $payload): array
createVoice(array $payload): array
```

#### `Ai::images()`

`ImagesRepositoryContract`

```php
generate(array $payload): array
streamGeneration(array $payload): iterable
edit(array $payload): array
streamEdit(array $payload): iterable
createVariation(array $payload): array
```

#### `Ai::videos()`

`VideosRepositoryContract`

```php
create(array $payload): array
retrieve(string $videoId): array
list(array $params = []): array
delete(string $videoId): array
remix(string $videoId, array $payload): array
downloadContent(string $videoId, array $params = []): array
edit(array $payload): array
extend(array $payload): array
createCharacter(array $payload): array
retrieveCharacter(string $characterId): array
```

### Knowledge and files

#### `Ai::embeddings()`

`EmbeddingsRepositoryContract`

```php
create(array $payload): array
```

#### `Ai::files()`

`FilesRepositoryContract`

```php
upload(string $filePath, string $purpose = 'assistants', array $params = []): array
list(array $params = []): array
retrieve(string $fileId): array
delete(string $fileId): bool
content(string $fileId): array
```

#### `Ai::uploads()`

`UploadsRepositoryContract`

```php
create(array $payload): array
addPart(string $uploadId, array $payload): array
complete(string $uploadId, array $payload): array
cancel(string $uploadId): array
uploadFile(string $filePath, string $purpose, ?string $mimeType = null, int $partSize = 67108864): array
```

#### `Ai::vectorStores()`

`VectorStoresRepositoryContract`

```php
create(array $payload): array
retrieve(string $vectorStoreId): array
update(string $vectorStoreId, array $payload): array
delete(string $vectorStoreId): bool
list(array $params = []): array
search(string $vectorStoreId, array $payload): array
```

#### `Ai::vectorStoreFiles()`

`VectorStoreFilesRepositoryContract`

```php
create(string $vectorStoreId, array $payload): array
retrieve(string $vectorStoreId, string $fileId): array
update(string $vectorStoreId, string $fileId, array $payload): array
delete(string $vectorStoreId, string $fileId): bool
list(string $vectorStoreId, array $params = []): array
content(string $vectorStoreId, string $fileId): array
```

#### `Ai::vectorStoreFileBatches()`

`VectorStoreFileBatchesRepositoryContract`

```php
create(string $vectorStoreId, array $payload): array
retrieve(string $vectorStoreId, string $batchId): array
cancel(string $vectorStoreId, string $batchId): array
listFiles(string $vectorStoreId, string $batchId, array $params = []): array
```

### Jobs, training and evaluation

#### `Ai::batches()`

`BatchesRepositoryContract`

```php
create(array $payload): array
retrieve(string $batchId): array
cancel(string $batchId): array
list(array $params = []): array
```

#### `Ai::fineTuningJobs()`

`FineTuningJobsRepositoryContract`

```php
create(array $payload): array
retrieve(string $fineTuningJobId): array
list(array $params = []): array
cancel(string $fineTuningJobId): array
pause(string $fineTuningJobId): array
resume(string $fineTuningJobId): array
listEvents(string $fineTuningJobId, array $params = []): array
listCheckpoints(string $fineTuningJobId, array $params = []): array
```

#### `Ai::fineTuningCheckpointPermissions()`

`FineTuningCheckpointPermissionsRepositoryContract`

```php
create(string $checkpoint, array $payload): array
list(string $checkpoint, array $params = []): array
delete(string $checkpoint, string $permissionId): array
```

#### `Ai::graders()`

`GradersRepositoryContract`

```php
run(array $payload): array
validate(array $payload): array
```

#### `Ai::evals()`

`EvalsRepositoryContract`

```php
create(array $payload): array
retrieve(string $evalId): array
update(string $evalId, array $payload): array
list(array $params = []): array
delete(string $evalId): array
```

#### `Ai::evalRuns()`

`EvalRunsRepositoryContract`

```php
create(string $evalId, array $payload): array
retrieve(string $evalId, string $runId): array
list(string $evalId, array $params = []): array
delete(string $evalId, string $runId): array
cancel(string $evalId, string $runId): array
listOutputItems(string $evalId, string $runId, array $params = []): array
retrieveOutputItem(string $evalId, string $runId, string $outputItemId): array
```

### Models, moderation and safety

#### `Ai::models()`

`ModelsRepositoryContract`

```php
list(): array
retrieve(string $model): array
delete(string $model): array
```

#### `Ai::moderations()`

`ModerationsRepositoryContract`

```php
create(array $payload): array
```

#### `Ai::decisions()`

`DecisionsRepositoryContract`

```php
create(array $payload): array
```

#### `Ai::contentProvenanceChecks()`

`ContentProvenanceChecksRepositoryContract`

```php
create(array $payload): array
```

#### `Ai::safety()`

`SafetyRepositoryContract`

```php
retrieveAlert(string $alertId): array
retrieveCase(string $caseId): array
```

### Realtime

#### `Ai::realtime()`

`RealtimeRepositoryContract`

```php
createClientSecret(array $payload = []): array
acceptCall(string $callId, array $payload): array
hangupCall(string $callId): array
referCall(string $callId, array $payload): array
rejectCall(string $callId, array $payload = []): array
createTranslationClientSecret(array $payload = []): array
createTranscriptionSession(array $payload = []): array
createCall(string $sdp, array $session = []): array
```

#### `Ai::realtimeSessions()`

`RealtimeSessionsRepositoryContract`

```php
create(array $payload): array
```

#### `Ai::live()`

`LiveRepositoryContract`

```php
create(array $payload): array
accept(string $sessionId, array $payload): array
hangup(string $sessionId): array
refer(string $sessionId, array $payload): array
reject(string $sessionId, array $payload = []): array
fork(string $sessionId, array $payload): array
downloadRecording(string $sessionId): array
```

### Agents, tools and platform

#### `Ai::agents()`

`AgentsRepositoryContract`

```php
create(array $payload): array
retrieve(string $agentId): array
update(string $agentId, array $payload): array
list(array $params = []): array
delete(string $agentId): array
```

#### `Ai::agentSessions()`

`AgentSessionsRepositoryContract`

```php
create(array $payload): array
stream(array $payload): iterable
retrieve(string $sessionId): array
update(string $sessionId, array $payload): array
list(array $params = []): array
delete(string $sessionId): array
createEvent(string $sessionId, array $payload): array
streamEvents(string $sessionId): iterable
listItems(string $sessionId, array $params = []): array
listTraces(string $sessionId, array $params = []): array
listArtifacts(string $sessionId, array $params = []): array
retrieveArtifact(string $sessionId, string $artifactId): array
deleteArtifact(string $sessionId, string $artifactId): array
artifactContent(string $sessionId, string $artifactId): array
listTurns(string $sessionId, array $params = []): array
retrieveTurn(string $sessionId, string $turnId): array
listTurnItems(string $sessionId, string $turnId, array $params = []): array
listSubagents(string $sessionId, array $params = []): array
retrieveSubagent(string $sessionId, string $subagentId): array
listSubagentItems(string $sessionId, string $subagentId, array $params = []): array
listSubagentTurns(string $sessionId, string $subagentId, array $params = []): array
retrieveSubagentTurn(string $sessionId, string $subagentId, string $turnId): array
listSubagentTurnItems(string $sessionId, string $subagentId, string $turnId, array $params = []): array
```

#### `Ai::agentEnvironments()`

`AgentEnvironmentsRepositoryContract`

```php
retrieve(string $environmentId): array
createFile(string $environmentId, array $payload): array
listFiles(string $environmentId, array $params = []): array
createTemplate(array $payload): array
retrieveTemplate(string $templateId): array
updateTemplate(string $templateId, array $payload): array
listTemplates(array $params = []): array
deleteTemplate(string $templateId): array
```

#### `Ai::vaults()`

`VaultsRepositoryContract`

```php
create(array $payload): array
retrieve(string $vaultId): array
list(array $params = []): array
delete(string $vaultId): array
createCredential(string $vaultId, array $payload): array
retrieveCredential(string $vaultId, string $credentialId): array
updateCredential(string $vaultId, string $credentialId, array $payload): array
listCredentials(string $vaultId, array $params = []): array
deleteCredential(string $vaultId, string $credentialId): array
```

#### `Ai::skills()`

`SkillsRepositoryContract`

```php
create(array $payload): array
retrieve(string $skillId): array
update(string $skillId, array $payload): array
list(array $params = []): array
delete(string $skillId): array
content(string $skillId): array
createVersion(string $skillId, array $payload): array
retrieveVersion(string $skillId, string $version): array
listVersions(string $skillId, array $params = []): array
deleteVersion(string $skillId, string $version): array
versionContent(string $skillId, string $version): array
```

#### `Ai::containers()`

`ContainersRepositoryContract`

```php
create(array $payload): array
retrieve(string $containerId): array
list(array $params = []): array
delete(string $containerId): array
```

#### `Ai::containerFiles()`

`ContainerFilesRepositoryContract`

```php
create(string $containerId, array $payload): array
retrieve(string $containerId, string $fileId): array
list(string $containerId, array $params = []): array
delete(string $containerId, string $fileId): array
content(string $containerId, string $fileId): array
```

#### `Ai::chatKit()`

`ChatKitRepositoryContract`

```php
createSession(array $payload): array
cancelSession(string $sessionId): array
retrieveThread(string $threadId): array
listThreads(array $params = []): array
deleteThread(string $threadId): array
listThreadItems(string $threadId, array $params = []): array
```

#### `Ai::webhookEndpoints()`

`WebhookEndpointsRepositoryContract`

```php
create(array $payload): array
retrieve(string $webhookEndpointId): array
update(string $webhookEndpointId, array $payload): array
list(array $params = []): array
delete(string $webhookEndpointId): array
rotateSecret(string $webhookEndpointId): array
test(string $webhookEndpointId, array $payload = []): array
listEventTypes(array $params = []): array
```

#### `Ai::assistants()` (deprecated)

`AssistantsRepositoryContract`

```php
create(array $payload): array
retrieve(string $assistantId): array
update(string $assistantId, array $payload): array
delete(string $assistantId): bool
list(array $params = []): array
```

## Administration (`Ai::admin()`)

#### `Ai::admin()->apiKeys()`

`AdminApiKeysRepositoryContract`

```php
create(array $payload): array
retrieve(string $keyId): array
list(array $params = []): array
delete(string $keyId): array
```

#### `Ai::admin()->auditLogs()`

`AuditLogsRepositoryContract`

```php
list(array $params = []): array
```

#### `Ai::admin()->certificates()`

`CertificatesRepositoryContract`

```php
create(array $payload): array
retrieve(string $certificateId): array
update(string $certificateId, array $payload): array
list(array $params = []): array
delete(string $certificateId): array
activate(array $payload): array
deactivate(array $payload): array
listForProject(string $projectId, array $params = []): array
activateForProject(string $projectId, array $payload): array
deactivateForProject(string $projectId, array $payload): array
```

#### `Ai::admin()->dataRetention()`

`DataRetentionRepositoryContract`

```php
retrieve(): array
update(array $payload): array
retrieveForProject(string $projectId): array
updateForProject(string $projectId, array $payload): array
```

#### `Ai::admin()->externalStorage()`

`ExternalStorageRepositoryContract`

```php
create(array $payload): array
retrieve(string $externalStorageId): array
list(array $params = []): array
delete(string $externalStorageId): array
validate(string $externalStorageId): array
```

#### `Ai::admin()->invites()`

`InvitesRepositoryContract`

```php
create(array $payload): array
retrieve(string $inviteId): array
list(array $params = []): array
delete(string $inviteId): array
```

#### `Ai::admin()->roles()`

`RolesRepositoryContract`

```php
create(array $payload): array
retrieve(string $roleId): array
update(string $roleId, array $payload): array
list(array $params = []): array
delete(string $roleId): array
createForProject(string $projectId, array $payload): array
retrieveForProject(string $projectId, string $roleId): array
updateForProject(string $projectId, string $roleId, array $payload): array
listForProject(string $projectId, array $params = []): array
deleteForProject(string $projectId, string $roleId): array
```

#### `Ai::admin()->spendAlerts()`

`SpendAlertsRepositoryContract`

```php
create(array $payload): array
retrieve(string $alertId): array
update(string $alertId, array $payload): array
list(array $params = []): array
delete(string $alertId): array
createForProject(string $projectId, array $payload): array
retrieveForProject(string $projectId, string $alertId): array
updateForProject(string $projectId, string $alertId, array $payload): array
listForProject(string $projectId, array $params = []): array
deleteForProject(string $projectId, string $alertId): array
```

#### `Ai::admin()->spendLimit()`

`SpendLimitRepositoryContract`

```php
retrieve(): array
update(array $payload): array
delete(): array
retrieveForProject(string $projectId): array
updateForProject(string $projectId, array $payload): array
deleteForProject(string $projectId): array
```

#### `Ai::admin()->usage()`

`UsageRepositoryContract`

```php
completions(array $params = []): array
embeddings(array $params = []): array
moderations(array $params = []): array
images(array $params = []): array
audioSpeeches(array $params = []): array
audioTranscriptions(array $params = []): array
vectorStores(array $params = []): array
codeInterpreterSessions(array $params = []): array
fileSearchCalls(array $params = []): array
webSearchCalls(array $params = []): array
costs(array $params = []): array
```

#### `Ai::admin()->groups()`

`GroupsRepositoryContract`

```php
create(array $payload): array
retrieve(string $groupId): array
update(string $groupId, array $payload): array
list(array $params = []): array
delete(string $groupId): array
addUser(string $groupId, array $payload): array
retrieveUser(string $groupId, string $userId): array
listUsers(string $groupId, array $params = []): array
removeUser(string $groupId, string $userId): array
assignRole(string $groupId, array $payload): array
retrieveRole(string $groupId, string $roleId): array
listRoles(string $groupId, array $params = []): array
unassignRole(string $groupId, string $roleId): array
```

#### `Ai::admin()->users()`

`UsersRepositoryContract`

```php
retrieve(string $userId): array
update(string $userId, array $payload): array
list(array $params = []): array
delete(string $userId): array
assignRole(string $userId, array $payload): array
retrieveRole(string $userId, string $roleId): array
listRoles(string $userId, array $params = []): array
unassignRole(string $userId, string $roleId): array
```

#### `Ai::admin()->projects()`

`ProjectsRepositoryContract`

```php
create(array $payload): array
retrieve(string $projectId): array
update(string $projectId, array $payload): array
list(array $params = []): array
archive(string $projectId): array
```

#### `Ai::admin()->projectUsers()`

`ProjectUsersRepositoryContract`

```php
create(string $projectId, array $payload): array
retrieve(string $projectId, string $userId): array
update(string $projectId, string $userId, array $payload): array
list(string $projectId, array $params = []): array
delete(string $projectId, string $userId): array
assignRole(string $projectId, string $userId, array $payload): array
retrieveRole(string $projectId, string $userId, string $roleId): array
listRoles(string $projectId, string $userId, array $params = []): array
unassignRole(string $projectId, string $userId, string $roleId): array
```

#### `Ai::admin()->projectGroups()`

`ProjectGroupsRepositoryContract`

```php
create(string $projectId, array $payload): array
retrieve(string $projectId, string $groupId): array
list(string $projectId, array $params = []): array
delete(string $projectId, string $groupId): array
assignRole(string $projectId, string $groupId, array $payload): array
retrieveRole(string $projectId, string $groupId, string $roleId): array
listRoles(string $projectId, string $groupId, array $params = []): array
unassignRole(string $projectId, string $groupId, string $roleId): array
```

#### `Ai::admin()->projectServiceAccounts()`

`ProjectServiceAccountsRepositoryContract`

```php
create(string $projectId, array $payload): array
retrieve(string $projectId, string $serviceAccountId): array
update(string $projectId, string $serviceAccountId, array $payload): array
list(string $projectId, array $params = []): array
delete(string $projectId, string $serviceAccountId): array
createApiKey(string $projectId, string $serviceAccountId, array $payload = []): array
```

#### `Ai::admin()->projectApiKeys()`

`ProjectApiKeysRepositoryContract`

```php
retrieve(string $projectId, string $apiKeyId): array
list(string $projectId, array $params = []): array
delete(string $projectId, string $apiKeyId): array
```

#### `Ai::admin()->projectRateLimits()`

`ProjectRateLimitsRepositoryContract`

```php
list(string $projectId, array $params = []): array
update(string $projectId, string $rateLimitId, array $payload): array
```

#### `Ai::admin()->projectPermissions()`

`ProjectPermissionsRepositoryContract`

```php
retrieveModelPermissions(string $projectId): array
updateModelPermissions(string $projectId, array $payload): array
deleteModelPermissions(string $projectId): array
retrieveHostedToolPermissions(string $projectId): array
updateHostedToolPermissions(string $projectId, array $payload): array
```

## Events

| Event | Properties |
|---|---|
| `Events\OpenAiWebhookReceived` | `type`, `data`, `payload`, `eventId` |
| `Events\ResponseCompleted` | `responseId`, `payload` |
| `Events\ResponseFailed` | `responseId`, `error`, `payload` |
| `Events\ToolCallRequested` | `responseId`, `toolCalls`, `payload` |

## Other facades and services

| Class | Purpose |
|---|---|
| `Facades\Observability` | Correlation ids, structured logs, metrics, memory tracking, error reports ([Operations](operations.md#observability)) |
| `Facades\AiAssistantCache` | Package cache helpers ([Operations](operations.md#caching)) |
| `Services\ToolRegistry` | Register PHP callables as tools ([Chat sessions & tools](chat-sessions-and-tools.md)) |
| `Services\ResponseStatusStore` | Last known status of responses updated by webhooks ([Webhooks](webhooks.md)) |
| `Http\Responses\StreamedAiResponse` | Turn a generator into a `text/event-stream` response ([Streaming](streaming.md)) |
| `Transport\OpenAITransport` | Call any endpoint directly ([Core concepts](core-concepts.md#escape-hatch-call-any-endpoint-directly)) |
| `Facades\AiAssistant` | Legacy API, deprecated (see `MIGRATION.md`) |
